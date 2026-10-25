<?php

namespace App\Support;

use App\Livewire\PendataanBosp\FormulirBosK7\Index as FormulirBosK7Index;
use App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index as LaporanRealisasiBospIndex;
use App\Livewire\PendataanBosp\PajakBospReguler\Index as PajakBospRegulerIndex;
use App\Models\ProfilSekolah;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Pengambilan data untuk setiap sheet file gabungan "Unduhan LPJ BOSP"
 * (menu baru, permintaan user 2026-10-25) - pola query untuk 10 menu
 * "sederhana" di bawah ini PERSIS SAMA dengan method render() pada
 * Livewire Index masing-masing menu (dicek satu-satu 2026-10-25,
 * lihat docblock per method) - TIDAK ADA aturan bisnis baru yang
 * ditambahkan di sini, murni meniru query yang SUDAH ADA & SUDAH
 * BENAR, supaya hasil LPJ identik dengan tampilan on-screen tiap menu
 * untuk Triwulan+Tahun+Sekolah yang sama.
 *
 * Untuk Pajak BOSP Reguler & Laporan Realisasi BOSP (dua menu yang
 * logikanya jauh lebih kompleks - lihat docblock method masing-masing
 * di bawah), data diambil dengan memanggil LANGSUNG method publik milik
 * Livewire Index aslinya (App\Livewire\PendataanBosp\PajakBospReguler\
 * Index::dataPerSekolah()/dataRekap()/daftarField() & App\Livewire\
 * PendataanBosp\LaporanRealisasiBosp\Index::renderTabTriwulan() - KETIGA
 * method PajakBospReguler & SATU method LaporanRealisasiBosp ini baru
 * dinaikkan visibility-nya dari protected ke public 2026-10-25 KHUSUS
 * untuk dipakai di sini, TANPA mengubah isi/logikanya sama sekali) -
 * supaya TIDAK ADA logika yang diduplikasi/ditulis ulang untuk kedua
 * menu tersebut.
 *
 * $sekolahId = null berarti SEMUA SEKOLAH (1 tabel gabungan, urutan
 * ProfilSekolah::urutStandar() - Negeri dulu, lalu Kecamatan, lalu Nama
 * Sekolah), sesuai konvensi `?ProfilSekolah $sekolah = null` yang sudah
 * dipakai di semua Export class terkait.
 */
class LpjBospData
{
    /**
     * Helper generik untuk 10 menu "sederhana": ambil daftar sekolah
     * (sesuai $sekolahId, null = semua, urutan ProfilSekolah::urutStandar()),
     * lalu "ratakan" (flatMap) relasi anak-nya jadi 1 koleksi baris,
     * dengan relasi `profilSekolah` diisi manual di tiap baris (meniru
     * pola yang sudah dipakai di Export class terkait: `$baris->profilSekolah->...`).
     */
    private static function ambilRelasi(string $relasi, ?int $sekolahId, callable $filter, array $eagerTambahan = []): SupportCollection
    {
        $query = ProfilSekolah::with(array_merge([$relasi => $filter], $eagerTambahan));

        if ($sekolahId) {
            $query->where('id', $sekolahId);
        }

        return $query->urutStandar()->get()
            ->flatMap(function (ProfilSekolah $sekolah) use ($relasi) {
                return $sekolah->{$relasi}->each(
                    fn ($baris) => $baris->setRelation('profilSekolah', $sekolah)
                );
            })
            ->values();
    }

    /** Lihat App\Livewire\PendataanBosp\PenerimaanHonorPtk\Index::render(). */
    public static function penerimaanHonorPtk(int $triwulan, int $tahun, ?int $sekolahId): SupportCollection
    {
        return self::ambilRelasi('penerimaanHonorPtk', $sekolahId, function ($q) use ($triwulan, $tahun) {
            $q->where('tahun', $tahun)->where('triwulan', $triwulan)->orderBy('nama_penerima');
        });
    }

    /** Lihat App\Livewire\PendataanBosp\LanggananDayaJasa\Index::render(). */
    public static function langgananDayaJasa(int $triwulan, int $tahun, ?int $sekolahId): SupportCollection
    {
        return self::ambilRelasi('langgananDayaJasa', $sekolahId, function ($q) use ($triwulan, $tahun) {
            $q->where('tahun', $tahun)->where('triwulan', $triwulan)->orderBy('uraian_pembayaran');
        });
    }

