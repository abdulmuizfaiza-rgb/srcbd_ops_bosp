<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris/singleton (pola sama seperti App\Models\SyncSetting/
 * PengaturanTampilan/GoogleDriveSetting) - "Tahun Anggaran Aktif" yang
 * diatur Superadmin lewat menu baru "Tahun Anggaran" (permintaan user
 * 2026-10-24, lihat App\Livewire\TahunAnggaran\Index).
 *
 * Dipakai sebagai tahun default yang langsung dituju Admin OPS/Admin
 * BOSP begitu berhasil login (lihat halaman baru
 * resources/views/livewire/pages/auth/pilih-tahun.blade.php), dan
 * sebagai acuan "tahun sebelumnya" di App\Models\PermintaanAksesTahun.
 * TIDAK berlaku untuk Superadmin - dropdown Tahun di menu Dana BOSP
 * Tahap/Rekap RKAS untuk Superadmin tetap bebas seperti sebelumnya
 * (lihat App\Livewire\PendataanBosp\DanaBospTahap\Index::mount()).
 */
#[Fillable(['tahun_aktif'])]
class TahunAnggaranSetting extends Model
{
    protected $table = 'tahun_anggaran_settings';

    public const ID_SINGLETON = 1;

    /**
     * PENTING: pastikan tahun_aktif SELALU PHP int (bukan string) -
     * PDO driver PostgreSQL bisa mengembalikan kolom integer sebagai
     * string tanpa cast ini, yang akan membuat perbandingan ketat
     * (===) dengan nilai int lain (mis. di App\Livewire\TahunAnggaran\
     * Index::ajukanPermintaan()) salah walau nilainya sebenarnya sama.
     */
    protected function casts(): array
    {
        return [
            'tahun_aktif' => 'integer',
        ];
    }

    /**
     * Ambil (atau buat kalau belum ada - baris pertama kali, default
     * tahun_aktif = tahun kalender berjalan) baris singleton ini.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => self::ID_SINGLETON],
            ['tahun_aktif' => now()->year]
        );
    }
}
