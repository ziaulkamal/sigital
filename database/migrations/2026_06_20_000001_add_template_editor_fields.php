<?php

/**
 * database/migrations/..._add_template_editor_fields.php
 * Perancang template visual (WYSIWYG): kolom pendukung editor & render Node.
 *  - templates.canvas_width / canvas_height: dimensi natural gambar latar (px),
 *    basis konversi fraksi→piksel saat render agar 1:1 dengan editor.
 *  - templates.fonts: subset font terkurasi yang dipakai template (json).
 *  - templates.thumbnail_path: pratinjau tersimpan dari editor (opsional).
 * Tanpa FK DB (selaras P2/P3).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->unsignedInteger('canvas_width')->nullable()->after('background_mime');
            $table->unsignedInteger('canvas_height')->nullable()->after('canvas_width');
            $table->json('fonts')->nullable()->after('posisi_field');
            $table->string('thumbnail_path')->nullable()->after('fonts');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn(['canvas_width', 'canvas_height', 'fonts', 'thumbnail_path']);
        });
    }
};
