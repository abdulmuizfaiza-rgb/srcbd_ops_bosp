<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menu "Backup & Restore" - pengaturan unggah otomatis file backup ke
 * Google Drive (permintaan user 2026-10-14, lanjutan dari poin 2/3).
 *
 * Tabel SINGLETON (1 baris saja, id=1 - pola sama dengan sync_settings/
 * pengaturan_tampilan) - lihat App\Models\GoogleDriveSetting. SENGAJA
 * TERPISAH per instalasi (Lokal & Online masing-masing punya baris
 * sendiri di database masing-masing, TIDAK disinkronkan lewat fitur
 * Sinkronisasi poin 3) karena masing-masing instalasi hanya mengunggah
 * file backup MILIKNYA SENDIRI (file fisik backup tidak pernah berpindah
 * antar server) - keputusan AskUserQuestion 2026-10-14.
 *
 * 'folder_url' = link folder Google Drive APA ADANYA seperti ditempel
 * Superadmin (ditampilkan kembali di form). 'folder_id' = ID folder hasil
 * ekstraksi otomatis dari folder_url (lihat App\Services\
 * GoogleDriveService::ekstrakFolderId()) - inilah yang BENAR-BENAR dipakai
 * saat memanggil Google Drive API.
 *
 * TIDAK ADA kolom kredensial/token di sini - kredensial Service Account
 * (file JSON, berisi private key) SENGAJA tidak pernah masuk ke database
 * sama sekali, hanya berupa file di server yang path-nya dibaca lewat
 * config('services.google_drive.credentials_path') - lihat panduan
 * update & App\Services\GoogleDriveService.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('google_drive_settings', function (Blueprint $table) {
            $table->id();
            $table->text('folder_url')->nullable();
            $table->string('folder_id')->nullable();
            $table->timestamp('terakhir_kirim_pada')->nullable();
            $table->boolean('terakhir_kirim_berhasil')->nullable();
            $table->text('pesan_error_terakhir')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_drive_settings');
    }
};
