<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom `catatan` (nullable) pada tabel `backups` - round
 * fitur Restore (permintaan user 2026-10-14 poin 2: "menu backup di
 * rubah menjadi Backup & Restore"). Dipakai KHUSUS untuk menandai
 * backup OTOMATIS yang dibuat sebagai jaring pengaman tepat SEBELUM
 * proses Restore dijalankan (lihat App\Services\RestoreService) -
 * jawaban AskUserQuestion "Pengamanan Restore" -> "Backup otomatis
 * dulu + ketik konfirmasi". Backup manual biasa (tombol "Buat Backup
 * Sekarang") tetap `catatan = null`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->text('catatan')->nullable()->after('dibuat_oleh_id');
        });
    }

    public function down(): void
    {
        Schema::table('backups', function (Blueprint $table) {
            $table->dropColumn('catatan');
        });
    }
};
