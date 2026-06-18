<?php

/**
 * app/Console/Commands/VerifyIntegrityCommand.php
 * Verifikasi integritas: bandingkan hash tersimpan vs hash berkas aktual (FR-13/NFR-08).
 */

namespace App\Console\Commands;

use App\Models\Certificate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class VerifyIntegrityCommand extends Command
{
    protected $signature = 'certificates:verify-integrity';
    protected $description = 'Cek integritas PDF sertifikat dengan membandingkan hash SHA-256.';

    public function handle(): int
    {
        $disk = Storage::disk(config('sigital.pdf_disk'));
        $ok = $missing = $tampered = 0;

        Certificate::whereNotNull('pdf_path')->chunk(200, function ($certs) use ($disk, &$ok, &$missing, &$tampered) {
            foreach ($certs as $cert) {
                if (! $disk->exists($cert->pdf_path)) {
                    $missing++;
                    $this->warn("HILANG: {$cert->nomor_unik}");

                    continue;
                }
                $hash = hash('sha256', $disk->get($cert->pdf_path));
                if ($hash !== $cert->pdf_hash) {
                    $tampered++;
                    $this->error("BERUBAH: {$cert->nomor_unik}");
                } else {
                    $ok++;
                }
            }
        });

        $this->info("Selesai. Utuh: {$ok}, Hilang: {$missing}, Berubah: {$tampered}.");

        return $tampered > 0 ? self::FAILURE : self::SUCCESS;
    }
}
