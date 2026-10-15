<?php

namespace App\Exports;

use App\Models\ProfilSekolah;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Bungkus 5 sheet (satu triwulan+tahun) jadi SATU file Excel (.xlsx)
 * untuk menu Unduhan (Pendataan OPS > Unduhan): Lembar Validasi TPG,
 * Lampiran 2a, Lampiran 2b, Lampiran 2c, Data Cair/Belum TPG - URUTAN
 * INI WAJIB dipertahankan (permintaan user 2026-10-16: "sheet 1 Lembar
 * Validasi TPG, sheet 2 lampiran 2a, sheet 3 lampiran 2b, sheet 4
 * lampiran 2c, sheet 5 Data Cair/Belum TPG").
 *
 * Sheet Lembar Validasi TPG & Data Cair/Belum TPG BARU ditambahkan
 * 2026-10-16 (sebelumnya export ini hanya 3 sheet Lampiran 2a/2b/2c) -
 * lihat App\Exports\LembarValidasiTpgExport & App\Exports\RekapCairTpgExport
 * utk detail formatnya, dan App\Support\LembarValidasiTpgUnduhanData /
 * App\Support\RekapCairTpgUnduhanData utk cara datanya diambil.
 *
 * $sekolah null berarti rekap gabungan seluruh sekolah (khusus
 * Superadmin) - diteruskan ke masing-masing sheet Export supaya lembar
 * tanda tangan Kepala Sekolah/Pengawas (atau Kasie KPML/Pengawas/Kepala
 * Sekolah pada 2 sheet baru) otomatis dilewati (lihat
 * Lampiran2aExport/Lampiran2bExport/Lampiran2cExport::tulisLembarTandaTangan()
 * & LembarValidasiTpgExport/RekapCairTpgExport::tulisTandaTangan()).
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
     * @param  array{baris: SupportCollection, tanggalVerifikasi: ?string, catatanHasilVerifikasi: ?string}  $dataLembarValidasi  dari App\Support\LembarValidasiTpgUnduhanData::ambil()
     * @param  array{baris: SupportCollection, catatanHasilVerifikasi: ?string}  $dataRekapCair  dari App\Support\RekapCairTpgUnduhanData::ambil()
     */
    public function __construct(
        protected SupportCollection $baris2a,
        protected SupportCollection $baris2b,
        protected SupportCollection $baris2c,
        protected int $triwulan,
        protected int $tahun,
        protected ?ProfilSekolah $sekolah = null,
        protected array $dataLembarValidasi = ['baris' => new SupportCollection, 'tanggalVerifikasi' => null, 'catatanHasilVerifikasi' => null],
        protected array $dataRekapCair = ['baris' => new SupportCollection, 'catatanHasilVerifikasi' => null],
    ) {}

    public function sheets(): array
    {
        return [
            new LembarValidasiTpgExport(
                $this->dataLembarValidasi['baris'],
                $this->triwulan,
                $this->tahun,
                $this->sekolah,
                $this->dataLembarValidasi['tanggalVerifikasi'],
                $this->dataLembarValidasi['catatanHasilVerifikasi'],
            ),
            new Lampiran2aExport($this->baris2a, $this->triwulan, $this->tahun, $this->sekolah),
            new Lampiran2bExport($this->baris2b, $this->triwulan, $this->tahun, $this->sekolah),
            new Lampiran2cExport($this->baris2c, $this->triwulan, $this->tahun, $this->sekolah),
            new RekapCairTpgExport(
                $this->dataRekapCair['baris'],
                $this->triwulan,
                $this->tahun,
                $this->sekolah,
                $this->dataRekapCair['catatanHasilVerifikasi'],
            ),
        ];
    }
}
