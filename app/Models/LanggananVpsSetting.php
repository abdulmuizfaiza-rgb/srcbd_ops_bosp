<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris/singleton (pola sama seperti App\Models\TahunAnggaranSetting/
 * SyncSetting/PengaturanTampilan/GoogleDriveSetting) - tanggal jatuh
 * tempo langganan VPS (idcloudhost) yang DIISI MANUAL oleh Superadmin,
 * ditampilkan di tab baru "Storage & Langganan VPS" pada menu Cek
 * Database dan Aplikasi (permintaan user 2026-10-24, lihat
 * App\Livewire\CekDatabaseAplikasi\Index).
 *
 * SENGAJA tidak ada integrasi API idcloudhost apa pun (dikonfirmasi
 * lewat AskUserQuestion: "Input manual oleh Superadmin") - tabel ini
 * TIDAK pernah menyimpan kredensial/API key.
 */
#[Fillable(['tanggal_jatuh_tempo', 'catatan'])]
class LanggananVpsSetting extends Model
{
    protected $table = 'langganan_vps_settings';

    public const ID_SINGLETON = 1;

    protected function casts(): array
    {
        return [
            'tanggal_jatuh_tempo' => 'date',
        ];
    }

    /**
     * Ambil (atau buat kalau belum ada - baris pertama kali, tanggal
     * jatuh tempo & catatan masih kosong sampai diisi Superadmin lewat
     * menu) baris singleton ini.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => self::ID_SINGLETON]);
    }
}
