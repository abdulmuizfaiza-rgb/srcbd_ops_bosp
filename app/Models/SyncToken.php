<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Fillable(['token_hash', 'label', 'dibuat_oleh_id'])]
class SyncToken extends Model
{
    protected $table = 'sync_tokens';

    protected function casts(): array
    {
        return [
            'terakhir_dipakai_pada' => 'datetime',
            'dicabut_pada' => 'datetime',
        ];
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh_id');
    }

    /**
     * Buat token API baru (mencabut semua token lama yang masih aktif -
     * HANYA 1 token aktif pada satu waktu, supaya Superadmin tidak
     * bingung token mana yang masih berlaku). Mengembalikan token ASLI
     * (plain text) - SATU-SATUNYA kesempatan token ini terlihat utuh,
     * dipanggil oleh App\Livewire\Sinkronisasi\Index::buatTokenBaru().
     */
    public static function buatBaru(?int $dibuatOlehId): string
    {
        static::query()->whereNull('dicabut_pada')->update(['dicabut_pada' => now()]);

        $tokenAsli = Str::random(64);

        static::create([
            'token_hash' => hash('sha256', $tokenAsli),
            'dibuat_oleh_id' => $dibuatOlehId,
        ]);

        return $tokenAsli;
    }

    /**
     * Cocokkan token mentah (dari header Authorization) dengan token
     * aktif (belum dicabut) yang tersimpan - dipakai
     * App\Http\Middleware\EnsureSyncTokenValid.
     */
    public static function cocok(?string $tokenMentah): ?self
    {
        if ($tokenMentah === null || $tokenMentah === '') {
            return null;
        }

        return static::query()
            ->where('token_hash', hash('sha256', $tokenMentah))
            ->whereNull('dicabut_pada')
            ->first();
    }

    public function tandaiDipakai(): void
    {
        $this->updateQuietly(['terakhir_dipakai_pada' => now()]);
    }
}
