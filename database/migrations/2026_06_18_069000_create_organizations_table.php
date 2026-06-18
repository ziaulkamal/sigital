<?php

/**
 * database/migrations/..._create_organizations_table.php
 * Organization = tenant (P1). Tiap data operasional dimiliki satu organisasi;
 * SuperAdmin = user global tanpa organization_id. `kode` jadi prefix nomor sertifikat.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode', 50)->unique();   // prefix nomor sertifikat (mis. DISKOMINFO)
            $table->string('type', 20)->default('dinas'); // dinas | komunitas
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('type');
        });

        // users.organization_id (nullable → SuperAdmin tanpa organisasi).
        // Tabel users dibuat sebelum organizations & SQLite tak bisa menambah FK via ALTER,
        // jadi kolom ini tanpa constraint DB — relasi tetap ditangani Eloquent.
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('organization_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('organization_id');
        });

        Schema::dropIfExists('organizations');
    }
};
