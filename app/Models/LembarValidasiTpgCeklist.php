<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Concerns\Syncable;

/**
 * Ceklist 8 kriteria verifikasi per-PTK pada tab "Lembar Validasi TPG"
 * (menu "Format Surat Rekomendasi & Pembatalan TPG", permintaan user
 * 2026-10-15/16). 1 baris per PTK per sekolah+tahun+triwulan, diidentifikasi
 * lewat kombinasi (profil_sekolah_id, tahun, triwulan, nuptk) - SENGAJA
 * BUKAN foreign key ke `lampiran_2a.id` (ID baris) - ID auto-increment
 * TIDAK BISA dipakai sbg kunci pencocokan krn Lokal & Online adalah 2
 * database TERPISAH yang ID auto-increment-nya tidak nyambung satu sama
 * lain (lihat docblock lengkap App\Services\SyncEngine & App\Models\
 * Concerns\Syncable) - NUPTK dipakai sbg identitas PTK krn wajib diisi
 * & presisi 16 digit di Lampiran 2a (lihat App\Livewire\PendataanOps\
 * Lampiran2a\Index validasi 'nuptk'), pola ini SAMA PERSIS dgn kunci
 * unik alami App\Models\PenerimaanHonorPtk (['profil_sekolah_id',
 * 'tahun', 'triwulan', 'nuptk']) yang sudah established & terbukti aman
 * utk fitur "Sinkronisasi dan Tarik Data" di aplikasi ini.
 *
 * Baris HANYA dibuat saat Admin OPS/Admin BOSP benar2 mencentang salah
 * satu kriteria utk PTK tsb (find-or-create saat toggle, lihat
 * App\Livewire\PendataanOps\SuratTpg\Index::simpanCeklist()) - TIDAK
 * dibuat otomatis utk seluruh PTK saat tab dibuka, supaya tidak ada
 * baris "kosong" (semua false) yang menumpuk tanpa guna.
 */
#[Fillable([
    'profil_sekolah_id',
    'tahun',
    'triwulan',
    'nuptk',
    'memiliki_sertifikat_pendidik',
    'berstatus_guru_asn',
    'aktif_melaksanakan_tugas_dapodik',
    'memiliki_nuptk_nrg',
    'memenuhi_beban_kerja',
    'mengajar_linier_sertifikat',
    'bukan_pegawai_tetap_instansi_lain',
    'berstatus_valid_info_gtk',
])]
class LembarValidasiTpgCeklist extends Model
{
    use Syncable;

    use HasFactory;

    protected $table = 'lembar_validasi_tpg_ceklist';

    /** Daftar nama kolom ceklist (8 kriteria) - dipakai Livewire component & Blade utk looping. */
    public const KOLOM_CEKLIST = [
        'memiliki_sertifikat_pendidik',
        'berstatus_guru_asn',
        'aktif_melaksanakan_tugas_dapodik',
        'memiliki_nuptk_nrg',
        'memenuhi_beban_kerja',
        'mengajar_linier_sertifikat',
        'bukan_pegawai_tetap_instansi_lain',
        'berstatus_valid_info_gtk',
    ];

    protected function casts(): array
    {
        return [
            'memiliki_sertifikat_pendidik' => 'boolean',
            'berstatus_guru_asn' => 'boolean',
            'aktif_melaksanakan_tugas_dapodik' => 'boolean',
            'memiliki_nuptk_nrg' => 'boolean',
            'memenuhi_beban_kerja' => 'boolean',
            'mengajar_linier_sertifikat' => 'boolean',
            'bukan_pegawai_tetap_instansi_lain' => 'boolean',
            'berstatus_valid_info_gtk' => 'boolean',
        ];
    }

    public function profilSekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class);
    }
}
