<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom lokasi (latitude/longitude) ke failed_login_attempts -
 * permintaan user 2026-10-03 (menu "Cek Database dan Aplikasi" > tab
 * "Percobaan Login Gagal"): tambahan kontrol keamanan supaya Superadmin
 * bisa melihat kira-kira titik lokasi percobaan login gagal, sama
 * seperti yang sudah ada di login_histories untuk login yang BERHASIL.
 *
 * Koordinat diisi dari Browser Geolocation API yang SUDAH ADA sejak
 * 2026-09-26 di halaman login (lihat login.blade.php,
 * ambilKoordinatLogin() - dipicu saat memilih jenis akses, SEBELUM
 * username/password dikirim) - berlaku untuk SEMUA percobaan login,
 * baik yang akhirnya berhasil maupun gagal, karena fungsi itu sudah
 * berjalan sebelum sistem tahu hasil login-nya. Kalau user menolak
 * izin lokasi browser (atau browsernya tidak mendukung/lambat), kedua
 * kolom ini tetap NULL - bukan error, hanya kosong (sama seperti
 * login_histories).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('failed_login_attempts', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('user_agent');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('failed_login_attempts', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
