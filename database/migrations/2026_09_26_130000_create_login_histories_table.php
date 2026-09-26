<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel riwayat login - permintaan user (2026-09-26): menu Pengguna diberi
 * tab baru "Riwayat Login" (3 sub-tab: Superadmin/Admin OPS/Admin BOSP)
 * yang menampilkan histori setiap kali akun login, lengkap dengan Nama
 * Sekolah, Alamat Email, hari & tanggal, waktu mulai/akhir, lama login, IP
 * Address, dan titik koordinat saat login.
 *
 * Kolom nama_sekolah & email SENGAJA disimpan sebagai SALINAN (snapshot)
 * di baris ini sendiri, bukan hanya relasi ke tabel users - supaya riwayat
 * lama tetap menampilkan nama sekolah/email yang benar persis saat login
 * itu terjadi, walau data akun users berubah/dihapus di kemudian hari.
 *
 * Koordinat (latitude/longitude) diisi dari Browser Geolocation API (izin
 * lokasi diminta ke user tepat saat memilih jenis akses di halaman login -
 * lihat login.blade.php). Kalau user menolak izin lokasi, kedua kolom ini
 * tetap NULL untuk baris tersebut (bukan error, hanya kosong).
 *
 * logout_at HANYA terisi kalau user benar-benar klik tombol Logout (lihat
 * app/Livewire/Actions/Logout.php) - kalau menutup tab/browser begitu saja
 * tanpa logout, logout_at tetap NULL selamanya (ditampilkan "Masih
 * berlangsung" di menu, bukan dihitung otomatis dari session timeout).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('level_akses');
            $table->string('email')->nullable();
            $table->string('nama_sekolah')->nullable();
            $table->timestamp('login_at');
            $table->timestamp('logout_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->index(['level_akses', 'login_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};
