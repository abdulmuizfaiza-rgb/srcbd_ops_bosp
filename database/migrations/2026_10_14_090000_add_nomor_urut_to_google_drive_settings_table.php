<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nomor urut file backup di Google Drive (permintaan user 2026-10-14,
 * sore - "hasil file backup nya di google drive di urutkan pakai nomor
 * baik itu di aplikasi lokal maupun di aplikasi online") - lihat
 * docblock App\Services\GoogleDriveService & App\Models\
 * GoogleDriveSetting::ambilNomorUrutBerikutnya(). TERPISAH per instalasi
 * (sama seperti seluruh pengaturan Google Drive lainnya) - counter
 * Lokal & Online BERJALAN SENDIRI-SENDIRI, tidak saling memengaruhi.
 *
 * default(1) di PostgreSQL otomatis mengisi baris singleton yang sudah
 * ada (dibuat migration sebelumnya) dengan nilai 1 juga - tidak perlu
 * langkah backfill terpisah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_drive_settings', function (Blueprint $table) {
            $table->unsignedInteger('nomor_urut_berikutnya')->default(1)->after('terhubung_pada');
        });
    }

    public function down(): void
    {
        Schema::table('google_drive_settings', function (Blueprint $table) {
            $table->dropColumn('nomor_urut_berikutnya');
        });
    }
};
