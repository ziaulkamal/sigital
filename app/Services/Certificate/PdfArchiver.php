<?php

/**
 * app/Services/Certificate/PdfArchiver.php
 * Konversi PDF → PDF/A-2b via Ghostscript untuk arsip jangka panjang (NFR-09).
 */

namespace App\Services\Certificate;

use Symfony\Component\Process\Process;

class PdfArchiver
{
    /** Apakah Ghostscript tersedia di environment ini. */
    public function available(): bool
    {
        $bin = config('sigital.pdfa.ghostscript');
        $probe = new Process([$bin, '--version']);
        $probe->run();

        return $probe->isSuccessful();
    }

    /**
     * Konversi berkas PDF di tempat (overwrite) menjadi PDF/A-2b.
     *
     * @return bool true bila berhasil dikonversi
     */
    public function toPdfA(string $absolutePath): bool
    {
        if (! is_file($absolutePath) || ! $this->available()) {
            return false;
        }

        $tmp = $absolutePath.'.pdfa.tmp';
        $process = new Process([
            config('sigital.pdfa.ghostscript'),
            '-dPDFA=2', '-dBATCH', '-dNOPAUSE', '-dNOOUTERSAVE',
            '-sColorConversionStrategy=UseDeviceIndependentColor',
            '-sDEVICE=pdfwrite', '-dPDFACompatibilityPolicy=1',
            '-sOutputFile='.$tmp, $absolutePath,
        ]);
        $process->setTimeout(120);
        $process->run();

        if ($process->isSuccessful() && is_file($tmp)) {
            rename($tmp, $absolutePath);

            return true;
        }

        @unlink($tmp);

        return false;
    }
}
