<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Pengaturan unggah otomatis backup ke Google Drive (permintaan user
 * 2026-10-14) - singleton (1 baris saja, pola sama dengan SyncSetting/
 * PengaturanTampilan), TERPISAH per instalasi (lihat docblock migration
 * create_google_drive_settings_table).
 *
 * GANTI ARSITEKTUR (2026-10-14, sore) ke OAuth 2.0 "Login dengan Google"
 * - lihat docblock lengkap App\Services\GoogleDriveService & migration
 * add_oauth_columns_to_google_drive_settings_table utk alasan lengkap
 * (Service Account semula tidak bisa dipakai utk akun Gmail pribadi).
 * refresh_token DISIMPAN TERENKRIPSI (cast 'encrypted', pakai APP_KEY
 * aplikasi) - TIDAK PERNAH ditampilkan di UI mana pun dalam bentuk apa
 * pun, konsisten dgn prinsip keamanan kredensial proyek ini.
 *
 * nomor_urut_berikutnya (permintaan user 2026-10-14, sore - penomoran
 * file backup di Google Drive) - counter sederhana, TERPISAH per
 * instalasi (Lokal & Online jalan sendiri-sendiri). Diambil & dinaikkan
 * ATOMIK lewat ambilNomorUrutBerikutnya() (DB transaction + row lock)
 * supaya aman dari race condition kalau ada 2 proses unggah bersamaan.
 */
#[Fillable([
    'folder_url',
    'folder_id',
    'terakhir_kirim_pada',
    'terakhir_kirim_berhasil',
    'pesan_error_terakhir',
    'refresh_token',
    'email_terhubung',
    'terhubung_pada',
    'nomor_urut_berikutnya',
])]
class GoogleDriveSetting extends Model
{
    protected $table = 'google_drive_settings';

    public const ID_SINGLETON = 1;

    protected function casts(): array
    {
        return [
            'terakhir_kirim_pada' => 'datetime',
            'terakhir_kirim_berhasil' => 'boolean',
            'terhubung_pada' => 'datetime',
            'refresh_token' => 'encrypted',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => self::ID_SINGLETON]);
    }

    /** Sudah login/terhubung ke SATU akun Google (lewat OAuth)? */
    public function sudahTerhubung(): bool
    {
        return ! empty($this->refresh_token);
    }

    /** Siap dipakai mengunggah: SUDAH terhubung ke akun Google DAN sudah mengisi folder tujuan. */
    public function sudahDikonfigurasi(): bool
    {
        return $this->sudahTerhubung() && ! empty($this->folder_id);
    }

    /**
     * Ambil nomor urut berikutnya utk prefix nama file di Google Drive
     * (misal "0007 - backup-...zip"), SEKALIGUS menaikkan counter-nya.
     * Dibungkus transaction + lockForUpdate supaya atomik/aman dari race
     * condition. Nomor dipakai SEKALI SAJA lalu tidak bisa dipakai lagi
     * (kalau unggahan gagal setelah nomor diambil, nomor itu "hilang" -
     * ini risiko yang sengaja diterima demi kesederhanaan & keamanan
     * dari race condition).
     */
    public function ambilNomorUrutBerikutnya(): int
    {
        return DB::transaction(function () {
            $baris = static::query()->where('id', self::ID_SINGLETON)->lockForUpdate()->first();
            $nomor = $baris->nomor_urut_berikutnya ?? 1;
            $baris->update(['nomor_urut_berikutnya' => $nomor + 1]);

            return $nomor;
        });
    }
}
