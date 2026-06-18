<?php

/**
 * app/Jobs/IssueCertificateJob.php
 * Terbitkan satu sertifikat secara asinkron sebagai bagian dari batch (FR-12).
 */

namespace App\Jobs;

use App\Models\Registration;
use App\Services\Certificate\CertificateIssuer;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class IssueCertificateJob implements ShouldQueue
{
    use Batchable, Queueable;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public readonly int $registrationId,
        public readonly ?int $issuedBy = null,
    ) {}

    public function handle(CertificateIssuer $issuer): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $registration = Registration::find($this->registrationId);
        if (! $registration) {
            return;
        }

        $issuer->issue($registration, $this->issuedBy, $this->batch()?->id);
    }
}
