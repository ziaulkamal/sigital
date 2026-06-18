<?php

/**
 * database/migrations/..._add_template_and_branding_fields.php
 * Fase P6 — Template per-user (K7) + Branding logo kop per-organisasi (K8).
 *  - templates.uploaded_by (jejak pengunggah) + background_mime.
 *  - organizations.logo_path + kop_path (disisipkan di kepala sertifikat saat terbit).
 * Tanpa FK DB (selaras P2/P3) — relasi via Eloquent.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->unsignedBigInteger('uploaded_by')->nullable()->after('organization_id');
            $table->string('background_mime')->nullable()->after('background_path');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('type');
            $table->string('kop_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn(['uploaded_by', 'background_mime']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'kop_path']);
        });
    }
};
