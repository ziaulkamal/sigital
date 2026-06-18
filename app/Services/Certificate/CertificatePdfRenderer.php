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
        ]);

        $event = $certificate->registration->event;
        $view = $event->template?->view ?: 'certificates.default';

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
            'instansi' => config('sigital.instansi_nama'),
        ])->setPaper('a4', 'landscape');

        return $pdf->output();
    }
}
