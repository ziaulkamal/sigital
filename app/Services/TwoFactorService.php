<?php

/**
 * app/Services/TwoFactorService.php
 * Logika 2FA TOTP (P5/K6): generate secret, render QR, verifikasi kode, recovery codes.
 * Implementasi mandiri pakai pragmarx/google2fa (tanpa Fortify) + simple-qrcode untuk QR SVG.
 */

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TwoFactorService
{
    public function __construct(private readonly Google2FA $engine) {}

    public function generateSecret(): string
    {
        return $this->engine->generateSecretKey();
    }

    /** Verifikasi kode TOTP terhadap secret (window default mentolerir drift waktu). */
    public function verify(string $secret, string $code): bool
    {
        return $this->engine->verifyKey($secret, $code);
    }

    /** QR otpauth dalam bentuk SVG (aman tanpa imagick) untuk dipindai authenticator. */
    public function qrCodeSvg(User $user, string $secret): string
    {
        $url = $this->engine->getQRCodeUrl(
            config('app.name', 'SIGITAL'),
            $user->email,
            $secret,
        );

        return (string) QrCode::format('svg')->size(200)->margin(1)->generate($url);
    }

    /**
     * Recovery codes sekali pakai (8 buah, format XXXXXXXXXX-XXXXXXXXXX).
     *
     * @return list<string>
     */
    public function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => Str::upper(Str::random(10).'-'.Str::random(10)))
            ->all();
    }
}
