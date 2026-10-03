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
 *
 * SEJAK permintaan user 2026-10-03: ditambahkan latitude/longitude
 * (titik lokasi saat percobaan login, dari Browser Geolocation API yang
 * sudah dipicu di halaman login SEBELUM username/password dikirim -
 * lihat LoginForm::$latitude/$longitude & login.blade.php). Info
 * perangkat/browser yang mudah dibaca (OS - Browser - Jenis Perangkat)
 * TIDAK disimpan sebagai kolom terpisah - diuraikan langsung dari
 * user_agent saat ditampilkan, lihat App\Support\InfoPerangkat.
 */
#[Fillable(['username_dicoba', 'ip_address', 'user_agent', 'latitude', 'longitude'])]
class FailedLoginAttempt extends Model
{
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /**
     * Mencatat satu percobaan login gagal. Dibungkus try/catch supaya
     * kegagalan mencatat (mis. tabel belum ter-migrate) TIDAK PERNAH
     * mengganggu proses login/pemulihan akun yang sedang berjalan.
     *
     * $latitude/$longitude opsional (nullable) - diisi dari titik lokasi
     * yang SUDAH diambil Browser Geolocation API di halaman login
     * (permintaan user 2026-10-03), tetap NULL kalau user menolak izin
     * lokasi atau browsernya tidak mendukung.
     */
    public static function catat(?string $usernameDicoba, ?string $ipAddress, ?string $userAgent, ?float $latitude = null, ?float $longitude = null): void
    {
        try {
            static::create([
                'username_dicoba' => $usernameDicoba,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
