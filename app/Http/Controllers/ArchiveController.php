<?php

/**
 * app/Http/Controllers/ArchiveController.php
 * Arsip & pencarian sertifikat + aksi distribusi/unduh/cabut (FR-18/21/22).
 */

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Event;
use App\Repositories\CertificateRepository;
use App\Services\Certificate\CertificateDistributor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArchiveController extends Controller
{
    public function __construct(
        private readonly CertificateRepository $repo,
        private readonly CertificateDistributor $distributor,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only('q', 'event_id', 'status', 'from', 'to');
        $page = $this->repo->search($filters);

        return Inertia::render('Certificates/Index', [
            'filters' => $filters,
            'events' => Event::orderByDesc('id')->get()->map->only('id', 'nama'),
            'certificates' => [
                'data' => collect($page->items())->map(fn (Certificate $c) => [
                    'id' => $c->id,
                    'nomor' => $c->nomor_unik,
                    'nama' => $c->registration->person->nama,
                    'acara' => $c->registration->event->nama,
                    'status' => $c->status,
                    'issued_at' => $c->issued_at?->toDateString(),
                    'has_email' => (bool) $c->registration->person->email,
                ]),
                'links' => $page->linkCollection(),
                'total' => $page->total(),
            ],
        ]);
    }

    /** Stream unduh PDF dari disk arsip (FR-21). */
    public function download(Certificate $certificate): StreamedResponse
    {
        abort_unless($certificate->pdf_path && Storage::disk(config('sigital.pdf_disk'))->exists($certificate->pdf_path), 404);

        return Storage::disk(config('sigital.pdf_disk'))->download(
            $certificate->pdf_path,
            'sertifikat-'.str_replace(['/', '\\'], '-', $certificate->nomor_unik).'.pdf'
        );
    }

    /** Tampilkan PDF inline (buka di tab baru) untuk dicetak (poin 3 — "cetak"). */
    public function view(Certificate $certificate): StreamedResponse
    {
        abort_unless($certificate->pdf_path && Storage::disk(config('sigital.pdf_disk'))->exists($certificate->pdf_path), 404);

        return Storage::disk(config('sigital.pdf_disk'))->response(
            $certificate->pdf_path,
            'sertifikat-'.str_replace(['/', '\\'], '-', $certificate->nomor_unik).'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function email(Certificate $certificate): RedirectResponse
    {
        try {
            $this->distributor->email($certificate);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Sertifikat dikirim ke email peserta.');
    }

    public function revoke(Request $request, Certificate $certificate): RedirectResponse
    {
        $request->validate(['alasan' => ['nullable', 'string', 'max:500']]);
        $this->distributor->revoke($certificate, $request->input('alasan'));

        return back()->with('success', 'Sertifikat dicabut.');
    }

    /** Pulihkan sertifikat yang dicabut. HANYA SuperAdmin. */
    public function restore(Request $request, Certificate $certificate): RedirectResponse
    {
        abort_unless($request->user()?->isSuperAdmin(), 403);

        try {
            $this->distributor->restore($certificate);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Sertifikat dipulihkan.');
    }
}
