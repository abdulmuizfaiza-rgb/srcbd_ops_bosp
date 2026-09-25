<?php

namespace App\Exports;

use App\Models\PajakBospReguler;
use App\Models\ProfilSekolah;
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
 * Export Pajak BOSP Reguler UNTUK SATU SEKOLAH (Tab 1) ke Excel -
 * permintaan user 2026-09-11, Part 23, mengikuti bentuk template Excel
 * "REKAPITULASI PAJAK REGULER DAN PAJAK DAERAH - DANA BANTUAN OPERASIONAL
 * SEKOLAH (BOS)" yang diupload user (2 tabel: 12 baris bulanan + 4 baris
 * Triwulan, masing-masing dengan kolom Jumlah & Saldo).
 *
 * BEDA dari kebanyakan Export lain di aplikasi ini (yang memakai
 * FromCollection+WithMapping baris-per-record): di sini SELURUH isi
 * sheet (2 tabel dengan header 2-baris/grouped, baris Jumlah, lembar
 * tanda tangan) ditulis LANGSUNG lewat AfterSheet (FromArray dipakai
 * hanya sebagai syarat teknis paket maatwebsite/excel, isinya kosong) -
 * karena datanya berupa GRID dengan kolom hasil rumus (Jumlah/Saldo),
 * bukan daftar record Eloquent biasa.
 *
 * Rumus Jumlah/Saldo/Triwulan TIDAK dihitung ulang di sini - SELALU
 * diterima sebagai parameter yang SUDAH dihitung oleh
 * App\Models\PajakBospReguler (dipanggil dari Livewire Index::export()),
 * supaya rumus tetap 100% terpusat di 1 tempat.
 *
 * PENTING (permintaan user 2026-09-23): lembar tanda tangan "Bendahara
 * BOSP" & "Kepala Sekolah" (dulu mengikuti pola PenerimaanHonorPtkExport)
 * SUDAH DIHAPUS - hanya judul tabel (tulisJudul()) yang dimunculkan di
 * atas tabel. Syarat pilih 1 sekolah (constructor $sekolah non-nullable)
 * SENGAJA TIDAK diubah (jawaban AskUserQuestion 2026-09-23: "Tetap
 * dipertahankan").
 */
class PajakBospRegulerExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    private const KOLOM_TERAKHIR = 'O';

    private const BARIS_JUDUL = 3;

    private const BARIS_HEADER_ATAS = 5;

    private const BARIS_HEADER_BAWAH = 6;

    private const BARIS_DATA_MULAI = 7;

    /**
     * @param  array<int, array<string, int|null>>  $baris  [bulan => [field => nilai mentah]]
     * @param  array<int, array{debit:int, kredit:int}>  $jumlahPerBulan
     * @param  array<int, int>  $saldoPerBulan
     * @param  array<int, array{debit:int, kredit:int, saldo:int}>  $triwulanData
     * @param  array<string, int>  $totalRaw
     */
    public function __construct(
        protected ProfilSekolah $sekolah,
        protected int $tahun,
        protected array $baris,
        protected array $jumlahPerBulan,
        protected array $saldoPerBulan,
        protected array $triwulanData,
        protected array $totalRaw,
        protected int $totalJumlahDebit,
        protected int $totalJumlahKredit,
        protected int $saldoAkhir,
    ) {}

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Pajak BOSP Reguler '.$this->tahun;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->tulisJudul($sheet);
                $barisAkhirBulanan = $this->tulisTabelBulanan($sheet);
                $this->tulisTabelTriwulan($sheet, $barisAkhirBulanan + 3);
            },
        ];
    }

    private function tulisJudul(Worksheet $sheet): void
    {
        $sheet->setCellValue('A1', 'REKAPITULASI PAJAK REGULER DAN PAJAK DAERAH');
        $sheet->setCellValue('A2', 'DANA BANTUAN OPERASIONAL SEKOLAH (BOS)');
        $sheet->setCellValue('A'.self::BARIS_JUDUL, $this->sekolah->nama_sekolah.' - PERIODE JANUARI-DESEMBER TAHUN ANGGARAN '.$this->tahun);

        foreach ([1, 2, self::BARIS_JUDUL] as $baris) {
            $rentang = 'A'.$baris.':'.self::KOLOM_TERAKHIR.$baris;
            $sheet->mergeCells($rentang);
            $sheet->getStyle($rentang)->applyFromArray([
                'font' => ['bold' => true, 'size' => $baris === 1 ? 12 : 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }
    }

    /**
     * Kolom A-O: No, Bulan, 5 Debit, Jumlah Debit, 5 Kredit, Jumlah
     * Kredit, Saldo.
     *
     * @return int baris terakhir tabel bulanan (baris "Jumlah" total).
     */
    private function tulisTabelBulanan(Worksheet $sheet): int
    {
        $labelKolomPajak = array_values(PajakBospReguler::LABEL_PAJAK);

        $sheet->setCellValue('A'.self::BARIS_HEADER_ATAS, 'No');
        $sheet->setCellValue('B'.self::BARIS_HEADER_ATAS, 'Bulan');
        $sheet->mergeCells('A'.self::BARIS_HEADER_ATAS.':A'.self::BARIS_HEADER_BAWAH);
        $sheet->mergeCells('B'.self::BARIS_HEADER_ATAS.':B'.self::BARIS_HEADER_BAWAH);
        $sheet->setCellValue('C'.self::BARIS_HEADER_ATAS, 'PENERIMAAN / DEBIT');
        $sheet->mergeCells('C'.self::BARIS_HEADER_ATAS.':H'.self::BARIS_HEADER_ATAS);
        $sheet->setCellValue('I'.self::BARIS_HEADER_ATAS, 'PENGELUARAN / KREDIT');
        $sheet->mergeCells('I'.self::BARIS_HEADER_ATAS.':N'.self::BARIS_HEADER_ATAS);
        $sheet->setCellValue('O'.self::BARIS_HEADER_ATAS, 'Saldo');
        $sheet->mergeCells('O'.self::BARIS_HEADER_ATAS.':O'.self::BARIS_HEADER_BAWAH);

        $kolomDebit = ['C', 'D', 'E', 'F', 'G'];
        $kolomKredit = ['I', 'J', 'K', 'L', 'M'];

        foreach ($kolomDebit as $i => $kolom) {
            $sheet->setCellValue($kolom.self::BARIS_HEADER_BAWAH, $labelKolomPajak[$i]);
        }
        $sheet->setCellValue('H'.self::BARIS_HEADER_BAWAH, 'Jumlah');

        foreach ($kolomKredit as $i => $kolom) {
            $sheet->setCellValue($kolom.self::BARIS_HEADER_BAWAH, $labelKolomPajak[$i]);
        }
        $sheet->setCellValue('N'.self::BARIS_HEADER_BAWAH, 'Jumlah');

        $sheet->getStyle('A'.self::BARIS_HEADER_ATAS.':'.self::KOLOM_TERAKHIR.self::BARIS_HEADER_BAWAH)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $baris = self::BARIS_DATA_MULAI;
        foreach (PajakBospReguler::BULAN_OPTIONS as $bulan => $labelBulan) {
            $sheet->setCellValue('A'.$baris, $bulan);
            $sheet->setCellValue('B'.$baris, $labelBulan);

            foreach (PajakBospReguler::FIELD_DEBIT as $i => $field) {
                $sheet->setCellValue($kolomDebit[$i].$baris, (int) ($this->baris[$bulan][$field] ?? 0));
            }
            $sheet->setCellValue('H'.$baris, $this->jumlahPerBulan[$bulan]['debit']);

            foreach (PajakBospReguler::FIELD_KREDIT as $i => $field) {
                $sheet->setCellValue($kolomKredit[$i].$baris, (int) ($this->baris[$bulan][$field] ?? 0));
            }
            $sheet->setCellValue('N'.$baris, $this->jumlahPerBulan[$bulan]['kredit']);

            $sheet->setCellValue('O'.$baris, $this->saldoPerBulan[$bulan]);

            $baris++;
        }

        $barisJumlah = $baris;
        $sheet->setCellValue('A'.$barisJumlah, 'Jumlah');
        $sheet->mergeCells('A'.$barisJumlah.':B'.$barisJumlah);
        foreach (array_merge($kolomDebit, $kolomKredit) as $kolom) {
            $field = null;
            // Peta kolom -> field mentah untuk total per kolom.
            $field = match ($kolom) {
                'C' => 'ppn_debit', 'D' => 'pph21_debit', 'E' => 'pph23_debit', 'F' => 'pph4_debit', 'G' => 'sspd_debit',
                'I' => 'ppn_kredit', 'J' => 'pph21_kredit', 'K' => 'pph23_kredit', 'L' => 'pph4_kredit', 'M' => 'sspd_kredit',
            };
            $sheet->setCellValue($kolom.$barisJumlah, $this->totalRaw[$field]);
        }
        $sheet->setCellValue('H'.$barisJumlah, $this->totalJumlahDebit);
        $sheet->setCellValue('N'.$barisJumlah, $this->totalJumlahKredit);
        $sheet->setCellValue('O'.$barisJumlah, $this->saldoAkhir);

        $sheet->getStyle('A'.$barisJumlah.':'.self::KOLOM_TERAKHIR.$barisJumlah)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
        ]);

        $rentangSeluruh = 'A'.self::BARIS_HEADER_ATAS.':'.self::KOLOM_TERAKHIR.$barisJumlah;
        $sheet->getStyle($rentangSeluruh)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('C'.self::BARIS_DATA_MULAI.':'.self::KOLOM_TERAKHIR.$barisJumlah)
            ->getNumberFormat()->setFormatCode('"Rp" #,##0');
        $sheet->freezePane('A'.self::BARIS_DATA_MULAI);

        return $barisJumlah;
    }

    /**
     * Tabel Triwulan (4 baris + Jumlah), SELURUHNYA hasil rumus - agregat
     * otomatis dari tabel bulanan (jawaban AskUserQuestion "Otomatis dari
     * data bulanan"). Sejak permintaan user 2026-09-15, kolom Debit/Kredit
     * dirinci per jenis pajak (mirroring tulisTabelBulanan()) & headernya
     * berganti nama menjadi "Penerimaan (Debit)"/"Pengeluaran (Kredit)".
     */
    private function tulisTabelTriwulan(Worksheet $sheet, int $barisMulaiJudul): int
    {
        $sheet->setCellValue('A'.$barisMulaiJudul, 'REKAPITULASI PER TRIWULAN');
        $sheet->mergeCells('A'.$barisMulaiJudul.':'.self::KOLOM_TERAKHIR.$barisMulaiJudul);
        $sheet->getStyle('A'.$barisMulaiJudul)->getFont()->setBold(true);

        $labelKolomPajak = array_values(PajakBospReguler::LABEL_PAJAK);

        $barisHeaderAtas = $barisMulaiJudul + 1;
        $barisHeaderBawah = $barisHeaderAtas + 1;

        $sheet->setCellValue('A'.$barisHeaderAtas, 'No');
        $sheet->setCellValue('B'.$barisHeaderAtas, 'Triwulan');
        $sheet->mergeCells('A'.$barisHeaderAtas.':A'.$barisHeaderBawah);
        $sheet->mergeCells('B'.$barisHeaderAtas.':B'.$barisHeaderBawah);
        $sheet->setCellValue('C'.$barisHeaderAtas, 'Penerimaan (Debit)');
        $sheet->mergeCells('C'.$barisHeaderAtas.':H'.$barisHeaderAtas);
        $sheet->setCellValue('I'.$barisHeaderAtas, 'Pengeluaran (Kredit)');
        $sheet->mergeCells('I'.$barisHeaderAtas.':N'.$barisHeaderAtas);
        $sheet->setCellValue('O'.$barisHeaderAtas, 'Saldo');
        $sheet->mergeCells('O'.$barisHeaderAtas.':O'.$barisHeaderBawah);

        $kolomDebit = ['C', 'D', 'E', 'F', 'G'];
        $kolomKredit = ['I', 'J', 'K', 'L', 'M'];

        foreach ($kolomDebit as $i => $kolom) {
            $sheet->setCellValue($kolom.$barisHeaderBawah, $labelKolomPajak[$i]);
        }
        $sheet->setCellValue('H'.$barisHeaderBawah, 'Jumlah');

        foreach ($kolomKredit as $i => $kolom) {
            $sheet->setCellValue($kolom.$barisHeaderBawah, $labelKolomPajak[$i]);
        }
        $sheet->setCellValue('N'.$barisHeaderBawah, 'Jumlah');

        $sheet->getStyle('A'.$barisHeaderAtas.':'.self::KOLOM_TERAKHIR.$barisHeaderBawah)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $baris = $barisHeaderBawah + 1;
        foreach (PajakBospReguler::TRIWULAN_OPTIONS as $tw => $labelTw) {
            $sheet->setCellValue('A'.$baris, $tw);
            $sheet->setCellValue('B'.$baris, $labelTw);

            foreach (PajakBospReguler::FIELD_DEBIT as $i => $field) {
                $sheet->setCellValue($kolomDebit[$i].$baris, $this->triwulanData[$tw]['rincian'][$field] ?? 0);
            }
            $sheet->setCellValue('H'.$baris, $this->triwulanData[$tw]['debit']);

            foreach (PajakBospReguler::FIELD_KREDIT as $i => $field) {
                $sheet->setCellValue($kolomKredit[$i].$baris, $this->triwulanData[$tw]['rincian'][$field] ?? 0);
            }
            $sheet->setCellValue('N'.$baris, $this->triwulanData[$tw]['kredit']);

            $sheet->setCellValue('O'.$baris, $this->triwulanData[$tw]['saldo']);

            $baris++;
        }

        $barisJumlah = $baris;
        $sheet->setCellValue('A'.$barisJumlah, 'Jumlah');
        $sheet->mergeCells('A'.$barisJumlah.':B'.$barisJumlah);
        foreach (array_merge($kolomDebit, $kolomKredit) as $kolom) {
            $field = match ($kolom) {
                'C' => 'ppn_debit', 'D' => 'pph21_debit', 'E' => 'pph23_debit', 'F' => 'pph4_debit', 'G' => 'sspd_debit',
                'I' => 'ppn_kredit', 'J' => 'pph21_kredit', 'K' => 'pph23_kredit', 'L' => 'pph4_kredit', 'M' => 'sspd_kredit',
            };
            $sheet->setCellValue($kolom.$barisJumlah, $this->totalRaw[$field]);
        }
        $sheet->setCellValue('H'.$barisJumlah, $this->totalJumlahDebit);
        $sheet->setCellValue('N'.$barisJumlah, $this->totalJumlahKredit);
        $sheet->setCellValue('O'.$barisJumlah, $this->saldoAkhir);

        $sheet->getStyle('A'.$barisJumlah.':'.self::KOLOM_TERAKHIR.$barisJumlah)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
        ]);

        $rentangSeluruh = 'A'.$barisHeaderAtas.':'.self::KOLOM_TERAKHIR.$barisJumlah;
        $sheet->getStyle($rentangSeluruh)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('C'.($barisHeaderBawah + 1).':'.self::KOLOM_TERAKHIR.$barisJumlah)
            ->getNumberFormat()->setFormatCode('"Rp" #,##0');

        return $barisJumlah;
    }

}
