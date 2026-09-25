<?php

namespace App\Models;

use Database\Factories\LanggananDayaJasaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Langganan Daya dan Jasa - data langganan/tagihan (listrik, air,
 * internet, telepon, dsb) per sekolah, per tahun, per triwulan (1-4).
 *
 * Sama seperti Penerimaan Honor PTK (BEDA dengan Rekap RKAS yang 1
 * baris tetap per sekolah): 1 sekolah bisa punya BANYAK baris di menu
 * ini - sesuai jawaban AskUserQuestion 2026-09-10 ("Banyak baris per
 * sekolah"). Kolom "Uraian Pembayaran" SENGAJA TIDAK diberi unique
 * constraint - boleh duplikat (sesuai jawaban AskUserQuestion, mis. 2
 * baris "Listrik" untuk bulan berbeda dalam 1 triwulan yang sama).
 *
 * NPSN & Nama Sekolah TIDAK disimpan sebagai kolom di tabel ini -
 * selalu diambil langsung dari relasi profilSekolah() saat ditampilkan
 * (read-only, otomatis dari menu Profil Sekolah), supaya selalu sinkron.
 */
#[Fillable([
    'profil_sekolah_id',
    'tahun',
    'triwulan',
    'uraian_pembayaran',
    'volume',
    'satuan',
    'tarif_harga',
    'jumlah',
    'tanggal_bayar',
    'created_by',
])]
class LanggananDayaJasa extends Model
{
    /** @use HasFactory<LanggananDayaJasaFactory> */
    use HasFactory;

    protected $table = 'langganan_daya_jasa';

    /**
     * 4 Tab Triwulan - permintaan user 2026-09-10 memakai teks pendek
     * "Daya & Jasa TW-1/2/3/4" (bukan "Triwulan 1/2/3/4") supaya tabnya
     * tidak melebar (pelajaran dari koreksi tab Penerimaan Honor PTK
     * round sebelumnya, sekarang diterapkan sejak awal). Label di sini
     * ("Triwulan 1", dst) tetap dipakai untuk subjudul modal Tambah/Edit
     * ("Tahun {tahun} - Triwulan {n}") - teks tab sendiri ditulis
     * langsung di view (lihat index.blade.php), tidak dari sini.
     */
    public const TRIWULAN_OPTIONS = [
        1 => 'Triwulan 1',
        2 => 'Triwulan 2',
        3 => 'Triwulan 3',
        4 => 'Triwulan 4',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'triwulan' => 'integer',
            'volume' => 'integer',
            'tarif_harga' => 'integer',
            'jumlah' => 'integer',
            'tanggal_bayar' => 'date',
        ];
    }

    /**
     * Jumlah = Volume x Tarif Harga - SELALU dihitung otomatis oleh
     * sistem, dipusatkan di sini supaya dipakai konsisten oleh input
     * langsung di kotak tabel, form modal Tambah/Edit, dan import Excel.
     * Nilai kosong (null) dianggap 0, hasilnya SELALU angka pasti.
     */
    public static function hitungJumlah(?int $volume, ?int $tarifHarga): int
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