    /** Lihat App\Livewire\PendataanBosp\BelanjaPemeliharaanBangunan\Index::render() (tab jenis Barang/Jasa). */
    public static function rincianPemeliharaan(string $jenis, int $triwulan, int $tahun, ?int $sekolahId): SupportCollection
    {
        return self::ambilRelasi('rincianPemeliharaan', $sekolahId, function ($q) use ($jenis, $triwulan, $tahun) {
            $q->where('jenis', $jenis)->where('tahun', $tahun)->where('triwulan', $triwulan)->orderBy('nama_barang');
        });
    }

    /** Lihat App\Livewire\PendataanBosp\BelanjaPemeliharaanPc\Index::render() (tab jenis Barang/Jasa). */
    public static function rincianPemeliharaanPc(string $jenis, int $triwulan, int $tahun, ?int $sekolahId): SupportCollection
    {
        return self::ambilRelasi('rincianPemeliharaanPc', $sekolahId, function ($q) use ($jenis, $triwulan, $tahun) {
            $q->where('jenis', $jenis)->where('tahun', $tahun)->where('triwulan', $triwulan)->orderBy('nama_barang');
        });
    }

    /** Lihat App\Livewire\PendataanBosp\BiayaPendaftaranLomba\Index::render(). */
    public static function biayaPendaftaranLomba(int $triwulan, int $tahun, ?int $sekolahId): SupportCollection
    {
        return self::ambilRelasi('biayaPendaftaranLomba', $sekolahId, function ($q) use ($triwulan, $tahun) {
            $q->where('tahun', $tahun)->where('triwulan', $triwulan)->orderBy('uraian');
        });
    }

    /** Lihat App\Livewire\PendataanBosp\BelanjaHonorKegiatan\Index::render() (tab jenis Honor Kegiatan/Makan Minum/Perjalanan Dinas). */
    public static function belanjaHonorKegiatan(string $jenis, int $triwulan, int $tahun, ?int $sekolahId): SupportCollection
    {
        return self::ambilRelasi('belanjaHonorKegiatan', $sekolahId, function ($q) use ($jenis, $triwulan, $tahun) {
            $q->where('jenis', $jenis)->where('tahun', $tahun)->where('triwulan', $triwulan)->orderBy('uraian');
        });
    }

    /** Lihat App\Livewire\PendataanBosp\RincianBelanjaModal\Index::render() (tab jenis Peralatan&Mesin KIB B/Aset Tetap Lainnya KIB E). */
    public static function rincianBelanjaModal(string $jenis, int $triwulan, int $tahun, ?int $sekolahId): SupportCollection
    {
        return self::ambilRelasi('rincianBelanjaModal', $sekolahId, function ($q) use ($jenis, $triwulan, $tahun) {
            $q->where('jenis', $jenis)->where('tahun', $tahun)->where('triwulan', $triwulan)->orderBy('nama_barang');
        });
    }

    /** Lihat App\Livewire\PendataanBosp\RincianBelanjaModal\Index::renderBmd() (tab BMD, terpisah dari jenis KIB B/E). */
    public static function rincianBelanjaModalBmd(int $triwulan, int $tahun, ?int $sekolahId): SupportCollection
    {
        return self::ambilRelasi('rincianBelanjaModalBmd', $sekolahId, function ($q) use ($triwulan, $tahun) {
            $q->where('tahun', $tahun)->where('triwulan', $triwulan)->orderBy('nama_barang');
        });
    }

    /** Lihat App\Livewire\PendataanBosp\RincianBelanjaBarangHabisPakai\Index::render() (tab Rincian Belanja Barang Habis Pakai). */
    public static function rincianBelanjaBarangHabisPakai(int $triwulan, int $tahun, ?int $sekolahId): SupportCollection
    {
        return self::ambilRelasi('rincianBelanjaBarangHabisPakai', $sekolahId, function ($q) use ($triwulan, $tahun) {
            $q->where('tahun', $tahun)->where('triwulan', $triwulan)->orderBy('nama_barang');
        });
    }

    /** Lihat App\Livewire\PendataanBosp\RincianBelanjaBarangHabisPakai\Index::render() (tab Stock Opname, termasuk relasi nested rincianBelanjaBarangHabisPakai supaya kolom "Nama Barang Persediaan" terisi tanpa N+1). */
    public static function stockOpnameBarangPersediaan(int $triwulan, int $tahun, ?int $sekolahId): SupportCollection
    {
        return self::ambilRelasi(
            'stockOpnameBarangPersediaan',
            $sekolahId,
            function ($q) use ($triwulan, $tahun) {
                $q->where('tahun', $tahun)->where('triwulan', $triwulan)->orderBy('id');
            },
            ['stockOpnameBarangPersediaan.rincianBelanjaBarangHabisPakai']
        );
    }

