<?php

/**
 * database/migrations/..._create_event_members_and_join_code.php
 * Fase P7 — Kolaborasi acara (K10). Pembuat acara = owner; user lain minta gabung via
 * kode undangan (Q8) → pending → owner/SuperAdmin approve → collaborator.
 *  - events.created_by (pemilik) + events.join_code (kode undangan unik).
 *  - event_members (owner|collaborator, status pending|approved|rejected), UNIQUE(event_id,user_id).
 * Kolaborasi hanya dalam organisasi yang sama (Q7) — tak perlu melonggarkan global scope acara.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('organization_id');
            $table->string('join_code', 20)->nullable()->unique()->after('kode');
        });

        Schema::create('event_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('role', 20)->default('collaborator'); // owner|collaborator
            $table->string('status', 20)->default('pending');    // pending|approved|rejected
            $table->timestamp('requested_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_members');

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'join_code']);
        });
    }
};
