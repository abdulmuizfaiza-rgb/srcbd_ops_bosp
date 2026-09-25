<?php

namespace App\Exports;

use App\Models\BiayaPendaftaranLomba;
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
 * Export data Biaya Pendaftaran Lomba/Bimtek/Workshop (satu triwulan,
 * satu sekolah) ke Excel - pola disalin PERSIS dari
 * LanggananDayaJasaExport (layout & lembar tanda tangan sama), hanya
 * judul & nama kolom Uraian/Tanggal yang disesuaikan dengan menu ini.
 *
 * Urutan kolom data: NPSN, Nama Sekolah, Uraian, Volume, Satuan, Tarif
 * Harga, Jumlah, Tanggal - sesuai urutan field pada gambar contoh tabel
 * yang diupload user.
 *
 * PENTING (permintaan user 2026-09-23): lembar tanda tangan "Mengetahui/
 * Menyetujui" Bendahara BOSP/Kepala Sekolah SUDAH DIHAPUS - hanya judul
 * tabel (tulisJudul()) yang dimunculkan. Syarat pilih 1 sekolah (kalau
 * ada di Livewire Index) SENGAJA TIDAK diubah (jawaban AskUserQuestion
 * 2026-09-23: "Tetap dipertahankan").
 */
class BiayaPendaftaranLombaExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_HEADER_TABEL = 5;

    private const BARIS_KOSONG_SEBELUM_TANDA_TANGAN = 4;

    /**
     * @param  SupportCollection<int, BiayaPendaftaranLomba>  $baris
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
        return 'Lomba-Bimtek TW'.$this->triwulan;
    }

    public function headings(): array
    {
        return [
            'NPSN',
            'Nama Sekolah',
            'Uraian',
            'Volume',
            'Satuan',
            'Tarif Harga',
            'Jumlah',
            'Tanggal',
        ];
    }

    public function map($baris): array
    {
        return [
            $baris->profilSekolah->npsn ?? $this->sekolah?->npsn,
            $baris->profilSekolah->nama_sekolah ?? $this->sekolah?->nama_sekolah,
            $baris->uraian,
            $baris->volume,
            $baris->satuan,
            $baris->tarif_harga,
            $baris->jumlah,
            $baris->tanggal?->format('d-m-Y'),
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
        $judul1 = 'DAFTAR BIAYA PENDAFTARAN LOMBA/BIMTEK/WORKSHOP';
        if ($this->sekolah === null) {
            $judul1 .= ' - REKAP SELURUH SEKOLAH';
        }

        $sheet->setCellValue('A1', $judul1);
        $sheet->setCellValue('A2', 'TRIWULAN '.$this->triwulan.', TAHUN ANGGARAN '.$this->tahun);

        foreach ([1, 2] as $baris) {
            $rentang = 'A'.$baris.':H'.$baris;
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
        $rentangHeader = 'A'.self::BARIS_HEADER_TABEL.':H'.self::BARIS_HEADER_TABEL;

        $sheet->getStyle($rentangHeader)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A'.self::BARIS_HEADER_TABEL.':H'.($barisTerakhir + self::BARIS_KOSONG_SEBELUM_TANDA_TANGAN))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        if ($this->baris->count() > 0) {
            foreach (['F', 'G'] as $kolom) {
                $sheet->getStyle($kolom.(self::BARIS_HEADER_TABEL + 1).':'.$kolom.$barisTerakhir)
                    ->getNumberFormat()->setFormatCode('"Rp" #,##0');
            }
        }

        $sheet->freezePane('A'.(self::BARIS_HEADER_TABEL + 1));
        $sheet->setAutoFilter($rentangHeader);
        $sheet->getRowDimension(self::BARIS_HEADER_TABEL)->setRowHeight(20);
    }
}
