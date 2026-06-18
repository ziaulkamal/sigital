<?php

/**
 * app/Http/Controllers/TwoFactorController.php
 * Pengaturan 2FA milik sendiri (P5): aktifkan (QR) → konfirmasi kode → recovery codes;
 * nonaktifkan (wajib password). Opsional per-user (Q5).
 */

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $service,
        private readonly AuditLogger $audit,
    ) {}

    /** Langkah 1 — buat secret (belum dikonfirmasi) lalu tampilkan QR + secret. */
    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return back()->with('error', '2FA sudah aktif.');
        }

        $secret = $this->service->generateSecret();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('twoFactor', [
            'stage' => 'setup',
            'svg' => $this->service->qrCodeSvg($user, $secret),
            'secret' => $secret,
        ]);
    }

    /** Langkah 2 — verifikasi kode TOTP → aktifkan + terbitkan recovery codes. */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();

        if ($user->two_factor_secret === null) {
            return back()->with('error', 'Mulai aktivasi 2FA terlebih dahulu.');
        }

        if (! $this->service->verify($user->two_factor_secret, $request->string('code'))) {
            throw ValidationException::withMessages(['code' => 'Kode verifikasi salah.']);
        }

        $codes = $this->service->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->audit->log('auth.2fa_enabled', $user);

        return back()->with('twoFactor', [
            'stage' => 'enabled',
            'recovery_codes' => $codes,
        ]);
    }

    /** Terbitkan ulang recovery codes (butuh 2FA aktif). */
    public function recoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return back()->with('error', '2FA belum aktif.');
        }

        $codes = $this->service->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return back()->with('twoFactor', ['stage' => 'enabled', 'recovery_codes' => $codes]);
    }

    /** Nonaktifkan 2FA — wajib verifikasi password (mencegah penyalahgunaan sesi). */
    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'current_password']]);
        $user = $request->user();

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->audit->log('auth.2fa_disabled', $user);

        return back()->with('success', '2FA berhasil dinonaktifkan.');
    }
}
