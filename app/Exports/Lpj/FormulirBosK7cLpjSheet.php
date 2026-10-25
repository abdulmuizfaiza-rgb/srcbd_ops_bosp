<?php

namespace App\Exports\Lpj;

use App\Exports\FormulirBosK7cSheetExport;
use App\Exports\Lpj\Concerns\TerapkanJudulLpj;
use App\Models\ProfilSekolah;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Formulir BOS K7c" pada file gabungan "Unduhan LPJ BOSP" -
 * lihat docblock App\Exports\Lpj\FormulirBosK7bLpjSheet (pola identik,
 * hanya mengganti kelas Export yang ditumpuk).
 */
class FormulirBosK7cLpjSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
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
        return 'Formulir BOS K7c';
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
            $export = new FormulirBosK7cSheetExport(
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
        );
    }
}
