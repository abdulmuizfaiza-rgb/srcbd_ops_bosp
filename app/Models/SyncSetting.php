<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris/singleton (pola sama seperti App\Models\PengaturanTampilan) -
 * lihat docblock migration create_sync_settings_table untuk penjelasan
 * lengkap kenapa `token_online` dienkripsi.
 */
#[Fillable(['url_online', 'token_online', 'terakhir_tes_koneksi_pada', 'terakhir_tes_koneksi_berhasil'])]
class SyncSetting extends Model
{
    protected $table = 'sync_settings';

    public const ID_SINGLETON = 1;

    protected function casts(): array
    {
        return [
            'token_online' => 'encrypted',
            'terakhir_tes_koneksi_pada' => 'datetime',
            'terakhir_tes_koneksi_berhasil' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => self::ID_SINGLETON]);
    }

    /**
     * Versi aman untuk ditampilkan di layar - HANYA 4 karakter terakhir
     * (mengikuti prinsip keamanan "jangan tampilkan ulang credential
     * secara penuh", sama seperti token_hash di App\Models\SyncToken
     * yang juga tidak pernah ditampilkan ulang secara penuh).
     */
    public function getTokenOnlineSamarAttribute(): ?string
    {
        if (empty($this->token_online)) {
            return null;
        }

        return '••••••••'.substr($this->token_online, -4);
    }
}
