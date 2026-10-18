<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom `asal` (nullable) pada tabel `backups` - fitur "Restore
 * dari File di Laptop/PC" (permintaan user 2026-10-18: "saya ingin
 * tombol restore ada pilihan restore dari backup yang di simpan di
 * Laptop/PC komputer bukan hanya yang tersimpan di riwayat backup").
 * Jawaban AskUserQuestion 2026-10-18 "Simpan ke Riwayat Backup
 * (Recommended)": file yang diunggah Superadmin dari Laptop/PC-nya
 * SEKARANG ikut disimpan permanen sebagai 1 baris `backups` biasa
 * (lihat App\Services\BackupService::simpanUnggahan()) - supaya BISA
 * dibedakan dari backup manual biasa (tombol "Buat Backup Sekarang")
 * MAUPUN dari backup otomatis sebelum Restore (ditandai kolom
 * `catatan`, lihat migration add_catatan_to_backups_table &
 * App\Models\Backup::otomatisSebelumRestore()), kolom BARU ini dipakai
 * KHUSUS untuk menandai asal-usul baris backup:
 * - null       = backup biasa (tombol "Buat Backup Sekarang") - TIDAK
 *                berubah dari perilaku sebelum migration ini ada.
 * - 'diunggah' = file .zip yang diunggah Superadmin dari Laptop/PC-nya
 *                sendiri (BUKAN dibuat oleh aplikasi ini).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->string('asal')->nullable()->after('catatan');
        });
    }

    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropColumn('asal');
        });
    }
};
