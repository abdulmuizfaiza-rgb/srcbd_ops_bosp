<?php

namespace App\Exports;

use App\Models\Lampiran2a;
use App\Models\ProfilSekolah;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export data Lampiran 2a (satu triwulan, satu sekolah) ke Excel.
 *
 * Formatnya mengikuti "Lampiran Surat Rekomendasi tentang Usulan Penerima
 * Tunjangan Profesi Guru": judul di baris 1-3, tabel mulai baris 5, dan
 * lembar tanda tangan Pengawas (kolom B, NIP diisi manual) & Kepala
 * Sekolah (kolom E, NIP otomatis dari Profil Sekolah) di bagian bawah.
 *
 * Kolom Status Kepegawaian diberi dropdown (Data Validation Excel) supaya
 * penulisannya selalu sama dengan pilihan yang ada di aplikasi - daftar
 * sumbernya ditulis ke kolom tersembunyi I.
 *
 * 4 baris kosong (bergaris tabel, mengikuti lebar tabel) disediakan
 * setelah baris data terakhir sebelum lembar tanda tangan Pengawas &
 * Kepala Sekolah (baris tanda tangan sendiri TIDAK bergaris tabel).
 *
 * Urutan kolom data WAJIB mengikuti: NRG, NUPTK, Nama PTK, Status
 * Kepegawaian, Nama Sekolah, Gaji Pokok Bulan Januari, NPWP - jangan
 * diubah urutannya tanpa persetujuan.
 */
