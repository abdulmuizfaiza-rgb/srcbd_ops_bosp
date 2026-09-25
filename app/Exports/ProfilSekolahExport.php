<?php

namespace App\Exports;

use App\Models\ProfilSekolah;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export data induk Profil Sekolah (NPSN, Nama Sekolah, Status,
 * Kecamatan) untuk pendataan awal oleh Superadmin.
 *
 * Sengaja HANYA berisi 4 kolom ini - detail lainnya (Kepala Sekolah,
 * Pengawas, Bendahara, Alamat) TIDAK ikut diexport/diimport lewat sini,
 * karena field-field tersebut wajib dilengkapi langsung oleh Admin
 * OPS/Admin BOSP masing-masing sekolah lewat menu Profil Sekolah setelah
 * mereka login.
 *
 * Kolom Status diberi dropdown (Data Validation Excel) berisi "Negeri"
 * dan "Swasta" supaya penulisannya sama dengan pilihan yang ada di
 * aplikasi saat file ini diisi lalu diimport kembali.
 *
 * Urutan kolom WAJIB mengikuti: NPSN, Nama Sekolah, Status, Kecamatan -
 * jangan diubah urutannya tanpa persetujuan.
 */
class ProfilSekolahExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_HEADER_TABEL = 1;

    /**
     * @param  SupportCollection<int, ProfilSekolah>  $daftar
     */
    public function __construct(
        protected SupportCollection $daftar,
    ) {}

    public function collection(): SupportCollection
    {
        return $this->daftar;
    }

    public function headings(): array
    {
        return ['NPSN', 'Nama Sekolah', 'Status', 'Kecamatan'];
    }

    /**
     * @param  ProfilSekolah  $sekolah
     */
    public function map($sekolah): array
    {
        return [
            $sekolah->npsn,
            $sekolah->nama_sekolah,
            $sekolah->status ? $sekolah->status_label : '',
            $sekolah->kecamatan,
        ];
    }

    public function title(): string
    {
        return 'Profil Sekolah';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A'.self::BARIS_HEADER_TABEL.':D'.self::BARIS_HEADER_TABEL)
                    ->getFont()->setBold(true);

                $this->tulisDaftarPilihanTersembunyi($sheet);
            },
        ];
    }

    /**
     * Dropdown Status pada hasil export supaya penulisannya selalu sama
     * persis dengan pilihan yang ada di aplikasi. Daftar sumbernya
     * ditulis ke kolom tersembunyi F lalu dirujuk lewat Data Validation
     * Excel (bukan kolom biasa).
     */
    private function tulisDaftarPilihanTersembunyi(Worksheet $sheet): void
    {
        $statusOptions = array_values(ProfilSekolah::statusOptions());
        foreach ($statusOptions as $indeks => $nilai) {
            $sheet->setCellValue('F'.($indeks + 1), $nilai);
        }

        $sheet->getColumnDimension('F')->setVisible(false);

        $this->terapkanValidasiPilihan(
            $sheet,
            'C',
            '=$F$1:$F$'.count($statusOptions),
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
        return self::BARIS_HEADER_TABEL + $this->daftar->count() + 300;
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
