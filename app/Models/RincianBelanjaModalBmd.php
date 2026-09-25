<?php

namespace App\Models;

use Database\Factories\RincianBelanjaModalBmdFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tab "BMD" - Daftar Belanja Modal (permintaan user 2026-09-22, gambar
 * acuan "DAFTAR BELANJA MODAL TAHUN ANGGARAN 2026") - tab utama ketiga
 * pada menu Pendataan BOSP > Rincian Belanja Modal (lihat
 * App\Livewire\PendataanBosp\RincianBelanjaModal\Index &
 * App\Models\RincianBelanjaModal::TAB_UTAMA_OPTIONS), tabel TERPISAH
 * dari 2 tab "jenis" yang sudah ada karena field-nya berbeda total.
 *
 * NPSN, Lokasi (Nama Sekolah), & Subrayon TIDAK disimpan - selalu dari
 * relasi profilSekolah(). Program/Kegiatan/Kode Sub Kegiatan/Nama Sub
 * Kegiatan JUGA TIDAK disimpan - nilainya konstan untuk SETIAP baris
 * (lihat konstanta PROGRAM/KEGIATAN/dst. di bawah).
 *
 * Field yang MASIH manual (bisa diedit lewat modal Tambah/Edit ATAUPUN
 * kotak langsung di tabel, jawaban AskUserQuestion 2026-09-22 "Modal +
 * kotak isi langsung di tabel"): Bentuk Kontrak/Transaksi, Atribusi,
 * Jumlah Termin, Nomor Dokumen, Tgl. Perolehan, Penyedia, Kode Belanja,
 * Rekening Belanja, Sub Sub Rincian Objek, Jumlah, Satuan, Harga Satuan,
 * No BAST, Tgl BAST, Nomor Surat Pernyataan, Tgl. Surat Pernyataan, Nama
 * Pengurus Barang, Jabatan, Pejabat Penata Usaha, Nama Barang,
 * Spesifikasi Nama Barang, Spesifikasi Lain (Serial Nomor),
 * Merk/Pengarang.
 *
 * Field yang OTOMATIS/read-only (hasil rumus/relasi, TIDAK BISA diketik
 * manual dari jalur manapun): Jenis Aset (KIB) - selalu mengikuti
 * Rekening Belanja (jawaban AskUserQuestion 2026-09-22 "Otomatis saling
 * mengikuti", lihat jenisAsetOtomatis()); Total = Jumlah x Harga Satuan
 * (lihat hitungTotal()); KEDUA kolom "Keterangan" (lihat
 * keteranganBosOtomatis()/keteranganBospOtomatis()); PPK - SEJAK
 * 2026-09-22 (perubahan keputusan, MENGGANTI keputusan sebelumnya di
 * atas yang manual) diambil otomatis dari field "Nama Kepala Sekolah"
 * pada Profil Sekolah baris tsb (jawaban AskUserQuestion 2026-09-22
 * "otomatis berdasarkan field Nama Kepala Sekolah pada profil sekolah
 * (berlaku untuk di aplikasi dan hasil file excel nya)", lihat
 * ppkOtomatis()) - TIDAK BISA lagi diketik manual dari modal/tabel/
 * Import Excel manapun.
 */
#[Fillable([
    'profil_sekolah_id',
    'tahun',
    'triwulan',
    'bentuk_kontrak',
    'atribusi',
    'jumlah_termin',
    'ppk',
    'nomor_dokumen',
    'tanggal_perolehan',
    'penyedia',
    'kode_belanja',
    'rekening_belanja',
    'jenis_aset',
    'sub_sub_rincian_objek',
    'jumlah',
    'satuan',
    'harga_satuan',
    'total',
    'no_bast',
    'tanggal_bast',
    'keterangan_bos',
    'nomor_surat_pernyataan',
    'tanggal_surat_pernyataan',
    'nama_pengurus_barang',
    'jabatan',
    'pejabat_penata_usaha',
    'nama_barang',
    'spesifikasi_nama_barang',
    'spesifikasi_lain',
    'merk_pengarang',
    'keterangan_bosp',
    'created_by',
])]
class RincianBelanjaModalBmd extends Model
{
    /** @use HasFactory<RincianBelanjaModalBmdFactory> */
    use HasFactory;

    protected $table = 'rincian_belanja_modal_bmd';

    public const TRIWULAN_OPTIONS = [
        1 => 'Triwulan 1',
        2 => 'Triwulan 2',
        3 => 'Triwulan 3',
        4 => 'Triwulan 4',
    ];

    /**
     * Field "Bentuk Kontrak / Transaksi" - pilihan (dropdown), sesuai
     * permintaan user.
     */
    public const BENTUK_KONTRAK_OPTIONS = [
        'Surat Pesanan' => 'Surat Pesanan',
        'Kuitansi' => 'Kuitansi',
    ];

    /**
     * Field "Rekening Belanja" - pilihan (dropdown). Kunci array =
     * pasangan "Jenis Aset (KIB)" otomatisnya (lihat jenisAsetOtomatis()
     * di bawah - jawaban AskUserQuestion 2026-09-22 "Otomatis saling
     * mengikuti").
     */
    public const REKENING_BELANJA_OPTIONS = [
        'Belanja Modal Peralatan dan Mesin BOSP-BOS Reguler' => 'Peralatan dan Mesin',
        'Belanja Modal Aset Tetap Lainnya BOSP-BOS Reguler' => 'Aset Tetap Lainnya',
    ];

