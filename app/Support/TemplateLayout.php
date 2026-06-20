<?php

/**
 * app/Support/TemplateLayout.php
 *
 * Sanitasi & normalisasi dokumen layout perancang template (WYSIWYG).
 * SEMUA layout dari klien WAJIB melewati sanitize() sebelum disimpan/dirender.
 *
 * Prinsip keamanan (anti-injeksi):
 *  - Whitelist ketat: hanya `type` & `font` yang dikenal yang lolos; sisanya dibuang.
 *  - Koordinat di-clamp ke [0,1]; ukuran ke rentang wajar.
 *  - `color` wajib hex valid (regex) → cegah CSS/JS injection di editor.
 *  - `text` di-strip tag & dibatasi panjang → cegah XSS saat ditampilkan.
 *  - Tidak ada HTML mentah / path file yang berasal dari klien diterima di sini
 *    (path gambar latar diatur server saat unggah, bukan dari layout).
 */

namespace App\Support;

class TemplateLayout
{
    /** Tipe element yang diizinkan. Identik dgn node-renderer/layout-geometry.mjs. */
    public const TYPES = [
        'nama_peserta',
        'event',
        'tanggal',
        'nomor',
        'qr',
        'ttd',
        'tanda_tangan', // blok multi penanda tangan (auto-layout saat render)
        'logo',
        'teks',
    ];

    /** Tipe yang membawa properti gaya teks (font/size/color/align/bold). */
    private const STYLED_TYPES = ['nama_peserta', 'event', 'tanggal', 'nomor', 'teks', 'tanda_tangan'];

    public const ALIGNS = ['left', 'center', 'right'];

    private const MAX_ELEMENTS = 50;

    private const MAX_TEXT_LEN = 1500;

    /**
     * Sanitasi penuh sebuah dokumen layout: {canvas:{w,h}, elements:[...]}.
     *
     * @param  array<string,mixed>|null  $layout
     * @return array{canvas: array{w:int,h:int}, elements: array<int, array<string,mixed>>}
     */
    public static function sanitize(?array $layout): array
    {
        $layout ??= [];

        $cw = (int) (data_get($layout, 'canvas.w') ?: 0);
        $ch = (int) (data_get($layout, 'canvas.h') ?: 0);

        $rawElements = is_array($layout['elements'] ?? null) ? $layout['elements'] : [];
        $elements = [];

        foreach (array_slice($rawElements, 0, self::MAX_ELEMENTS) as $i => $raw) {
            if (! is_array($raw)) {
                continue;
            }
            $clean = self::sanitizeElement($raw, $i);
            if ($clean !== null) {
                $elements[] = $clean;
            }
        }

        return [
            'canvas' => ['w' => max(0, $cw), 'h' => max(0, $ch)],
            'elements' => $elements,
        ];
    }

    /** Daftar nama font yang diizinkan (keys config/fonts). */
    public static function allowedFonts(): array
    {
        return array_keys((array) config('fonts.families', []));
    }

    /**
     * @param  array<string,mixed>  $raw
     * @return array<string,mixed>|null null bila element tak valid (dibuang)
     */
    private static function sanitizeElement(array $raw, int $index): ?array
    {
        $type = is_string($raw['type'] ?? null) ? $raw['type'] : null;
        if (! in_array($type, self::TYPES, true)) {
            return null; // tipe tak dikenal → buang (anti-injeksi)
        }

        $el = [
            'id' => self::cleanId($raw['id'] ?? null, $index),
            'type' => $type,
            'x' => self::clamp($raw['x'] ?? 0, 0, 1),
            'y' => self::clamp($raw['y'] ?? 0, 0, 1),
            'w' => self::clamp($raw['w'] ?? 0.2, 0.01, 1),
        ];

        // Properti gaya teks (juga dipakai blok tanda_tangan untuk nama/jabatan).
        if (in_array($type, self::STYLED_TYPES, true)) {
            $el['size'] = self::clamp($raw['size'] ?? 0.04, 0.005, 0.5);
            $el['align'] = in_array($raw['align'] ?? null, self::ALIGNS, true) ? $raw['align'] : 'center';
            $el['color'] = self::cleanColor($raw['color'] ?? null);
            $el['bold'] = (bool) ($raw['bold'] ?? false);
            $el['font'] = self::cleanFont($raw['font'] ?? null);

            // 'teks' = teks statis; 'event' = template keterangan (boleh berisi
            // placeholder {tanggal}/{durasi}/{lokasi}/{acara}/{instansi} & **bold**).
            if ($type === 'teks' || $type === 'event') {
                $el['text'] = self::cleanText($raw['text'] ?? '');
            }
        }

        return $el;
    }

    private static function clamp(mixed $value, float $min, float $max): float
    {
        $n = is_numeric($value) ? (float) $value : $min;

        return max($min, min($max, $n));
    }

    private static function cleanColor(mixed $value): string
    {
        $v = is_string($value) ? trim($value) : '';

        return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? strtolower($v) : '#111827';
    }

    private static function cleanFont(mixed $value): string
    {
        $allowed = self::allowedFonts();
        $v = is_string($value) ? $value : '';

        if (in_array($v, $allowed, true)) {
            return $v;
        }

        return (string) config('fonts.default', 'DejaVu Sans');
    }

    private static function cleanText(mixed $value): string
    {
        $v = is_string($value) ? $value : '';
        $v = strip_tags($v);

        return mb_substr($v, 0, self::MAX_TEXT_LEN);
    }

    private static function cleanId(mixed $value, int $index): string
    {
        $v = is_string($value) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $value) : '';

        return $v !== '' ? $v : 'el'.$index;
    }
}
