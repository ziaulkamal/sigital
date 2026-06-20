<?php

/**
 * database/migrations/..._create_template_usage_transactions_table.php
 * Histori penggunaan template marketplace (Bagian 6.3) — dasar royalti, pencairan,
 * & laporan pendapatan platform. Tak dihapus meski template di-unpublish.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_usage_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('template_id')->index();
            $table->unsignedBigInteger('owner_user_id')->index();
            $table->unsignedBigInteger('buyer_user_id')->index();
            $table->integer('price_credit');    // total dibayar pembeli
            $table->integer('owner_credit');    // royalti pemilik
            $table->integer('platform_credit'); // pendapatan platform
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_usage_transactions');
    }
};
