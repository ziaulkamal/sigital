<?php

/**
 * database/migrations/..._create_signatories_table.php
 * Penanda tangan: nama, jabatan, spesimen TTD. (bsre_cert_id disiapkan untuk v2.)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatories', function (Blueprint $table) {
            $table->id();
            // P1: signatory dimiliki satu organisasi (nullable demi resiliensi migrasi).
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('nama');
            $table->string('jabatan');
            $table->string('gambar_ttd')->nullable();
            $table->string('bsre_cert_id')->nullable(); // future: TTE BSrE
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signatories');
    }
};
