<?php

/**
 * database/migrations/..._add_two_factor_to_users.php
 * Fase P5 — 2FA (K6, opsional per-user). Secret & recovery codes disimpan terenkripsi
 * (cast 'encrypted' di User). two_factor_confirmed_at NULL = belum dikonfirmasi (QR diaktifkan
 * tapi kode TOTP belum diverifikasi) → belum jadi tantangan saat login.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('approved_at');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
    }
};
