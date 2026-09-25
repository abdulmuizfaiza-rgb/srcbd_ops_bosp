<?php

namespace App\Exports;

use App\Models\RincianBelanjaModalBmd;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export sub-tab "Rekap BMD Tahun Anggaran {tahun}" (permintaan user
 * 2026-09-22, jawaban AskUserQuestion "Hanya tampil & export
 * (Recommended)") - REKAP data BMD TW-1 s.d. TW-4 tahun berjalan dalam
 * SATU file Excel, urutan baris SAMA PERSIS dengan tampilan di aplikasi
 * (App\Livewire\PendataanBosp\RincianBelanjaModal\Index::renderRekapBmd()):
 * Triwulan -> Negeri/Swasta -> Nama Sekolah (A-Z) -> Tgl. Perolehan.
 *
 * SENGAJA class TERPISAH & MANDIRI (BUKAN extends
 * RincianBelanjaModalBmdExport) - kolom "Triwulan" ditambahkan sebagai
 * kolom PALING AWAL (kolom A), yang akan MENGGESER seluruh 35 kolom BMD
 * lainnya satu huruf ke kanan (B=NPSN ... AJ=Keterangan/BOSP) sehingga
 * SELURUH konstanta kolom di RincianBelanjaModalBmdExport tidak berlaku
 * lagi di sini - mewarisi class itu & menimpa konstantanya jauh lebih
 * berisiko/rawan salah dibanding menulis ulang sendiri, sesuai konvensi
 * yang sudah ada di codebase ini (RincianBelanjaModalExport vs
 * RincianBelanjaModalBmdExport juga class TERPISAH meski strukturnya
 * mirip).
 *
 * BEDA lain dengan RincianBelanjaModalBmdExport (export per-Triwulan):
 * - TIDAK ADA dropdown Data Validation & TIDAK ADA formula Excel sama
 *   sekali (VLOOKUP/perkalian) - file Rekap ini MURNI laporan/snapshot
 *   untuk dilihat/dicetak/diarsipkan, TIDAK PERNAH di-Import kembali ke
 *   aplikasi (jawaban AskUserQuestion "Hanya tampil & export" - tidak
 *   ada Import utk sub-tab Rekap), jadi seluruh nilai (termasuk Jenis
 *   Aset/Total/PPK/Keterangan) ditulis APA ADANYA dari database, bukan
 *   formula yang bisa berubah kalau diedit manual di Excel.
 * - TIDAK ADA baris buffer kosong tambahan di bawah data (beda dengan
 *   export per-Triwulan yang sengaja menyediakan baris kosong siap-isi
 *   utk baris baru) - Rekap ini read-only, tidak ada skenario "tambah
 *   baris baru langsung di Excel Rekap".
 * - TIDAK ADA lembar tanda tangan Bendahara BOSP/Kepala Sekolah (SAMA
 *   seperti export per-Triwulan sejak 2026-09-22 - lihat
 *   RincianBelanjaModalBmdExport).
 * - Tanggal (Tgl. Perolehan/Tgl BAST/Tgl. Surat Pernyataan) TETAP
 *   ditulis sebagai tanggal Excel sungguhan (Date::PHPToExcel(), format
 *   "dd-mm-yyyy") - pola sama seperti export per-Triwulan, murni supaya
 *   bisa diurutkan/dibaca sebagai tanggal saat dibuka di Excel.
 * - Warna kuning (sama seperti kotak "otomatis/read-only" di aplikasi)
 *   TETAP diterapkan pada kolom yang otomatis dari relasi/database/
 *   rumus (NPSN/Lokasi/Subrayon/PPK/Program/Kegiatan/Kode Sub Kegiatan/
 *   Nama Sub Kegiatan/Jenis Aset/Total/kedua Keterangan) - MURNI visual,
 *   TIDAK ada dropdown/formula di baliknya (lihat poin di atas).
 */
class RincianBelanjaModalBmdRekapExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_HEADER_TABEL = 3;

    private const KOLOM_TERAKHIR = 'AJ';

    // Kolom otomatis dari relasi profilSekolah()/database (highlight kuning).
    private const KOLOM_TRIWULAN = 'A';

    private const KOLOM_NPSN = 'B';

    private const KOLOM_LOKASI = 'C';

    private const KOLOM_SUBRAYON = 'D';

    private const KOLOM_PROGRAM = 'F';

    private const KOLOM_KEGIATAN = 'G';

    private const KOLOM_KODE_SUB_KEGIATAN = 'H';

    private const KOLOM_NAMA_SUB_KEGIATAN = 'I';

    private const KOLOM_PPK = 'L';

    private const KOLOM_JENIS_ASET = 'R';

    private const KOLOM_HARGA_SATUAN = 'V';

    private const KOLOM_TOTAL = 'W';

    private const KOLOM_KETERANGAN_BOS = 'Z';

    private const KOLOM_KETERANGAN_BOSP = 'AJ';

    // Kolom tanggal - ditulis sebagai tanggal Excel sungguhan.
    private const KOLOM_TGL_PEROLEHAN = 'N';

    private const KOLOM_TGL_BAST = 'Y';

    private const KOLOM_TGL_SURAT_PERNYATAAN = 'AB';

    /**
     * @param  SupportCollection<int, RincianBelanjaModalBmd>  $baris
     */
    public function __construct(
        protected SupportCollection $baris,
        protected int $tahun,
    ) {}

    public function collection(): SupportCollection
    {
        return $this->baris;
    }

    public function startCell(): string
    {
        return 'A'.self::BARIS_HEADER_TABEL;
    }

    public function title(): string
    {
        return 'Rekap BMD '.$this->tahun;
    }

    public function headings(): array
    {
        return [
            'Triwulan',
            'NPSN',
            'Lokasi',
            'Subrayon',
            'Bentuk Kontrak / Transaksi',
            'Program',
            'Kegiatan',
            'Kode Sub Kegiatan',
            'Nama Sub Kegiatan',
            'Atribusi',
            'Jumlah Termin',
            'PPK',
            'Nomor Dokumen',
            'Tgl. Perolehan',
            'Penyedia',
            'Kode Belanja',
            'Rekening Belanja',
            'Jenis Aset (KIB)',
            'Sub Sub Rincian Objek',
            'Jumlah',
            'Satuan',
            'Harga Satuan',
            'Total',
            'No BAST',
            'Tgl BAST',
            'Keterangan',
            'Nomor Surat Pernyataan',
            'Tgl. Surat Pernyataan',
            'Nama Pengurus Barang',
            'Jabatan',
            'Pejabat Penata Usaha',
            'Nama Barang',
            'Spesifikasi Nama Barang',
            'Spesifikasi Lain (Serial Nomor)',
            'Merk/Pengarang',
            'Keterangan',
        ];
    }

    public function map($baris): array
    {
        return [
            RincianBelanjaModalBmd::TRIWULAN_OPTIONS[$baris->triwulan] ?? $baris->triwulan,
            $baris->profilSekolah->npsn ?? null,
            $baris->profilSekolah->nama_sekolah ?? null,
            $baris->profilSekolah->subrayon ?? null,
            $baris->bentuk_kontrak,
            RincianBelanjaModalBmd::PROGRAM,
            RincianBelanjaModalBmd::KEGIATAN,
            RincianBelanjaModalBmd::KODE_SUB_KEGIATAN,
            RincianBelanjaModalBmd::NAMA_SUB_KEGIATAN,
            $baris->atribusi,
            $baris->jumlah_termin,
            $baris->ppk,
            $baris->nomor_dokumen,
            $baris->tanggal_perolehan ? Date::PHPToExcel($baris->tanggal_perolehan) : null,
            $baris->penyedia,
            $baris->kode_belanja,
            $baris->rekening_belanja,
            $baris->jenis_aset,
            $baris->sub_sub_rincian_objek,
            $baris->jumlah,
            $baris->satuan,
            $baris->harga_satuan,
            $baris->total,
            $baris->no_bast,
            $baris->tanggal_bast ? Date::PHPToExcel($baris->tanggal_bast) : null,
            $baris->keterangan_bos,
            $baris->nomor_surat_pernyataan,
            $baris->tanggal_surat_pernyataan ? Date::PHPToExcel($baris->tanggal_surat_pernyataan) : null,
            $baris->nama_pengurus_barang,
            $baris->jabatan,
            $baris->pejabat_penata_usaha,
            $baris->nama_barang,
            $baris->spesifikasi_nama_barang,
            $baris->spesifikasi_lain,
            $baris->merk_pengarang,
            $baris->keterangan_bosp,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->tulisJudul($sheet);
                $this->rapikanTabel($sheet);
                $this->terapkanFormat($sheet);
            },
        ];
    }

    private function tulisJudul(Worksheet $sheet): void
    {
        $sheet->setCellValue('A1', 'DAFTAR BELANJA MODAL TAHUN ANGGARAN '.$this->tahun);
        $sheet->setCellValue('A2', 'REKAP TRIWULAN 1 - TRIWULAN 4');

        foreach ([1, 2] as $baris) {
            $rentang = 'A'.$baris.':'.self::KOLOM_TERAKHIR.$baris;
            $sheet->mergeCells($rentang);
            $sheet->getStyle($rentang)->applyFromArray([
                'font' => ['bold' => true, 'size' => $baris === 1 ? 12 : 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }
    }

    private function barisTerakhirTabel(): int
    {
        return self::BARIS_HEADER_TABEL + $this->baris->count();
    }

    private function rapikanTabel(Worksheet $sheet): void
    {
        $barisTerakhir = $this->barisTerakhirTabel();
        $rentangHeader = 'A'.self::BARIS_HEADER_TABEL.':'.self::KOLOM_TERAKHIR.self::BARIS_HEADER_TABEL;

        $sheet->getStyle($rentangHeader)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        if ($barisTerakhir >= self::BARIS_HEADER_TABEL + 1) {
            $sheet->getStyle('A'.self::BARIS_HEADER_TABEL.':'.self::KOLOM_TERAKHIR.$barisTerakhir)
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        $sheet->freezePane('A'.(self::BARIS_HEADER_TABEL + 1));
        $sheet->setAutoFilter($rentangHeader);
        $sheet->getRowDimension(self::BARIS_HEADER_TABEL)->setRowHeight(20);
    }

    /**
     * Format tampilan (warna kuning kolom otomatis, format Rupiah, format
     * tanggal) HANYA pada rentang baris DATA (TIDAK ada baris buffer
     * tambahan di export ini - lihat docblock kelas).
     */
    private function terapkanFormat(Worksheet $sheet): void
    {
        $barisMulai = self::BARIS_HEADER_TABEL + 1;
        $barisSelesai = $this->barisTerakhirTabel();

        if ($barisSelesai < $barisMulai) {
            return;
        }

        foreach ([self::KOLOM_HARGA_SATUAN, self::KOLOM_TOTAL] as $kolom) {
            $sheet->getStyle($kolom.$barisMulai.':'.$kolom.$barisSelesai)
                ->getNumberFormat()->setFormatCode('"Rp" #,##0');
        }

        foreach ([self::KOLOM_TGL_PEROLEHAN, self::KOLOM_TGL_BAST, self::KOLOM_TGL_SURAT_PERNYATAAN] as $kolom) {
            $sheet->getStyle($kolom.$barisMulai.':'.$kolom.$barisSelesai)
                ->getNumberFormat()->setFormatCode('dd-mm-yyyy');
        }

        $kolomOtomatis = [
            self::KOLOM_NPSN, self::KOLOM_LOKASI, self::KOLOM_SUBRAYON,
            self::KOLOM_PROGRAM, self::KOLOM_KEGIATAN, self::KOLOM_KODE_SUB_KEGIATAN, self::KOLOM_NAMA_SUB_KEGIATAN,
            self::KOLOM_PPK, self::KOLOM_JENIS_ASET, self::KOLOM_TOTAL, self::KOLOM_KETERANGAN_BOS, self::KOLOM_KETERANGAN_BOSP,
        ];
        foreach ($kolomOtomatis as $kolom) {
            $sheet->getStyle($kolom.$barisMulai.':'.$kolom.$barisSelesai)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
            ]);
        }
    }
}
