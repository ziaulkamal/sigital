<?php

/**
 * database/migrations/..._create_topup_requests_table.php
 * Permintaan topup credit oleh user — alur MANUAL + konfirmasi SuperAdmin.
 * Struktur disiapkan agar gateway pembayaran mudah ditambah nanti
 * (payment_provider/external_ref).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('topup_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->integer('amount_credit');
            $table->integer('amount_rupiah'); // amount_credit * rupiah_per_credit
            $table->string('status', 20)->default('pending'); // pending|approved|rejected
            $table->string('proof_path')->nullable();         // bukti transfer (disk privat)
            $table->string('note')->nullable();
            // Slot gateway masa depan.
            $table->string('payment_provider')->nullable();
            $table->string('external_ref')->nullable();
            // Verifikasi SuperAdmin.
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('reject_reason')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topup_requests');
    }
};
