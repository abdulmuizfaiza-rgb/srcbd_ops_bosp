<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat/audit trail setiap kali fitur Restore dijalankan - menu
 * "Backup & Restore" tab "Restore Data & Aplikasi" (permintaan user
 * 2026-10-14 poin 2). Dicatat lewat App\Services\RestoreService setiap
 * kali Superadmin menjalankan Restore, BERHASIL maupun GAGAL (jejak
 * audit untuk tindakan yang berpotensi sangat merusak/tidak bisa
 * dibatalkan).
 *
 * `backup_id` = file backup yang DIPILIH untuk di-restore (nullOnDelete
 * - riwayat tetap ada walau file backupnya sendiri belakangan dihapus).
 * `backup_pengaman_id` = backup OTOMATIS dari kondisi SAAT ITU yang
 * dibuat SEBELUM restore dijalankan (jawaban AskUserQuestion "Backup
 * otomatis dulu + ketik konfirmasi") - jaring pengaman kalau restore
 * ternyata salah pilih/ingin dibatalkan; nullable karena kalau backup
 * pengaman ITU SENDIRI gagal dibuat, proses Restore dibatalkan total
 * SEBELUM sempat menyentuh database sama sekali (lihat
 * App\Services\RestoreService::restore()) - tidak ada baris riwayat yg
 * dibuat utk kegagalan di tahap ini (belum ada apa pun yg "terjadi").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restore_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backup_id')->nullable()->constrained('backups')->nullOnDelete();
            $table->foreignId('backup_pengaman_id')->nullable()->constrained('backups')->nullOnDelete();
            $table->foreignId('dilakukan_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status');
            $table->text('pesan_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restore_logs');
    }
};
