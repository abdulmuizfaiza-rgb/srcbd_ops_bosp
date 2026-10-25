<?php

namespace App\Exports;

use App\Exports\Lpj\BelanjaHonorKegiatanLpjSheet;
use App\Exports\Lpj\BiayaPendaftaranLombaLpjSheet;
use App\Exports\Lpj\FormulirBosK7bLpjSheet;
use App\Exports\Lpj\FormulirBosK7cLpjSheet;
use App\Exports\Lpj\LanggananDayaJasaLpjSheet;
use App\Exports\Lpj\LaporanRealisasiBospTriwulanLpjSheet;
use App\Exports\Lpj\PajakBospRegulerLpjSheet;
use App\Exports\Lpj\PajakBospRegulerRekapLpjSheet;
use App\Exports\Lpj\PenerimaanHonorPtkLpjSheet;
use App\Exports\Lpj\RincianBelanjaBarangHabisPakaiLpjSheet;
use App\Exports\Lpj\RincianBelanjaModalBmdLpjSheet;
use App\Exports\Lpj\RincianBelanjaModalLpjSheet;
use App\Exports\Lpj\RincianPemeliharaanLpjSheet;
use App\Exports\Lpj\RincianPemeliharaanPcLpjSheet;
use App\Exports\Lpj\StockOpnameBarangPersediaanLpjSheet;
use App\Models\BelanjaHonorKegiatan;
use App\Models\ProfilSekolah;
use App\Models\RincianBelanjaModal;
use App\Models\RincianPemeliharaan;
use App\Models\RincianPemeliharaanPc;
use App\Support\LpjBospData;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * File gabungan "LPJ BOSP Triwulan {n} Tahun {y}_SMP {nama sekolah|
 * Semua Sekolah}" - menu baru "Unduhan LPJ BOSP" (permintaan user
 * 2026-10-25). Urutan sheet 1-19 PERSIS seperti permintaan user:
 *
 * 1. Penerimaan Honor PTK
 * 2. Langganan Daya dan Jasa
 * 3. Rincian Pemeliharaan Bangunan (jenis Barang)
 * 4. Rincian Jasa Pemeliharaan (jenis Jasa, Bangunan)
 * 5. Rincian Pemeliharaan PC Komputer-Laptop-Printer dll (jenis Barang)
 * 6. Rincian Jasa Pemeliharaan PC-Laptop-Printer dll (jenis Jasa)
 * 7. Biaya Pendaftaran Lomba/Bimtek/Workshop
 * 8. Belanja Honor Kegiatan
 * 9. Makan Minum Kegiatan
 * 10. Perjalanan Dinas
 * 11. Rincian Belanja Modal Peralatan & Mesin (KIB B)
 * 12. Rincian Belanja Modal Aset Tetap Lainnya (KIB E)
 * 13. BMD
 * 14. Rincian Belanja Barang Habis Pakai
 * 15. Stock Opname
 * 16. Pajak BOSP Reguler (per-sekolah -> PajakBospRegulerExport, kolom
 *     sesuai tampilan 1 sekolah; Semua Sekolah -> PajakBospRegulerRekapExport,
 *     format kolom khusus sesuai jawaban AskUserQuestion 2026-10-25:
 *     No, Nama Sekolah, Penerimaan [PPN/PPh21/PPh23/PPh4/SSPD/Jumlah],
 *     Pengeluaran [sama], Saldo, Keterangan)
 * 17. Laporan Realisasi BOSP (Form BPK)
 * 18. Formulir BOS K7b (1 sheet, 3 blok bulan dalam triwulan terpilih,
 *     page break di antaranya - HANYA saat 1 sekolah dipilih)
 * 19. Formulir BOS K7c (sama seperti K7b)
 *
 * SETIAP sheet memakai ULANG kelas Export per-menu yang SUDAH ADA & SUDAH
 * BENAR (data/kolom/border persis sama dengan unduhan satuan menu
 * masing-masing) - dibungkus lewat kelas kecil di App\Exports\Lpj yang
 * HANYA menambah blok judul LPJ (Triwulan/Tahun Anggaran/Nama
 * Sekolah/Kecamatan/Sub Rayon : Cibadak) & page setup Legal/Landscape
 * di atasnya (lihat App\Exports\Lpj\Concerns\TerapkanJudulLpj) - TIDAK
 * mengubah kelas aslinya sama sekali (kecuali FormulirBosK7b/cSheetExport
 * yang menerima 1 parameter baru $barisAwal berdefault 1, lihat
 * docblock-nya - unduhan 1 bulan yang sudah ada TIDAK berubah perilaku).
 *
 * $sekolah = null berarti Superadmin memilih "Semua Sekolah" - sheet
 * Formulir BOS K7b/K7c DILEWATI sama sekali pada mode ini (dikonfirmasi
 * lewat AskUserQuestion 2026-10-25), sheet lainnya menampilkan tabel
 * gabungan seluruh sekolah (konvensi `?ProfilSekolah $sekolah = null`
 * yang sudah dipakai di semua Export class terkait).
 */
