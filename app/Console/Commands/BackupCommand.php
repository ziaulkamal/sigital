<?php

/**
 * app/Console/Commands/BackupCommand.php
 * Backup ringan: dump database + arsip berkas storage ke satu folder ber-timestamp (NFR-09).
 * Untuk produksi (PostgreSQL di kontainer) gunakan pg_dump; lihat skrip entrypoint Docker.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupCommand extends Command
{
    protected $signature = 'sigital:backup {--path=storage/app/backups}';
    protected $description = 'Backup database & berkas arsip sertifikat.';

    public function handle(): int
    {
        $dir = base_path($this->option('path').'/'.now()->format('Ymd-His'));
        File::ensureDirectoryExists($dir);

        $connection = config('database.default');

        if ($connection === 'sqlite') {
            File::copy(config('database.connections.sqlite.database'), $dir.'/database.sqlite');
            $this->info('Database SQLite disalin.');
        } elseif ($connection === 'pgsql') {
            $db = config('database.connections.pgsql');
            $process = Process::fromShellCommandline(
                'pg_dump -h '.$db['host'].' -p '.$db['port'].' -U '.$db['username'].' '.$db['database'].' > '.escapeshellarg($dir.'/database.sql')
            );
            $process->setEnv(['PGPASSWORD' => $db['password']]);
            $process->setTimeout(300);
            $process->run();
            $this->info($process->isSuccessful() ? 'Dump PostgreSQL selesai.' : 'pg_dump gagal: '.$process->getErrorOutput());
        }

        // Salin berkas arsip sertifikat.
        $certDir = storage_path('app/'.config('sigital.pdf_dir'));
        if (File::isDirectory($certDir)) {
            File::copyDirectory($certDir, $dir.'/certificates');
            $this->info('Berkas arsip sertifikat disalin.');
        }

        $this->info("Backup selesai di: {$dir}");

        return self::SUCCESS;
    }
}
