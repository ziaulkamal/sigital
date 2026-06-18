<?php

/**
 * database/migrations/..._create_templates_table.php
 * Template sertifikat: layout + posisi field (perancang visual = v2).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            // P1: template milik organisasi; null + is_global = template bersama lintas-org.
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->boolean('is_global')->default(false);
            $table->string('nama');
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();
            // Path gambar latar (opsional) + view blade yang dipakai untuk render PDF.
            $table->string('background_path')->nullable();
            $table->string('view', 100)->default('certificates.default');
            // Koordinat/penempatan field untuk perancang visual (v2). JSON bebas-skema.
            $table->json('posisi_field')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
