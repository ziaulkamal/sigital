<?php

/**
 * app/Support/Phone.php
 * Normalisasi nomor WhatsApp ke format Indonesia 62…:
 *  - "08xxxx"  → "628xxxx"   (awalan 0 diganti 62)
 *  - "+62xxxx" → "62xxxx"    (buang tanda +)
 *  - "62xxxx"  → "62xxxx"    (sudah sesuai)
 *  - "8xxxx"   → "628xxxx"   (tanpa awalan, anggap nomor seluler)
 * Mengembalikan null bila input kosong.
 */

namespace App\Support;

class Phone
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        // Sisakan digit saja (buang +, spasi, tanda hubung, dll.).
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return null;
        }

        // Awalan 0 → 62 (mis. 0812… → 62812…).
        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        // Sudah berawalan 62 → biarkan.
        if (str_starts_with($digits, '62')) {
            return $digits;
        }

        // Tanpa awalan (mis. 812…) → tambahkan 62.
        return '62'.$digits;
    }
}
