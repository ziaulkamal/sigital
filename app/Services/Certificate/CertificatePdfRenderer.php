<?php

/**
 * app/Services/Certificate/CertificatePdfRenderer.php
 * Render PDF sertifikat dari template Blade (sisip nama, acara, nomor, TTD, QR).
 */

namespace App\Services\Certificate;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificatePdfRenderer
{
    public function __construct(private readonly QrCodeGenerator $qr) {}

    /** Hasilkan biner PDF untuk satu sertifikat. */
    public function render(Certificate $certificate): string
    {
        $certificate->loadMissing([
            'registration.person',
            'registration.event.signatories',
            'registration.event.template',
            'registration.event.organization',
        ]);

        $event = $certificate->registration->event;
        $template = $event->template;
        $org = $event->organization;

        // Latar dari template (P6) → render via kanvas koordinat; selain itu view default.
        $backgroundPath = $this->publicPath($template?->background_path);
        $view = $backgroundPath !== null
            ? 'certificates.canvas'
            : ($template?->view ?: 'certificates.default');

        // QR sebagai data URI SVG (andal di DomPDF, tanpa imagick).
        $qrSvg = $this->qr->svg($certificate->qr_token, 150);
        $qrDataUri = 'data:image/svg+xml;base64,'.base64_encode($qrSvg);

        $pdf = Pdf::loadView($view, [
            'certificate' => $certificate,
            'person' => $certificate->registration->person,
            'event' => $event,
            'signatories' => $event->signatories,
            'qrDataUri' => $qrDataUri,
            'verifyUrl' => $this->qr->verifyUrl($certificate->qr_token),
            'instansi' => $org?->nama ?? config('sigital.instansi_nama'),
            // Branding organisasi (P6/K8) — disisipkan di kepala sertifikat.
            'logoPath' => $this->publicPath($org?->logo_path),
            'kopPath' => $this->publicPath($org?->kop_path),
            // Template kanvas: latar + koordinat field.
            'backgroundPath' => $backgroundPath,
            'positions' => $template?->posisi_field ?? [],
        ])->setPaper('a4', 'landscape');

        return $pdf->output();
    }

    /** Path absolut berkas pada disk public bila ada, untuk disematkan DomPDF. */
    private function publicPath(?string $relative): ?string
    {
        if ($relative === null || $relative === '') {
            return null;
        }

        $absolute = storage_path('app/public/'.$relative);

        return file_exists($absolute) ? $absolute : null;
    }
}
