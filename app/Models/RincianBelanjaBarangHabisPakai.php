<?php

namespace App\Models;

use Database\Factories\RincianBelanjaBarangHabisPakaiFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Rincian Belanja Barang Habis Pakai - data belanja barang habis pakai
 * per sekolah, per tahun, per triwulan (1-4). Menu ini HANYA SATU jenis
 * (tidak ada tab utama/pembeda "jenis" seperti RincianPemeliharaan/
 * RincianBelanjaModal) - pola SATU LAPIS tab sama seperti
 * BiayaPendaftaranLomba (Part 19), hanya field-nya mengikuti struktur
 * RincianPemeliharaan (sesuai gambar 3 contoh tabel yang diupload user
 * 2026-09-11, Part 21 poin 3).
 *
 * Sama seperti RincianPemeliharaan: 1 sekolah bisa punya BANYAK baris di
 * menu ini - sesuai jawaban AskUserQuestion 2026-09-11 ("Banyak baris
 * per sekolah"). Kolom "Nama Barang" SENGAJA TIDAK diberi unique
 * constraint - boleh duplikat.
 *
 * NPSN & Nama Sekolah TIDAK disimpan sebagai kolom di tabel ini - selalu
 * diambil langsung dari relasi profilSekolah() saat ditampilkan.
 *
 * Kolom "Kode UPB" diisi manual, teks bebas per baris - TIDAK terhubung
 * ke data lain (sesuai jawaban AskUserQuestion).
 *
 * Sejak permintaan user 2026-09-18: menu ini sekarang punya TAB UTAMA
 * kedua "Stock Opname" (lihat TAB_UTAMA_OPTIONS di bawah) - data Stock
 * Opname disimpan di tabel TERPISAH
 * (App\Models\StockOpnameBarangPersediaan, field-nya beda total), pola
 * 2-lapis tab (tab utama + Triwulan 1-4) disalin dari
 * App\Models\RincianBelanjaModal, lihat
 * App\Livewire\PendataanBosp\RincianBelanjaBarangHabisPakai\Index.
 *
 * SEJAK permintaan user 2026-09-19 (jawaban AskUserQuestion "Baris
 * otomatis mengikuti RBBHP (Recommended)"): SETIAP baris baru di menu
 * INI (lewat jalur MANAPUN - form modal Tambah, baris placeholder kotak
 * tabel, maupun Import Excel, karena ketiganya sama-sama lewat Eloquent
 * create()/save() sehingga event `created` di bawah SELALU ikut
 * terpanggil) OTOMATIS membuatkan SATU baris
 * App\Models\StockOpnameBarangPersediaan yang terhubung (kolom
 * `rincian_belanja_barang_habis_pakai_id`), utk sekolah+tahun+triwulan
 * yang SAMA - lihat booted() di bawah. Baris Stock Opname itu OTOMATIS
 * ikut terhapus kalau baris RBBHP ini dihapus (FK cascadeOnDelete pada
 * migration add_rincian_link_dan_rumus_stock_opname, BUKAN lewat event
 * PHP - supaya konsisten kehapus lewat jalur manapun termasuk query
 * massal). Nama Barang Persediaan pada Stock Opname SEJAK ini SELALU
 * mengikuti (read-only) kolom `nama_barang` baris RBBHP terkait - lihat
 * App\Models\StockOpnameBarangPersediaan::namaBarangTampil().
 */
#[Fillable([
    'profil_sekolah_id',
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
class RincianBelanjaBarangHabisPakai extends Model
{
    /** @use HasFactory<RincianBelanjaBarangHabisPakaiFactory> */
    use HasFactory;

    protected $table = 'rincian_belanja_barang_habis_pakai';

    public const TRIWULAN_OPTIONS = [
        1 => 'Triwulan 1',
        2 => 'Triwulan 2',
        3 => 'Triwulan 3',
        4 => 'Triwulan 4',
    ];

    /**
     * Tab UTAMA menu ini (permintaan user 2026-09-18) - "Stock Opname"
     * memakai tabel StockOpnameBarangPersediaan yang terpisah, TIDAK
     * ADA kolom "jenis" di tabel INI (beda dengan RincianBelanjaModal
     * yang jenisnya sama-sama disimpan di 1 tabel).
     */
    public const TAB_BARANG_HABIS_PAKAI = 'barang_habis_pakai';

    public const TAB_STOCK_OPNAME = 'stock_opname';

    public const TAB_UTAMA_OPTIONS = [
        self::TAB_BARANG_HABIS_PAKAI => 'Rincian Belanja Barang Habis Pakai',
        self::TAB_STOCK_OPNAME => 'Stock Opname',
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

    /**
     * Event `created` - lihat catatan lengkap di docblock kelas di atas
     * (permintaan user 2026-09-19, jawaban AskUserQuestion "Baris
     * otomatis mengikuti RBBHP"). firstOrCreate() dikunci pada
     * `rincian_belanja_barang_habis_pakai_id` (kolom UNIQUE di migration
     * add_rincian_link_dan_rumus_stock_opname) supaya TIDAK PERNAH
     * membuat duplikat kalau event ini kebetulan terpanggil lebih dari
     * sekali utk baris yang sama.
     */
    protected static function booted(): void
    {
        static::created(function (self $rincian) {
            StockOpnameBarangPersediaan::firstOrCreate(
                ['rincian_belanja_barang_habis_pakai_id' => $rincian->id],
                [
                    'profil_sekolah_id' => $rincian->profil_sekolah_id,
                    'tahun' => $rincian->tahun,
                    'triwulan' => $rincian->triwulan,
                    'keterangan' => StockOpnameBarangPersediaan::keteranganOtomatis($rincian->triwulan, $rincian->tahun),
                    'created_by' => $rincian->created_by,
                ]
            );
        });
    }

    public function profilSekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stockOpnameBarangPersediaan(): HasOne
    {
        return $this->hasOne(StockOpnameBarangPersediaan::class);
    }
}
