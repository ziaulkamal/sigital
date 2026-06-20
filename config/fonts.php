<?php

/**
 * config/fonts.php
 *
 * Whitelist font terkurasi (Google Fonts) untuk perancang & render sertifikat.
 * SUMBER KEBENARAN TUNGGAL — dipakai oleh:
 *   - Validasi backend (App\Support\TemplateLayout) → tolak font di luar daftar.
 *   - Dropdown editor (TemplateController::editor mengirim array_keys).
 *   - Registrasi font di Node renderer (node-renderer/render.mjs) via path .ttf.
 *
 * Keamanan: hanya font dari daftar ini yang boleh dipakai template. Tidak ada
 * jalur upload font sembarang (fase berikutnya, dengan hardening tersendiri).
 *
 * Berkas .ttf di-bundle lokal (offline & deterministik) di:
 *   base_path('resources/fonts')
 * Lihat resources/fonts/README.md untuk cara mengunduh berkas font.
 *
 * Setiap entri:
 *   family      → nama keluarga font (dipakai @font-face editor & registerFont Node)
 *   regular     → nama berkas .ttf bobot normal
 *   bold        → nama berkas .ttf bobot tebal (opsional; fallback ke regular)
 *   category    → 'sans' | 'serif' | 'display' | 'mono' (sekadar info UI)
 */

return [

    // Direktori berkas .ttf relatif base path.
    'path' => 'resources/fonts',

    // Font default bila element tidak menyebut font (atau font tak dikenal).
    'default' => 'DejaVu Sans',

    'families' => [
        // DejaVu selalu tersedia (di-bundle DomPDF) → fallback aman.
        'DejaVu Sans' => [
            'family' => 'DejaVu Sans',
            'regular' => 'DejaVuSans.ttf',
            'bold' => 'DejaVuSans-Bold.ttf',
            'category' => 'sans',
        ],
        'Poppins' => [
            'family' => 'Poppins',
            'regular' => 'Poppins-Regular.ttf',
            'bold' => 'Poppins-Bold.ttf',
            'category' => 'sans',
        ],
        'Roboto' => [
            'family' => 'Roboto',
            'regular' => 'Roboto-Regular.ttf',
            'bold' => 'Roboto-Bold.ttf',
            'category' => 'sans',
        ],
        'Montserrat' => [
            'family' => 'Montserrat',
            'regular' => 'Montserrat-Regular.ttf',
            'bold' => 'Montserrat-Bold.ttf',
            'category' => 'sans',
        ],
        'Open Sans' => [
            'family' => 'Open Sans',
            'regular' => 'OpenSans-Regular.ttf',
            'bold' => 'OpenSans-Bold.ttf',
            'category' => 'sans',
        ],
        'Lato' => [
            'family' => 'Lato',
            'regular' => 'Lato-Regular.ttf',
            'bold' => 'Lato-Bold.ttf',
            'category' => 'sans',
        ],
        'Merriweather' => [
            'family' => 'Merriweather',
            'regular' => 'Merriweather-Regular.ttf',
            'bold' => 'Merriweather-Bold.ttf',
            'category' => 'serif',
        ],
        'Lora' => [
            'family' => 'Lora',
            'regular' => 'Lora-Regular.ttf',
            'bold' => 'Lora-Bold.ttf',
            'category' => 'serif',
        ],
        'Playfair Display' => [
            'family' => 'Playfair Display',
            'regular' => 'PlayfairDisplay-Regular.ttf',
            'bold' => 'PlayfairDisplay-Bold.ttf',
            'category' => 'display',
        ],
        'Cormorant Garamond' => [
            'family' => 'Cormorant Garamond',
            'regular' => 'CormorantGaramond-Regular.ttf',
            'bold' => 'CormorantGaramond-Bold.ttf',
            'category' => 'serif',
        ],
        'Great Vibes' => [
            'family' => 'Great Vibes',
            'regular' => 'GreatVibes-Regular.ttf',
            'bold' => 'GreatVibes-Regular.ttf',
            'category' => 'display',
        ],
    ],

    // Biner Node untuk memanggil renderer (override via .env NODE_BINARY).
    'node_binary' => env('NODE_BINARY', 'node'),
];
