<?php

namespace App\Exports;

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
use PhpOffice\PhpSpreadsheet\Style\Fill;
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
 *
 * SEJAK permintaan user 2026-10-18: kolom Status (dropdown) diberi
 * warna kuning (FFFBEB) & 1 baris keterangan ditambahkan tepat di atas
 * tabel (baris 1) - pola sama persis dengan App\Exports\DataPtkExport,
 * lihat docblock kelas itu untuk detail & catatan keterbatasannya.
 * Header & data digeser 1 baris ke bawah lewat
 * WithCustomStartCell::startCell().
 */
class ProfilSekolahExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_KETERANGAN = 1;

    private const BARIS_HEADER_TABEL = 2;

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

    /**
     * Header & data digeser ke baris 2 supaya baris 1 bisa dipakai utk
     * keterangan kolom kuning (permintaan user 2026-10-18).
     */
    public function startCell(): string
    {
        return 'A'.self::BARIS_HEADER_TABEL;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A'.self::BARIS_HEADER_TABEL.':D'.self::BARIS_HEADER_TABEL)
                    ->getFont()->setBold(true);

                $this->tulisKeteranganKolomPilihan($sheet);
                $this->tulisDaftarPilihanTersembunyi($sheet);
            },
        ];
    }

    /**
     * Baris keterangan di atas tabel (permintaan user 2026-10-18) -
     * pola sama persis dgn App\Exports\DataPtkExport::
     * tulisKeteranganKolomPilihan().
     */
    private function tulisKeteranganKolomPilihan(Worksheet $sheet): void
    {
        $rentang = 'A'.self::BARIS_KETERANGAN.':D'.self::BARIS_KETERANGAN;

        $sheet->mergeCells($rentang);
        $sheet->setCellValue('A'.self::BARIS_KETERANGAN, 'Kolom berwarna kuning: silahkan pilih dari dropdown yang tersedia, jangan diketik manual atau copy-paste, untuk meminimalisir kesalahan saat diimpor kembali.');
        $sheet->getStyle($rentang)->applyFromArray([
            'font' => ['italic' => true, 'bold' => true, 'color' => ['rgb' => '9A3412']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'wrapText' => true],
        ]);
        $sheet->getRowDimension(self::BARIS_KETERANGAN)->setRowHeight(30);
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

        // Warna kuning (permintaan user 2026-10-18, lihat
        // tulisKeteranganKolomPilihan()) - rentang sama dgn dropdown-nya.
        $sheet->getStyle('C'.(self::BARIS_HEADER_TABEL + 1).':C'.$this->barisAkhirValidasi())
            ->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
            ]);
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
