<?php

namespace App\Exports;

use App\Models\ProfilSekolah;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Bungkus 2 sheet Formulir BOS K7b & K7c (1 sekolah + 1 tahun + 1 bulan)
 * jadi SATU file Excel (.xlsx) - pola sama seperti UnduhanLampiranExport
 * (Lampiran 2a/2b/2c). Implements marker "Export" secara eksplisit karena
 * WithMultipleSheets sendiri tidak meng-extend Export.
 */
class FormulirBosK7Export implements Export, WithMultipleSheets
{
    /**
     * @param  array<string, mixed>  $data  Hasil Index::dataFormulir().
     * @param  array{kertas?: string, margin?: array{kiri: float, kanan: float, atas: float, bawah: float}}  $pengaturanCetak
     *         Jenis kertas & margin halaman (permintaan user 2026-09-23,
     *         round ketiga - tombol "Jenis Kertas" & "Setting Margin").
     *         Default 'a4' + Left/Right 2.5cm, Top 3cm, Bottom 2.5cm kalau
     *         tidak dikirim, lihat FormulirBosK7bSheetExport/
     *         FormulirBosK7cSheetExport::pengaturanHalaman().
     */
    public function __construct(
        protected ProfilSekolah $sekolah,
        protected int $tahun,
        protected int $bulan,
        protected array $data,
        protected array $pengaturanCetak = [],
    ) {}

    public function sheets(): array
    {
        return [
            new FormulirBosK7bSheetExport($this->sekolah, $this->tahun, $this->bulan, $this->data, $this->pengaturanCetak),
            new FormulirBosK7cSheetExport($this->sekolah, $this->tahun, $this->bulan, $this->data, $this->pengaturanCetak),
        ];
    }
}
