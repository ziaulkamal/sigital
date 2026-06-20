<?php

/**
 * app/Http/Controllers/Auth/AuthenticatedSessionController.php
 * Login/logout berbasis sesi untuk UI internal (Inertia).
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $user = $request->user();

        // Akun diblokir SuperAdmin → tolak login & tampilkan alasan pemblokiran.
        if ($user->isBanned()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Akun Anda diblokir. Alasan: '.$user->banned_reason,
            ]);
        }

        // Akun dinonaktifkan (tanpa jejak ban) → tolak login.
        if ($user->isSuspended()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages(['email' => 'Akun Anda dinonaktifkan. Hubungi administrator.']);
        }

        // P5 — bila 2FA aktif: tahan login, alihkan ke tantangan kode TOTP/recovery.
        if ($user->hasTwoFactorEnabled()) {
            Auth::guard('web')->logout();
            $request->session()->put('login.2fa_user_id', $user->id);
            $request->session()->put('login.2fa_remember', $request->boolean('remember'));

            return redirect()->route('two-factor.login');
        }

        $request->session()->regenerate();
        $this->audit->log('auth.login', $user);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->audit->log('auth.logout', $request->user());

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
