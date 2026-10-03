<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom session_id ke login_histories - permintaan user 2026-10-03:
 * kunci 1 perangkat per akun (khusus Admin OPS/Admin BOSP, Superadmin
 * dikecualikan). Kolom ini menyimpan ID session Laravel (tabel bawaan
 * `sessions`) milik baris riwayat login tsb, diisi saat login berhasil
 * (lihat LoginForm::catatRiwayatLogin()).
 *
 * KENAPA DIPERLUKAN: baris login_histories dengan logout_at masih NULL
 * SAJA tidak cukup untuk menyimpulkan "perangkat itu masih aktif" -
 * kebanyakan orang menutup tab/browser tanpa klik Logout, yang akan
 * membuat logout_at tetap NULL SELAMANYA kalau hanya mengandalkan itu
 * (berakibat akun terkunci permanen tidak bisa login dari manapun).
 * Dengan session_id, sistem bisa mencocokkan ke tabel `sessions` bawaan
 * Laravel (kolom last_activity-nya ikut terupdate otomatis oleh Laravel
 * di SETIAP request yang diautentikasi) untuk menyimpulkan apakah sesi
 * itu BENAR-BENAR masih aktif (last_activity dalam batas waktu
 * config('session.lifetime')) atau sudah basi - lihat
 * LoginForm::cariSesiLainAktif().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_histories', function (Blueprint $table) {
            $table->string('session_id')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('login_histories', function (Blueprint $table) {
            $table->dropColumn('session_id');
        });
    }
};
