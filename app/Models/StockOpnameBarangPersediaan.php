<?php

namespace App\Models;

use Database\Factories\StockOpnameBarangPersediaanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stock Opname (Rincian Barang Persediaan BOSP) - Tab 2 pada menu
 * "Rincian Belanja Barang Habis Pakai" (permintaan user 2026-09-18,
 * rumus & keterkaitan RBBHP ditambahkan 2026-09-19). Lihat
 * App\Livewire\PendataanBosp\RincianBelanjaBarangHabisPakai\Index &
 * migration add_rincian_link_dan_rumus_stock_opname untuk catatan
 * lengkap.
 *
 * SEJAK 2026-09-19 (jawaban AskUserQuestion "Baris otomatis mengikuti
 * RBBHP"): 1 baris RincianBelanjaBarangHabisPakai (triwulan yang sama)
 * = 1 baris Stock Opname, dibuat OTOMATIS lewat
 * RincianBelanjaBarangHabisPakai::booted() (event `created`) - lihat
 * catatan lengkap di sana. Kolom `rincian_belanja_barang_habis_pakai_id`
 * menyimpan keterkaitan ini (FK, cascadeOnDelete - baris Stock Opname
 * OTOMATIS ikut terhapus kalau baris RBBHP sumbernya dihapus).
 *
 * Kolom `nama_barang` TETAP ADA di skema (untuk baris LAMA/legacy,
 * SEBELUM 2026-09-19, yang diisi manual & TIDAK punya
 * rincian_belanja_barang_habis_pakai_id - jawaban AskUserQuestion
 * 2026-09-19 "Biarkan tersimpan apa adanya", data lama TIDAK
 * diubah/dihapus), TAPI TIDAK PERNAH diisi lagi utk baris BARU sejak
 * ronde ini - lihat namaBarangTampil() di bawah, yang SELALU dipakai
 * utk menampilkan Nama Barang Persediaan (baik di tabel, Export, maupun
 * lainnya) supaya konsisten menangani baris lama (manual) & baris baru
 * (otomatis dari relasi) sekaligus.
 *
 * NPSN, Nama Sekolah, & Subrayon TIDAK disimpan sebagai kolom di tabel
 * ini - selalu diambil langsung dari relasi profilSekolah() saat
 * ditampilkan.
 *
 * Field yang MASIH manual sejak 2026-09-19: Satuan (Unit), Harga, &
 * KETIGA kolom Kuantitas (Saldo Awal, Penerimaan, Pengeluaran) -
 * TIDAK diminta rumus oleh user. Field yang JADI hasil rumus/read-only
 * sejak 2026-09-19: KEEMPAT kolom Jumlah (Rp), Kuantitas Saldo Akhir, &
 * Keterangan (lihat method hitung*()/keteranganOtomatis() di bawah,
 * dipanggil oleh Livewire Index setiap salah satu field manual sumbernya
 * berubah - pola sama seperti RincianBelanjaBarangHabisPakai::hitungTotalHarga()).
 */
#[Fillable([
    'profil_sekolah_id',
    'rincian_belanja_barang_habis_pakai_id',
    'tahun',
    'triwulan',
    'nama_barang',
    'satuan',
    'harga',
    'saldo_awal_kuantitas',
    'saldo_awal_jumlah',
    'penerimaan_kuantitas',
    'penerimaan_jumlah',
    'pengeluaran_kuantitas',
    'pengeluaran_jumlah',
    'saldo_akhir_kuantitas',
    'saldo_akhir_jumlah',
    'keterangan',
    'created_by',
])]
class StockOpnameBarangPersediaan extends Model
{
    /** @use HasFactory<StockOpnameBarangPersediaanFactory> */
    use HasFactory;

    protected $table = 'stock_opname_barang_persediaan';

    public const TRIWULAN_OPTIONS = [
        1 => 'Triwulan 1',
        2 => 'Triwulan 2',
        3 => 'Triwulan 3',
        4 => 'Triwulan 4',
    ];

    /**
     * Field-field Kuantitas & Jumlah (Rp) - dipakai bersama oleh
     * Livewire Index (daftarFieldEditableOpname(), aturanFieldOpname())
     * & Export/Import, supaya urutan/daftarnya konsisten di satu
     * tempat saja.
     */
    public const FIELD_KUANTITAS = [
        'saldo_awal_kuantitas',
        'penerimaan_kuantitas',
        'pengeluaran_kuantitas',
        'saldo_akhir_kuantitas',
    ];

