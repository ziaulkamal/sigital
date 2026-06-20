<?php

/**
 * database/migrations/..._add_marketplace_to_templates.php
 * Marketplace template (Bagian 6.2): tandai template dapat dipakai user lain
 * dengan biaya credit + bagi hasil ke pemilik.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->boolean('is_marketplace')->default(false)->after('is_global');
            $table->integer('marketplace_price')->default(15)->after('is_marketplace');
            $table->timestamp('published_at')->nullable()->after('marketplace_price');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn(['is_marketplace', 'marketplace_price', 'published_at']);
        });
    }
};
