<?php

namespace App\Models;

use Database\Factories\RincianBelanjaModalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rincian Belanja Modal - data belanja modal per sekolah, per tahun, per
 * triwulan (1-4), terbagi 2 jenis: "peralatan_mesin" (Rincian Belanja
 * Modal Peralatan & Mesin (KIB B)) & "aset_tetap_lainnya" (Rincian
 * Belanja Modal Aset Tetap Lainnya (KIB E)) - lihat konstanta
 * JENIS_PERALATAN_MESIN/JENIS_ASET_TETAP_LAINNYA. Field kedua jenis ini
 * PERSIS SAMA (dikonfirmasi lewat gambar contoh tabel yang diupload
 * user), karena itu memakai SATU tabel dengan kolom "jenis" sebagai
 * pembeda, bukan 2 model/tabel terpisah - pola sama seperti
 * App\Models\RincianPemeliharaan (Part 17).
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
class RincianBelanjaModal extends Model
{
    /** @use HasFactory<RincianBelanjaModalFactory> */
    use HasFactory;

    protected $table = 'rincian_belanja_modal';

    public const JENIS_PERALATAN_MESIN = 'peralatan_mesin';

    public const JENIS_ASET_TETAP_LAINNYA = 'aset_tetap_lainnya';

    /**
     * Nama tab UTAMA (bukan tab triwulan) - dipakai untuk judul tab,
     * judul modal, & judul Export.
     */
    public const JENIS_OPTIONS = [
        self::JENIS_PERALATAN_MESIN => 'Rincian Belanja Modal Peralatan & Mesin (KIB B)',
        self::JENIS_ASET_TETAP_LAINNYA => 'Rincian Belanja Modal Aset Tetap Lainnya (KIB E)',
    ];

    public const TAB_BMD = 'bmd';

    /**
     * Daftar tab UTAMA lengkap pada menu ini (permintaan user 2026-09-22:
     * tab ketiga "BMD" ditambahkan DI SAMPING 2 tab "jenis" yang sudah
     * ada) - dipakai untuk tab bar & validasi pindahTabUtama() pada
     * App\Livewire\PendataanBosp\RincianBelanjaModal\Index. BEDA dengan
     * JENIS_OPTIONS di atas: JENIS_OPTIONS HANYA 2 nilai yang valid untuk
     * kolom `jenis` pada tabel rincian_belanja_modal (dipakai saat
     * assignment `$data['jenis'] = $tabUtama`), sedangkan TAB_UTAMA_OPTIONS
     * dipakai murni untuk navigasi tab - baris tab "BMD" TIDAK PERNAH
     * disimpan ke kolom `jenis` manapun karena memakai tabel TERPISAH
     * (lihat App\Models\RincianBelanjaModalBmd).
     */
    public const TAB_UTAMA_OPTIONS = [
        self::JENIS_PERALATAN_MESIN => 'Rincian Belanja Modal Peralatan & Mesin (KIB B)',
        self::JENIS_ASET_TETAP_LAINNYA => 'Rincian Belanja Modal Aset Tetap Lainnya (KIB E)',
        self::TAB_BMD => 'BMD',
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
