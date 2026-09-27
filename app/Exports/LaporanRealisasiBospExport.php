<?php

namespace App\Exports;

use App\Models\LaporanRealisasiBosp;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Export "Unduh Excel" menu Laporan Realisasi BOSP (Form BPK) - 5 sheet:
 * "Form BPK TW-1" s.d. "TW-4" (SEMUA sekolah, 1 baris/sekolah, kolom
 * sesuai LaporanRealisasiBosp::LABEL_KOLOM) + "Rekap Form BPK TA" (1
 * sheet gabungan, 5 baris/sekolah: Triwulan 1-4 + Jumlah, pola kolom
 * "Triwulan" di depan sama seperti RincianBelanjaModalBmdRekapExport) -
 * permintaan user 2026-09-27 poin 10.
 *
 * BEDA dengan Export lain di codebase ini: menu ini TIDAK PUNYA model
 * Eloquent per-baris yang bisa langsung di-export (SELURUH kolom 8-29
 * adalah hasil rumus/agregat REAL-TIME dari banyak menu sumber, TIDAK
 * disimpan ke database - lihat docblock App\Models\LaporanRealisasiBosp
 * & App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index::renderTabTriwulan()/
 * renderTabRekap()). Karena itu constructor di sini menerima ARRAY data
 * yang SUDAH dihitung lebih dulu oleh Livewire Index (memanggil ulang
 * method protected yang sama persis dipakai tampilan tab TW1-4/Rekap
 * biasa, supaya angkanya PASTI sama dengan yang tampil di aplikasi),
 * BUKAN Collection model seperti Export lain.
 */
class LaporanRealisasiBospExport implements Export, WithMultipleSheets
{
    /**
     * @param  array<int, array{daftarSekolah: \Illuminate\Support\Collection, totalBaris: array<string, mixed>}>  $dataPerTriwulan  Kunci = triwulan (1-4).
     * @param  array{hasil: array, totalTahunAnggaran: array}  $dataRekap
     */
    public function __construct(
        protected array $dataPerTriwulan,
        protected array $dataRekap,
        protected int $tahun,
    ) {}

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->dataPerTriwulan as $triwulan => $data) {
            $sheets[] = new LaporanRealisasiBospTriwulanSheet($data['daftarSekolah'], $data['totalBaris'], $triwulan);
        }

        $sheets[] = new LaporanRealisasiBospRekapSheet($this->dataRekap['hasil'], $this->dataRekap['totalTahunAnggaran'], $this->tahun);

        return $sheets;
    }
}

/**
 * 1 sheet "Form BPK TW-N" - SEMUA sekolah, 1 baris/sekolah. Kelas
 * terpisah (bukan anonim) karena dipakai murni dari
 * LaporanRealisasiBospExport::sheets() di atas.
 */
class LaporanRealisasiBospTriwulanSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @param  \Illuminate\Support\Collection  $daftarSekolah  Hasil ProfilSekolah::with('laporanRealisasiBosp')... (lihat renderTabTriwulan()).
     * @param  array<string, mixed>  $totalBaris  $this->baris[$sekolah->id] per sekolah (dari renderTabTriwulan()).
     */
    public function __construct(
        protected \Illuminate\Support\Collection $daftarSekolah,
        protected array $totalBaris,
        protected int $triwulan,
    ) {}

    public function title(): string
    {
        return 'Form BPK TW-'.$this->triwulan;
    }

    public function headings(): array
    {
        return array_merge(
            ['No', 'NPSN', 'Nama Sekolah'],
            array_values(LaporanRealisasiBosp::LABEL_KOLOM)
        );
    }

    public function array(): array
    {
        $baris = [];
        $urutanField = array_keys(LaporanRealisasiBosp::LABEL_KOLOM);

        foreach ($this->daftarSekolah as $i => $sekolah) {
            $data = $this->totalBaris[$sekolah->id] ?? [];
            $kolomNilai = [];
            foreach ($urutanField as $field) {
                $nilai = $data[$field] ?? '';
                $kolomNilai[] = $field === 'verifikasi_saldo' ? ($nilai !== '' ? $nilai : '-') : (int) $nilai;
            }

            $baris[] = array_merge([
                $i + 1,
                $sekolah->npsn,
                $sekolah->nama_sekolah,
            ], $kolomNilai);
        }

        return $baris;
    }
}

/**
 * Sheet "Rekap Form BPK TA" - gabungan 4 triwulan + baris "Jumlah" per
 * sekolah (pola sama seperti tab "rekap" di aplikasi, lihat
 * App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index::renderTabRekap()),
 * ditutup baris "JUMLAH TAHUN ANGGARAN {tahun}" - kolom "Triwulan" di
 * depan (bukan di belakang), pola sama seperti
 * RincianBelanjaModalBmdRekapExport.
 */
class LaporanRealisasiBospRekapSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @param  array<int, array{sekolah: \App\Models\ProfilSekolah, perTriwulan: array<int, array<string, mixed>>, jumlah: array<string, mixed>}>  $hasil  Dari renderTabRekap().
     * @param  array<string, mixed>  $totalTahunAnggaran
     */
    public function __construct(
        protected array $hasil,
        protected array $totalTahunAnggaran,
        protected int $tahun,
    ) {}

    public function title(): string
    {
        return 'Rekap Form BPK TA';
    }

    public function headings(): array
    {
        return array_merge(
            ['No', 'Triwulan', 'NPSN', 'Nama Sekolah'],
            array_values(LaporanRealisasiBosp::LABEL_KOLOM)
        );
    }

    private function baris(array $data, array $urutanField): array
    {
        $kolomNilai = [];
        foreach ($urutanField as $field) {
            $nilai = $data[$field] ?? '';
            $kolomNilai[] = $field === 'verifikasi_saldo' ? ($nilai !== null && $nilai !== '' ? $nilai : '-') : (int) $nilai;
        }

        return $kolomNilai;
    }

    public function array(): array
    {
        $urutanField = array_keys(LaporanRealisasiBosp::LABEL_KOLOM);
        $baris = [];
        $no = 1;

        foreach ($this->hasil as $item) {
            $sekolah = $item['sekolah'];

            foreach ([1, 2, 3, 4] as $triwulan) {
                $baris[] = array_merge(
                    [$no++, 'Triwulan '.$triwulan, $sekolah->npsn, $sekolah->nama_sekolah],
                    $this->baris($item['perTriwulan'][$triwulan] ?? [], $urutanField)
                );
            }

            $baris[] = array_merge(
                [$no++, 'Jumlah', $sekolah->npsn, $sekolah->nama_sekolah],
                $this->baris($item['jumlah'] ?? [], $urutanField)
            );
        }

        $baris[] = array_merge(
            ['', 'JUMLAH TAHUN ANGGARAN '.$this->tahun, '', ''],
            $this->baris($this->totalTahunAnggaran, $urutanField)
        );

        return $baris;
    }
}