    /**
     * Nilai KONSTAN (SAMA untuk SETIAP baris) - permintaan user 2026-09-22
     * memakai frasa "di isi X" (BUKAN "diisi manual" seperti kelompok
     * field sesudahnya), jadi disimpulkan field ini adalah nilai tetap
     * otomatis, bukan input bebas - TIDAK disimpan sebagai kolom
     * database, cukup konstanta yang ditampilkan apa adanya di
     * tabel/form/Export.
     */
    public const PROGRAM = 'Program Pengelolaan Pendidikan';

    public const KEGIATAN = 'Pengelolaan Pendidikan Sekolah Menengah Pertama';

    public const KODE_SUB_KEGIATAN = '1.01.02.2.02.0042';

    public const NAMA_SUB_KEGIATAN = 'Pengelolaan Dana BOS Sekolah Menengah Pertama';

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'triwulan' => 'integer',
            'tanggal_perolehan' => 'date',
            'jumlah' => 'integer',
            'harga_satuan' => 'integer',
            'total' => 'integer',
            'tanggal_bast' => 'date',
            'tanggal_surat_pernyataan' => 'date',
        ];
    }

    public function profilSekolah(): BelongsTo
    {
        return $this->belongsTo(ProfilSekolah::class);
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Jenis Aset (KIB) OTOMATIS mengikuti Rekening Belanja yang dipilih
     * (jawaban AskUserQuestion 2026-09-22) - null kalau Rekening Belanja
     * belum diisi/tidak dikenal.
     */
    public static function jenisAsetOtomatis(?string $rekeningBelanja): ?string
    {
        return self::REKENING_BELANJA_OPTIONS[$rekeningBelanja] ?? null;
    }

    /**
     * Total = Jumlah x Harga Satuan - ditentukan EKSPLISIT oleh user
     * ("field Total (otomatis hasil perhitungan field Jumlah di kali
     * field Harga Satuan)"). Nilai kosong (null) dianggap 0, hasilnya
     * SELALU angka pasti - pola sama seperti
     * RincianBelanjaModal::hitungTotalHarga().
     */
    public static function hitungTotal(?int $jumlah, ?int $hargaSatuan): int
    {
        return (int) $jumlah * (int) $hargaSatuan;
    }

    /**
     * Keterangan (kolom PERTAMA, dekat No BAST/Tgl BAST pada gambar
     * acuan) = "BOS SMPN TRIWULAN {n} TAHUN {tahun} (REGULER)" - SELALU
     * dihitung dari triwulan+tahun baris itu sendiri.
     */
    public static function keteranganBosOtomatis(int $triwulan, int $tahun): string
    {
        return 'BOS SMPN TRIWULAN '.$triwulan.' TAHUN '.$tahun.' (REGULER)';
    }

    /**
     * Keterangan (kolom KEDUA, paling akhir pada gambar acuan) = "BOSP
     * TRIWULAN {n} TAHUN {tahun}" - SELALU dihitung dari triwulan+tahun
     * baris itu sendiri. SENGAJA berupa kolom TERPISAH dari
     * keteranganBosOtomatis() di atas meski sama-sama berjudul
     * "Keterangan" pada tabel/Export - keduanya memang muncul 2x pada
     * gambar acuan user, di 2 posisi berbeda dengan teks otomatis yang
     * berbeda pula.
     */
    public static function keteranganBospOtomatis(int $triwulan, int $tahun): string
    {
        return 'BOSP TRIWULAN '.$triwulan.' TAHUN '.$tahun;
    }

    /**
     * PPK OTOMATIS = Nama Kepala Sekolah pada Profil Sekolah baris ini
     * (jawaban AskUserQuestion 2026-09-22, MENGGANTI keputusan
     * sebelumnya yang manual - lihat docblock kelas di atas). SENGAJA
     * dipisah dari hitungSemuaOtomatis() (BUKAN ditambahkan ke
     * parameter/return array method tsb) supaya signature method itu
     * tidak berubah - method itu dipanggil langsung dengan argumen
     * posisional oleh banyak test yang sudah ada, jadi memisahkan PPK ke
     * method sendiri meminimalkan risiko terhadap test yang sudah lolos.
     * Null kalau sekolahnya belum mengisi Nama Kepala Sekolah pada Profil
     * Sekolah.
     */
    public static function ppkOtomatis(?string $namaKepalaSekolah): ?string
    {
        return $namaKepalaSekolah !== '' ? $namaKepalaSekolah : null;
    }

    /**
     * Menghitung ULANG SELURUH field otomatis/rumus sekaligus dari nilai
     * manual TERKINI pada baris ini - dipakai oleh Livewire Index setiap
     * salah satu field sumbernya berubah (Rekening Belanja/Jumlah/Harga
     * Satuan lewat kotak tabel, modal, maupun Import Excel), single
     * source of truth (pola sama seperti
     * StockOpnameBarangPersediaan::hitungSemuaRumus()).
     *
     * @return array{jenis_aset: ?string, total: int, keterangan_bos: string, keterangan_bosp: string}
     */
    public static function hitungSemuaOtomatis(?string $rekeningBelanja, ?int $jumlah, ?int $hargaSatuan, int $triwulan, int $tahun): array
    {
        return [
            'jenis_aset' => self::jenisAsetOtomatis($rekeningBelanja),
            'total' => self::hitungTotal($jumlah, $hargaSatuan),
            'keterangan_bos' => self::keteranganBosOtomatis($triwulan, $tahun),
            'keterangan_bosp' => self::keteranganBospOtomatis($triwulan, $tahun),
        ];
    }
}
