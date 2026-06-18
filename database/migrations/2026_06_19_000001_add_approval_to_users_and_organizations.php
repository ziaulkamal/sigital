<?php

/**
 * database/migrations/..._add_approval_to_users_and_organizations.php
 * Fase P2 — Approval & Onboarding (K4/K9).
 *  - users: status onboarding + jejak approval + peran yang diminta saat registrasi.
 *  - organizations: pengajuan organisasi baru (requested_by) + surat rekomendasi (dinas).
 *
 * Catatan: default users.status = 'approved' agar akun seed/undangan langsung aktif;
 * HANYA registrasi mandiri yang menyetel 'pending' secara eksplisit (lihat RegisterController).
 * approved_by/requested_by tanpa FK DB (SQLite tak bisa ALTER-FK) — relasi via Eloquent.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status', 20)->default('approved')->after('password'); // pending|approved|rejected|suspended
            $table->string('requested_role', 50)->nullable()->after('status');     // peran diminta saat registrasi
            $table->unsignedBigInteger('approved_by')->nullable()->after('requested_role');
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            $table->index('status');
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->unsignedBigInteger('requested_by')->nullable()->after('is_active'); // pengaju (registrasi)
            $table->timestamp('approved_at')->nullable()->after('requested_by');
            $table->string('recommendation_letter_path')->nullable()->after('approved_at'); // wajib bila dinas (K9)
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'requested_role', 'approved_by', 'approved_at']);
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['requested_by', 'approved_at', 'recommendation_letter_path']);
        });
    }
};
