<?php

namespace App\Exports;

use App\Models\RincianBelanjaModal;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Bungkus 4 sheet (1 per triwulan), masing-masing berisi data SEMUA
 * sekolah untuk 1 JENIS/tab utama yang sedang aktif (KIB B/Peralatan &
 * Mesin, atau KIB E/Aset Tetap Lainnya) - pakai RincianBelanjaModalExport
 * dengan $sekolah = null (mode "REKAP SELURUH SEKOLAH"). Nama sheet
 * memakai class anonim yang meng-override title() saja (TANPA mengubah
 * RincianBelanjaModalExport aslinya) supaya nama sheet PERSIS sesuai
 * permintaan user 2026-09-27: "Belanja KIB-B TW-1..4" / "Belanja KIB-E
 * TW-1..4".
 */
class RincianBelanjaModalSemuaTriwulanExport implements Export, WithMultipleSheets
{
    /**
     * @param  array<int, SupportCollection>  $dataPerTriwulan
     */
    public function __construct(
        protected array $dataPerTriwulan,
        protected string $jenis,
        protected int $tahun,
    ) {}

    private function namaSheet(int $triwulan): string
    {
        $singkatan = $this->jenis === RincianBelanjaModal::JENIS_ASET_TETAP_LAINNYA ? 'KIB-E' : 'KIB-B';

        return 'Belanja '.$singkatan.' TW-'.$triwulan;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->dataPerTriwulan as $triwulan => $baris) {
            $judulSheet = $this->namaSheet($triwulan);

            $sheets[] = new class($baris, $this->jenis, $triwulan, $this->tahun, $judulSheet) extends RincianBelanjaModalExport
            {
                public function __construct(SupportCollection $baris, string $jenis, int $triwulan, int $tahun, protected string $judulSheet)
                {
                    parent::__construct($baris, $jenis, $triwulan, $tahun, null);
                }

                public function title(): string
                {
                    return $this->judulSheet;
                }
            };
        }

        return $sheets;
    }
}
