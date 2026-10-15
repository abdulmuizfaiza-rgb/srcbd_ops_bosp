<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Database\Factories\RekapCairTpgFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Header tab "Data Cair/Belum TPG" (BARU 2026-10-16) - 1 baris per
 * sekolah+tahun+triwulan, hanya menyimpan Catatan Hasil Verifikasi.
 * Data ceklist 12 bulan per-PTK ada di RekapCairTpgBulan (tabel
 * terpisah). Lihat migration create_rekap_cair_tpg_table.php & App\
 * Models\SuratTpg::JENIS_REKAP_CAIR_TPG utk konteks lengkap.
 */
#[Fillable([
    'profil_sekolah_id',
    'tahun',
    'triwulan',
    'catatan_hasil_verifikasi',
])]
class RekapCairTpg extends Model
{
    use Syncable;

    /** @use HasFactory<RekapCairTpgFactory> */
    use HasFactory;

    protected $table = 'rekap_cair_tpg';

    /** Field data (non-identitas) yang dicek utk auto-hapus baris saat semuanya kosong. */
    public const FIELD_DATA = ['catatan_hasil_verifikasi'];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'triwulan' => 'integer',
        ];
    }

    public function profilSekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class);
    }

    /** Baris dianggap kosong (boleh dihapus otomatis) kalau semua field di FIELD_DATA null/kosong. */
    public function semuaFieldDataKosong(): bool
    {
        foreach (self::FIELD_DATA as $field) {
            if (! blank($this->{$field})) {
                return false;
            }
        }

        return true;
    }
}
