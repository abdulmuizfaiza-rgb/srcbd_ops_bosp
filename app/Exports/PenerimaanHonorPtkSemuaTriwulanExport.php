<?php

namespace App\Exports;

use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Bungkus 4 sheet Penerimaan Honor PTK (Triwulan 1-4, SEMUA sekolah) jadi
 * SATU file Excel (.xlsx) - permintaan user 2026-09-26 "unduh file excel
 * yang terdiri dari 4 sheet yaitu sheet 1 Honor PTK TW-1, sheet 2 Honor
 * PTK TW-2, sheet 3 Honor PTK TW-3, sheet4 Honor PTK TW-4". Pola sama
 * seperti App\Exports\FormulirBosK7Export (implements marker "Export"
 * secara eksplisit karena WithMultipleSheets sendiri tidak meng-extend
 * Export).
 *
 * Tiap sheet memakai App\Exports\PenerimaanHonorPtkExport yang SUDAH ADA
 * (dipakai juga oleh tombol Export Excel single-triwulan/single-sekolah),
 * dengan $sekolah=null supaya judulnya otomatis menyertakan "- REKAP
 * SELURUH SEKOLAH" (lihat PenerimaanHonorPtkExport::tulisJudul()) - TIDAK
 * ada perubahan pada PenerimaanHonorPtkExport itu sendiri.
 */
class PenerimaanHonorPtkSemuaTriwulanExport implements Export, WithMultipleSheets
{
    /**
     * @param  array<int, SupportCollection>  $dataPerTriwulan  kunci = nomor triwulan (1-4), nilai = koleksi baris PenerimaanHonorPtk (SUDAH diurutkan Status-Kecamatan-Nama Sekolah-Nama Penerima, lihat Livewire\PendataanBosp\PenerimaanHonorPtk\Index::baruSemuaSekolahUntukTriwulan()).
     */
    public function __construct(
        protected array $dataPerTriwulan,
        protected int $tahun,
    ) {}

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->dataPerTriwulan as $triwulan => $baris) {
            $sheets[] = new PenerimaanHonorPtkExport($baris, $triwulan, $this->tahun, null);
        }

        return $sheets;
    }
}
