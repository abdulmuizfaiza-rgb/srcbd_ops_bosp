<?php

namespace App\Exports;

use App\Models\ProfilSekolah;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Bungkus 3 sheet Lampiran 2a/2b/2c (satu triwulan+tahun) jadi SATU file
 * Excel (.xlsx) untuk menu Unduhan (Pendataan OPS > Unduhan).
 *
 * $sekolah null berarti rekap gabungan seluruh sekolah (khusus
 * Superadmin) - diteruskan ke masing-masing sheet Export supaya lembar
 * tanda tangan Kepala Sekolah/Pengawas otomatis dilewati (lihat
 * Lampiran2aExport/Lampiran2bExport/Lampiran2cExport::tulisLembarTandaTangan()).
 *
 * Implements the "Export" marker interface (kosong, tidak butuh method
 * tambahan) secara eksplisit karena WithMultipleSheets sendiri TIDAK
 * meng-extend Export - tanpa ini, Excel::download() menolak instance
 * class ini dengan TypeError.
 */
class UnduhanLampiranExport implements Export, WithMultipleSheets
{
    /**
     * @param  SupportCollection<int, \App\Models\Lampiran2a>  $baris2a
     * @param  SupportCollection<int, \App\Models\Lampiran2b>  $baris2b
     * @param  SupportCollection<int, \App\Models\Lampiran2c>  $baris2c
     */
    public function __construct(
        protected SupportCollection $baris2a,
        protected SupportCollection $baris2b,
        protected SupportCollection $baris2c,
        protected int $triwulan,
        protected int $tahun,
        protected ?ProfilSekolah $sekolah = null,
    ) {}

    public function sheets(): array
    {
        return [
            new Lampiran2aExport($this->baris2a, $this->triwulan, $this->tahun, $this->sekolah),
            new Lampiran2bExport($this->baris2b, $this->triwulan, $this->tahun, $this->sekolah),
            new Lampiran2cExport($this->baris2c, $this->triwulan, $this->tahun, $this->sekolah),
        ];
    }
}
