<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris = satu percobaan login yang GAGAL (username tidak ada atau
 * password salah). Lihat catatan lengkap di migration
 * create_failed_login_attempts_table. Dicatat dari
 * App\Livewire\Forms\LoginForm - permintaan user 2026-09-27 (menu "Cek
 * Database dan Aplikasi") untuk memantau percobaan akses tanpa izin.
 */
#[Fillable(['username_dicoba', 'ip_address', 'user_agent'])]
class FailedLoginAttempt extends Model
{
    /**
     * Mencatat satu percobaan login gagal. Dibungkus try/catch supaya
     * kegagalan mencatat (mis. tabel belum ter-migrate) TIDAK PERNAH
     * mengganggu proses login/pemulihan akun yang sedang berjalan.
     */
    public static function catat(?string $usernameDicoba, ?string $ipAddress, ?string $userAgent): void
    {
        try {
            static::create([
                'username_dicoba' => $usernameDicoba,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
