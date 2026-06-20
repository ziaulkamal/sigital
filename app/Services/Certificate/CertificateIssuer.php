<?php

/**
 * app/Services/Certificate/CertificateIssuer.php
 * Orkestrasi penerbitan satu sertifikat: nomor unik → token → PDF → hash → arsip → audit.
 */

namespace App\Services\Certificate;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\Registration;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CertificateIssuer
{
    public function __construct(
        private readonly CertificateNumberGenerator $numbers,
        private readonly CertificatePdfRenderer $renderer,
        private readonly AuditLogger $audit,
        private readonly PdfArchiver $archiver,
    ) {}

    /**
     * Terbitkan sertifikat untuk satu registrasi. Idempoten: jika sudah ada, kembalikan yang lama.
     * Nomor unik & terkunci — tak bisa dipakai ulang (FR-14).
     */
    public function issue(Registration $registration, ?int $issuedBy = null, ?string $batchId = null): Certificate
    {
        $registration->loadMissing('event');
        $event = $registration->event;

        if (! $event->canIssue()) {
            throw new RuntimeException('Acara belum siap terbit: wajib punya minimal satu template dan satu penanda tangan.');
        }

        // Buat record sertifikat + nomor di dalam transaksi (cegah duplikasi/race).
        $certificate = DB::transaction(function () use ($registration, $event, $issuedBy, $batchId) {
            // Kunci baris acara untuk serialisasi nomor urut per-acara (portabel pgsql/mysql/sqlite).
            Event::whereKey($event->id)->lockForUpdate()->first();

            if ($existing = $registration->certificate()->first()) {
                return $existing; // sudah terbit — jangan terbitkan ulang
            }

            // Nomor urut per acara (transaksi sudah terserialisasi oleh kunci acara di atas).
            $sequence = Certificate::whereHas('registration', fn ($q) => $q->where('event_id', $event->id))
                ->count() + 1;

            return Certificate::create([
                // Denormalisasi org dari event — eksplisit karena batch berjalan di job (tanpa tenancy request).
                'organization_id' => $event->organization_id,
                'registration_id' => $registration->id,
                'nomor_unik' => $this->numbers->generate($event, $sequence),
                'qr_token' => bin2hex(random_bytes(20)),
                'status' => Certificate::STATUS_ISSUED,
                'issued_at' => now(),
                'issued_by' => $issuedBy,
                'batch_id' => $batchId,
            ]);
        });

        // Render & arsipkan PDF di luar transaksi (operasi berat).
        if (! $certificate->pdf_path) {
            $this->renderAndStore($certificate);
        }

        $this->audit->log('certificate.issued', $certificate, [
            'nomor_unik' => $certificate->nomor_unik,
            'batch_id' => $batchId,
        ], $issuedBy);

        return $certificate;
    }

    /**
     * Terbitkan ULANG PDF dari template terbaru, PERTAHANKAN nomor_unik, qr_token,
     * issued_at (identitas & link verifikasi tetap valid). Hanya pdf_path/pdf_hash
     * diperbarui. Hanya untuk sertifikat berstatus issued.
     */
    public function regenerate(Certificate $certificate, ?int $by = null): Certificate
    {
        if ($certificate->status !== Certificate::STATUS_ISSUED) {
            throw new RuntimeException('Hanya sertifikat aktif yang bisa dibuat ulang.');
        }

        $this->renderAndStore($certificate);

        $this->audit->log('certificate.regenerated', $certificate, [
            'nomor_unik' => $certificate->nomor_unik,
        ], $by);

        return $certificate;
    }

    /** Render PDF, simpan ke disk arsip, dan catat hash SHA-256 (FR-13/NFR-08). */
    private function renderAndStore(Certificate $certificate): void
    {
        $binary = $this->renderer->render($certificate);
        $hash = hash('sha256', $binary);

        $disk = config('sigital.pdf_disk');
        $path = config('sigital.pdf_dir').'/'.$certificate->qr_token.'.pdf';
        Storage::disk($disk)->put($path, $binary);

        // Konversi PDF/A opsional (arsip jangka panjang) — hash dihitung ulang setelahnya.
        if (config('sigital.pdfa.enabled') && $this->archiver->toPdfA(Storage::disk($disk)->path($path))) {
            $hash = hash('sha256', Storage::disk($disk)->get($path));
        }

        $certificate->forceFill(['pdf_path' => $path, 'pdf_hash' => $hash])->save();
    }
}
