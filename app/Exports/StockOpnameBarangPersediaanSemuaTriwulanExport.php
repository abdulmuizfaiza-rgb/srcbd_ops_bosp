<?php

namespace App\Exports;

use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Bungkus 4 sheet (Stock Opname TW-1 s.d. TW-4), masing-masing berisi
 * data SEMUA sekolah (pakai StockOpnameBarangPersediaanExport dengan
 * $sekolah = null, mode "REKAP SELURUH SEKOLAH"). Nama sheet memakai
 * class anonim yang meng-override title() saja (TANPA mengubah
 * StockOpnameBarangPersediaanExport aslinya) supaya nama sheet PERSIS
 * sesuai permintaan user 2026-09-27 ("Stock Opname TW-1" dst).
 */
class StockOpnameBarangPersediaanSemuaTriwulanExport implements Export, WithMultipleSheets
{
    /**
     * @param  array<int, SupportCollection>  $dataPerTriwulan
     */
    public function __construct(
        protected array $dataPerTriwulan,
        protected int $tahun,
    ) {}

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->dataPerTriwulan as $triwulan => $baris) {
            $sheets[] = new class($baris, $triwulan, $this->tahun) extends StockOpnameBarangPersediaanExport
            {
                public function __construct(SupportCollection $baris, protected int $triwulanUntukJudul, int $tahun)
                {
                    parent::__construct($baris, $triwulanUntukJudul, $tahun, null);
                }

                public function title(): string
                {
                    return 'Stock Opname TW-'.$this->triwulanUntukJudul;
                }
            };
        }

        return $sheets;
    }
}
