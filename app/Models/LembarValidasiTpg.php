<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\Syncable;

/**
 * Data header (Tanggal Verifikasi dan Validasi & Catatan Hasil
 * Verifikasi) untuk tab "Lembar Validasi TPG" pada menu "Format Surat
 * Rekomendasi & Pembatalan TPG" (Pendataan OPS, permintaan user
 * 2026-10-15/16, format sesuai gambar contoh yang diupload user).
 *
 * 1 baris per sekolah+tahun+triwulan (sama seperti pola App\Models\
 * SuratTpg) - nama/NIP Kasie KPML, Pengawas, & Kepala Sekolah SENGAJA
 * TIDAK disimpan di sini, selalu diambil langsung dari
 * App\Models\ProfilSekolah setiap render/export (pola sama persis dgn
 * SuratTpg). Daftar PTK & ceklist 8 kriteria per-PTK ada di tabel
 * TERPISAH `lembar_validasi_tpg_ceklist` (lihat
 * App\Models\LembarValidasiTpgCeklist), krn itu data per-PTK (banyak
 * baris), bukan data header (1 baris).
 */
#[Fillable([
    'profil_sekolah_id',
    'tahun',
    'triwulan',
    'tanggal_verifikasi',
    'catatan_hasil_verifikasi',
])]
class LembarValidasiTpg extends Model
{
    use Syncable;

    use HasFactory;

    protected $table = 'lembar_validasi_tpg';

    /**
     * Daftar kolom "data" - dipakai semuaFieldDataKosong() utk
     * mendeteksi baris yang seluruh field datanya sudah kosong (mis.
     * user isi lalu hapus lagi lewat auto-save), supaya baris tsb
     * otomatis dihapus - pola sama persis dgn App\Models\SuratTpg::
     * FIELD_DATA/semuaFieldDataKosong().
     */
    public const FIELD_DATA = ['tanggal_verifikasi', 'catatan_hasil_verifikasi'];

    protected function casts(): array
    {
        return [
            'tanggal_verifikasi' => 'date',
        ];
    }

    public function profilSekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class);
    }

    /** True jika SELURUH field data (FIELD_DATA) baris ini null. */
    public function semuaFieldDataKosong(): bool
    {
        return collect(self::FIELD_DATA)->every(fn (string $field) => $this->{$field} === null);
    }
}
