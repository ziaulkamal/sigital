<?php

/**
 * app/Http/Controllers/Api/V1/VerificationController.php
 * Verifikasi keaslian sertifikat di sisi server via token opaque (publik, stateless).
 */

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;

class VerificationController extends Controller
{
    /**
     * Bedakan tiga keadaan (FR-17): asli, dicabut, atau tidak ditemukan.
     * Hanya tampilkan metadata secukupnya — tanpa data pribadi (NFR-06).
     */
    public function show(string $token): JsonResponse
    {
        $certificate = Certificate::with('registration.person', 'registration.event')
            ->where('qr_token', $token)
            ->first();

        if (! $certificate) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Sertifikat tidak ditemukan. Kemungkinan tidak sah.',
            ], 404);
        }

        $base = [
            'nomor' => $certificate->nomor_unik,
            'nama' => $certificate->registration->person->nama,
            'acara' => $certificate->registration->event->nama,
            'tanggal_acara' => optional($certificate->registration->event->jadwal_mulai)->toDateString(),
            'diterbitkan' => optional($certificate->issued_at)->toDateString(),
        ];

        if ($certificate->status === Certificate::STATUS_REVOKED) {
            return response()->json([
                'status' => 'revoked',
                'message' => 'Sertifikat ini telah dicabut/dibatalkan.',
                'data' => $base,
            ]);
        }

        return response()->json([
            'status' => 'valid',
            'message' => 'Sertifikat ASLI dan terverifikasi.',
            'data' => $base,
        ]);
    }
}
