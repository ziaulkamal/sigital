<?php

/**
 * database/migrations/..._add_identity_and_phone_otp_to_users.php
 * Syarat mutlak registrasi: NIK + Nomor WhatsApp, dengan verifikasi OTP WhatsApp
 * (anti akun palsu). OTP disimpan ter-hash + kedaluwarsa. Tanpa FK (selaras migrasi lain).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik', 16)->nullable()->unique()->after('email');
            $table->string('phone', 20)->nullable()->after('nik'); // nomor WhatsApp (format 62…)
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
            $table->string('phone_otp')->nullable()->after('phone_verified_at');       // hash OTP
            $table->timestamp('phone_otp_expires_at')->nullable()->after('phone_otp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nik', 'phone', 'phone_verified_at', 'phone_otp', 'phone_otp_expires_at']);
        });
    }
};
