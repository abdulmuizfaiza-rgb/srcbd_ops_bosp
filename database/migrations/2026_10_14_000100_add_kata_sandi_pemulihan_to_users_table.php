<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit keamanan 2026-10-14: sebelumnya jalur "Pemulihan Akun" Superadmin
 * (lihat App\Livewire\Forms\LoginForm) memeriksa kata sandi pemulihan
 * terhadap 1 nilai TETAP yang tertulis langsung di kode (sama untuk semua
 * akun Superadmin, tidak bisa diganti lewat aplikasi). Kolom baru ini
 * memindahkan kata sandi pemulihan menjadi ACAK PER-AKUN & disimpan
 * TER-HASH di database (seperti kolom `password`), supaya bisa dibuat
 * ulang sendiri oleh pemiliknya (menu Profil) atau oleh Superadmin lain
 * (menu Kelola Pengguna) tanpa perlu ubah kode/deploy ulang.
 *
 * `kata_sandi_pemulihan_hash` NULL berarti akun tsb BELUM punya kata sandi
 * pemulihan (jalur "Pemulihan Akun" tidak akan berhasil sampai dibuat
 * lewat menu Profil/Kelola Pengguna). `kata_sandi_pemulihan_dibuat_at`
 * hanya informatif (ditampilkan di menu Profil), ikut dikosongkan lagi
 * otomatis setiap kali kata sandi pemulihan berhasil DIPAKAI untuk login
 * (sekali pakai - lihat LoginForm::pemulihan()), supaya Superadmin wajib
 * membuat yang baru lagi setelah setiap pemulihan.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('kata_sandi_pemulihan_hash')->nullable()->after('must_change_password');
            $table->timestamp('kata_sandi_pemulihan_dibuat_at')->nullable()->after('kata_sandi_pemulihan_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['kata_sandi_pemulihan_hash', 'kata_sandi_pemulihan_dibuat_at']);
        });
    }
};
