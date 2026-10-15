<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Database\Factories\RekapCairTpgBulanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Detail tab "Data Cair/Belum TPG" (BARU 2026-10-16) - 1 baris per PTK
 * per sekolah+tahun+triwulan, menyimpan status cair TPG tiap bulan
 * (Januari-Desember, ejaan "Pebruari"/"Nopember" mengikuti gambar
 * contoh user). Lihat migration create_rekap_cair_tpg_bulan_table.php
 * utk penjelasan lengkap skema & alasan kunci unik (profil_sekolah_id,
 * tahun, triwulan, nuptk) - BUKAN foreign key ke lampiran_2a.id/
 * data_ptk.id (keamanan sinkron Lokal/Online).
 */
#[Fillable([
    'profil_sekolah_id',
    'tahun',
    'triwulan',
    'nuptk',
    'bulan_januari',
    'bulan_pebruari',
    'bulan_maret',
    'bulan_april',
    'bulan_mei',
    'bulan_juni',
    'bulan_juli',
    'bulan_agustus',
    'bulan_september',
    'bulan_oktober',
    'bulan_nopember',
    'bulan_desember',
])]
class RekapCairTpgBulan extends Model
{
    use Syncable;

    /** @use HasFactory<RekapCairTpgBulanFactory> */
    use HasFactory;

    protected $table = 'rekap_cair_tpg_bulan';

    /**
     * 12 nama kolom bulan, URUT Januari->Desember - dipakai bersama
     * oleh Livewire component & partial Blade supaya daftar kolomnya
     * satu sumber kebenaran (pola sama seperti LembarValidasiTpgCeklist::
     * KOLOM_CEKLIST).
     *
     * @return array<int, string>
     */
    public const KOLOM_BULAN = [
        'bulan_januari',
        'bulan_pebruari',
        'bulan_maret',
        'bulan_april',
        'bulan_mei',
        'bulan_juni',
        'bulan_juli',
        'bulan_agustus',
        'bulan_september',
        'bulan_oktober',
        'bulan_nopember',
        'bulan_desember',
    ];

    /**
     * 3 pilihan status cair TPG per bulan (permintaan user verbatim,
     * dipakai sbg <select> per sel - BUKAN checkbox, krn 3 pilihan
     * saling eksklusif per bulan per PTK).
     *
     * @return array<string, string>
     */
    public const STATUS_OPTIONS = [
        'sudah_cair' => 'Sudah Cair TPG',
        'sudah_sktpg_belum_cair' => 'Sudah SKTPG-Belum Cair',
        'belum_sktpg_belum_cair' => 'Belum SKTPG-Belum Cair',
    ];

    public function profilSekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class);
    }
}
