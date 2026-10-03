<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom user_agent & lokasi (latitude/longitude) ke
 * akses_data_logs - permintaan user 2026-10-03 (menu "Cek Database dan
 * Aplikasi" > tab "Log Akses Data"): tambahan kontrol keamanan supaya
 * Superadmin tahu juga info perangkat/browser & kira-kira lokasi akun
 * saat membuka halaman/mengunduh data, bukan hanya IP Address seperti
 * sebelumnya.
 *
 * user_agent diisi otomatis dari header browser setiap request (tidak
 * perlu izin apapun dari Admin OPS/BOSP, selalu tersedia - lihat
 * App\Models\AksesDataLog::catat()).
 *
 * latitude/longitude SENGAJA TIDAK diminta ulang lewat browser di
 * setiap aksi buka halaman/unduh (aksi ini bisa terjadi puluhan kali
 * per sesi - meminta izin lokasi browser berulang-ulang akan sangat
 * mengganggu, bertentangan dengan tujuan user sendiri "meminimalisir
 * gangguan pada aplikasi"). Keduanya diisi dari baris login_histories
 * yang SEDANG AKTIF milik akun tsb (baris dengan logout_at masih NULL)
 * - yaitu titik lokasi yang sudah diambil 1x saat akun ini login, lalu
 * dipakai ulang untuk semua baris Log Akses Data selama sesi itu masih
 * berlangsung. Kalau tidak ada baris login_histories aktif, atau akun
 * itu menolak izin lokasi saat login, kedua kolom ini tetap NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('akses_data_logs', function (Blueprint $table) {
            $table->string('user_agent')->nullable()->after('ip_address');
            $table->decimal('latitude', 10, 7)->nullable()->after('user_agent');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('akses_data_logs', function (Blueprint $table) {
            $table->dropColumn(['user_agent', 'latitude', 'longitude']);
        });
    }
};
