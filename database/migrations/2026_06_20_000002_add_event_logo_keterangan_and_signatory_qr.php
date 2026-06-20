<?php

/**
 * database/migrations/..._add_event_logo_keterangan_and_signatory_qr.php
 * Enhancement template sertifikat:
 *  - events.logo_path: logo per-acara (fallback ke logo organisasi bila kosong).
 *  - events.keterangan: keterangan kegiatan (kosong → digenerate otomatis saat render).
 *  - signatories.qr_srikandi_path: QR tanda tangan digital SRIKANDI (pengganti/penyerta TTD basah).
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('lokasi');
            $table->text('keterangan')->nullable()->after('logo_path');
        });

        Schema::table('signatories', function (Blueprint $table) {
            $table->string('qr_srikandi_path')->nullable()->after('gambar_ttd');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'keterangan']);
        });

        Schema::table('signatories', function (Blueprint $table) {
            $table->dropColumn('qr_srikandi_path');
        });
    }
};
