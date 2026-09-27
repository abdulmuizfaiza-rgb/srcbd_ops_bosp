<?php

namespace App\Exports;

use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Bungkus 4 sheet Langganan Daya dan Jasa (Triwulan 1-4, SEMUA sekolah)
 * jadi SATU file Excel (.xlsx) - permintaan user 2026-09-27 ("unduh file
 * excel yang terdiri dari 4 sheet yaitu sheet 1 Daya-Jasa TW-1, ...
 * sheet4 Daya-Jasa TW-4"). Pola sama persis seperti
 * App\Exports\PenerimaanHonorPtkSemuaTriwulanExport (dibuat 2026-09-26).
 *
 * Tiap sheet memakai App\Exports\LanggananDayaJasaExport yang SUDAH ADA
 * (dipakai juga oleh tombol Export Excel single-triwulan/single-sekolah),
 * dengan $sekolah=null supaya judulnya otomatis menyertakan "- REKAP
 * SELURUH SEKOLAH" (lihat LanggananDayaJasaExport::tulisJudul()) - TIDAK
 * ada perubahan pada LanggananDayaJasaExport itu sendiri.
 */
class LanggananDayaJasaSemuaTriwulanExport implements Export, WithMultipleSheets
{
    /**
     * @param  array<int, SupportCollection>  $dataPerTriwulan  kunci = nomor triwulan (1-4), nilai = koleksi baris LanggananDayaJasa (SUDAH diurutkan Status-Kecamatan-Nama Sekolah, lihat Livewire\PendataanBosp\LanggananDayaJasa\Index::baruSemuaSekolahUntukTriwulan()).
     */
    public function __construct(
        protected array $dataPerTriwulan,
        protected int $tahun,
    ) {}

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->dataPerTriwulan as $triwulan => $baris) {
            $sheets[] = new LanggananDayaJasaExport($baris, $triwulan, $this->tahun, null);
        }

        return $sheets;
    }
}
