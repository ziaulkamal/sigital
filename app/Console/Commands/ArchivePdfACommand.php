<?php

/**
 * app/Console/Commands/ArchivePdfACommand.php
 * Konversi PDF sertifikat ke PDF/A untuk arsip jangka panjang (NFR-09).
 */

namespace App\Console\Commands;

use App\Models\Certificate;
use App\Services\Certificate\PdfArchiver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ArchivePdfACommand extends Command
{
    protected $signature = 'certificates:pdfa {--id= : Konversi satu sertifikat saja}';
    protected $description = 'Konversi PDF sertifikat menjadi PDF/A via Ghostscript.';

    public function handle(PdfArchiver $archiver): int
    {
        if (! $archiver->available()) {
            $this->error('Ghostscript tidak ditemukan. Set GHOSTSCRIPT_BIN atau pasang Ghostscript.');

            return self::FAILURE;
        }

        $disk = Storage::disk(config('sigital.pdf_disk'));
        $query = Certificate::whereNotNull('pdf_path');
        if ($id = $this->option('id')) {
            $query->whereKey($id);
        }

        $converted = $failed = 0;
        $query->chunk(100, function ($certs) use ($disk, $archiver, &$converted, &$failed) {
            foreach ($certs as $cert) {
                if (! $disk->exists($cert->pdf_path)) {
                    continue;
                }
                $abs = $disk->path($cert->pdf_path);
                if ($archiver->toPdfA($abs)) {
                    // Hash berubah setelah konversi → perbarui agar verifikasi tetap valid.
                    $cert->forceFill(['pdf_hash' => hash('sha256', file_get_contents($abs))])->save();
                    $converted++;
                } else {
                    $failed++;
                    $this->warn("Gagal: {$cert->nomor_unik}");
                }
            }
        });

        $this->info("Selesai. Dikonversi: {$converted}, Gagal: {$failed}.");

        return self::SUCCESS;
    }
}
