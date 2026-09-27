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
 */
#[Fillable(['user_id', 'username_snapshot', 'email_snapshot', 'level_akses_snapshot', 'jenis_aksi', 'nama_menu', 'detail', 'ip_address'])]
class AksesDataLog extends Model
{
    public const JENIS_BUKA_HALAMAN = 'buka_halaman';

    public const JENIS_UNDUH = 'unduh';

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

            static::create([
                'user_id' => $user->id,
                'username_snapshot' => $user->username,
                'email_snapshot' => $user->email,
                'level_akses_snapshot' => $user->level_akses,
                'jenis_aksi' => $jenisAksi,
                'nama_menu' => $namaMenu,
                'detail' => $detail,
                'ip_address' => request()?->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
