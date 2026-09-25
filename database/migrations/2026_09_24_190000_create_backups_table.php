<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Round DUA PULUH LIMA (2026-09-24), poin 2: permintaan user "buatkan menu
 * backup berdasarkan tahun untuk semua data yang ada pada aplikasi yang
 * terdiri dari Aplikasi nya dan database nya yang terbaru".
 *
 * Keputusan bisnis via AskUserQuestion (2026-09-24) sebelum menulis
 * migration ini - lihat juga App\Services\BackupService & Gate
 * 'akses-backup' di App\Providers\AppServiceProvider:
 * - "Cakupan Backup" -> "Kode aplikasi (di-zip) + dump database terbaru
 *   (Recommended)": satu baris = satu PAKET backup (file .zip) berisi
 *   seluruh folder source code aplikasi (minus vendor/node_modules/.git/
 *   .env/log - lihat App\Services\BackupService utk daftar lengkap
 *   pengecualian teknis) DIGABUNG dgn hasil dump database terbaru
 *   (database.sql di dalam zip yang sama).
 * - "Penyimpanan & Akses" -> "Tersimpan di server, terdaftar per tahun,
 *   khusus Superadmin (Recommended)": setiap backup yang dibuat dicatat di
 *   sini dgn kolom `tahun` (tahun saat backup dibuat, dipakai utk
 *   filter/pengelompokan di menu), disimpan lewat disk 'local' (BUKAN
 *   'public' - paket backup berisi source code & dump database, TIDAK
 *   boleh bisa diakses publik, hanya lewat route ter-Gate 'akses-backup'
 *   sama seperti menu Panduan Aplikasi). Hanya Superadmin yang bisa
 *   membuka menu & membuat/mengunduh/menghapus backup.
 *
 * `dibuat_oleh_id` nullable & `nullOnDelete` (bukan `cascadeOnDelete')
 * supaya baris riwayat backup TETAP ADA meskipun akun user yang membuatnya
 * suatu saat dihapus dari menu Kelola Pengguna - riwayat backup adalah
 * data historis, tidak boleh ikut hilang gara-gara user dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('tahun');
            $table->string('nama_file');
            $table->string('path');
            $table->unsignedBigInteger('ukuran')->nullable();
            $table->foreignId('dibuat_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
