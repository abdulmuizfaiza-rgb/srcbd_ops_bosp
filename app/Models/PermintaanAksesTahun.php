<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Permintaan akses "tahun sebelumnya" dari Admin OPS/Admin BOSP ke
 * Superadmin (permintaan user 2026-10-24) - lihat docblock migration
 * create_permintaan_akses_tahun_table untuk latar belakang & arti kolom
 * `status` secara lengkap.
 */
#[Fillable(['user_id', 'tahun', 'status', 'diminta_pada', 'diproses_oleh', 'diproses_pada', 'catatan'])]
class PermintaanAksesTahun extends Model
{
    protected $table = 'permintaan_akses_tahun';

    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    protected function casts(): array
    {
        return [
            'diminta_pada' => 'datetime',
            'diproses_pada' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    /**
     * Daftar tahun yang BOLEH diakses 1 akun (Admin OPS/Admin BOSP) saat
     * login - Tahun Anggaran Aktif SELALU masuk (tidak perlu izin), lalu
     * ditambah tahun-tahun lain yang pernah 'disetujui' Superadmin untuk
     * akun ini (permanen sampai dicabut - lihat docblock migration).
     * Dipakai di halaman baru pilih-tahun.blade.php (setelah login) dan
     * di menu Tahun Anggaran (riwayat permintaan akun sendiri). Hasil
     * diurutkan DESC (tahun terbaru paling atas) & tanpa duplikat.
     *
     * @return array<int, int>
     */
    public static function tahunDiizinkanUntuk(User $user): array
    {
        $tahunAktif = TahunAnggaranSetting::current()->tahun_aktif;

        $tahunDisetujui = static::where('user_id', $user->id)
            ->where('status', self::STATUS_DISETUJUI)
            ->pluck('tahun')
            ->map(fn ($tahun) => (int) $tahun);

        return collect([$tahunAktif])
            ->merge($tahunDisetujui)
            ->unique()
            ->sortDesc()
            ->values()
            ->all();
    }
}
