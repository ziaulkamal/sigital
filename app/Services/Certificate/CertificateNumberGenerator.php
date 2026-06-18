<?php

/**
 * app/Services/Certificate/CertificateNumberGenerator.php
 * Membuat nomor sertifikat unik & terstruktur + sufiks acak (FR-10, NFR-01).
 * Format: [INSTANSI]/[KODE-ACARA]/[NO-URUT]/[BULAN-ROMAWI]/[TAHUN]-[SUFIKS]
 */

namespace App\Services\Certificate;

use App\Models\Certificate;
use App\Models\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CertificateNumberGenerator
{
    private const ROMAN = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];

    /** Hasilkan nomor unik untuk satu penerbitan di acara tertentu. */
    public function generate(Event $event, int $sequence, ?Carbon $at = null): string
    {
        $at ??= now();
        $cfg = config('sigital');

        // Prefix nomor memakai kode organisasi penerbit (P1); fallback ke config legacy.
        $event->loadMissing('organization');
        $instansi = Str::upper($event->organization?->kode ?? $cfg['instansi_kode']);
        $kodeAcara = Str::upper($event->kode ?: Str::slug($event->nama));
        $urut = str_pad((string) $sequence, $cfg['nomor']['urut_pad'], '0', STR_PAD_LEFT);
        $bulan = self::ROMAN[(int) $at->format('n')];
        $tahun = $at->format('Y');

        // Retry bila sufiks acak bentrok (kolom nomor_unik unique).
        do {
            $suffix = $this->randomSuffix($cfg['nomor']['suffix_length'], $cfg['nomor']['suffix_alphabet']);
            $nomor = "{$instansi}/{$kodeAcara}/{$urut}/{$bulan}/{$tahun}-{$suffix}";
        } while (Certificate::where('nomor_unik', $nomor)->exists());

        return $nomor;
    }

    private function randomSuffix(int $length, string $alphabet): string
    {
        $max = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }

        return $out;
    }
}
