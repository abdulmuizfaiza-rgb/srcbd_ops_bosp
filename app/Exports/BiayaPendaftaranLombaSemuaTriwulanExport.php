<?php

namespace App\Exports;

use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Bungkus 4 sheet (Biaya-Pendaftaran TW-1 s.d. TW-4), masing-masing berisi
 * data SEMUA sekolah (pakai BiayaPendaftaranLombaExport dengan $sekolah =
 * null, mode "REKAP SELURUH SEKOLAH"). Nama sheet memakai class anonim
 * yang meng-override title() saja (TANPA mengubah BiayaPendaftaranLombaExport
 * aslinya) supaya nama sheet PERSIS sesuai permintaan user 2026-09-27
 * ("Biaya-Pendaftaran TW-1" dst), bukan judul default class itu
 * ("Lomba-Bimtek TW1") yang dipakai tombol Export Excel per-sekolah yang
 * sudah ada (TIDAK disentuh).
 */
class BiayaPendaftaranLombaSemuaTriwulanExport implements Export, WithMultipleSheets
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
            $sheets[] = new class($baris, $triwulan, $this->tahun) extends BiayaPendaftaranLombaExport
            {
                public function __construct(SupportCollection $baris, protected int $triwulanUntukJudul, int $tahun)
                {
                    parent::__construct($baris, $triwulanUntukJudul, $tahun, null);
                }

                public function title(): string
                {
                    return 'Biaya-Pendaftaran TW-'.$this->triwulanUntukJudul;
                }
            };
        }

        return $sheets;
    }
}
