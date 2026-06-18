<?php

/**
 * database/migrations/..._add_created_by_to_signatories.php
 * Fase P3 — Signatory per-user (K3): jejak pembuat penanda tangan.
 * Tanpa FK DB (selaras P2: SQLite tak bisa ALTER-FK) — relasi via Eloquent (creator()).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signatories', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('organization_id');
        });
    }

    public function down(): void
    {
        Schema::table('signatories', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });
    }
};
