<?php

/**
 * app/Http/Middleware/EnsureApproved.php
 * Fase P2 — memblokir akses aplikasi untuk akun yang belum di-approve SuperAdmin (K4).
 * User non-approved diarahkan ke halaman "menunggu persetujuan"; SuperAdmin selalu lolos.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Diblokir saat sesi berjalan → keluarkan & arahkan ke login dengan alasan.
        if ($user && $user->isBanned()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda diblokir. Alasan: '.$user->banned_reason,
            ]);
        }

        if ($user && ! $user->isApproved()) {
            return redirect()->route('pending');
        }

        return $next($request);
    }
}
