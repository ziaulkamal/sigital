<?php

/**
 * database/migrations/..._add_creator_application_and_bank_to_users.php
 * Pendaftaran Marketplace Creator (verifikasi SuperAdmin) + rekening pencairan
 * (wajib diverifikasi). marketplace_enabled tetap sumber kebenaran "creator aktif"
 * (di-set saat aplikasi di-approve); kolom di sini menyimpan detail aplikasi & bank.
 *
 * FK ke users (reviewed_by) tanpa constrained DB — relasi via Eloquent (pola eksisting).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Aplikasi creator (verifikasi SuperAdmin).
            $table->string('creator_status', 20)->nullable()->after('marketplace_joined_at'); // null|pending|approved|rejected
            $table->string('creator_full_name')->nullable()->after('creator_status');
            $table->text('creator_address')->nullable()->after('creator_full_name');
            $table->string('creator_ktp_path')->nullable()->after('creator_address'); // disk privat
            $table->timestamp('creator_terms_accepted_at')->nullable()->after('creator_ktp_path');
            $table->timestamp('creator_applied_at')->nullable()->after('creator_terms_accepted_at');
            $table->timestamp('creator_reviewed_at')->nullable()->after('creator_applied_at');
            $table->unsignedBigInteger('creator_reviewed_by')->nullable()->after('creator_reviewed_at');
            $table->string('creator_reject_reason')->nullable()->after('creator_reviewed_by');

            // Rekening pencairan (wajib diverifikasi SuperAdmin).
            $table->string('bank_name')->nullable()->after('creator_reject_reason');
            $table->string('bank_account_no')->nullable()->after('bank_name');
            $table->string('bank_account_holder')->nullable()->after('bank_account_no');
            $table->string('bank_status', 20)->nullable()->after('bank_account_holder'); // null|pending|verified|rejected
            $table->timestamp('bank_reviewed_at')->nullable()->after('bank_status');
            $table->unsignedBigInteger('bank_reviewed_by')->nullable()->after('bank_reviewed_at');
            $table->string('bank_reject_reason')->nullable()->after('bank_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'creator_status', 'creator_full_name', 'creator_address', 'creator_ktp_path',
                'creator_terms_accepted_at', 'creator_applied_at', 'creator_reviewed_at',
                'creator_reviewed_by', 'creator_reject_reason',
                'bank_name', 'bank_account_no', 'bank_account_holder', 'bank_status',
                'bank_reviewed_at', 'bank_reviewed_by', 'bank_reject_reason',
            ]);
        });
    }
};
