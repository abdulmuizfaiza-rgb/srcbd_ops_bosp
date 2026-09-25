<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'password', 'level_akses', 'nama_sekolah', 'profil_sekolah_id', 'jabatan', 'must_change_password', 'is_approved'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const LEVEL_SUPERADMIN = 'superadmin';

    public const LEVEL_ADMIN_OPS = 'admin_ops';

    public const LEVEL_ADMIN_BOSP = 'admin_bosp';

    /**
     * Daftar level akses yang tersedia beserta labelnya.
     *
     * @return array<string, string>
     */
    public static function levelAksesOptions(): array
    {
        return [
            self::LEVEL_SUPERADMIN => 'Superadmin',
            self::LEVEL_ADMIN_OPS => 'Admin OPS',
            self::LEVEL_ADMIN_BOSP => 'Admin BOSP',
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_approved' => 'boolean',
        ];
    }

    public function isSuperadmin(): bool
    {
        return $this->level_akses === self::LEVEL_SUPERADMIN;
    }

    public function isAdminOps(): bool
    {
        return $this->level_akses === self::LEVEL_ADMIN_OPS;
    }

    public function isAdminBosp(): bool
    {
        return $this->level_akses === self::LEVEL_ADMIN_BOSP;
    }

    /**
     * Sekolah yang terhubung dengan akun ini (khusus Admin OPS/Admin BOSP).
     *
     * @return BelongsTo<ProfilSekolah, User>
     */
    public function profilSekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class);
    }

    /**
     * Label level akses yang mudah dibaca (Superadmin/Admin OPS/Admin BOSP).
     */
    public function getLevelAksesLabelAttribute(): string
    {
        return self::levelAksesOptions()[$this->level_akses] ?? $this->level_akses;
    }

    /**
     * Nama tampilan pada navigasi: nama sekolah untuk Admin OPS/BOSP,
     * atau username untuk Superadmin.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->nama_sekolah ?: $this->username;
    }
}
