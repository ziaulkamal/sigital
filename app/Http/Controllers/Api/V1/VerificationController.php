<?php

/**
 * app/Http/Controllers/Api/V1/VerificationController.php
 * Verifikasi keaslian sertifikat di sisi server via token opaque (publik, stateless).
 */

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VerificationController extends Controller
{
    /**
     * Bedakan tiga keadaan (FR-17): asli, dicabut, atau tidak ditemukan.
     * Hanya tampilkan metadata secukupnya — tanpa data pribadi (NFR-06).
     */
    public function show(string $token): JsonResponse
    {
        $certificate = Certificate::with('registration.person', 'registration.event.organization')
            ->where('qr_token', $token)
            ->first();

        if (! $certificate) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'Sertifikat tidak ditemukan. Kemungkinan tidak sah.',
                'app' => $this->appBrand(),
            ], 404);
        }

        $event = $certificate->registration->event;
        $org = $event->organization;

        // Logo acara (jika ada) → fallback ke logo organisasi. URL publik absolut.
        $logoPath = $event->logo_path ?: $org?->logo_path;

        $base = [
            'nomor' => $certificate->nomor_unik,
            'nama' => $certificate->registration->person->nama,
            'acara' => $event->nama,
            // Pelaksana = organisasi pemilik acara (atau instansi default).
            'pelaksana' => $org?->nama ?? config('sigital.instansi_nama'),
            'lokasi' => $event->lokasi,
            'tanggal_mulai' => optional($event->jadwal_mulai)->translatedFormat('d F Y'),
            'tanggal_selesai' => optional($event->jadwal_selesai)->translatedFormat('d F Y'),
            'tanggal_acara' => optional($event->jadwal_mulai)->toDateString(),
            'diterbitkan' => optional($certificate->issued_at)->translatedFormat('d F Y'),
            'logo' => $logoPath ? asset('storage/'.$logoPath) : null,
        ];

        if ($certificate->status === Certificate::STATUS_REVOKED) {
            return response()->json([
                'status' => 'revoked',
                'message' => 'Sertifikat ini telah dicabut/dibatalkan.',
                'data' => $base,
                'app' => $this->appBrand(),
            ]);
        }

        return response()->json([
            'status' => 'valid',
            'message' => 'Sertifikat ASLI dan terverifikasi.',
            'data' => $base,
            'app' => $this->appBrand(),
        ]);
    }

    /**
     * Unduh PDF sertifikat secara publik via token QR — HANYA bila sertifikat
     * masih sah (issued) dan berkas tersedia. Sertifikat dicabut/tidak ada → 404.
     */
    public function download(string $token): StreamedResponse
    {
        $certificate = Certificate::where('qr_token', $token)
            ->where('status', Certificate::STATUS_ISSUED)
            ->first();

        $disk = config('sigital.pdf_disk');
        abort_unless(
            $certificate && $certificate->pdf_path && Storage::disk($disk)->exists($certificate->pdf_path),
            404
        );

        return Storage::disk($disk)->download(
            $certificate->pdf_path,
            'sertifikat-'.str_replace(['/', '\\'], '-', $certificate->nomor_unik).'.pdf'
        );
    }

    /** Identitas aplikasi untuk ditampilkan di halaman verifikasi publik. */
    private function appBrand(): array
    {
        $logo = config('sigital.brand.logo');

        return [
            'name' => config('sigital.brand.name'),
            'tagline' => config('sigital.brand.tagline'),
            'logo' => $logo ? asset('storage/'.$logo) : null,
        ];
    }
}
