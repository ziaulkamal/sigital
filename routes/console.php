<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Penjadwalan (pengerasan M5)
|--------------------------------------------------------------------------
*/
Schedule::command('certificates:verify-integrity')->daily();   // deteksi perubahan berkas
Schedule::command('sigital:retention')->daily();                // anonimisasi PII lewat retensi
Schedule::command('sigital:backup')->dailyAt('01:00');          // backup harian
