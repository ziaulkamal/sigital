<?php

/**
 * database/migrations/..._create_people_table.php
 * Person: identitas durabel lintas acara → basis arsip & portal peserta masa depan.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('email')->nullable();
            // NIK opsional (minimisasi data UU PDP) — tak pernah tampil di verifikasi publik.
            $table->string('nik')->nullable();
            $table->timestamps();

            $table->index('nama');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
