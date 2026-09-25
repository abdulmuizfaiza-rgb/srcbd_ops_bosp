<?php

namespace App\Exports;

use App\Models\ProfilSekolah;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export data Stock Opname (Rincian Barang Persediaan BOSP) - satu
 * triwulan, satu sekolah - ke Excel. Layout SAMA PERSIS seperti
 * RincianBelanjaBarangHabisPakaiExport, hanya kolomnya disesuaikan dengan
 * gambar contoh tabel "STOCK OPNAME RINCIAN BARANG PERSEDIAAN BOSP TAHUN
 * ANGGARAN" yang diupload user 2026-09-18.
 *
 * Urutan kolom data mengikuti urutan pada gambar contoh (tanpa kolom
 * "No", sama seperti export menu lain): NPSN, Nama Sekolah, Subrayon,
 * Nama Barang Persediaan, Satuan (Unit), Harga, Saldo Awal
 * (Kuantitas/Jumlah), Penerimaan (Kuantitas/Jumlah), Pengeluaran
 * (Kuantitas/Jumlah), Saldo Akhir (Kuantitas/Jumlah), Keterangan.
 *
 * PENTING (permintaan user 2026-09-23): lembar tanda tangan "Mengetahui/
 * Menyetujui" Bendahara BOSP/Kepala Sekolah SUDAH DIHAPUS - hanya judul
 * tabel (tulisJudul()) yang dimunculkan. Syarat pilih 1 sekolah (kalau
 * ada di Livewire Index) SENGAJA TIDAK diubah (jawaban AskUserQuestion
 * 2026-09-23: "Tetap dipertahankan").
 */
class StockOpnameBarangPersediaanExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_HEADER_TABEL = 5;

    private const BARIS_KOSONG_SEBELUM_TANDA_TANGAN = 4;

    private const KOLOM_TERAKHIR = 'O';

    /**
     * @param  SupportCollection<int, \App\Models\StockOpnameBarangPersediaan>  $baris
     */
    public function __construct(
        protected SupportCollection $baris,
        protected int $triwulan,
        protected int $tahun,
        protected ?ProfilSekolah $sekolah = null,
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
        return 'Stock Opname TW'.$this->triwulan;
    }

    public function headings(): array
    {
        return [
            'NPSN',
            'Nama Sekolah',
            'Subrayon',
            'Nama Barang Persediaan',
            'Satuan (Unit)',
            'Harga',
            'Saldo Awal - Kuantitas',
            'Saldo Awal - Jumlah (Rp)',
            'Penerimaan - Kuantitas',
            'Penerimaan - Jumlah (Rp)',
            'Pengeluaran - Kuantitas',
            'Pengeluaran - Jumlah (Rp)',
            'Saldo Akhir - Kuantitas',
            'Saldo Akhir - Jumlah (Rp)',
            'Keterangan',
        ];
    }

    public function map($baris): array
    {
        return [
            $baris->profilSekolah->npsn ?? $this->sekolah?->npsn,
            $baris->profilSekolah->nama_sekolah ?? $this->sekolah?->nama_sekolah,
            $baris->profilSekolah->subrayon ?? $this->sekolah?->subrayon,
            $baris->namaBarangTampil(),
            $baris->satuan,
            $baris->harga,
            $baris->saldo_awal_kuantitas,
            $baris->saldo_awal_jumlah,
            $baris->penerimaan_kuantitas,
            $baris->penerimaan_jumlah,
            $baris->pengeluaran_kuantitas,
            $baris->pengeluaran_jumlah,
            $baris->saldo_akhir_kuantitas,
            $baris->saldo_akhir_jumlah,
            $baris->keterangan,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->tulisJudul($sheet);
                $this->rapikanTabel($sheet);
            },
        ];
    }

    private function tulisJudul(Worksheet $sheet): void
    {
        $judul1 = 'STOCK OPNAME RINCIAN BARANG PERSEDIAAN BOSP';
        if ($this->sekolah === null) {
            $judul1 .= ' - REKAP SELURUH SEKOLAH';
        }

        $sheet->setCellValue('A1', $judul1);
        $sheet->setCellValue('A2', 'TRIWULAN '.$this->triwulan.', TAHUN ANGGARAN '.$this->tahun);

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

        // Garis tabel diteruskan sampai 4 baris kosong setelah baris data
        // terakhir, supaya format kolomnya tetap kelihatan meski baris itu
        // belum diisi.
        $sheet->getStyle('A'.self::BARIS_HEADER_TABEL.':'.self::KOLOM_TERAKHIR.($barisTerakhir + self::BARIS_KOSONG_SEBELUM_TANDA_TANGAN))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        if ($this->baris->count() > 0) {
            // Format Rupiah (mis. "Rp 1.000.000") pada kolom Harga (F) &
            // 4 kolom "Jumlah (Rp)" (H, J, L, N), supaya sama dengan
            // tampilan di aplikasi.
            foreach (['F', 'H', 'J', 'L', 'N'] as $kolom) {
                $sheet->getStyle($kolom.(self::BARIS_HEADER_TABEL + 1).':'.$kolom.$barisTerakhir)
                    ->getNumberFormat()->setFormatCode('"Rp" #,##0');
            }
        }

        $sheet->freezePane('A'.(self::BARIS_HEADER_TABEL + 1));
        $sheet->setAutoFilter($rentangHeader);
        $sheet->getRowDimension(self::BARIS_HEADER_TABEL)->setRowHeight(20);
    }

}
