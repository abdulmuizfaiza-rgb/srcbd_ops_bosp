<?php

namespace App\Exports\Lpj;

use App\Exports\FormulirBosK7bSheetExport;
use App\Exports\Lpj\Concerns\TerapkanJudulLpj;
use App\Models\ProfilSekolah;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Formulir BOS K7b" pada file gabungan "Unduhan LPJ BOSP"
 * (Task #68, permintaan user 2026-10-25: "dibuat 1 sheet dengan format
 * halaman 1 bulan ke-1, halaman 2 bulan ke-2, halaman 3 bulan ke-3 nya
 * setiap triwulan nya") - menumpuk 3 instance App\Exports\
 * FormulirBosK7bSheetExport (1 per bulan dalam triwulan terpilih) pada
 * SATU Worksheet yang sama, masing-masing dengan $barisAwal berbeda
 * (lihat FormulirBosK7bSheetExport::$barisAwal, BARU ditambahkan khusus
 * untuk fitur ini - TIDAK ADA perubahan pada unduhan 1 bulan yang sudah
 * ada) & page break PhpSpreadsheet (Worksheet::BREAK_ROW) di antara tiap
 * blok bulan, supaya saat dicetak tiap bulan mulai di halaman baru.
 *
 * HANYA dipakai saat Superadmin memilih 1 sekolah tertentu ATAU Admin
 * BOSP - BUKAN mode "Semua Sekolah" (dikonfirmasi lewat AskUserQuestion
 * 2026-10-25: "Dikosongkan/dilewati saat mode Semua Sekolah").
 */
class FormulirBosK7bLpjSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    use TerapkanJudulLpj;

    private const JARAK_ANTAR_BLOK = 3;

    /**
     * @param  array<int, array{sekolah: ProfilSekolah, tahun: int, bulan: int, data: array, pengaturanCetak: array}>  $dataPerBulan  3 entry (1 per bulan dalam triwulan terpilih).
     */
    public function __construct(
        protected array $dataPerBulan,
        protected string $judulSheetLpj,
        protected int $triwulanLpj,
        protected int $tahunLpj,
        protected ?ProfilSekolah $sekolahLpj,
        protected string $kolomTerakhirLpj,
    ) {}

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Formulir BOS K7b';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->tulisSeluruhBulan($event->sheet->getDelegate());
            },
        ];
    }

    private function tulisSeluruhBulan(Worksheet $sheet): void
    {
        $barisAwal = 1;
        $jumlahBulan = count($this->dataPerBulan);

        foreach ($this->dataPerBulan as $i => $bulanData) {
            $export = new FormulirBosK7bSheetExport(
                $bulanData['sekolah'],
                $bulanData['tahun'],
                $bulanData['bulan'],
                $bulanData['data'],
                $bulanData['pengaturanCetak'],
                $barisAwal,
            );

            $barisTerakhir = $export->tulisFormulir($sheet);

            if ($i < $jumlahBulan - 1) {
                $sheet->setBreak('A'.$barisTerakhir, Worksheet::BREAK_ROW);
            }

            $barisAwal = $barisTerakhir + self::JARAK_ANTAR_BLOK;
        }

        $this->tambahkanJudulDanLayoutLpj(
            $sheet,
            $this->judulSheetLpj,
            $this->triwulanLpj,
            $this->tahunLpj,
            $this->sekolahLpj,
            $this->kolomTerakhirLpj,
            aturPageSetup: false,
        );
    }
}
