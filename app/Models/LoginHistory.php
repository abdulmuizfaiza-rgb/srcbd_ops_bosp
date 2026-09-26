<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris = satu sesi login (dari saat login sampai logout, kalau ada).
 * Lihat catatan lengkap di migration create_login_histories_table.
 */
#[Fillable(['user_id', 'level_akses', 'email', 'nama_sekolah', 'login_at', 'logout_at', 'ip_address', 'latitude', 'longitude'])]
class LoginHistory extends Model
{
    protected function casts(): array
    {
        return [
            'login_at' => 'datetime',
            'logout_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /**
     * @return BelongsTo<User, LoginHistory>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * "Berapa Lama Login" dalam format Indonesia singkat (mis. "1 jam 23
     * menit", "45 menit", "12 detik"). NULL kalau sesi masih berlangsung
     * (belum ada logout_at).
     */
    public function getDurasiAttribute(): ?string
    {
        if (! $this->logout_at) {
            return null;
        }

        $detik = $this->login_at->diffInSeconds($this->logout_at);

        $jam = intdiv($detik, 3600);
        $menit = intdiv($detik % 3600, 60);
        $sisaDetik = $detik % 60;

        if ($jam > 0) {
            return $menit > 0 ? "{$jam} jam {$menit} menit" : "{$jam} jam";
        }

        if ($menit > 0) {
            return "{$menit} menit";
        }

        return "{$sisaDetik} detik";
    }
}
