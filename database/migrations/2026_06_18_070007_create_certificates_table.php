<?php

/**
 * database/migrations/..._create_certificates_table.php
 * Sertifikat: nomor unik terkunci, token QR opaque, path & hash PDF, status keaslian.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            // P1: organization_id didenormalisasi dari event (scoping & index cepat arsip).
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            // Satu sertifikat per registrasi (= per Person-Event).
            $table->foreignId('registration_id')->unique()->constrained('registrations')->cascadeOnDelete();
            $table->string('nomor_unik')->unique();         // FR-10/FR-14: unik & terkunci
            $table->string('qr_token', 64)->unique();        // NFR-02: token opaque, bukan data
            $table->string('pdf_path')->nullable();
            $table->string('pdf_hash', 64)->nullable();      // FR-13: SHA-256
            $table->string('status', 20)->default('issued'); // issued | revoked
            $table->text('alasan_pencabutan')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('batch_id')->nullable();          // korelasi penerbitan massal (FR-12)
            $table->timestamps();

            $table->index('status');
            $table->index('nomor_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
