<?php

/**
 * database/migrations/..._create_registrations_table.php
 * Registration: peserta-acara. Menyiapkan `sumber` & `status_kehadiran` untuk v2 (registrasi mandiri).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            // Gerbang fitur v2 — UI tayang nanti, kolom sudah ada agar tak migrasi besar.
            $table->string('sumber', 20)->default('impor');          // impor | manual | daftar_sendiri
            $table->string('status_kehadiran', 20)->default('hadir'); // hadir | tidak_hadir | menunggu
            $table->timestamps();

            // Satu orang hanya sekali per acara (basis "satu sertifikat per Person-Event").
            $table->unique(['person_id', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