class LpjBospExport implements WithMultipleSheets
{
    public function __construct(
        protected int $triwulan,
        protected int $tahun,
        protected ?ProfilSekolah $sekolah,
    ) {}

    public function sheets(): array
    {
        $tw = $this->triwulan;
        $th = $this->tahun;
        $sekolah = $this->sekolah;
        $sekolahId = $sekolah?->id;

        $sheets = [];

        $sheets[] = new PenerimaanHonorPtkLpjSheet(
            'Penerimaan Honor PTK', $tw, $th, $sekolah, 'I',
            LpjBospData::penerimaanHonorPtk($tw, $th, $sekolahId), $tw, $th, $sekolah,
        );

        $sheets[] = new LanggananDayaJasaLpjSheet(
            'Langganan Daya dan Jasa', $tw, $th, $sekolah, 'H',
            LpjBospData::langgananDayaJasa($tw, $th, $sekolahId), $tw, $th, $sekolah,
        );

        $sheets[] = new RincianPemeliharaanLpjSheet(
            'Rincian Pemeliharaan Bangunan', $tw, $th, $sekolah, 'L',
            LpjBospData::rincianPemeliharaan(RincianPemeliharaan::JENIS_BARANG, $tw, $th, $sekolahId), RincianPemeliharaan::JENIS_BARANG, $tw, $th, $sekolah,
        );

        $sheets[] = new RincianPemeliharaanLpjSheet(
            'Rincian Jasa Pemeliharaan', $tw, $th, $sekolah, 'L',
            LpjBospData::rincianPemeliharaan(RincianPemeliharaan::JENIS_JASA, $tw, $th, $sekolahId), RincianPemeliharaan::JENIS_JASA, $tw, $th, $sekolah,
        );

        $sheets[] = new RincianPemeliharaanPcLpjSheet(
            'Rincian Pemeliharaan PC Komputer-Laptop-Printer dll', $tw, $th, $sekolah, 'L',
            LpjBospData::rincianPemeliharaanPc(RincianPemeliharaanPc::JENIS_BARANG, $tw, $th, $sekolahId), RincianPemeliharaanPc::JENIS_BARANG, $tw, $th, $sekolah,
        );

        $sheets[] = new RincianPemeliharaanPcLpjSheet(
            'Rincian Jasa Pemeliharaan PC-Laptop-Printer dll', $tw, $th, $sekolah, 'L',
            LpjBospData::rincianPemeliharaanPc(RincianPemeliharaanPc::JENIS_JASA, $tw, $th, $sekolahId), RincianPemeliharaanPc::JENIS_JASA, $tw, $th, $sekolah,
        );

        $sheets[] = new BiayaPendaftaranLombaLpjSheet(
            'Biaya Pendaftaran Lomba/Bimtek/Workshop', $tw, $th, $sekolah, 'H',
            LpjBospData::biayaPendaftaranLomba($tw, $th, $sekolahId), $tw, $th, $sekolah,
        );

        $sheets[] = new BelanjaHonorKegiatanLpjSheet(
            'Belanja Honor Kegiatan', $tw, $th, $sekolah, 'H',
            LpjBospData::belanjaHonorKegiatan(BelanjaHonorKegiatan::JENIS_HONOR_KEGIATAN, $tw, $th, $sekolahId), BelanjaHonorKegiatan::JENIS_HONOR_KEGIATAN, $tw, $th, $sekolah,
        );

        $sheets[] = new BelanjaHonorKegiatanLpjSheet(
            'Makan Minum Kegiatan', $tw, $th, $sekolah, 'H',
            LpjBospData::belanjaHonorKegiatan(BelanjaHonorKegiatan::JENIS_MAKAN_MINUM, $tw, $th, $sekolahId), BelanjaHonorKegiatan::JENIS_MAKAN_MINUM, $tw, $th, $sekolah,
        );

        $sheets[] = new BelanjaHonorKegiatanLpjSheet(
            'Perjalanan Dinas', $tw, $th, $sekolah, 'H',
            LpjBospData::belanjaHonorKegiatan(BelanjaHonorKegiatan::JENIS_PERJALANAN_DINAS, $tw, $th, $sekolahId), BelanjaHonorKegiatan::JENIS_PERJALANAN_DINAS, $tw, $th, $sekolah,
        );

        $sheets[] = new RincianBelanjaModalLpjSheet(
            'Rincian Belanja Modal Peralatan & Mesin (KIB B)', $tw, $th, $sekolah, 'L',
            LpjBospData::rincianBelanjaModal(RincianBelanjaModal::JENIS_PERALATAN_MESIN, $tw, $th, $sekolahId), RincianBelanjaModal::JENIS_PERALATAN_MESIN, $tw, $th, $sekolah,
        );

        $sheets[] = new RincianBelanjaModalLpjSheet(
            'Rincian Belanja Modal Aset Tetap Lainnya (KIB E)', $tw, $th, $sekolah, 'L',
            LpjBospData::rincianBelanjaModal(RincianBelanjaModal::JENIS_ASET_TETAP_LAINNYA, $tw, $th, $sekolahId), RincianBelanjaModal::JENIS_ASET_TETAP_LAINNYA, $tw, $th, $sekolah,
        );

        $sheets[] = new RincianBelanjaModalBmdLpjSheet(
            'BMD', $tw, $th, $sekolah, 'AI',
            LpjBospData::rincianBelanjaModalBmd($tw, $th, $sekolahId), $tw, $th, $sekolah,
        );

        $sheets[] = new RincianBelanjaBarangHabisPakaiLpjSheet(
            'Rincian Belanja Barang Habis Pakai', $tw, $th, $sekolah, 'L',
            LpjBospData::rincianBelanjaBarangHabisPakai($tw, $th, $sekolahId), $tw, $th, $sekolah,
        );

        $sheets[] = new StockOpnameBarangPersediaanLpjSheet(
            'Stock Opname', $tw, $th, $sekolah, 'O',
            LpjBospData::stockOpnameBarangPersediaan($tw, $th, $sekolahId), $tw, $th, $sekolah,
        );

        if ($sekolah !== null) {
            $pajak = LpjBospData::pajakPerSekolah($th, $sekolahId);
            $sheets[] = new PajakBospRegulerLpjSheet(
                'Pajak BOSP Reguler', $tw, $th, $sekolah, 'O',
                $sekolah, $th, $pajak['baris'], $pajak['jumlahPerBulan'], $pajak['saldoPerBulan'],
                $pajak['triwulanData'], $pajak['totalRaw'], $pajak['totalJumlahDebit'],
                $pajak['totalJumlahKredit'], $pajak['saldoAkhir'],
            );
        } else {
            $sheets[] = new PajakBospRegulerRekapLpjSheet(
                'Pajak BOSP Reguler', $tw, $th, null, 'P',
                LpjBospData::pajakRekap($th), $th,
            );
        }

        [$daftarSekolahRealisasi, $totalBarisRealisasi] = LpjBospData::laporanRealisasiBosp($tw, $th, $sekolahId);
        $sheets[] = new LaporanRealisasiBospTriwulanLpjSheet(
            'Laporan Realisasi BOSP (Form BPK)', $tw, $th, $sekolah, 'Y',
            $daftarSekolahRealisasi, $totalBarisRealisasi, $tw,
        );

        // Formulir BOS K7b/K7c: HANYA saat 1 sekolah spesifik dipilih -
        // dilewati sama sekali pada mode Semua Sekolah (dikonfirmasi
        // lewat AskUserQuestion 2026-10-25).
        if ($sekolahId) {
            $dataPerBulan = LpjBospData::formulirBosK7PerTriwulan($tw, $th, $sekolahId);

            $sheets[] = new FormulirBosK7bLpjSheet($dataPerBulan, 'Formulir BOS K7b', $tw, $th, $sekolah, 'F');
            $sheets[] = new FormulirBosK7cLpjSheet($dataPerBulan, 'Formulir BOS K7c', $tw, $th, $sekolah, 'F');
        }

        return $sheets;
    }
}
