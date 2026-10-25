<?php

namespace App\Exports\Lpj;

use App\Exports\PenerimaanHonorPtkExport;
use App\Exports\Lpj\Concerns\TerapkanJudulLpj;
use App\Models\ProfilSekolah;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * Pembungkus PenerimaanHonorPtkExport khusus untuk file gabungan "Unduhan LPJ
 * BOSP" (menu baru, permintaan user 2026-10-25) - lihat docblock
 * App\Exports\Lpj\Concerns\TerapkanJudulLpj untuk penjelasan lengkap.
 * PenerimaanHonorPtkExport ASLI TIDAK diubah sama sekali - kelas ini HANYA
 * menambah blok judul LPJ & page setup Legal/Landscape di atas hasil
 * AfterSheet aslinya.
 */
class PenerimaanHonorPtkLpjSheet extends PenerimaanHonorPtkExport
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
