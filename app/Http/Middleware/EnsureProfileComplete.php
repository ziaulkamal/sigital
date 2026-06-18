<?php

/**
 * app/Http/Middleware/EnsureProfileComplete.php
 * Setelah akun di-approve, seluruh fitur dikunci hingga profil lengkap (NIK + nomor HP).
 * User yang belum melengkapi diarahkan ke halaman "lengkapi profil". SuperAdmin lolos.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->needsProfileCompletion()) {
            return redirect()->route('profile.complete');
        }

        return $next($request);
    }
}
