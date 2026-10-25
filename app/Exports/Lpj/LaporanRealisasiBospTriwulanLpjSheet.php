<?php

namespace App\Exports\Lpj;

use App\Exports\Lpj\Concerns\TerapkanJudulLpj;
use App\Models\ProfilSekolah;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

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
                $this->tambahkanJudulDanLayoutLpj(
                    $event->sheet->getDelegate(),
                    $this->judulSheetLpj,
                    $this->triwulanLpj,
                    $this->tahunLpj,
                    $this->sekolahLpj,
                    $this->kolomTerakhirLpj,
                );
            },
        ];
    }
}
