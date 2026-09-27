<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom untuk fitur keamanan Google Authenticator (2FA / TOTP).
     *
     * - google2fa_secret: kunci rahasia TOTP milik pengguna, disimpan terenkripsi
     *   (cast 'encrypted' di model User) - null berarti pengguna BELUM pernah
     *   mengaktifkan Authenticator.
     * - google2fa_aktif_at: waktu pengguna menyelesaikan aktivasi (scan barcode +
     *   verifikasi kode pertama kali berhasil). Null berarti belum aktif -
     *   dipakai untuk menentukan apakah pengguna harus diarahkan ke halaman
     *   aktivasi (kalau null) atau halaman verifikasi kode (kalau sudah terisi)
     *   setiap kali login.
     * - google2fa_reset_diminta_at: waktu pengguna menekan "Lupa Authenticator?"
     *   di halaman verifikasi kode. Dipakai Superadmin untuk melihat siapa yang
     *   sedang menunggu di-reset di tab Authenticator (menu Pengguna).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('google2fa_secret')->nullable()->after('is_approved');
            $table->timestamp('google2fa_aktif_at')->nullable()->after('google2fa_secret');
            $table->timestamp('google2fa_reset_diminta_at')->nullable()->after('google2fa_aktif_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google2fa_secret', 'google2fa_aktif_at', 'google2fa_reset_diminta_at']);
        });
    }
};
