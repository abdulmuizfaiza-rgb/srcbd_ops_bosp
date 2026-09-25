<?php

namespace App\Exports;

use App\Models\PajakBospReguler;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Rekapitulasi Pajak BOSP Reguler SELURUH SEKOLAH (Tab 2,
 * Superadmin) ke Excel - 1 baris per sekolah, total Debit/Kredit setahun
 * (DIRINCI per jenis pajak - permintaan user 2026-09-15, Part 24) & Saldo
 * akhir, + kolom "No" & baris "Jumlah" total di baris terakhir
 * (permintaan user 2026-09-15, Part 25).
 *
 * BERUBAH dari pola FromCollection+WithMapping+WithHeadings semula
 * (row-per-record standar, hanya bisa header 1-baris flat) menjadi
 * FromArray+WithEvents grid-style yang sama seperti PajakBospRegulerExport
 * (Tab 1) - karena kolom "Total Debit/Kredit Setahun" sekarang perlu
 * header 2-baris (merged group header + 5 sub-kolom jenis pajak + kolom
 * Jumlah), yang tidak bisa direpresentasikan lewat WithHeadings.
 *
 * TIDAK ADA lembar tanda tangan - karena ini rekap gabungan seluruh
 * sekolah, tidak ada satupun Bendahara BOSP/Kepala Sekolah tunggal yang
 * bisa mewakili semua sekolah (pola sama seperti versi sebelumnya).
 *
 * @phpstan-type BarisRekap array{sekolah: \App\Models\ProfilSekolah, total_debit: int, total_kredit: int, saldo_akhir: int, rincian: array<string, int>}
 */
class PajakBospRegulerRekapExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    private const KOLOM_TERAKHIR = 'P';

    private const BARIS_HEADER_ATAS = 4;

    private const BARIS_HEADER_BAWAH = 5;

    private const BARIS_DATA_MULAI = 6;

    /**
     * @param  SupportCollection<int, array{sekolah: \App\Models\ProfilSekolah, total_debit: int, total_kredit: int, saldo_akhir: int, rincian: array<string, int>}>  $baris
     */
    public function __construct(
        protected SupportCollection $baris,
        protected int $tahun,
    ) {}

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Rekap Pajak BOSP '.$this->tahun;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->tulisJudul($sheet);
                $this->tulisTabel($sheet);
            },
        ];
    }

    private function tulisJudul(Worksheet $sheet): void
    {
        $sheet->setCellValue('A1', 'REKAPITULASI PAJAK BOSP REGULER SELURUH SEKOLAH');
        $sheet->setCellValue('A2', 'TAHUN ANGGARAN '.$this->tahun);

        foreach ([1, 2] as $baris) {
            $rentang = 'A'.$baris.':'.self::KOLOM_TERAKHIR.$baris;
            $sheet->mergeCells($rentang);
            $sheet->getStyle($rentang)->applyFromArray([
                'font' => ['bold' => true, 'size' => $baris === 1 ? 12 : 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }
    }

    /**
     * Kolom A-P: No, NPSN, Nama Sekolah, 5 sub-kolom Debit + Jumlah, 5
     * sub-kolom Kredit + Jumlah, Saldo Akhir - header 2-baris mirroring
     * PajakBospRegulerExport::tulisTabelBulanan() (kolom "No" ditambahkan
     * di depan "NPSN" - permintaan user 2026-09-15, Part 25 - supaya
     * sama seperti tampilan tabel di aplikasi).
     */
    private function tulisTabel(Worksheet $sheet): void
    {
        $labelKolomPajak = array_values(PajakBospReguler::LABEL_PAJAK);

        $sheet->setCellValue('A'.self::BARIS_HEADER_ATAS, 'No');
        $sheet->setCellValue('B'.self::BARIS_HEADER_ATAS, 'NPSN');
        $sheet->setCellValue('C'.self::BARIS_HEADER_ATAS, 'Nama Sekolah');
        $sheet->mergeCells('A'.self::BARIS_HEADER_ATAS.':A'.self::BARIS_HEADER_BAWAH);
        $sheet->mergeCells('B'.self::BARIS_HEADER_ATAS.':B'.self::BARIS_HEADER_BAWAH);
        $sheet->mergeCells('C'.self::BARIS_HEADER_ATAS.':C'.self::BARIS_HEADER_BAWAH);
        $sheet->setCellValue('D'.self::BARIS_HEADER_ATAS, 'Total Debit Setahun');
        $sheet->mergeCells('D'.self::BARIS_HEADER_ATAS.':I'.self::BARIS_HEADER_ATAS);
        $sheet->setCellValue('J'.self::BARIS_HEADER_ATAS, 'Total Kredit Setahun');
        $sheet->mergeCells('J'.self::BARIS_HEADER_ATAS.':O'.self::BARIS_HEADER_ATAS);
        $sheet->setCellValue('P'.self::BARIS_HEADER_ATAS, 'Saldo Akhir');
        $sheet->mergeCells('P'.self::BARIS_HEADER_ATAS.':P'.self::BARIS_HEADER_BAWAH);

        $kolomDebit = ['D', 'E', 'F', 'G', 'H'];
        $kolomKredit = ['J', 'K', 'L', 'M', 'N'];

        foreach ($kolomDebit as $i => $kolom) {
            $sheet->setCellValue($kolom.self::BARIS_HEADER_BAWAH, $labelKolomPajak[$i]);
        }
        $sheet->setCellValue('I'.self::BARIS_HEADER_BAWAH, 'Jumlah');

        foreach ($kolomKredit as $i => $kolom) {
            $sheet->setCellValue($kolom.self::BARIS_HEADER_BAWAH, $labelKolomPajak[$i]);
        }
        $sheet->setCellValue('O'.self::BARIS_HEADER_BAWAH, 'Jumlah');

        $sheet->getStyle('A'.self::BARIS_HEADER_ATAS.':'.self::KOLOM_TERAKHIR.self::BARIS_HEADER_BAWAH)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $baris = self::BARIS_DATA_MULAI;
        $nomor = 1;
        foreach ($this->baris as $rekap) {
            $sheet->setCellValue('A'.$baris, $nomor);
            $sheet->setCellValue('B'.$baris, $rekap['sekolah']->npsn);
            $sheet->setCellValue('C'.$baris, $rekap['sekolah']->nama_sekolah);

            foreach (PajakBospReguler::FIELD_DEBIT as $i => $field) {
                $sheet->setCellValue($kolomDebit[$i].$baris, $rekap['rincian'][$field] ?? 0);
            }
            $sheet->setCellValue('I'.$baris, $rekap['total_debit']);

            foreach (PajakBospReguler::FIELD_KREDIT as $i => $field) {
                $sheet->setCellValue($kolomKredit[$i].$baris, $rekap['rincian'][$field] ?? 0);
            }
            $sheet->setCellValue('O'.$baris, $rekap['total_kredit']);

            $sheet->setCellValue('P'.$baris, $rekap['saldo_akhir']);

            $baris++;
            $nomor++;
        }

        $barisTerakhirData = $baris - 1;

        if ($barisTerakhirData >= self::BARIS_DATA_MULAI) {
            // Kolom NPSN (B) diposisikan center - permintaan user 2026-09-15, Part 25.
            $sheet->getStyle('B'.self::BARIS_DATA_MULAI.':B'.$barisTerakhirData)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            // Baris "Jumlah" total setelah baris data terakhir - kolom 1-3
            // (No/NPSN/Nama Sekolah) di-merge, tiap kolom pajak/Jumlah/Saldo
            // Akhir dijumlahkan dari seluruh baris data di atasnya -
            // permintaan user 2026-09-15, Part 25.
            $barisJumlah = $barisTerakhirData + 1;
            $sheet->setCellValue('A'.$barisJumlah, 'Jumlah');
            $sheet->mergeCells('A'.$barisJumlah.':C'.$barisJumlah);

            foreach (PajakBospReguler::FIELD_DEBIT as $i => $field) {
                $total = $this->baris->sum(fn (array $rekap) => $rekap['rincian'][$field] ?? 0);
                $sheet->setCellValue($kolomDebit[$i].$barisJumlah, $total);
            }
            $sheet->setCellValue('I'.$barisJumlah, $this->baris->sum('total_debit'));

            foreach (PajakBospReguler::FIELD_KREDIT as $i => $field) {
                $total = $this->baris->sum(fn (array $rekap) => $rekap['rincian'][$field] ?? 0);
                $sheet->setCellValue($kolomKredit[$i].$barisJumlah, $total);
            }
            $sheet->setCellValue('O'.$barisJumlah, $this->baris->sum('total_kredit'));

            $sheet->setCellValue('P'.$barisJumlah, $this->baris->sum('saldo_akhir'));

            $sheet->getStyle('A'.$barisJumlah.':'.self::KOLOM_TERAKHIR.$barisJumlah)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            ]);

            $barisTerakhir = $barisJumlah;

            $sheet->getStyle('A'.self::BARIS_HEADER_ATAS.':'.self::KOLOM_TERAKHIR.$barisTerakhir)
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle('D'.self::BARIS_DATA_MULAI.':'.self::KOLOM_TERAKHIR.$barisTerakhir)
                ->getNumberFormat()->setFormatCode('"Rp" #,##0');
        } else {
            $sheet->getStyle('A'.self::BARIS_HEADER_ATAS.':'.self::KOLOM_TERAKHIR.self::BARIS_HEADER_BAWAH)
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        $sheet->setAutoFilter('A'.self::BARIS_HEADER_ATAS.':'.self::KOLOM_TERAKHIR.self::BARIS_HEADER_BAWAH);
        $sheet->freezePane('A'.self::BARIS_DATA_MULAI);
        $sheet->getRowDimension(self::BARIS_HEADER_ATAS)->setRowHeight(20);
    }
}
