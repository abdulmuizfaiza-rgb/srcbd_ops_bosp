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
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
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
 * 2026-10-01), DITAMBAH kolom "No" di paling depan (permintaan user
 * 2026-10-03, jawaban AskUserQuestion "Export Excel & Unduh, keduanya"):
 * No, NIK, NUPTK, NIP, Nama PTK, Tempat Lahir, Tanggal Lahir, Jabatan,
 * Pangkat/Golongan, Status Kepegawaian, Jenis PTK, TMT Di Sekolah Induk,
 * Pendidikan Terakhir, Jurusan/Prodi, Tahun Lulus Ijazah, Status
 * Sertifikasi, Bidang Studi Sertifikasi, Tahun Lulus Sertifikasi, Nomor
 * Sertifikat Sertifikasi, Nomor Registrasi Guru, Nomor Peserta
 * Sertifikasi, Nama Sekolah, Status Dapodik, Status Keaktifan - jangan
 * diubah urutannya tanpa persetujuan. Kolom "No" HANYA nomor urut
 * tampilan (bukan kolom data), diabaikan sepenuhnya oleh DataPtkImport
 * (import memakai WithHeadingRow + nama kolom, bukan posisi, jadi aman).
 *
 * SEJAK permintaan user 2026-10-03: ditambahkan 2 hal lagi -
 * 1) Format tanggal Excel asli (dd-mm-yyyy) pada kolom Tanggal Lahir &
 *    TMT Di Sekolah Induk SEKARANG diterapkan juga ke baris-baris KOSONG
 *    di bawah data (buffer, sama seperti baris buffer dropdown di
 *    tulisDaftarPilihanTersembunyi()) - supaya saat admin OPS mengetik
 *    tanggal baru untuk PTK baru, Excel langsung mengenalinya &
 *    menampilkannya sebagai tanggal (kalender), bukan hanya untuk baris
 *    yang sudah berisi data lama. Lihat tulisTanggal().
 * 2) Conditional Formatting: begitu kolom Status Keaktifan (kolom
 *    terakhir) pada 1 baris terisi, baris tsb (dari kolom No sampai
 *    Status Keaktifan) otomatis diberi garis bawah hitam sebagai
 *    penanda baris tsb sudah lengkap diisi - bersifat dinamis (otomatis
 *    hilang lagi kalau isian kolom itu dihapus), jawaban AskUserQuestion
 *    "Otomatis muncul begitu kolom Status Keaktifan terisi". Lihat
 *    tulisGarisBarisSelesai().
 */
class DataPtkExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_HEADER_TABEL = 1;

    private const KOLOM_TANGGAL_LAHIR = 'G';

    private const KOLOM_TMT_SEKOLAH_INDUK = 'L';

    /**
     * Kolom data pertama (No) & terakhir (Status Keaktifan) - dipakai
     * untuk rentang garis bawah otomatis (tulisGarisBarisSelesai()).
     */
    private const KOLOM_PERTAMA = 'A';

    private const KOLOM_STATUS_KEAKTIFAN = 'X';

    /**
     * Penghitung nomor urut baris untuk kolom "No" - diincrement tiap
     * map() dipanggil (urutannya mengikuti urutan $daftar apa adanya).
     */
    private int $nomorBaris = 0;

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
            'No', 'NIK', 'NUPTK', 'NIP', 'Nama PTK', 'Tempat Lahir', 'Tanggal Lahir',
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
        $this->nomorBaris++;

        return [
            $this->nomorBaris,
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

                $sheet->getStyle('A'.self::BARIS_HEADER_TABEL.':'.self::KOLOM_STATUS_KEAKTIFAN.self::BARIS_HEADER_TABEL)
                    ->getFont()->setBold(true);

                $this->tulisTanggal($sheet);
                $this->tulisDaftarPilihanTersembunyi($sheet);
                $this->tulisGarisBarisSelesai($sheet);
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

        // SEJAK permintaan user 2026-10-03: format tanggal Excel (dd-mm-yyyy)
        // diterapkan juga ke baris-baris KOSONG di bawah data (buffer, sama
        // rentangnya dengan buffer dropdown - lihat barisAkhirValidasi()),
        // supaya tanggal yang diketik admin OPS untuk PTK BARU juga langsung
        // dikenali & ditampilkan Excel sebagai tanggal (kalender), bukan
        // hanya berlaku untuk baris yang sudah ada datanya saat export.
        $sheet->getStyle(self::KOLOM_TANGGAL_LAHIR.$baris.':'.self::KOLOM_TANGGAL_LAHIR.$this->barisAkhirValidasi())
            ->getNumberFormat()->setFormatCode('dd-mm-yyyy');

        $sheet->getStyle(self::KOLOM_TMT_SEKOLAH_INDUK.$baris.':'.self::KOLOM_TMT_SEKOLAH_INDUK.$this->barisAkhirValidasi())
            ->getNumberFormat()->setFormatCode('dd-mm-yyyy');
    }

    /**
     * SEJAK permintaan user 2026-10-03 (jawaban AskUserQuestion "Otomatis
     * muncul begitu kolom Status Keaktifan terisi"): Conditional Formatting
     * Excel - begitu sel Status Keaktifan pada 1 baris TERISI, baris tsb
     * (dari kolom No sampai Status Keaktifan) otomatis diberi garis bawah
     * hitam sebagai penanda baris sudah lengkap diisi admin OPS & siap
     * lanjut ke baris berikutnya. Bersifat DINAMIS (formula Excel, bukan
     * style statis) - kalau isian Status Keaktifan baris tsb dihapus lagi,
     * garisnya otomatis ikut hilang. Diterapkan ke rentang buffer yang sama
     * dengan dropdown (barisAkhirValidasi()) supaya tetap berfungsi untuk
     * baris PTK baru yang ditambahkan admin OPS.
     */
    private function tulisGarisBarisSelesai(Worksheet $sheet): void
    {
        $barisMulai = self::BARIS_HEADER_TABEL + 1;
        $barisSelesai = $this->barisAkhirValidasi();
        $rentang = self::KOLOM_PERTAMA.$barisMulai.':'.self::KOLOM_STATUS_KEAKTIFAN.$barisSelesai;

        $kondisi = new Conditional();
        $kondisi->setConditionType(Conditional::CONDITION_EXPRESSION);
        $kondisi->addCondition('$'.self::KOLOM_STATUS_KEAKTIFAN.$barisMulai.'<>""');
        $kondisi->getStyle()->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setRGB('000000');

        $gayaKondisional = $sheet->getStyle($rentang)->getConditionalStyles();
        $gayaKondisional[] = $kondisi;
        $sheet->getStyle($rentang)->setConditionalStyles($gayaKondisional);
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
            'H' => array_values(DataPtk::jabatanOptions()),
            'I' => array_values(DataPtk::pangkatGolonganOptions()),
            'J' => array_values(DataPtk::statusKepegawaianOptions()),
            'K' => array_values(DataPtk::jenisPtkOptions()),
            'M' => array_values(DataPtk::pendidikanTerakhirOptions()),
            'P' => array_values(DataPtk::statusSertifikasiOptions()),
            'V' => ProfilSekolah::urutStandar()->pluck('nama_sekolah')->all(),
            'W' => array_values(DataPtk::statusDapodikOptions()),
            'X' => array_values(DataPtk::statusKeaktifanOptions()),
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
