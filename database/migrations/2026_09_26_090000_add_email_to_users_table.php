<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom "email" ke tabel users, terpisah dari "username".
 *
 * Latar belakang (2026-09-26): fitur gerbang verifikasi email + token 6
 * digit sebelum halaman login (lihat resources/views/livewire/pages/auth/
 * verifikasi-akses.blade.php) butuh alamat email nyata untuk SEMUA akun
 * termasuk Superadmin, supaya token OTP bisa dikirim. Sebelumnya:
 * - Admin OPS/Admin BOSP: "username" mereka SUDAH berupa email (divalidasi
 *   'email' sejak registrasi), tapi tidak ada kolom "email" terpisah.
 * - Superadmin: "username" adalah string literal 'superadmin' (BUKAN
 *   email) - lihat database/seeders/UserSeeder.php - jadi sama sekali
 *   tidak ada alamat email tersimpan untuk akun ini di manapun.
 *
 * "username" TETAP dipakai untuk LOGIN seperti biasa (SAMA SEKALI TIDAK
 * DIUBAH) - kolom "email" ini HANYA dipakai untuk pengiriman token
 * verifikasi akses & (nantinya) notifikasi lain, supaya tidak mengganggu
 * alur login username/password yang sudah berjalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('username');
        });

        // Backfill data lama - Admin OPS/Admin BOSP: username mereka sudah
        // berupa email sejak awal (divalidasi 'email' di form registrasi).
        DB::table('users')
            ->where('level_akses', '!=', 'superadmin')
            ->whereNull('email')
            ->update(['email' => DB::raw('username')]);

        // Superadmin: alamat email asli diberikan langsung oleh pemilik
        // aplikasi (Abdul) saat fitur ini dirancang, 2026-09-26. SENGAJA
        // hanya menyasar username = 'superadmin' (akun Superadmin utama/
        // asli dari UserSeeder.php), BUKAN "semua baris level_akses =
        // superadmin" - ditemukan 2026-09-26 bahwa database lokal Abdul
        // sempat punya lebih dari satu akun Superadmin (mis. akun sisa
        // testing 'superadmin1'), dan mengisi email yang sama ke lebih
        // dari satu akun sekaligus akan melanggar constraint unique di
        // kolom email ini.
        DB::table('users')
            ->where('username', 'superadmin')
            ->where('level_akses', 'superadmin')
            ->whereNull('email')
            ->update(['email' => 'abdulmuizfaiza@gmail.com']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
