<?php

/**
 * app/Services/Certificate/QrCodeGenerator.php
 * Membuat QR SVG berisi URL verifikasi (token opaque) — bukan data pribadi (NFR-02).
 */

namespace App\Services\Certificate;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeGenerator
{
    /** URL verifikasi publik untuk sebuah token. */
    public function verifyUrl(string $token): string
    {
        return url('/verify/'.$token);
    }

    /** SVG inline (tanpa deklarasi XML) siap disisipkan ke DomPDF. */
    public function svg(string $token, int $size = 150): string
    {
        $svg = QrCode::format('svg')
            ->size($size)
            ->margin(0)
            ->errorCorrection('M')
            ->generate($this->verifyUrl($token));

        // Buang deklarasi XML di awal agar valid sebagai inline SVG di DomPDF.
        return (string) preg_replace('/<\?xml.*?\?>/', '', (string) $svg);
    }

    /** Data URI SVG (base64) — dipakai Node renderer yang memuat via loadImage. */
    public function svgDataUri(string $token, int $size = 300): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($token, $size));
    }
}
