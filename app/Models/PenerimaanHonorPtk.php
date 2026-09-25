<?php

namespace App\Models;

use Database\Factories\PenerimaanHonorPtkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Penerimaan Honor PTK - data honor yang diterima PTK (Pendidik dan
 * Tenaga Kependidikan) per sekolah, per tahun, per triwulan (1-4).
 *
 * BEDA dengan Rekap RKAS (1 baris TETAP per sekolah per tahun): 1
 * sekolah bisa punya BANYAK baris pada menu ini (1 baris = 1 PTK
 * penerima honor) - sesuai jawaban AskUserQuestion 2026-09-09 ("Banyak
 * baris per sekolah, bisa tambah/hapus baris PTK").
 *
 * NPSN & Nama Sekolah TIDAK disimpan sebagai kolom di tabel ini -
 * selalu diambil langsung dari relasi profilSekolah() saat ditampilkan
 * (read-only, "otomatis dari field NPSN/Nama Sekolah pada menu Profil
 * Sekolah" sesuai permintaan user), supaya selalu sinkron.
 */
#[Fillable([
    'profil_sekolah_id',
    'tahun',
    'triwulan',
    'nuptk',
    'nama_penerima',
    'volume',
    'satuan',
    'tarif_harga',
    'jumlah_honor',
    'tanggal_bayar',
    'created_by',
])]
class PenerimaanHonorPtk extends Model
{
    /** @use HasFactory<PenerimaanHonorPtkFactory> */
    use HasFactory;

    protected $table = 'penerimaan_honor_ptk';

    /**
     * 4 Tab Triwulan dengan warna masing-masing (permintaan user
     * 2026-09-10): Triwulan 1 Biru, Triwulan 2 Ungu, Triwulan 3 Kuning,
     * Triwulan 4 Hijau - SENGAJA tidak memakai komponen <x-tab-triwulan>
     * yang sudah ada (dipakai Lampiran 2a/2b/2c) karena skema warnanya
     * beda (itu: Biru/Hijau/Orange/Ungu) - mengubah komponen itu akan
     * ikut mengubah tampilan menu-menu lain yang sudah ada.
     */
    public const TRIWULAN_OPTIONS = [
        1 => ['label' => 'Triwulan 1', 'warna' => 'blue'],
        2 => ['label' => 'Triwulan 2', 'warna' => 'purple'],
        3 => ['label' => 'Triwulan 3', 'warna' => 'yellow'],
        4 => ['label' => 'Triwulan 4', 'warna' => 'green'],
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'triwulan' => 'integer',
            'volume' => 'integer',
            'tarif_harga' => 'integer',
            'jumlah_honor' => 'integer',
            'tanggal_bayar' => 'date',
        ];
    }

    /**
     * Jumlah Honor Yang Diterima = Volume x Tarif Harga (permintaan user
     * verbatim) - SELALU dihitung otomatis oleh sistem, dipusatkan di
     * sini supaya dipakai konsisten oleh input langsung di kotak tabel,
     * form modal Tambah/Edit, dan import Excel.
     *
     * Nilai kosong (null) dianggap 0 untuk perhitungan ini, hasilnya
     * SELALU angka pasti (tidak pernah null).
     */
    public static function hitungJumlahHonor(?int $volume, ?int $tarifHarga): int
    {
        return (int) $volume * (int) $tarifHarga;
    }

    public function profilSekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
