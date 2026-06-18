<?php

/**
 * database/migrations/..._create_events_table.php
 * Acara: jadwal, lokasi, template, status alur penerbitan.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            // P1: acara dimiliki satu organisasi (scoping + prefix nomor sertifikat).
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('nama');
            $table->string('kode', 50)->nullable(); // komponen nomor sertifikat
            $table->dateTime('jadwal_mulai');
            $table->dateTime('jadwal_selesai')->nullable();
            $table->string('lokasi')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('templates')->nullOnDelete();
            // Alur: draft → siap_terbit → selesai (FR-05).
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
