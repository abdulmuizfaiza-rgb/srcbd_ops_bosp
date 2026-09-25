<?php

namespace App\Models;

use Database\Factories\Lampiran2aFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lampiran 2a - data gaji pokok PTK per sekolah, per triwulan (1-4).
 * Satu sekolah bisa punya banyak baris (satu per PTK) pada tiap triwulan.
 */
#[Fillable([
    'profil_sekolah_id',
    'triwulan',
    'tahun',
    'nrg',
    'nuptk',
    'nama_ptk',
    'status_kepegawaian',
    'gaji_pokok_januari',
    'npwp',
    'created_by',
])]
class Lampiran2a extends Model
{
    /** @use HasFactory<Lampiran2aFactory> */
    use HasFactory;

    protected $table = 'lampiran_2a';

    protected function casts(): array
    {
        return [
            'triwulan' => 'integer',
            'tahun' => 'integer',
            'gaji_pokok_januari' => 'integer',
        ];
    }

    public const TRIWULAN_OPTIONS = [
        1 => 'Triwulan 1',
        2 => 'Triwulan 2',
        3 => 'Triwulan 3',
        4 => 'Triwulan 4',
    ];

    public const STATUS_KEPEGAWAIAN_OPTIONS = [
        'PNS' => 'PNS',
        'PPPK' => 'PPPK',
        'PPPK-PW' => 'PPPK-PW',
        'GTY' => 'GTY',
        'GTT' => 'GTT',
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
