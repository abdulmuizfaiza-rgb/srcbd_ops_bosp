<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel log AKSES DATA - permintaan user (2026-09-27, menu baru "Cek
 * Database dan Aplikasi") sebagai kontrol keamanan: mencatat setiap kali
 * ada akun yang MEMBUKA HALAMAN data (jenis_aksi='buka_halaman') maupun
 * MENGUNDUH data (jenis_aksi='unduh'), supaya Superadmin tahu "data apa
 * saja yang diambil" oleh siapa.
 *
 * user_id SENGAJA nullOnDelete (BUKAN cascadeOnDelete seperti
 * login_histories) - baris log ini adalah BUKTI AUDIT KEAMANAN yang harus
 * tetap ada walau akun pelakunya kemudian dihapus. Supaya baris lama
 * tetap bisa dibaca meski akunnya sudah tidak ada, kolom
 * username/email/level_akses SENGAJA disimpan sebagai SALINAN (snapshot)
 * di baris ini sendiri, mengikuti pola yang sama seperti login_histories.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akses_data_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('username_snapshot')->nullable();
            $table->string('email_snapshot')->nullable();
            $table->string('level_akses_snapshot')->nullable();
            $table->string('jenis_aksi', 20);
            $table->string('nama_menu');
            $table->string('detail')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['jenis_aksi', 'created_at']);
            $table->index(['user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akses_data_logs');
    }
};
