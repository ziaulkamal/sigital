<?php

/**
 * database/migrations/..._create_audit_logs_table.php
 * AuditLog append-only: aktor, aksi, entitas, waktu, detail. Tak boleh diubah/dihapus diam-diam.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            // P1: jejak audit dapat ditampilkan per-organisasi (null = aksi sistem/lintas-org).
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi');                       // mis. certificate.issued
            $table->string('entitas')->nullable();        // mis. Certificate
            $table->unsignedBigInteger('entitas_id')->nullable();
            $table->json('detail')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();  // append-only: tanpa updated_at

            $table->index(['entitas', 'entitas_id']);
            $table->index('aksi');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
