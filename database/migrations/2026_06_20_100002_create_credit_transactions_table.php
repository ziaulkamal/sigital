<?php

/**
 * database/migrations/..._create_credit_transactions_table.php
 * Ledger credit per-user (APPEND-ONLY). Setiap mutasi saldo lewat CreditService
 * menulis satu baris di sini agar audit saldo lengkap & dapat direkonsiliasi.
 *
 * FK ke users/created_by tanpa constrained DB (selaras pola eksisting; SQLite
 * tak bisa ALTER-FK) — relasi via Eloquent.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            // topup|consume|grant|refund|adjust + (marketplace) template_purchase|template_royalty|withdraw|withdraw_fee
            $table->string('type', 30);
            $table->integer('amount');        // +/- delta
            $table->integer('balance_after'); // snapshot saldo setelah mutasi
            // Referensi morph opsional (Event/Template/TopupRequest/dll).
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // actor (SuperAdmin/diri sendiri)
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
