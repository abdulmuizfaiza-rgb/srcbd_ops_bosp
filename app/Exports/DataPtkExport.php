<?php

namespace App\Exports;

use App\Models\DataPtk;
use App\Models\ProfilSekolah;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * "Export Excel" Data PTK (menu Data Sekolah > tab Data PTK) - format
 * POLOS sesuai urutan field di aplikasi, untuk diedit lalu diimport
 * kembali lewat DataPtkImport (jawaban AskUserQuestion "Dua fungsi
 * berbeda" - berbeda dari DataPtkLaporanExport/tombol "Unduh").
 *
 * Kolom Tanggal Lahir & TMT Sekolah Induk ditulis sebagai tanggal Excel
 * asli (bukan teks), dan ke-9 kolom "pilihan" (Jabatan, Pangkat/
 * Golongan, Status Kepegawaian, Jenis PTK, Pendidikan Terakhir, Status
 * Sertifikasi, Nama Sekolah, Status Dapodik, Status Keaktifan) diberi
 * dropdown (Data Validation Excel) - pola sama seperti
 * ProfilSekolahExport/Lampiran2aExport.
 *
 * Urutan kolom WAJIB mengikuti urutan field pada form tambah/edit di
 * aplikasi (lihat app/Livewire/ProfilSekolah/Index.php & permintaan user
 * 2026-10-01): NIK, NUPTK, NIP, Nama PTK, Tempat Lahir, Tanggal Lahir,
 * Jabatan, Pangkat/Golongan, Status Kepegawaian, Jenis PTK, TMT Di
 * Sekolah Induk, Pendidikan Terakhir, Jurusan/Prodi, Tahun Lulus Ijazah,
 * Status Sertifikasi, Bidang Studi Sertifikasi, Tahun Lulus Sertifikasi,
 * Nomor Sertifikat Sertifikasi, Nomor Registrasi Guru, Nomor Peserta
 * Sertifikasi, Nama Sekolah, Status Dapodik, Status Keaktifan - jangan
 * diubah urutannya tanpa persetujuan.
 */
class DataPtkExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_HEADER_TABEL = 1;

    private const KOLOM_TANGGAL_LAHIR = 'F';

    private const KOLOM_TMT_SEKOLAH_INDUK = 'K';

    /**
     * @param  SupportCollection<int, DataPtk>  $daftar
     */
    public function __construct(
        protected SupportCollection $daftar,
    ) {}

    public function collection(): SupportCollection
    {
        return $this->daftar;
    }

    public function title(): string
    {
        return 'Data PTK';
    }

    public function headings(): array
    {
        return [
            'NIK', 'NUPTK', 'NIP', 'Nama PTK', 'Tempat Lahir', 'Tanggal Lahir',
            'Jabatan', 'Pangkat / Golongan', 'Status Kepegawaian', 'Jenis PTK', 'TMT Di Sekolah Induk',
            'Pendidikan Terakhir', 'Jurusan / Prodi Sesuai Ijazah Terakhir', 'Tahun Lulus Ijazah',
            'Status Sertifikasi', 'Bidang Studi Sertifikasi', 'Tahun Lulus Sertifikasi',
            'Nomor Sertifikat Sertifikasi', 'Nomor Registrasi Guru', 'Nomor Peserta Sertifikasi',
            'Nama Sekolah', 'Status Dapodik', 'Status Keaktifan',
        ];
    }

    /**
     * @param  DataPtk  $p
     */
    public function map($p): array
    {
        return [
            $p->nik,
            $p->nuptk,
            $p->nip,
            $p->nama_ptk,
            $p->tempat_lahir,
            null, // Tanggal Lahir - ditulis ulang sbg tanggal Excel asli di tulisTanggal()
            $p->jabatan,
            $p->pangkat_golongan,
            $p->status_kepegawaian,
            $p->jenis_ptk,
            null, // TMT Di Sekolah Induk - ditulis ulang sbg tanggal Excel asli di tulisTanggal()
            $p->pendidikan_terakhir,
            $p->jurusan_prodi,
            $p->tahun_lulus_ijazah,
            $p->status_sertifikasi,
            $p->bidang_studi_sertifikasi,
            $p->tahun_lulus_sertifikasi,
            $p->nomor_sertifikat_sertifikasi,
            $p->nomor_registrasi_guru,
            $p->nomor_peserta_sertifikasi,
            $p->profilSekolah->nama_sekolah ?? '',
            $p->status_dapodik,
            $p->status_keaktifan,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A'.self::BARIS_HEADER_TABEL.':W'.self::BARIS_HEADER_TABEL)
                    ->getFont()->setBold(true);

                $this->tulisTanggal($sheet);
                $this->tulisDaftarPilihanTersembunyi($sheet);
            },
        ];
    }

    /**
     * Tulis ulang kolom Tanggal Lahir & TMT Di Sekolah Induk sebagai
     * tanggal Excel asli (numeric date value + format kalender
     * dd-mm-yyyy), sama seperti pola Lampiran2bExport::tulisTanggalTmt().
     */
    private function tulisTanggal(Worksheet $sheet): void
    {
        $baris = self::BARIS_HEADER_TABEL + 1;

        foreach ($this->daftar as $p) {
            if ($p->tanggal_lahir) {
                $sheet->setCellValue(self::KOLOM_TANGGAL_LAHIR.$baris, Date::dateTimeToExcel($p->tanggal_lahir));
                $sheet->getStyle(self::KOLOM_TANGGAL_LAHIR.$baris)->getNumberFormat()->setFormatCode('dd-mm-yyyy');
            }

            if ($p->tmt_sekolah_induk) {
                $sheet->setCellValue(self::KOLOM_TMT_SEKOLAH_INDUK.$baris, Date::dateTimeToExcel($p->tmt_sekolah_induk));
                $sheet->getStyle(self::KOLOM_TMT_SEKOLAH_INDUK.$baris)->getNumberFormat()->setFormatCode('dd-mm-yyyy');
            }

            $baris++;
        }
    }

    /**
     * Dropdown (Data Validation Excel) untuk ke-9 kolom "pilihan" supaya
     * penulisannya selalu sama persis dengan pilihan yang ada di
     * aplikasi saat file ini diisi/diedit lalu diimport kembali. Daftar
     * sumber tiap kolom ditulis ke kolom tersembunyi mulai Y - pola sama
     * seperti ProfilSekolahExport/Lampiran2aExport.
     */
    private function tulisDaftarPilihanTersembunyi(Worksheet $sheet): void
    {
        $daftarPilihan = [
            'G' => array_values(DataPtk::jabatanOptions()),
            'H' => array_values(DataPtk::pangkatGolonganOptions()),
            'I' => array_values(DataPtk::statusKepegawaianOptions()),
            'J' => array_values(DataPtk::jenisPtkOptions()),
            'L' => array_values(DataPtk::pendidikanTerakhirOptions()),
            'O' => array_values(DataPtk::statusSertifikasiOptions()),
            'U' => ProfilSekolah::urutStandar()->pluck('nama_sekolah')->all(),
            'V' => array_values(DataPtk::statusDapodikOptions()),
            'W' => array_values(DataPtk::statusKeaktifanOptions()),
        ];

        $indeksKolomTersembunyi = Coordinate::columnIndexFromString('Y');

        foreach ($daftarPilihan as $kolomData => $pilihan) {
            $kolomTersembunyi = Coordinate::stringFromColumnIndex($indeksKolomTersembunyi);

            foreach ($pilihan as $indeks => $nilai) {
                $sheet->setCellValue($kolomTersembunyi.($indeks + 1), $nilai);
            }

            $sheet->getColumnDimension($kolomTersembunyi)->setVisible(false);

            $this->terapkanValidasiPilihan(
                $sheet,
                $kolomData,
                '=$'.$kolomTersembunyi.'$1:$'.$kolomTersembunyi.'$'.count($pilihan),
                self::BARIS_HEADER_TABEL + 1,
                $this->barisAkhirValidasi()
            );

            $indeksKolomTersembunyi++;
        }
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
