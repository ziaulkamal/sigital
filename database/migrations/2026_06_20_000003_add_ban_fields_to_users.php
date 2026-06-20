<?php

/**
 * database/migrations/..._add_ban_fields_to_users.php
 * Pemblokiran akun oleh SuperAdmin. Status memakai 'suspended' yang sudah ada;
 * kolom ini menyimpan ALASAN + jejak siapa/kapan agar bisa ditampilkan ke user
 * saat login dan dibatalkan (unban) kembali.
 *
 * banned_by tanpa FK DB (selaras approved_by; SQLite tak bisa ALTER-FK) — relasi via Eloquent.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('banned_reason', 500)->nullable()->after('approved_at');
            $table->timestamp('banned_at')->nullable()->after('banned_reason');
            $table->unsignedBigInteger('banned_by')->nullable()->after('banned_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['banned_reason', 'banned_at', 'banned_by']);
        });
    }
};
