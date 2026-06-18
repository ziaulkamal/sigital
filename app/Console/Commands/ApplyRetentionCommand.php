<?php

/**
 * app/Console/Commands/ApplyRetentionCommand.php
 * Anonimisasi data pribadi peserta melewati masa retensi (NFR-07, UU PDP).
 * Sertifikat & nomor tetap utuh untuk integritas arsip — hanya PII di Person yang dianonimkan.
 */

namespace App\Console\Commands;

use App\Models\Person;
use App\Services\AuditLogger;
use Illuminate\Console\Command;

class ApplyRetentionCommand extends Command
{
    protected $signature = 'sigital:retention {--dry-run : Tampilkan tanpa mengubah}';
    protected $description = 'Anonimisasi PII peserta yang melewati masa retensi.';

    public function handle(AuditLogger $audit): int
    {
        $hari = config('sigital.retensi_hari');
        if (! $hari) {
            $this->info('Retensi tidak diset (SIGITAL_RETENSI_HARI kosong) — tidak ada tindakan.');

            return self::SUCCESS;
        }

        $batas = now()->subDays((int) $hari);
        $dry = $this->option('dry-run');

        // Person yang sertifikat terakhirnya lebih lama dari batas retensi.
        $query = Person::whereDoesntHave('certificates', fn ($q) => $q->where('issued_at', '>=', $batas))
            ->where('nik', '!=', null);

        $count = 0;
        $query->chunkById(200, function ($people) use (&$count, $dry, $audit) {
            foreach ($people as $person) {
                $count++;
                if ($dry) {
                    continue;
                }
                $person->forceFill(['nik' => null, 'email' => null])->save();
                $audit->log('person.anonymized', $person, ['alasan' => 'retensi']);
            }
        });

        $this->info(($dry ? '[dry-run] ' : '')."{$count} peserta dianonimisasi (batas: {$batas->toDateString()}).");

        return self::SUCCESS;
    }
}
