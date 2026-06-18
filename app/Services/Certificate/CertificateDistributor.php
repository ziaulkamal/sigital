<?php

/**
 * app/Services/Certificate/CertificateDistributor.php
 * Distribusi (email) & pencabutan sertifikat, dengan jejak audit (FR-22, FR-17).
 */

namespace App\Services\Certificate;

use App\Mail\CertificateMail;
use App\Models\Certificate;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class CertificateDistributor
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** Kirim sertifikat ke email peserta (antre). */
    public function email(Certificate $certificate): void
    {
        $certificate->loadMissing('registration.person', 'registration.event');
        $email = $certificate->registration->person->email;

        if (! $email) {
            throw new \RuntimeException('Peserta tidak memiliki alamat email.');
        }
        if (! $certificate->pdf_path || ! Storage::disk(config('sigital.pdf_disk'))->exists($certificate->pdf_path)) {
            throw new \RuntimeException('Berkas PDF sertifikat tidak ditemukan.');
        }

        Mail::to($email)->queue(new CertificateMail($certificate));
        $this->audit->log('certificate.emailed', $certificate, ['email' => $email]);
    }

    /** Cabut sertifikat (status → revoked). Nomor tetap terkunci, tak dihapus (jejak audit). */
    public function revoke(Certificate $certificate, ?string $reason = null): void
    {
        $certificate->update([
            'status' => Certificate::STATUS_REVOKED,
            'alasan_pencabutan' => $reason,
        ]);
        $this->audit->log('certificate.revoked', $certificate, ['alasan' => $reason]);
    }
}