class Lampiran2aExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_HEADER_TABEL = 5;

    private const BARIS_KOSONG_SEBELUM_TANDA_TANGAN = 4;

    /**
     * @param  SupportCollection<int, Lampiran2a>  $baris
     */
    public function __construct(
        protected SupportCollection $baris,
        protected int $triwulan,
        protected int $tahun,
        protected ?ProfilSekolah $sekolah = null,
    ) {}

    public function collection(): SupportCollection
    {
        return $this->baris;
    }

    public function startCell(): string
    {
        return 'A'.self::BARIS_HEADER_TABEL;
    }

    public function title(): string
    {
        return 'Lampiran 2a.TW'.$this->triwulan;
    }

    public function headings(): array
    {
        return [
            'NRG',
            'NUPTK',
            'Nama PTK',
            'Status Kepegawaian',
            'Nama Sekolah',
            'Gaji Pokok Bulan Januari '.$this->tahun,
            'NPWP',
        ];
    }

    public function map($baris): array
    {
        return [
            $baris->nrg,
            $baris->nuptk,
            $baris->nama_ptk,
            $baris->status_kepegawaian,
            $baris->profilSekolah->nama_sekolah ?? $this->sekolah?->nama_sekolah,
            $baris->gaji_pokok_januari,
            $baris->npwp,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->tulisJudul($sheet);
                $this->rapikanTabel($sheet);
                $this->tulisLembarTandaTangan($sheet);
                $this->tulisDaftarPilihanTersembunyi($sheet);
            },
        ];
    }

    private function tulisJudul(Worksheet $sheet): void
    {
        $judul1 = 'LAMPIRAN SURAT REKOMENDASI TENTANG USULAN PENERIMA TUNJANGAN PROFESI GURU';
        if ($this->sekolah === null) {
            $judul1 .= ' - REKAP SELURUH SEKOLAH';
        }

        $sheet->setCellValue('A1', $judul1);
        $sheet->setCellValue('A2', 'TRIWULAN '.$this->triwulan.' TAHUN ANGGARAN '.$this->tahun);
        $sheet->setCellValue('A3', 'KOMISARIAT/KECAMATAN CIBADAK');

        foreach ([1, 2, 3] as $baris) {
            $rentang = 'A'.$baris.':G'.$baris;
            $sheet->mergeCells($rentang);
            $sheet->getStyle($rentang)->applyFromArray([
                'font' => ['bold' => true, 'size' => $baris === 1 ? 12 : 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }
    }

    private function barisTerakhirTabel(): int
    {
        return self::BARIS_HEADER_TABEL + $this->baris->count();
    }

    private function rapikanTabel(Worksheet $sheet): void
    {
        $barisTerakhir = $this->barisTerakhirTabel();
        $rentangHeader = 'A'.self::BARIS_HEADER_TABEL.':G'.self::BARIS_HEADER_TABEL;

        $sheet->getStyle($rentangHeader)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Garis tabel diteruskan sampai 4 baris kosong setelah baris data
        // terakhir, supaya format kolomnya tetap kelihatan meski baris itu
        // belum diisi.
        $sheet->getStyle('A'.self::BARIS_HEADER_TABEL.':G'.($barisTerakhir + self::BARIS_KOSONG_SEBELUM_TANDA_TANGAN))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        if ($this->baris->count() > 0) {
            // Format Rupiah (mis. "Rp 1.000.000") supaya sama dengan tampilan
            // di aplikasi.
            $sheet->getStyle('F'.(self::BARIS_HEADER_TABEL + 1).':F'.$barisTerakhir)
                ->getNumberFormat()->setFormatCode('"Rp" #,##0');
        }

        $sheet->freezePane('A'.(self::BARIS_HEADER_TABEL + 1));
        $sheet->setAutoFilter($rentangHeader);
        $sheet->getRowDimension(self::BARIS_HEADER_TABEL)->setRowHeight(20);
    }

    /**
     * Lembar "Mengetahui/Menyetujui" Pengawas (kolom B) & "Kepala Sekolah,"
     * (kolom F) - 4 baris kosong (bergaris tabel) setelah baris terakhir
     * tabel, lalu jabatan, lalu 2 baris kosong untuk tempat tanda tangan
     * basah (TIDAK bergaris tabel). Nama & NIP Pengawas dan Nama & NIP
     * Kepala Sekolah otomatis diambil dari menu Profil Sekolah.
     */
    private function tulisLembarTandaTangan(Worksheet $sheet): void
    {
        // Lembar tanda tangan Pengawas & Kepala Sekolah hanya berlaku untuk
        // 1 sekolah - pada rekap gabungan seluruh sekolah (Superadmin,
        // $sekolah null) tidak ada satupun Pengawas/Kepala Sekolah tunggal
        // yang bisa mewakili semua sekolah, jadi lembar ini dilewati.
        if ($this->sekolah === null) {
            return;
        }

        $barisTerakhirTabel = $this->barisTerakhirTabel();
        $barisMengetahui = $barisTerakhirTabel + self::BARIS_KOSONG_SEBELUM_TANDA_TANGAN + 1;
        $barisJabatan = $barisMengetahui + 1;
        $barisNamaPengawas = $barisJabatan + 3;
        $barisNipPengawas = $barisNamaPengawas + 1;
        $barisNamaKepsek = $barisJabatan + 3;
        $barisNipKepsek = $barisNamaKepsek + 1;

        $sheet->setCellValue('B'.$barisMengetahui, 'Mengetahui/Menyetujui :');
        $sheet->setCellValue('F'.$barisMengetahui, 'Sukabumi, .................... '.$this->tahun);

        $sheet->setCellValue('B'.$barisJabatan, 'Pengawas');
        $sheet->setCellValue('F'.$barisJabatan, 'Kepala Sekolah,');

        $sheet->setCellValue('B'.$barisNamaPengawas, $this->sekolah->nama_pengawas ?: '...................................');
        $sheet->setCellValue('B'.$barisNipPengawas, 'NIP. '.($this->sekolah->nip_pengawas ?: '...................................'));
        $sheet->setCellValue('F'.$barisNamaKepsek, $this->sekolah->nama_kepala_sekolah ?: '...................................');
        $sheet->setCellValue('F'.$barisNipKepsek, 'NIP. '.($this->sekolah->nip_kepala_sekolah ?: '...................................'));

        foreach ([$barisMengetahui, $barisJabatan, $barisNamaPengawas, $barisNipPengawas] as $baris) {
            $sheet->mergeCells('B'.$baris.':D'.$baris);
        }

        foreach ([$barisMengetahui, $barisJabatan, $barisNamaKepsek, $barisNipKepsek] as $baris) {
            $sheet->mergeCells('F'.$baris.':G'.$baris);
        }
    }

    /**
     * Dropdown Status Kepegawaian pada hasil export supaya penulisannya
     * selalu sama persis dengan pilihan yang ada di aplikasi. Daftar
     * sumbernya ditulis ke kolom tersembunyi I lalu dirujuk lewat Data
     * Validation Excel (bukan kolom biasa).
     */
    private function tulisDaftarPilihanTersembunyi(Worksheet $sheet): void
    {
        $statusKepegawaian = array_values(Lampiran2a::STATUS_KEPEGAWAIAN_OPTIONS);
        foreach ($statusKepegawaian as $indeks => $nilai) {
            $sheet->setCellValue('I'.($indeks + 1), $nilai);
        }

        $sheet->getColumnDimension('I')->setVisible(false);

        $this->terapkanValidasiPilihan(
            $sheet,
            'D',
            '=$I$1:$I$'.count($statusKepegawaian),
            self::BARIS_HEADER_TABEL + 1,
            $this->barisAkhirValidasi()
        );
    }

    /**
     * Baris terakhir tempat dropdown pilihan diterapkan - diberi buffer
     * jauh di bawah baris data terakhir supaya masih bisa dipakai kalau
     * ada baris baru yang ditambahkan manual di Excel sebelum di-import
     * lagi.
     */
    private function barisAkhirValidasi(): int
    {
        return $this->barisTerakhirTabel() + 300;
    }

    private function terapkanValidasiPilihan(Worksheet $sheet, string $kolom, string $referensiRentang, int $barisMulai, int $barisSelesai): void
    {
        for ($baris = $barisMulai; $baris <= $barisSelesai; $baris++) {
            $validasi = new DataValidation();
            $validasi->setType(DataValidation::TYPE_LIST);
            $validasi->setErrorStyle(DataValidation::STYLE_STOP);
            $validasi->setAllowBlank(true);
            $validasi->setShowInputMessage(true);
            $validasi->setShowErrorMessage(true);
            $validasi->setShowDropDown(true);
            $validasi->setErrorTitle('Nilai tidak valid');
            $validasi->setError('Silakan pilih nilai dari daftar dropdown yang tersedia.');
            $validasi->setPromptTitle('Pilih dari daftar');
            $validasi->setPrompt('Silakan pilih salah satu nilai dari daftar pilihan.');
            $validasi->setFormula1($referensiRentang);

            $sheet->getCell($kolom.$baris)->setDataValidation($validasi);
        }
    }
}
