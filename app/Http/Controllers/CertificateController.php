<?php

/**
 * app/Http/Controllers/CertificateController.php
 * Penerbitan sertifikat: satuan, massal (batch asinkron), dan status progres (FR-11/12).
 */

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Event;
use App\Models\Registration;
use App\Services\Certificate\BatchIssuanceService;
use App\Services\Certificate\CertificateIssuer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Bus;

class CertificateController extends Controller
{
    public function __construct(
        private readonly CertificateIssuer $issuer,
        private readonly BatchIssuanceService $batch,
    ) {}

    /** Terbitkan satu sertifikat (sinkron). */
    public function issueOne(Registration $registration): RedirectResponse
    {
        try {
            $this->issuer->issue($registration, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Sertifikat diterbitkan.');
    }

    /** Antrekan penerbitan massal untuk seluruh peserta acara. */
    public function issueBatch(Event $event): RedirectResponse
    {
        $this->authorize('view', $event); // P7 — hanya owner/member acara

        try {
            $batch = $this->batch->dispatchFor($event, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Penerbitan massal diantrekan.')
            ->with('batchId', $batch->id);
    }

    /** Buat ulang PDF satu sertifikat dari template terbaru (nomor & QR tetap). */
    public function regenerate(Certificate $certificate): RedirectResponse
    {
        $this->authorize('view', $certificate->registration->event);

        try {
            $this->issuer->regenerate($certificate, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Sertifikat dibuat ulang sesuai template terbaru.');
    }

    /** Buat ulang PDF semua sertifikat aktif pada acara (terapkan template terbaru). */
    public function regenerateEvent(Event $event): RedirectResponse
    {
        $this->authorize('view', $event);

        $certificates = Certificate::whereHas('registration', fn ($q) => $q->where('event_id', $event->id))
            ->where('status', Certificate::STATUS_ISSUED)
            ->get();

        $count = 0;
        foreach ($certificates as $cert) {
            try {
                $this->issuer->regenerate($cert, auth()->id());
                $count++;
            } catch (\RuntimeException) {
                // lewati yang gagal; lanjutkan sisanya
            }
        }

        return back()->with('success', "{$count} sertifikat dibuat ulang sesuai template terbaru.");
    }

    /** Progres batch untuk polling UI (FR-12). */
    public function batchStatus(string $batchId): JsonResponse
    {
        $batch = Bus::findBatch($batchId);

        if (! $batch) {
            return response()->json(['finished' => true, 'progress' => 100], 404);
        }

        return response()->json([
            'total' => $batch->totalJobs,
            'pending' => $batch->pendingJobs,
            'processed' => $batch->processedJobs(),
            'failed' => $batch->failedJobs,
            'progress' => $batch->progress(),
            'finished' => $batch->finished(),
            'cancelled' => $batch->cancelled(),
        ]);
    }
}
