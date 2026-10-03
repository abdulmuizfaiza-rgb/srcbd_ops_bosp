<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Satu baris = satu kali akun MEMBUKA HALAMAN data (jenis_aksi=BUKA_HALAMAN)
 * atau MENGUNDUH data (jenis_aksi=UNDUH). Lihat catatan lengkap di migration
 * create_akses_data_logs_table. Permintaan user 2026-09-27 (menu "Cek
 * Database dan Aplikasi") - kontrol keamanan supaya Superadmin tahu "data
 * apa saja yang diambil" oleh siapa.
 *
 * SEJAK permintaan user 2026-10-03: ditambahkan user_agent (header
 * browser mentah, diambil otomatis dari request() - tanpa izin apapun,
 * selalu tersedia) & latitude/longitude. Info perangkat/browser yang
 * mudah dibaca (OS - Browser - Jenis Perangkat) TIDAK disimpan sebagai
 * kolom terpisah - diuraikan langsung dari user_agent saat ditampilkan,
 * lihat App\Support\InfoPerangkat.
 *
 * latitude/longitude SENGAJA TIDAK diminta ulang ke browser setiap kali
 * catat() dipanggil (aksi ini bisa terjadi puluhan kali per sesi -
 * meminta izin lokasi berulang-ulang akan sangat mengganggu). Keduanya
 * diambil dari baris App\Models\LoginHistory yang SEDANG AKTIF milik
 * user ini (logout_at masih NULL, baris login paling baru) - yaitu
 * titik lokasi yang sudah diambil 1x lewat Browser Geolocation API saat
 * user ini login (lihat LoginForm::catatRiwayatLogin()). Tetap NULL
 * kalau tidak ada baris LoginHistory aktif, atau user menolak izin
 * lokasi saat login.
 */
#[Fillable(['user_id', 'username_snapshot', 'email_snapshot', 'level_akses_snapshot', 'jenis_aksi', 'nama_menu', 'detail', 'ip_address', 'user_agent', 'latitude', 'longitude'])]
class AksesDataLog extends Model
{
    public const JENIS_BUKA_HALAMAN = 'buka_halaman';

    public const JENIS_UNDUH = 'unduh';

    /**
     * Cache hasil lookup LoginHistory aktif PER-REQUEST (bukan permanen/
     * antar-request) - supaya kalau catat() dipanggil lebih dari 1x
     * dalam 1 request yang sama untuk user yang sama, tidak perlu query
     * LoginHistory berkali-kali. Array di-reset otomatis setiap kali PHP
     * memulai request baru (static property biasa, bukan cache/session).
     *
     * @var array<int, LoginHistory|null>
     */
    private static array $cacheRiwayatAktif = [];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /**
     * @return BelongsTo<User, AksesDataLog>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mencatat satu baris akses data untuk USER YANG SEDANG LOGIN saat ini
     * (Auth::user()). Dibungkus try/catch supaya kegagalan mencatat (mis.
     * tabel belum ter-migrate, atau dipanggil di luar konteks web/auth)
     * TIDAK PERNAH mengganggu fitur asli yang sedang dijalankan (buka
     * halaman / unduh PDF-Excel yang sudah berfungsi baik).
     */
    public static function catat(string $jenisAksi, string $namaMenu, ?string $detail = null): void
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return;
            }

            $riwayatAktif = self::riwayatLoginAktif($user->id);

            static::create([
                'user_id' => $user->id,
                'username_snapshot' => $user->username,
                'email_snapshot' => $user->email,
                'level_akses_snapshot' => $user->level_akses,
                'jenis_aksi' => $jenisAksi,
                'nama_menu' => $namaMenu,
                'detail' => $detail,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'latitude' => $riwayatAktif?->latitude,
                'longitude' => $riwayatAktif?->longitude,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Baris App\Models\LoginHistory yang SEDANG AKTIF (logout_at masih
     * NULL, paling baru login_at-nya) milik 1 user - sumber latitude/
     * longitude untuk catat() di atas. Lihat docblock cache di atas &
     * docblock kelas untuk alasan lengkap kenapa lokasi tidak diminta
     * ulang ke browser di sini.
     */
    private static function riwayatLoginAktif(int $userId): ?LoginHistory
    {
        if (! array_key_exists($userId, self::$cacheRiwayatAktif)) {
            self::$cacheRiwayatAktif[$userId] = LoginHistory::where('user_id', $userId)
                ->whereNull('logout_at')
                ->latest('login_at')
                ->first();
        }

        return self::$cacheRiwayatAktif[$userId];
    }
}
