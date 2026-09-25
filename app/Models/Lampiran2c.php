<?php

namespace App\Models;

use Database\Factories\Lampiran2cFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lampiran 2c - Daftar Penyesuaian Gaji Pokok PTK, per sekolah, per
 * triwulan (1-4).
 */
#[Fillable([
    'profil_sekolah_id',
    'triwulan',
    'nrg',
    'nuptk',
    'nama_ptk',
    'kecamatan',
    'jenis_kepangkatan',
    'golongan',
    'masa_kerja',
    'pangkat_berkala',
    'tmt',
    'gaji_pokok_lama',
    'gaji_pokok_baru',
    'keterangan',
    'created_by',
])]
class Lampiran2c extends Model
{
    /** @use HasFactory<Lampiran2cFactory> */
    use HasFactory;

    protected $table = 'lampiran_2c';

    protected function casts(): array
    {
        return [
            'triwulan' => 'integer',
            'tmt' => 'date',
            'gaji_pokok_lama' => 'integer',
            'gaji_pokok_baru' => 'integer',
        ];
    }

    public const TRIWULAN_OPTIONS = [
        1 => 'Triwulan 1',
        2 => 'Triwulan 2',
        3 => 'Triwulan 3',
        4 => 'Triwulan 4',
    ];

    public const KECAMATAN_OPTIONS = [
        'Caringin' => 'Caringin',
        'Cicantayan' => 'Cicantayan',
        'Cibadak' => 'Cibadak',
        'Nagrak' => 'Nagrak',
        'Cikidang' => 'Cikidang',
    ];

    public const JENIS_KEPANGKATAN_OPTIONS = [
        'Pangkat' => 'Pangkat',
        'KGB' => 'KGB',
    ];

    public const PANGKAT_BERKALA_OPTIONS = [
        'Pangkat' => 'Pangkat',
        'KGB' => 'KGB',
    ];

    /**
     * Daftar Golongan (pangkat + kode ruang digabung jadi 1 pilihan) -
     * diganti total sesuai permintaan user (2026-09-05), menggantikan
     * daftar lama yang keliru memisahkan nama pangkat & kode romawi
     * sebagai 16 pilihan terpisah (mis. "Penata Muda" dan "III/a" bisa
     * dipilih sendiri-sendiri, padahal seharusnya selalu berpasangan).
     */
    public const GOLONGAN_OPTIONS = [
        'Penata Muda III/a' => 'Penata Muda III/a',
        'Penata Muda Tk. I III/b' => 'Penata Muda Tk. I III/b',
        'Penata III/c' => 'Penata III/c',
        'Penata Tk. I III/d' => 'Penata Tk. I III/d',
        'Pembina IV/a' => 'Pembina IV/a',
        'Pembina Tk. I IV/b' => 'Pembina Tk. I IV/b',
        'Pembina Utama Muda IV/c' => 'Pembina Utama Muda IV/c',
        'Pembina Utama Madya IV/d' => 'Pembina Utama Madya IV/d',
    ];

    public function profilSekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
