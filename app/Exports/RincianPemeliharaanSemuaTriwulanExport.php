<?php

namespace App\Exports;

use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Bungkus 4 sheet Rincian Pemeliharaan Bangunan (Triwulan 1-4, SEMUA
 * sekolah, 1 jenis - barang ATAU jasa, sesuai tab utama yang aktif saat
 * tombol ditekan) jadi SATU file Excel (.xlsx) - permintaan user
 * 2026-09-27. Pola sama persis seperti
 * App\Exports\PenerimaanHonorPtkSemuaTriwulanExport.
 *
 * Tiap sheet memakai App\Exports\RincianPemeliharaanExport yang SUDAH ADA
 * (dipakai juga oleh tombol Export Excel single-triwulan/single-sekolah),
 * dengan $sekolah=null supaya judulnya otomatis menyertakan "- REKAP
 * SELURUH SEKOLAH" - TIDAK ada perubahan pada RincianPemeliharaanExport
 * itu sendiri.
 */
class RincianPemeliharaanSemuaTriwulanExport implements Export, WithMultipleSheets
{
    /**
     * @param  array<int, SupportCollection>  $dataPerTriwulan  kunci = nomor triwulan (1-4), nilai = koleksi baris RincianPemeliharaan (SUDAH difilter 1 jenis & diurutkan Status-Kecamatan-Nama Sekolah, lihat Livewire\PendataanBosp\BelanjaPemeliharaanBangunan\Index::baruSemuaSekolahUntukTriwulan()).
     */
    public function __construct(
        protected array $dataPerTriwulan,
        protected string $jenis,
        protected int $tahun,
    ) {}

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->dataPerTriwulan as $triwulan => $baris) {
            $sheets[] = new RincianPemeliharaanExport($baris, $this->jenis, $triwulan, $this->tahun, null);
        }

        return $sheets;
    }
}
