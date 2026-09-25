<?php

namespace App\Models;

use Database\Factories\RincianPemeliharaanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rincian Pemeliharaan - data belanja pemeliharaan bangunan per sekolah,
 * per tahun, per triwulan (1-4), terbagi 2 jenis: "barang" (Rincian
 * Pemeliharaan Bangunan) & "jasa" (Rincian Jasa Pemeliharaan) - lihat
 * konstanta JENIS_BARANG/JENIS_JASA. Field kedua jenis ini PERSIS SAMA
 * (dikonfirmasi lewat AskUserQuestion 2026-09-10), karena itu memakai
 * SATU tabel dengan kolom "jenis" sebagai pembeda, bukan 2 model/tabel
 * terpisah.
 *
 * Sama seperti LanggananDayaJasa (BEDA dengan Rekap RKAS yang 1 baris
 * tetap per sekolah): 1 sekolah bisa punya BANYAK baris di menu ini -
 * sesuai jawaban AskUserQuestion ("Banyak baris per sekolah"). Kolom
 * "Nama Barang" SENGAJA TIDAK diberi unique constraint - boleh duplikat
 * (sesuai jawaban AskUserQuestion, sama seperti Uraian Pembayaran di
 * LanggananDayaJasa).
 *
 * NPSN & Nama Sekolah TIDAK disimpan sebagai kolom di tabel ini - selalu
 * diambil langsung dari relasi profilSekolah() saat ditampilkan (read-
 * only, otomatis dari menu Profil Sekolah), supaya selalu sinkron.
 *
 * Kolom "Kode UPB" diisi manual, teks bebas per baris - TIDAK terhubung
 * ke data lain (sesuai jawaban AskUserQuestion).
 */
#[Fillable([
    'profil_sekolah_id',
    'jenis',
    'tahun',
    'triwulan',
    'kode_upb',
    'nama_barang',
    'nama_merk_barang',
    'volume',
    'satuan',
    'harga_satuan',
    'total_harga',
    'asal_usul',
    'tanggal',
    'keterangan',
    'created_by',
])]
class RincianPemeliharaan extends Model
{
    /** @use HasFactory<RincianPemeliharaanFactory> */
    use HasFactory;

    protected $table = 'rincian_pemeliharaan';

    public const JENIS_BARANG = 'barang';

    public const JENIS_JASA = 'jasa';

    /**
     * Nama tab UTAMA (bukan tab triwulan) - dipakai untuk judul tab,
     * judul modal, & judul Export.
     */
    public const JENIS_OPTIONS = [
        self::JENIS_BARANG => 'Rincian Pemeliharaan Bangunan',
        self::JENIS_JASA => 'Rincian Jasa Pemeliharaan',
    ];

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
            'harga_satuan' => 'integer',
            'total_harga' => 'integer',
            'tanggal' => 'date',
        ];
    }

    /**
     * Total Harga = Volume x Harga Satuan - SELALU dihitung otomatis
     * oleh sistem, dipusatkan di sini supaya dipakai konsisten oleh
     * input langsung di kotak tabel, form modal Tambah/Edit, dan import
     * Excel. Nilai kosong (null) dianggap 0, hasilnya SELALU angka
     * pasti.
     */
    public static function hitungTotalHarga(?int $volume, ?int $hargaSatuan): int
    {
        return (int) $volume * (int) $hargaSatuan;
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
