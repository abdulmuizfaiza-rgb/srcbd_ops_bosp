<?php

namespace App\Exports;

use App\Models\RekapRkas;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Rekap RKAS Awal-Perubahan ke Excel (permintaan user 2026-09-26,
 * "field yang sama dengan di aplikasi... file yang sudah rapih tidak
 * perlu diedit kembali").
 *
 * Mengikuti pola App\Exports\PajakBospRegulerExport (FromArray+WithEvents,
 * SELURUH isi sheet ditulis manual lewat AfterSheet) karena tabelnya
 * berbentuk GRID dengan header BERKELOMPOK per kategori Belanja
 * (rowspan/colspan 2 baris), bukan daftar record flat biasa yang cocok
 * dipetakan lewat WithMapping/WithHeadings 1 baris.
 *
 * Data & urutan sekolah SUDAH final saat diterima di constructor (dihitung
 * di Livewire\PendataanBosp\RekapRkas\Index::dataUntukUnduhan(), memakai
 * urutan Status(Negeri dulu)-Kecamatan-Nama Sekolah yang SUDAH dipakai di
 * render() menu ini) - class ini TIDAK menghitung ulang rumus apapun,
 * murni menulis nilai yang sudah dihitung/disimpan.
 */
class RekapRkasExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    /**
     * @param  SupportCollection<int, \App\Models\ProfilSekolah>  $daftarSekolah  Sudah di-map dengan rekapRkasTahunIni & anggaranBospOtomatis (lihat Index::dataUntukUnduhan()).
     * @param  array<string, int>  $totalBaris
     */
    public function __construct(
        protected SupportCollection $daftarSekolah,
        protected int $tahun,
        protected array $totalBaris,
    ) {}

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Rekap RKAS '.$this->tahun;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->tulis($event->sheet->getDelegate());
            },
        ];
    }

    private function kolom(int $index): string
    {
        return Coordinate::stringFromColumnIndex($index);
    }

    private function tulis(Worksheet $sheet): void
    {
        $kategoriList = RekapRkas::KATEGORI;
        $jumlahKolom = 3 + (count($kategoriList) * 5) + 3; // No + Nama Sekolah + Anggaran BOSP + (5 kategori x 5 kolom) + 3 kolom Jumlah
        $kolomTerakhir = $this->kolom($jumlahKolom);

        $sheet->setCellValue('A1', 'REKAP RKAS AWAL-PERUBAHAN');
        $sheet->setCellValue('A2', 'TAHUN ANGGARAN '.$this->tahun);
        foreach ([1, 2] as $baris) {
            $rentang = 'A'.$baris.':'.$kolomTerakhir.$baris;
            $sheet->mergeCells($rentang);
            $sheet->getStyle($rentang)->applyFromArray([
                'font' => ['bold' => true, 'size' => $baris === 1 ? 12 : 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        $barisHeaderAtas = 4;
        $barisHeaderBawah = 5;

        $sheet->setCellValue('A'.$barisHeaderAtas, 'No');
        $sheet->mergeCells('A'.$barisHeaderAtas.':A'.$barisHeaderBawah);
        $sheet->setCellValue('B'.$barisHeaderAtas, 'Nama Sekolah');
        $sheet->mergeCells('B'.$barisHeaderAtas.':B'.$barisHeaderBawah);
        $sheet->setCellValue('C'.$barisHeaderAtas, 'Anggaran BOSP '.$this->tahun);
        $sheet->mergeCells('C'.$barisHeaderAtas.':C'.$barisHeaderBawah);

        $kolomIndex = 4; // D
        $kolomPerKategori = [];
        foreach ($kategoriList as $kunci => $info) {
            $mulai = $kolomIndex;
            $akhir = $kolomIndex + 4;

            $sheet->setCellValue($this->kolom($mulai).$barisHeaderAtas, $info['label']);
            $sheet->mergeCells($this->kolom($mulai).$barisHeaderAtas.':'.$this->kolom($akhir).$barisHeaderAtas);

            $labelBawah = [
                'Sebelum '.$info['sebelum'],
                'Realisasi Tahap 1',
                'Perubahan Tahap 2',
                'Jml Sesudah',
                'Selisih '.$info['singkatan'],
            ];
            foreach ($labelBawah as $i => $label) {
                $sheet->setCellValue($this->kolom($mulai + $i).$barisHeaderBawah, $label);
            }

            $kolomPerKategori[$kunci] = range($mulai, $akhir);
            $kolomIndex = $akhir + 1;
        }

        $mulaiJumlah = $kolomIndex;
        $sheet->setCellValue($this->kolom($mulaiJumlah).$barisHeaderAtas, 'JUMLAH');
        $sheet->mergeCells($this->kolom($mulaiJumlah).$barisHeaderAtas.':'.$this->kolom($mulaiJumlah + 2).$barisHeaderAtas);
        $labelJumlah = ['Sebelum (Jumlah)', 'Sesudah', 'Selisih Belanja'];
        foreach ($labelJumlah as $i => $label) {
            $sheet->setCellValue($this->kolom($mulaiJumlah + $i).$barisHeaderBawah, $label);
        }

        $sheet->getStyle('A'.$barisHeaderAtas.':'.$kolomTerakhir.$barisHeaderBawah)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $barisDataMulai = $barisHeaderBawah + 1;
        $baris = $barisDataMulai;

        foreach ($this->daftarSekolah as $i => $sekolah) {
            $rekap = $sekolah->rekapRkasTahunIni;

            $sheet->setCellValue('A'.$baris, $i + 1);
            $sheet->setCellValue('B'.$baris, $sekolah->nama_sekolah);
            $sheet->setCellValue('C'.$baris, (int) ($sekolah->anggaranBospOtomatis ?? 0));

            foreach ($kategoriList as $kunci => $info) {
                [$c1, $c2, $c3, $c4, $c5] = $kolomPerKategori[$kunci];
                $sheet->setCellValue($this->kolom($c1).$baris, (int) ($rekap?->{$kunci.'_sebelum'} ?? 0));
                $sheet->setCellValue($this->kolom($c2).$baris, (int) ($rekap?->{$kunci.'_realisasi_tahap1'} ?? 0));
                $sheet->setCellValue($this->kolom($c3).$baris, (int) ($rekap?->{$kunci.'_perubahan_tahap2'} ?? 0));
                $sheet->setCellValue($this->kolom($c4).$baris, (int) ($rekap?->{$kunci.'_jml_sesudah'} ?? 0));
                $sheet->setCellValue($this->kolom($c5).$baris, (int) ($rekap?->{$kunci.'_selisih'} ?? 0));
            }

            $sheet->setCellValue($this->kolom($mulaiJumlah).$baris, (int) ($rekap?->jumlah_sebelum ?? 0));
            $sheet->setCellValue($this->kolom($mulaiJumlah + 1).$baris, (int) ($rekap?->jumlah_sesudah ?? 0));
            $sheet->setCellValue($this->kolom($mulaiJumlah + 2).$baris, (int) ($rekap?->jumlah_selisih ?? 0));

            $baris++;
        }

        $barisTotal = $baris;
        $sheet->setCellValue('A'.$barisTotal, 'JUMLAH');
        $sheet->mergeCells('A'.$barisTotal.':B'.$barisTotal);
        $sheet->setCellValue('C'.$barisTotal, $this->totalBaris['anggaran_bosp'] ?? 0);

        foreach ($kategoriList as $kunci => $info) {
            [$c1, $c2, $c3, $c4, $c5] = $kolomPerKategori[$kunci];
            $sheet->setCellValue($this->kolom($c1).$barisTotal, $this->totalBaris[$kunci.'_sebelum'] ?? 0);
            $sheet->setCellValue($this->kolom($c2).$barisTotal, $this->totalBaris[$kunci.'_realisasi_tahap1'] ?? 0);
            $sheet->setCellValue($this->kolom($c3).$barisTotal, $this->totalBaris[$kunci.'_perubahan_tahap2'] ?? 0);
            $sheet->setCellValue($this->kolom($c4).$barisTotal, $this->totalBaris[$kunci.'_jml_sesudah'] ?? 0);
            $sheet->setCellValue($this->kolom($c5).$barisTotal, $this->totalBaris[$kunci.'_selisih'] ?? 0);
        }
        $sheet->setCellValue($this->kolom($mulaiJumlah).$barisTotal, $this->totalBaris['jumlah_sebelum'] ?? 0);
        $sheet->setCellValue($this->kolom($mulaiJumlah + 1).$barisTotal, $this->totalBaris['jumlah_sesudah'] ?? 0);
        $sheet->setCellValue($this->kolom($mulaiJumlah + 2).$barisTotal, $this->totalBaris['jumlah_selisih'] ?? 0);

        $sheet->getStyle('A'.$barisTotal.':'.$kolomTerakhir.$barisTotal)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
        ]);

        $rentangSeluruh = 'A'.$barisHeaderAtas.':'.$kolomTerakhir.$barisTotal;
        $sheet->getStyle($rentangSeluruh)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('C'.$barisDataMulai.':'.$kolomTerakhir.$barisTotal)
            ->getNumberFormat()->setFormatCode('"Rp" #,##0');

        $sheet->freezePane('A'.$barisDataMulai);
        $sheet->getRowDimension($barisHeaderAtas)->setRowHeight(20);
        $sheet->getRowDimension($barisHeaderBawah)->setRowHeight(32);
        $sheet->setAutoFilter('A'.$barisHeaderAtas.':'.$kolomTerakhir.$barisHeaderBawah);
    }
}
