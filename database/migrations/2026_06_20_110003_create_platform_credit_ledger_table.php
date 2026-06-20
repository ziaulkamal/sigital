<?php

/**
 * database/migrations/..._create_platform_credit_ledger_table.php
 * Ledger pendapatan platform (Bagian 6.5) — TERPISAH dari saldo user.
 * Mencatat seluruh credit yang menjadi pendapatan sistem (mis. share marketplace).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_credit_ledger', function (Blueprint $table) {
            $table->id();
            $table->string('source_type')->nullable(); // morph (TemplateUsageTransaction/dll)
            $table->unsignedBigInteger('source_id')->nullable();
            $table->integer('credit_amount');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_credit_ledger');
    }
};
