<?php

/**
 * app/Services/Certificate/BatchIssuanceService.php
 * Antrekan penerbitan massal seluruh peserta acara (FR-12, NFR-10).
 */

namespace App\Services\Certificate;

use App\Jobs\IssueCertificateJob;
use App\Models\Event;
use App\Services\AuditLogger;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

class BatchIssuanceService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Buat batch job untuk peserta yang hadir & belum punya sertifikat.
     *
     * @throws \RuntimeException bila acara belum siap atau tak ada peserta yang perlu diterbitkan
     */
    public function dispatchFor(Event $event, ?int $issuedBy = null): Batch
    {
        if (! $event->canIssue()) {
            throw new \RuntimeException('Acara belum siap terbit: wajib punya minimal satu template dan satu penanda tangan.');
        }

        $registrationIds = $event->registrations()
            ->where('status_kehadiran', 'hadir')
            ->whereDoesntHave('certificate')
            ->pluck('id');

        if ($registrationIds->isEmpty()) {
            throw new \RuntimeException('Tidak ada peserta yang perlu diterbitkan (semua sudah terbit atau belum hadir).');
        }

        $jobs = $registrationIds->map(fn ($id) => new IssueCertificateJob($id, $issuedBy))->all();

        $batch = Bus::batch($jobs)
            ->name("Penerbitan: {$event->nama}")
            ->allowFailures()
            ->dispatch();

        $this->audit->log('certificate.batch_started', $event, [
            'batch_id' => $batch->id,
            'jumlah' => $registrationIds->count(),
        ], $issuedBy);

        return $batch;
    }
}
