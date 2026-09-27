<?php

namespace App\Exports;

use App\Models\BelanjaHonorKegiatan;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Bungkus 4 sheet (1 per triwulan), masing-masing berisi data SEMUA
 * sekolah untuk 1 JENIS/tab utama yang sedang aktif (Honor Kegiatan /
 * Belanja Mamin / Perjalanan Dinas) - pakai BelanjaHonorKegiatanExport
 * dengan $sekolah = null (mode "REKAP SELURUH SEKOLAH"). Nama sheet
 * memakai class anonim yang meng-override title() saja (TANPA mengubah
 * BelanjaHonorKegiatanExport aslinya) supaya nama sheet PERSIS sesuai
 * permintaan user 2026-09-27: "Honor Kegiatan TW-1..4" / "Belanja Mamin
 * TW-1..4" / "Perjalanan Dinas TW-1..4".
 */
class BelanjaHonorKegiatanSemuaTriwulanExport implements Export, WithMultipleSheets
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
        $singkatan = match ($this->jenis) {
            BelanjaHonorKegiatan::JENIS_MAKAN_MINUM => 'Belanja Mamin',
            BelanjaHonorKegiatan::JENIS_PERJALANAN_DINAS => 'Perjalanan Dinas',
            default => 'Honor Kegiatan',
        };

        return $singkatan.' TW-'.$triwulan;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->dataPerTriwulan as $triwulan => $baris) {
            $judulSheet = $this->namaSheet($triwulan);

            $sheets[] = new class($baris, $this->jenis, $triwulan, $this->tahun, $judulSheet) extends BelanjaHonorKegiatanExport
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
