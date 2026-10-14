<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GANTI ARSITEKTUR Google Drive dari Service Account menjadi OAuth 2.0
 * "Login dengan Google" biasa (ditemukan 2026-10-14 lewat pengujian
 * LANGSUNG ke server Google: Service Account TERNYATA tidak pernah
 * punya kuota penyimpanan di Google Drive biasa, di luar Shared Drive
 * khusus Google Workspace - lihat docblock lengkap App\Services\
 * GoogleDriveService & App\Http\Controllers\GoogleDriveOAuthController).
 * Dikonfirmasi lewat AskUserQuestion: akun Drive yang dipakai adalah
 * Gmail pribadi (bukan Workspace) & user memilih pindah ke OAuth
 * (gratis) dibanding berlangganan Google Workspace.
 *
 * Kolom LAMA (folder_url, folder_id, terakhir_kirim_pada,
 * terakhir_kirim_berhasil, pesan_error_terakhir) TETAP DIPAKAI APA
 * ADANYA - migration ini HANYA menambah kolom baru utk menyimpan hasil
 * login OAuth (refresh_token DISIMPAN TERENKRIPSI lewat cast Eloquent
 * 'encrypted' di App\Models\GoogleDriveSetting, lihat docblock model).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('google_drive_settings', function (Blueprint $table) {
            $table->text('refresh_token')->nullable()->after('folder_id');
            $table->string('email_terhubung')->nullable()->after('refresh_token');
            $table->timestamp('terhubung_pada')->nullable()->after('email_terhubung');
        });
    }

    public function down(): void
    {
        Schema::table('google_drive_settings', function (Blueprint $table) {
            $table->dropColumn(['refresh_token', 'email_terhubung', 'terhubung_pada']);
        });
    }
};
