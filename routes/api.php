<?php

/**
 * routes/api.php
 * Permukaan API stateless ber-versi (/api/v1). Untuk verifikasi publik QR,
 * status penerbitan batch, dan kanal masa depan (portal peserta, integrasi BSrE/e-Office).
 */

use App\Http\Controllers\Api\V1\VerificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Publik — verifikasi keaslian sertifikat (server-side, token opaque).
    Route::get('/verify/{token}', [VerificationController::class, 'show'])
        ->name('verify')
        ->middleware('throttle:60,1');

    // Publik — unduh PDF sertifikat via token (hanya bila masih sah).
    Route::get('/verify/{token}/download', [VerificationController::class, 'download'])
        ->name('verify.download')
        ->middleware('throttle:30,1');

    // Terlindungi token (Sanctum) — status batch, dsb. Diisi pada M3.
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', fn (\Illuminate\Http\Request $r) => $r->user());
    });
});
