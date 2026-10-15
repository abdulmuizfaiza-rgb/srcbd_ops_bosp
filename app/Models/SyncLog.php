<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['arah', 'status', 'ringkasan', 'pesan_error', 'backup_pengaman_id', 'dilakukan_oleh_id'])]
class SyncLog extends Model
{
    protected $table = 'sync_logs';

    public const ARAH_KIRIM = 'kirim';

    public const ARAH_TERIMA = 'terima';

    public const ARAH_TARIK = 'tarik';

    public const ARAH_SAJIKAN = 'sajikan';

    /** BARU 2026-10-16 - lihat poin 8 docblock App\Services\SyncEngine. */
    public const ARAH_TARIK_PENGGUNA = 'tarik_pengguna';

    public const ARAH_SAJIKAN_PENGGUNA = 'sajikan_pengguna';

    public const STATUS_BERHASIL = 'berhasil';

    public const STATUS_GAGAL = 'gagal';

    protected function casts(): array
    {
        return [
            'ringkasan' => 'array',
        ];
    }

    public function backupPengaman(): BelongsTo
    {
        return $this->belongsTo(Backup::class, 'backup_pengaman_id');
    }

    public function dilakukanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dilakukan_oleh_id');
    }

    public static function labelArah(string $arah): string
    {
        return match ($arah) {
            self::ARAH_KIRIM => 'Kirim ke Online (Sinkronisasi)',
            self::ARAH_TERIMA => 'Terima dari Lokal (Sinkronisasi)',
            self::ARAH_TARIK => 'Tarik Data dari Online',
            self::ARAH_SAJIKAN => 'Melayani Tarikan dari Lokal',
            self::ARAH_TARIK_PENGGUNA => 'Tarik Data Pengguna dari Online',
            self::ARAH_SAJIKAN_PENGGUNA => 'Melayani Tarikan Pengguna dari Lokal',
            default => $arah,
        };
    }
}
