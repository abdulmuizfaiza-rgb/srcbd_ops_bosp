<?php

namespace App\Models;

use Database\Factories\Lampiran2bFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lampiran 2b - keterangan/TMT per PTK, per sekolah, per triwulan (1-4).
 *
 * NRG & NUPTK selalu diambil otomatis dari data Lampiran 2a (dicocokkan
 * lewat Nama PTK, sekolah, & triwulan yang sama) - lihat
 * App\Livewire\PendataanOps\Lampiran2b\Index::isiOtomatisNrgNuptk().
 */
#[Fillable([
    'profil_sekolah_id',
    'triwulan',
    'nrg',
    'nuptk',
    'nama_ptk',
    'keterangan',
    'tmt',
    'created_by',
])]
class Lampiran2b extends Model
{
    /** @use HasFactory<Lampiran2bFactory> */
    use HasFactory;

    protected $table = 'lampiran_2b';

    protected function casts(): array
    {
        return [
            'triwulan' => 'integer',
            'tmt' => 'date',
        ];
    }

    public const TRIWULAN_OPTIONS = [
        1 => 'Triwulan 1',
        2 => 'Triwulan 2',
        3 => 'Triwulan 3',
        4 => 'Triwulan 4',
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
