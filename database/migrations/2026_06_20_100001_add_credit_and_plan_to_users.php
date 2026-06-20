<?php

/**
 * database/migrations/..._add_credit_and_plan_to_users.php
 * Monetisasi (PAD): saldo credit per-user, paket Enterprise, & flag Marketplace Creator.
 *
 * Backfill grandfather: user lama diberi saldo awal (signup_grant) agar tetap bisa
 * membuat satu acara, namun tetap perlu topup berikutnya.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Credit (Bagian 2) — saldo dikelola CreditService, bukan mass-assignment.
            $table->integer('credit_balance')->default(0)->after('status');

            // Paket Enterprise (Bagian 3).
            $table->string('plan', 20)->default('free')->after('credit_balance'); // free|enterprise
            $table->timestamp('enterprise_started_at')->nullable()->after('plan');
            $table->timestamp('enterprise_expires_at')->nullable()->after('enterprise_started_at');

            // Marketplace Creator (Bagian 6.1).
            $table->boolean('marketplace_enabled')->default(false)->after('enterprise_expires_at');
            $table->timestamp('marketplace_joined_at')->nullable()->after('marketplace_enabled');
        });

        // Grandfather: beri saldo awal ke seluruh user yang sudah ada.
        $grant = (int) config('sigital.credit.signup_grant', 60);
        DB::table('users')->update(['credit_balance' => $grant]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'credit_balance',
                'plan',
                'enterprise_started_at',
                'enterprise_expires_at',
                'marketplace_enabled',
                'marketplace_joined_at',
            ]);
        });
    }
};
