<?php

namespace App\Exports\Lpj;

use App\Exports\Lpj\Concerns\TerapkanJudulLpj;
use App\Models\ProfilSekolah;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

require_once app_path('Exports/LaporanRealisasiBospExport.php');

class LaporanRealisasiBospTriwulanLpjSheet extends \App\Exports\LaporanRealisasiBospTriwulanSheet implements WithEvents
{
    use TerapkanJudulLpj;

    public function __construct(
        protected string $judulSheetLpj,
        protected int $triwulanLpj,
        protected int $tahunLpj,
        protected ?ProfilSekolah $sekolahLpj,
        protected string $kolomTerakhirLpj,
        mixed ...$argsAsli,
    ) {
        parent::__construct(...$argsAsli);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->tambahkanJudulDanLayoutLpj(
                    $sheet,
                    $this->judulSheetLpj,
                    $this->triwulanLpj,
                    $this->tahunLpj,
                    $this->sekolahLpj,
                    $this->kolomTerakhirLpj,
                );

                $this->rapikanTabelFormBpkLpj($sheet);
            },
        ];
    }

    /**
     * BARU (permintaan user 2026-10-25): berbeda dengan 16 sheet LPJ
     * lain yang mewarisi border dari kelas Export menu aslinya (sudah
     * ada garis tabel di sana), parent class sheet ini
     * (LaporanRealisasiBospTriwulanSheet, lihat
     * App\Exports\LaporanRealisasiBospExport) TIDAK implements
     * WithEvents sama sekali - menu "Laporan Realisasi BOSP" aslinya
     * memang belum punya border tabel. Supaya sheet "Form BPK TW-{n}"
     * pada file LPJ gabungan ini rapi & konsisten seperti sheet
     * lainnya, border garis hitam tipis diterapkan DI SINI SAJA (pada
     * kelas pembungkus LPJ) - menu "Laporan Realisasi BOSP" yang sudah
     * ada (tombol unduh satu-menu-nya) TIDAK disentuh & TIDAK berubah
     * perilaku sama sekali. Pola border/header sama persis seperti
     * App\Exports\PenerimaanHonorPtkExport::rapikanTabel().
     */
    private function rapikanTabelFormBpkLpj(Worksheet $sheet): void
    {
        // Baris 1 (header asli dari WithHeadings) bergeser jadi baris 6
        // setelah 5 baris judul LPJ disisipkan oleh
        // tambahkanJudulDanLayoutLpj() di atas (insertNewRowBefore(1, 5)).
        $barisHeader = 6;
        $barisTerakhir = $barisHeader + $this->daftarSekolah->count();
        $rentangHeader = 'A'.$barisHeader.':'.$this->kolomTerakhirLpj.$barisHeader;

        $sheet->getStyle($rentangHeader)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        $sheet->getStyle('A'.$barisHeader.':'.$this->kolomTerakhirLpj.$barisTerakhir)
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->freezePane('A'.($barisHeader + 1));
        $sheet->setAutoFilter($rentangHeader);
        $sheet->getRowDimension($barisHeader)->setRowHeight(20);
    }
}
