<?php

/**
 * app/Http/Controllers/Auth/TwoFactorChallengeController.php
 * Tantangan 2FA saat login (P5): kredensial sudah benar, user "ditahan" (belum login penuh)
 * via session login.2fa_user_id. Terima kode TOTP atau recovery code (sekali pakai).
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $service,
        private readonly AuditLogger $audit,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('login.2fa_user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('login.2fa_user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $user = User::findOrFail($userId);

        if (! $this->passesChallenge($request, $user)) {
            throw ValidationException::withMessages([
                'code' => 'Kode 2FA tidak valid.',
            ]);
        }

        // Lolos → login penuh.
        $remember = (bool) $request->session()->pull('login.2fa_remember', false);
        $request->session()->forget('login.2fa_user_id');

        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();

        $this->audit->log('auth.login', $user, ['via' => '2fa']);

        return redirect()->intended(route('dashboard'));
    }

    /** Verifikasi kode TOTP, atau konsumsi satu recovery code. */
    private function passesChallenge(Request $request, User $user): bool
    {
        $code = trim((string) $request->input('code'));
        if ($code !== '' && $user->two_factor_secret !== null) {
            return $this->service->verify($user->two_factor_secret, $code);
        }

        $recovery = trim((string) $request->input('recovery_code'));
        if ($recovery !== '') {
            return $this->consumeRecoveryCode($user, $recovery);
        }

        return false;
    }

    private function consumeRecoveryCode(User $user, string $recovery): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];

        if (! in_array($recovery, $codes, true)) {
            return false;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => array_values(array_filter($codes, fn ($c) => $c !== $recovery)),
        ])->save();

        return true;
    }
}
