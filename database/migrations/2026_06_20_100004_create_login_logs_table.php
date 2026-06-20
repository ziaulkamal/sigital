<?php

/**
 * database/migrations/..._create_login_logs_table.php
 * Jejak login per-user: IP + user-agent setiap login sukses (termasuk via 2FA).
 * Kolom country/country_code disiapkan untuk diisi belakangan (GeoIP enrichment).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('country')->nullable();       // slot GeoIP
            $table->string('country_code', 2)->nullable();
            $table->timestamp('logged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
