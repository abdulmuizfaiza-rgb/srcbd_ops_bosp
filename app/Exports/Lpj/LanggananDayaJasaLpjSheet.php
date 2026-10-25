<?php

namespace App\Exports\Lpj;

use App\Exports\LanggananDayaJasaExport;
use App\Exports\Lpj\Concerns\TerapkanJudulLpj;
use App\Models\ProfilSekolah;
use Maatwebsite\Excel\Events\AfterSheet;

class LanggananDayaJasaLpjSheet extends LanggananDayaJasaExport
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
        $events = parent::registerEvents();
        $afterSheetAsli = $events[AfterSheet::class] ?? null;

        $events[AfterSheet::class] = function (AfterSheet $event) use ($afterSheetAsli) {
            if ($afterSheetAsli) {
                $afterSheetAsli($event);
            }

            $this->tambahkanJudulDanLayoutLpj(
                $event->sheet->getDelegate(),
                $this->judulSheetLpj,
                $this->triwulanLpj,
                $this->tahunLpj,
                $this->sekolahLpj,
                $this->kolomTerakhirLpj,
            );
        };

        return $events;
    }
}
