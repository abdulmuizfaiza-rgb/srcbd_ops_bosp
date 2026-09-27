<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel riwayat PERCOBAAN LOGIN GAGAL - permintaan user (2026-09-27, menu
 * baru "Cek Database dan Aplikasi") sebagai kontrol keamanan: supaya
 * Superadmin bisa melihat kalau ada pihak yang mencoba masuk ke aplikasi
 * tanpa akun/kata sandi yang benar (username yang dicoba, dari IP mana,
 * kapan).
 *
 * SENGAJA TIDAK ada relasi/foreign key ke tabel users - baris di sini
 * justru paling penting DIISI ketika akun yang dicoba TIDAK ADA/salah,
 * jadi tidak selalu bisa dikaitkan ke user_id manapun. username_dicoba
 * hanya teks bebas (snapshot apa yang diketik di form login).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_login_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('username_dicoba')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['username_dicoba']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_login_attempts');
    }
};
