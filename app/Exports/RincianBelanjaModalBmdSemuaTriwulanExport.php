<?php

namespace App\Exports;

use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Bungkus 4 sheet (BMD TW-1 s.d. TW-4), masing-masing berisi data SEMUA
 * sekolah (pakai RincianBelanjaModalBmdExport dengan $sekolah = null,
 * mode "REKAP SELURUH SEKOLAH" - sudah didukung sejak 2026-09-22). Nama
 * sheet memakai class anonim yang meng-override title() saja (TANPA
 * mengubah RincianBelanjaModalBmdExport aslinya, TIDAK menyentuh formula/
 * dropdown/warna yang sudah ada di class itu) supaya nama sheet PERSIS
 * sesuai permintaan user 2026-09-27 ("BMD TW-1" dst). TIDAK berkaitan
 * dengan RincianBelanjaModalBmdRekapExport (fitur "Rekap BMD Tahun
 * Anggaran" yang sudah ada, 1 sheet gabungan 4 triwulan) - keduanya
 * sengaja dipertahankan terpisah sesuai jawaban AskUserQuestion
 * 2026-09-27 ("Tetap tambahkan 'Unduh Excel 4 sheet' terpisah + 'Unduh
 * PDF'").
 */
class RincianBelanjaModalBmdSemuaTriwulanExport implements Export, WithMultipleSheets
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
            $sheets[] = new class($baris, $triwulan, $this->tahun) extends RincianBelanjaModalBmdExport
            {
                public function __construct(SupportCollection $baris, protected int $triwulanUntukJudul, int $tahun)
                {
                    parent::__construct($baris, $triwulanUntukJudul, $tahun, null);
                }

                public function title(): string
                {
                    return 'BMD TW-'.$this->triwulanUntukJudul;
                }
            };
        }

        return $sheets;
    }
}
