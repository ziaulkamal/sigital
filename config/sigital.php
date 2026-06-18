<?php

/**
 * config/sigital.php
 * Konfigurasi produk: penomoran sertifikat & penyimpanan arsip.
 */

return [
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
