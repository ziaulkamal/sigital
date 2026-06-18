<?php

/**
 * database/migrations/..._create_event_signatory_table.php
 * Relasi M:N acara ↔ penanda tangan (inti jejak "siapa menandatangani apa").
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_signatory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('signatory_id')->constrained('signatories')->cascadeOnDelete();
            $table->unsignedSmallInteger('urutan')->default(1);
            $table->timestamps();

            $table->unique(['event_id', 'signatory_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_signatory');
    }
};
