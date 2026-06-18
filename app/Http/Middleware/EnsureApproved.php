<?php

/**
 * app/Http/Middleware/EnsureApproved.php
 * Fase P2 — memblokir akses aplikasi untuk akun yang belum di-approve SuperAdmin (K4).
 * User non-approved diarahkan ke halaman "menunggu persetujuan"; SuperAdmin selalu lolos.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isApproved()) {
            return redirect()->route('pending');
        }

        return $next($request);
    }
}