    /**
     * Data Pajak BOSP Reguler untuk 1 sekolah (TAHUNAN - menu ini TIDAK
     * berbasis triwulan, lihat docblock App\Exports\PajakBospRegulerExport)
     * - memanggil LANGSUNG dataPerSekolah()/daftarField() milik
     * App\Livewire\PendataanBosp\PajakBospReguler\Index.
     *
     * @return array{sekolah: ?ProfilSekolah, baris: array, jumlahPerBulan: array, saldoPerBulan: array, triwulanData: array, totalRaw: array, totalJumlahDebit: int, totalJumlahKredit: int, saldoAkhir: int}
     */
    public static function pajakPerSekolah(int $tahun, ?int $sekolahId): array
    {
        $index = new PajakBospRegulerIndex();
        $index->tahun = $tahun;
        $index->profil_sekolah_id = $sekolahId;

        $data = $index->dataPerSekolah();
        $data['baris'] = $index->baris;

        return $data;
    }

    /** Data rekap Pajak BOSP Reguler (Semua Sekolah, TAHUNAN) - lihat PajakBospReguler\Index::dataRekap(). */
    public static function pajakRekap(int $tahun): SupportCollection
    {
        $index = new PajakBospRegulerIndex();
        $index->tahun = $tahun;

        return $index->dataRekap()['rekapSekolah'];
    }

    /**
     * Data Laporan Realisasi BOSP (Form BPK) untuk 1 triwulan - lihat
     * LaporanRealisasiBosp\Index::renderTabTriwulan(). Kalau $sekolahId
     * diisi, daftar sekolah hasil method aslinya (yang untuk Superadmin
     * selalu berisi SEMUA sekolah - method aslinya TIDAK diubah) disaring
     * jadi 1 sekolah SAJA DI SINI; $totalBaris tidak perlu disaring
     * karena LaporanRealisasiBospTriwulanSheet hanya membaca entry
     * sekolah yang ada di $daftarSekolah.
     *
     * @return array{0: SupportCollection, 1: array}
     */
    public static function laporanRealisasiBosp(int $triwulan, int $tahun, ?int $sekolahId): array
    {
        $index = new LaporanRealisasiBospIndex();
        $index->tahun = $tahun;

        [$daftarSekolah, $totalBaris] = $index->renderTabTriwulan($triwulan);

        if ($sekolahId) {
            $daftarSekolah = $daftarSekolah->where('id', $sekolahId)->values();
        }

        return [$daftarSekolah, $totalBaris];
    }

    /**
     * Data 3 bulan (1 triwulan) untuk Formulir BOS K7b/K7c - HANYA
     * dipakai saat ada 1 sekolah spesifik (BUKAN mode Semua Sekolah -
     * dikonfirmasi lewat AskUserQuestion 2026-10-25: "Dikosongkan/
     * dilewati saat mode Semua Sekolah") - memanggil LANGSUNG
     * dataFormulir()/pengaturanCetak() milik App\Livewire\PendataanBosp\
     * FormulirBosK7\Index (baru dinaikkan ke public 2026-10-25) untuk
     * tiap bulan dalam triwulan tersebut. Pemetaan Triwulan -> 3 bulan
     * (bulan 1-3 TW1, 4-6 TW2, 7-9 TW3, 10-12 TW4) SUDAH ADA & dipakai
     * App\Livewire\PendataanBosp\PajakBospReguler\Index::triwulanDariBulan()
     * (standar kuartal kalender, bukan aturan baru).
     *
     * @return array<int, array{sekolah: ?ProfilSekolah, tahun: int, bulan: int, data: array, pengaturanCetak: array}>
     */
    public static function formulirBosK7PerTriwulan(int $triwulan, int $tahun, int $sekolahId): array
    {
        $bulanAwal = ($triwulan - 1) * 3 + 1;
        $sekolah = ProfilSekolah::find($sekolahId);
        $hasil = [];

        for ($bulan = $bulanAwal; $bulan < $bulanAwal + 3; $bulan++) {
            $index = new FormulirBosK7Index();
            $index->tahun = $tahun;
            $index->bulan = $bulan;
            $index->profil_sekolah_id = $sekolahId;

            $hasil[] = [
                'sekolah' => $sekolah,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'data' => $index->dataFormulir(),
                'pengaturanCetak' => $index->pengaturanCetak(),
            ];
        }

        return $hasil;
    }
}
