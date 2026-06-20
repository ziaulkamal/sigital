<?php

/**
 * app/Services/SignatoryService.php
 * Logika manajemen penanda tangan + penyimpanan spesimen TTD (FR-01).
 */

namespace App\Services;

use App\Models\Signatory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SignatoryService
{
    /**
     * Gelar/sebutan umum (depan & belakang) yang diabaikan saat membandingkan nama.
     * Tujuan: "Dr. Budi" dianggap serupa dengan "Budi" (K3).
     */
    private const TITLES = [
        'prof', 'dr', 'drs', 'dra', 'ir', 'h', 'hj', 'kh', 'r', 'rr',
        'st', 'se', 'sh', 'si', 'ssi', 'skom', 'spd', 'sip',
        'mm', 'mkom', 'mpd', 'mt', 'msi', 'msc', 'ma', 'phd',
    ];

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{nama:string,jabatan:string,is_active?:bool}  $data
     * @param  UploadedFile|null  $qrSrikandi  QR tanda tangan digital SRIKANDI (opsional, poin 7)
     */
    public function create(array $data, ?UploadedFile $ttd = null, ?UploadedFile $qrSrikandi = null): Signatory
    {
        if ($ttd) {
            $data['gambar_ttd'] = $ttd->store('signatures', 'public');
        }
        if ($qrSrikandi) {
            $data['qr_srikandi_path'] = $qrSrikandi->store('srikandi', 'public');
        }

        $signatory = new Signatory($data);
        $signatory->created_by = Auth::id(); // server-controlled (P3)
        $signatory->save();

        $this->audit->log('signatory.created', $signatory, ['nama' => $signatory->nama]);

        return $signatory;
    }

    /**
     * Normalisasi nama untuk pembandingan: lowercase, buang tanda baca & gelar,
     * rapatkan spasi. "Dr. Budi, S.Kom" → "budi".
     */
    public function normalizeName(string $nama): string
    {
        $lower = mb_strtolower(trim($nama));
        // Hapus titik/koma tanpa menambah spasi agar gelar berdempet tetap utuh ("S.Kom" → "skom"),
        // sedangkan tanda hubung dipisah jadi spasi.
        $lower = str_replace(['.', ','], '', $lower);
        $lower = str_replace('-', ' ', $lower);
        $tokens = preg_split('/\s+/', $lower, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = array_filter($tokens, fn (string $t): bool => ! in_array($t, self::TITLES, true));

        return implode(' ', $tokens);
    }

    /**
     * Cari penanda tangan dalam organisasi aktif yang namanya serupa (setelah normalisasi).
     * Global scope BelongsToOrganization memastikan pencarian tetap ter-scope per-org (K3).
     *
     * @return Collection<int,Signatory>
     */
    public function findSimilar(string $nama, ?int $excludeId = null): Collection
    {
        $target = $this->normalizeName($nama);

        if ($target === '') {
            return new Collection;
        }

        return Signatory::query()
            ->when($excludeId, fn ($q) => $q->whereKeyNot($excludeId))
            ->get()
            ->filter(function (Signatory $s) use ($target): bool {
                $n = $this->normalizeName($s->nama);

                return $n !== '' && ($n === $target || str_contains($n, $target) || str_contains($target, $n));
            })
            ->values();
    }

    /** @param array{nama:string,jabatan:string,is_active?:bool} $data */
    public function update(Signatory $signatory, array $data, ?UploadedFile $ttd = null, ?UploadedFile $qrSrikandi = null): Signatory
    {
        if ($ttd) {
            $this->deleteTtd($signatory);
            $data['gambar_ttd'] = $ttd->store('signatures', 'public');
        }
        if ($qrSrikandi) {
            if ($signatory->qr_srikandi_path) {
                Storage::disk('public')->delete($signatory->qr_srikandi_path);
            }
            $data['qr_srikandi_path'] = $qrSrikandi->store('srikandi', 'public');
        }

        $signatory->update($data);
        $this->audit->log('signatory.updated', $signatory, ['nama' => $signatory->nama]);

        return $signatory;
    }

    /** Nonaktifkan (soft) ketimbang hapus, agar jejak penerbitan lama tetap utuh. */
    public function deactivate(Signatory $signatory): void
    {
        $signatory->update(['is_active' => false]);
        $this->audit->log('signatory.deactivated', $signatory);
    }

    private function deleteTtd(Signatory $signatory): void
    {
        if ($signatory->gambar_ttd) {
            Storage::disk('public')->delete($signatory->gambar_ttd);
        }
    }
}
