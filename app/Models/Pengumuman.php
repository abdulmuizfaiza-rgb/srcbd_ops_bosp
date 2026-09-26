<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Lihat catatan lengkap di migration create_pengumuman_table.
 */
#[Fillable(['judul', 'isi', 'tanggal_aktif', 'tanggal_nonaktif'])]
class Pengumuman extends Model
{
    /**
     * Laravel menebak nama tabel dari bentuk jamak Bahasa Inggris kelas ini,
     * dan salah menerka "Pengumuman" (berakhiran "man") sebagai "pengumumen"
     * (aturan irregular man->men). Nama tabel di-set eksplisit di sini
     * supaya sesuai dengan migration create_pengumuman_table.
     */
    protected $table = 'pengumuman';

    protected function casts(): array
    {
        return [
            'tanggal_aktif' => 'date',
            'tanggal_nonaktif' => 'date',
        ];
    }

    /**
     * Pengumuman yang SEDANG aktif hari ini (tanggal hari ini di antara
     * tanggal_aktif & tanggal_nonaktif, INKLUSIF kedua ujungnya) -
     * dipakai di landing page (beranda) untuk running text. Diurutkan
     * dari yang paling baru mulai aktif, supaya pengumuman terbaru
     * tampil lebih dulu di teks berjalan.
     *
     * @param  Builder<Pengumuman>  $query
     * @return Builder<Pengumuman>
     */
    public function scopeAktifSaatIni(Builder $query): Builder
    {
        $hariIni = now()->toDateString();

        return $query->whereDate('tanggal_aktif', '<=', $hariIni)
            ->whereDate('tanggal_nonaktif', '>=', $hariIni)
            ->orderByDesc('tanggal_aktif');
    }

    public function getStatusAttribute(): string
    {
        $hariIni = now()->toDateString();

        if ($hariIni < $this->tanggal_aktif->toDateString()) {
            return 'Akan Datang';
        }

        if ($hariIni > $this->tanggal_nonaktif->toDateString()) {
            return 'Berakhir';
        }

        return 'Aktif';
    }
}
