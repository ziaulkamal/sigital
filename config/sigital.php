<?php

/**
 * config/sigital.php
 * Konfigurasi produk: penomoran sertifikat & penyimpanan arsip.
 */

return [
    // Identitas/brand aplikasi (dipakai di login, sidebar, topbar — tanpa hardcode di Vue).
    'brand' => [
        'name' => env('SIGITAL_BRAND_NAME', 'SIGITAL'),
        'tagline' => env('SIGITAL_BRAND_TAGLINE', 'Sertifikat Digital'),
        // Path logo relatif ke disk public (mis. 'branding/app-logo.png'). Null = pakai ikon bawaan.
        'logo' => env('SIGITAL_BRAND_LOGO'),
    ],

    // Gerbang WhatsApp untuk OTP verifikasi akun (anti akun palsu).
    // Driver 'waha' = WhatsApp HTTP API (https://waha.devlike.pro/) self-host; 'log' = tulis ke log (dev).
    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'waha'),
        'waha' => [
            'base_url' => env('WAHA_BASE_URL', 'http://localhost:3000'),
            'session' => env('WAHA_SESSION', 'default'),
            'api_key' => env('WAHA_API_KEY'), // header X-Api-Key bila di-set
        ],
        'otp' => [
            'length' => 6,
            'ttl_minutes' => 10,
        ],
    ],

    // Kode instansi pada nomor sertifikat (nomenklatur final — lihat Pertanyaan Terbuka PRD).
    'instansi_kode' => env('SIGITAL_INSTANSI_KODE', 'DISKOMINFO'),
    'instansi_nama' => env('SIGITAL_INSTANSI_NAMA', 'Dinas Komunikasi dan Informatika'),

    // Penomoran: panjang nomor urut & sufiks acak (NFR-01: sulit ditebak).
    'nomor' => [
        'urut_pad' => 4,
        'suffix_length' => 4,
        'suffix_alphabet' => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789', // hindari karakter mirip (0/O, 1/I)
    ],

    // Disk penyimpanan PDF arsip (Laravel Filesystem).
    'pdf_disk' => env('SIGITAL_PDF_DISK', 'local'),
    'pdf_dir' => 'certificates',

    // Arsip jangka panjang PDF/A via Ghostscript (NFR-09).
    'pdfa' => [
        'enabled' => env('SIGITAL_PDFA', false),     // konversi otomatis saat terbit
        'ghostscript' => env('GHOSTSCRIPT_BIN', 'gs'), // 'gswin64c' di Windows
    ],

    // Retensi data (NFR-07). Null = simpan selamanya. Satuan: hari.
    'retensi_hari' => env('SIGITAL_RETENSI_HARI'),
];