    public const FIELD_JUMLAH = [
        'saldo_awal_jumlah',
        'penerimaan_jumlah',
        'pengeluaran_jumlah',
        'saldo_akhir_jumlah',
    ];

    /**
     * Field Kuantitas yang MASIH manual sejak 2026-09-19 (Saldo Akhir
     * TIDAK termasuk - lihat hitungSaldoAkhirKuantitas() di bawah).
     */
    public const FIELD_KUANTITAS_MANUAL = [
        'saldo_awal_kuantitas',
        'penerimaan_kuantitas',
        'pengeluaran_kuantitas',
    ];

    /**
     * SELURUH kolom Jumlah (Rp) & Kuantitas Saldo Akhir - SUDAH JADI
     * HASIL RUMUS/read-only sejak permintaan user 2026-09-19 (lihat
     * catatan lengkap di hitungJumlah()/hitungSaldoAkhirKuantitas()/
     * hitungSaldoAkhirJumlah() di bawah). Dipakai oleh Livewire Index &
     * blade tabel supaya kotaknya TIDAK LAGI berupa input (jadi tampilan
     * bg-yellow-50 read-only, pola sama seperti
     * LaporanRealisasiBosp::FIELD_KOMPUTASI_RINCIAN).
     */
    public const FIELD_RUMUS = [
        'saldo_awal_jumlah',
        'penerimaan_jumlah',
        'pengeluaran_jumlah',
        'saldo_akhir_kuantitas',
        'saldo_akhir_jumlah',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'triwulan' => 'integer',
            'harga' => 'integer',
            'saldo_awal_kuantitas' => 'integer',
            'saldo_awal_jumlah' => 'integer',
            'penerimaan_kuantitas' => 'integer',
            'penerimaan_jumlah' => 'integer',
            'pengeluaran_kuantitas' => 'integer',
            'pengeluaran_jumlah' => 'integer',
            'saldo_akhir_kuantitas' => 'integer',
            'saldo_akhir_jumlah' => 'integer',
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
     * Baris RincianBelanjaBarangHabisPakai sumber baris Stock Opname ini
     * - NULL utk baris LAMA/legacy (sebelum 2026-09-19, Nama Barang diisi
     * manual, lihat catatan kelas di atas).
     */
    public function rincianBelanjaBarangHabisPakai(): BelongsTo
    {
        return $this->belongsTo(RincianBelanjaBarangHabisPakai::class);
    }

    /**
     * Nama Barang Persediaan yang DITAMPILKAN (tabel, Export, dll) -
     * SELALU dipakai sebagai pengganti kolom `nama_barang` mentah,
     * supaya baris BARU (otomatis, terhubung ke RBBHP - kolom
     * `nama_barang` di baris ini SENGAJA dibiarkan null/tidak diisi) &
     * baris LAMA (manual, kolom `nama_barang` terisi langsung) SAMA-SAMA
     * tampil benar dari SATU method saja (single source of truth,
     * permintaan user 2026-09-19 poin 7 & jawaban AskUserQuestion
     * "Baris otomatis mengikuti RBBHP").
     */
    public function namaBarangTampil(): ?string
    {
        return $this->rincianBelanjaBarangHabisPakai?->nama_barang ?? $this->nama_barang;
    }

    /**
     * Kolom Jumlah (Rp) = Harga x Kuantitas pada GRUP yang sama (Saldo
     * Awal/Penerimaan/Pengeluaran) - rumus ditentukan EKSPLISIT oleh
     * user 2026-09-19 poin 2-4 ("field Jumlah merupakan hasil
     * perhitungan dari field Harga dikali field Kuantitas" pada
     * masing-masing grup, berlaku SAMA di TW-1 s.d. TW-4). Nilai kosong
     * (null) dianggap 0, hasilnya SELALU angka pasti - pola sama seperti
     * RincianBelanjaBarangHabisPakai::hitungTotalHarga().
     */
    public static function hitungJumlah(?int $harga, ?int $kuantitas): int
    {
        return (int) $harga * (int) $kuantitas;
    }

    /**
     * Kolom Kuantitas Saldo Akhir = Kuantitas Saldo Awal + Kuantitas
     * Penerimaan - Kuantitas Pengeluaran - rumus ditentukan EKSPLISIT
     * oleh user 2026-09-19 poin 5, berlaku SAMA di TW-1 s.d. TW-4. Nilai
     * kosong (null) dianggap 0, hasilnya SELALU angka pasti.
     */
    public static function hitungSaldoAkhirKuantitas(?int $saldoAwalKuantitas, ?int $penerimaanKuantitas, ?int $pengeluaranKuantitas): int
    {
        return (int) $saldoAwalKuantitas + (int) $penerimaanKuantitas - (int) $pengeluaranKuantitas;
    }

    /**
     * Kolom Jumlah (Rp) Saldo Akhir = Jumlah (Rp) Saldo Awal + Jumlah
     * (Rp) Penerimaan - Jumlah (Rp) Pengeluaran - rumus ditentukan
     * EKSPLISIT oleh user 2026-09-19 poin 6, berlaku SAMA di TW-1 s.d.
     * TW-4. Nilai kosong (null) dianggap 0, hasilnya SELALU angka pasti.
     */
    public static function hitungSaldoAkhirJumlah(?int $saldoAwalJumlah, ?int $penerimaanJumlah, ?int $pengeluaranJumlah): int
    {
        return (int) $saldoAwalJumlah + (int) $penerimaanJumlah - (int) $pengeluaranJumlah;
    }

    /**
     * Kolom Keterangan = "BOSP Triwulan {triwulan} Tahun {tahun}" -
     * ditentukan EKSPLISIT oleh user 2026-09-19 poin 8, berlaku SAMA di
     * TW-1 s.d. TW-4 (hanya angka triwulan & tahun yang berbeda). SELALU
     * dihitung dari triwulan+tahun baris itu SENDIRI (tidak pernah
     * berubah setelah baris dibuat, karena triwulan/tahun baris juga
     * tidak pernah berubah) - jadi CUKUP diisi SEKALI saat baris dibuat
     * (lihat RincianBelanjaBarangHabisPakai::booted() & migration
     * backfill), TIDAK PERLU dihitung ulang setiap render seperti
     * kolom Jumlah/Saldo Akhir di atas.
     */
    public static function keteranganOtomatis(int $triwulan, int $tahun): string
    {
        return 'BOSP Triwulan '.$triwulan.' Tahun '.$tahun;
    }

    /**
     * Menghitung ULANG SELURUH kolom rumus (FIELD_RUMUS) sekaligus dari
     * nilai manual TERKINI pada baris ini (Harga & ketiga Kuantitas
     * manual) - dipakai oleh Livewire Index setiap salah satu field
     * manual sumbernya berubah (baik lewat kotak tabel langsung maupun
     * Import Excel), supaya rumusnya SELALU dihitung dari SATU tempat
     * saja (single source of truth, pola sama seperti
     * LaporanRealisasiBosp::hitungVerifikasiSaldo() dkk).
     *
     * @return array{saldo_awal_jumlah: int, penerimaan_jumlah: int, pengeluaran_jumlah: int, saldo_akhir_kuantitas: int, saldo_akhir_jumlah: int}
     */
    public static function hitungSemuaRumus(?int $harga, ?int $saldoAwalKuantitas, ?int $penerimaanKuantitas, ?int $pengeluaranKuantitas): array
    {
        $saldoAwalJumlah = self::hitungJumlah($harga, $saldoAwalKuantitas);
        $penerimaanJumlah = self::hitungJumlah($harga, $penerimaanKuantitas);
        $pengeluaranJumlah = self::hitungJumlah($harga, $pengeluaranKuantitas);
        $saldoAkhirKuantitas = self::hitungSaldoAkhirKuantitas($saldoAwalKuantitas, $penerimaanKuantitas, $pengeluaranKuantitas);
        $saldoAkhirJumlah = self::hitungSaldoAkhirJumlah($saldoAwalJumlah, $penerimaanJumlah, $pengeluaranJumlah);

        return [
            'saldo_awal_jumlah' => $saldoAwalJumlah,
            'penerimaan_jumlah' => $penerimaanJumlah,
            'pengeluaran_jumlah' => $pengeluaranJumlah,
            'saldo_akhir_kuantitas' => $saldoAkhirKuantitas,
            'saldo_akhir_jumlah' => $saldoAkhirJumlah,
        ];
    }
}
