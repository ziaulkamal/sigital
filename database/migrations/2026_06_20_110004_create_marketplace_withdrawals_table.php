<?php

/**
 * database/migrations/..._create_marketplace_withdrawals_table.php
 * Pencairan royalti Creator (Bagian 6.8): credit → rupiah, dikenakan biaya admin.
 * Alur status: pending → scheduled → approved → paid (atau rejected).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->integer('credit_requested');   // credit yang diajukan (sudah dipotong saat request)
            $table->integer('admin_fee_credit');   // biaya admin
            $table->integer('credit_paid');        // credit bersih dicairkan (requested - fee)
            $table->integer('rupiah_paid');        // credit_paid * rupiah_per_credit
            $table->string('status', 20)->default('pending'); // pending|scheduled|approved|rejected|paid
            $table->timestamp('scheduled_payout_date')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_withdrawals');
    }
};
