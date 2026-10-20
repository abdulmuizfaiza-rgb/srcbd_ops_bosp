<?php

namespace App\Exports;

use App\Models\DataPtk;
use App\Models\ProfilSekolah;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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
 * No, NIK, NUPTK, Nama PTK, Tempat Lahir, Tanggal Lahir, Jabatan, NIP,
 * Pangkat/Golongan, Status Kepegawaian, Jenis PTK, TMT Di Sekolah Induk,
 * Pendidikan Terakhir, Jurusan/Prodi, Tahun Lulus Ijazah, NPWP, Status
 * Sertifikasi, Bidang Studi Sertifikasi, Tahun Lulus Sertifikasi, Nomor
 * Sertifikat Sertifikasi, Nomor Registrasi Guru, Nomor Peserta
 * Sertifikasi, Nama Sekolah, Status Dapodik, Status Keaktifan - jangan
 * diubah urutannya tanpa persetujuan. Kolom "No" HANYA nomor urut
 * tampilan (bukan kolom data), diabaikan sepenuhnya oleh DataPtkImport
 * (import memakai WithHeadingRow + nama kolom, bukan posisi, jadi aman).
 *
 * KOLOM NPWP ditambahkan 2026-10-15 (permintaan user: field NPWP belum
 * muncul di export, posisinya mengikuti urutan field NPWP pada form
 * aplikasi - setelah Tahun Lulus Ijazah, sebelum Status Sertifikasi).
 * Semua referensi kolom (huruf) di bawah untuk kolom-kolom SETELAH NPWP
 * bergeser satu huruf (P->Q, V->W, W->X, X->Y) - kolom SEBELUM NPWP
 * (termasuk G=Tanggal Lahir, H-M) TIDAK berubah.
 *
 * KOLOM NIP DIGESER 2026-10-16 (permintaan user: NIP mengikuti posisi
 * field NIP di form aplikasi yang digeser ke setelah Jabatan - lihat
 * app/Livewire/ProfilSekolah/Index.php & docblock DataPtk::
 * terapkanAturanPangkatGolongan()). Posisi lama NIP (kolom D, sebelum
 * Nama PTK) DIHAPUS lalu disisipkan kembali tepat setelah Jabatan.
 * Karena 1 kolom dihapus di depan & 1 kolom disisipkan lagi sebelum
 * Pangkat/Golongan, kolom I (Pangkat/Golongan) DAN SETERUSNYA (J-Y)
 * TIDAK berubah huruf - HANYA kolom D-H yang berubah: D=Nama PTK (dari
 * E), E=Tempat Lahir (dari F), F=Tanggal Lahir (dari G), G=Jabatan (dari
 * H), H=NIP (posisi baru, dari D). KOLOM_TANGGAL_LAHIR berubah dari 'G'
 * jadi 'F'. Dropdown Jabatan (lihat tulisDaftarPilihanTersembunyi())
 * berubah dari kolom 'H' jadi 'G'. KOLOM_TMT_SEKOLAH_INDUK ('L') &
 * KOLOM_STATUS_KEAKTIFAN ('Y') TIDAK berubah.
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
 *
 * SEJAK permintaan user 2026-10-18 ("untuk field/kolom yang sudah ada
 * pilihannya ... diberi warna kuning dan diberi keterangan silahkan
 * pilih ... untuk meminimalisir kesalahan ketika saat import excel
 * nya"): ke-9 kolom dropdown di atas (G, I, J, K, M, Q, W, X, Y) kini
 * diberi warna kuning (FFFBEB, sama dgn warna kolom otomatis di
 * RincianBelanjaModalBmdExport - jawaban AskUserQuestion), & 1 baris
 * keterangan ditambahkan tepat di atas tabel (baris 1, lihat
 * BARIS_KETERANGAN/tulisKeteranganKolomPilihan()) - header & data
 * digeser 1 baris ke bawah lewat WithCustomStartCell::startCell().
 * CATATAN KETERBATASAN: Data Validation Excel (ErrorStyle STOP) TIDAK
 * menjamin 100% mencegah copy-paste nilai di luar daftar pilihan pada
 * semua versi Excel - validasi paling pasti berlaku saat mengetik
 * manual atau memilih dari dropdown; perilaku saat paste bergantung
 * versi Excel yang dipakai.
 *
 * SEJAK permintaan user 2026-10-18 ("field yang inputannya berupa angka
 * ... tidak berubah sesuai inputan dan tidak terpotong angkanya"):
 * kolom NIK/NUPTK/NIP/Tahun Lulus Ijazah/NPWP/Tahun Lulus Sertifikasi/
 * Nomor Sertifikat Sertifikasi/Nomor Registrasi Guru/Nomor Peserta
 * Sertifikasi dipaksa bertipe TEKS lewat WithCustomValueBinder (lihat
 * bindValue()/kolomDipaksaTeks()) - pola sama persis dengan
 * App\Exports\Lampiran2aExport/Lampiran2bExport/Lampiran2cExport yang
 * sudah menerapkan ini sejak 2026-10-16.
 */
class DataPtkExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithTitle
{
    /**
     * Baris 1 dipakai untuk baris keterangan kolom kuning (lihat
     * tulisKeteranganKolomPilihan(), permintaan user 2026-10-18) -
     * tabel (header + data) digeser 1 baris ke bawah lewat
     * WithCustomStartCell::startCell().
     */
    private const BARIS_KETERANGAN = 1;

    private const BARIS_HEADER_TABEL = 2;

    private const KOLOM_TANGGAL_LAHIR = 'F';

    private const KOLOM_TMT_SEKOLAH_INDUK = 'L';

    /**
     * Kolom data pertama (No) & terakhir (Status Keaktifan) - dipakai
     * untuk rentang garis bawah otomatis (tulisGarisBarisSelesai()).
     */
    private const KOLOM_PERTAMA = 'A';

    private const KOLOM_STATUS_KEAKTIFAN = 'Y';

    /**
     * Kolom Keterangan (BARU 2026-10-20, permintaan user poin 5 & 7) -
     * ditambahkan sebagai kolom PALING TERAKHIR (setelah Status
     * Keaktifan, dikonfirmasi lewat AskUserQuestion "Tepat setelah
     * Status Keaktifan"). KARENA kolom ini menggeser kolom tersembunyi
     * (daftar sumber dropdown) yang SEBELUMNYA mulai dari Z, kolom
     * tersembunyi itu SEKARANG digeser mulai dari AA (lihat
     * tulisDaftarPilihanTersembunyi()).
     */
    private const KOLOM_KETERANGAN = 'Z';

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

    /**
     * Header & data digeser ke baris 2 (lihat BARIS_KETERANGAN/
     * BARIS_HEADER_TABEL) supaya baris 1 bisa dipakai utk keterangan
     * kolom kuning (permintaan user 2026-10-18).
     */
    public function startCell(): string
    {
        return 'A'.self::BARIS_HEADER_TABEL;
    }

    public function headings(): array
    {
        return [
            'No', 'NIK', 'NUPTK', 'Nama PTK', 'Tempat Lahir', 'Tanggal Lahir',
            'Jabatan', 'NIP', 'Pangkat / Golongan', 'Status Kepegawaian', 'Jenis PTK', 'TMT Di Sekolah Induk',
            'Pendidikan Terakhir', 'Jurusan / Prodi Sesuai Ijazah Terakhir', 'Tahun Lulus Ijazah', 'NPWP',
            'Status Sertifikasi', 'Bidang Studi Sertifikasi', 'Tahun Lulus Sertifikasi',
            'Nomor Sertifikat Sertifikasi', 'Nomor Registrasi Guru', 'Nomor Peserta Sertifikasi',
            'Nama Sekolah', 'Status Dapodik', 'Status Keaktifan', 'Keterangan',
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
            $p->nama_ptk,
            $p->tempat_lahir,
            null, // Tanggal Lahir - ditulis ulang sbg tanggal Excel asli di tulisTanggal()
            $p->jabatan,
            $p->nip,
            $p->pangkat_golongan,
            $p->status_kepegawaian,
            $p->jenis_ptk,
            null, // TMT Di Sekolah Induk - ditulis ulang sbg tanggal Excel asli di tulisTanggal()
            $p->pendidikan_terakhir,
            $p->jurusan_prodi,
            $p->tahun_lulus_ijazah,
            $p->npwp,
            $p->status_sertifikasi,
            $p->bidang_studi_sertifikasi,
            $p->tahun_lulus_sertifikasi,
            $p->nomor_sertifikat_sertifikasi,
            $p->nomor_registrasi_guru,
            $p->nomor_peserta_sertifikasi,
            $p->profilSekolah->nama_sekolah ?? '',
            $p->status_dapodik,
            $p->status_keaktifan,
            $p->keterangan,
        ];
    }

    /**
     * Kolom berisi NIK/NUPTK/NIP/Tahun Lulus Ijazah/NPWP/Tahun Lulus
     * Sertifikasi/Nomor Sertifikat/Nomor Registrasi Guru/Nomor Peserta
     * Sertifikasi dipaksa bertipe TEKS (bukan Angka) - kalau dibiarkan
     * memakai tipe Angka bawaan Excel, angka yang berawalan "0" bisa
     * TERPOTONG (hilang nolnya) & angka berdigit panjang (NIK/NUPTK 16
     * digit, NIP 18 digit) bisa tampil beda (notasi ilmiah/dibulatkan,
     * krn melebihi 15 digit presisi Excel) - harus sama persis dengan
     * yang tampil di aplikasi. Permintaan user 2026-10-18 - pola SAMA
     * PERSIS dengan App\Exports\Lampiran2aExport/Lampiran2bExport/
     * Lampiran2cExport::bindValue() yang sudah ada sejak 2026-10-16.
     * Kolom "No" (A) SENGAJA TIDAK dipaksa teks (nomor urut biasa, tidak
     * berisiko kepanjangan digit).
     */
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (in_array($cell->getColumn(), $this->kolomDipaksaTeks(), true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return (new DefaultValueBinder())->bindValue($cell, $value);
    }

    /**
     * @return string[] kolom NIK, NUPTK, NIP, Tahun Lulus Ijazah, NPWP,
     *                   Tahun Lulus Sertifikasi, Nomor Sertifikat
     *                   Sertifikasi, Nomor Registrasi Guru, Nomor
     *                   Peserta Sertifikasi - urutan kolom tabel ini
     *                   tetap, lihat headings()/map().
     */
    private function kolomDipaksaTeks(): array
    {
        return ['B', 'C', 'H', 'O', 'P', 'S', 'T', 'U', 'V'];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('A'.self::BARIS_HEADER_TABEL.':'.self::KOLOM_STATUS_KEAKTIFAN.self::BARIS_HEADER_TABEL)
                    ->getFont()->setBold(true);

                $this->tulisKeteranganKolomPilihan($sheet);
                $this->tulisTanggal($sheet);
                $this->tulisDaftarPilihanTersembunyi($sheet);
                $this->tulisGarisBarisSelesai($sheet);
                $this->tulisWarnaKondisionalWajib($sheet);
            },
        ];
    }

    /**
     * Baris keterangan di atas tabel (permintaan user 2026-10-18,
     * dikonfirmasi lewat AskUserQuestion "Tambahkan baris keterangan
     * di atas tabel") - menjelaskan kolom berwarna kuning (lihat
     * tulisDaftarPilihanTersembunyi()) harus dipilih dari dropdown,
     * bukan diketik/copy-paste manual, untuk meminimalisir kesalahan
     * saat diimpor kembali.
     */
    private function tulisKeteranganKolomPilihan(Worksheet $sheet): void
    {
        $rentang = 'A'.self::BARIS_KETERANGAN.':'.self::KOLOM_KETERANGAN.self::BARIS_KETERANGAN;

        $sheet->mergeCells($rentang);
        $sheet->setCellValue('A'.self::BARIS_KETERANGAN,
            'Kolom berwarna kuning: silahkan pilih dari dropdown yang tersedia, jangan diketik manual atau copy-paste, untuk meminimalisir kesalahan saat diimpor kembali. '
            .'Kolom berwarna merah muda: Bidang Studi/Tahun Lulus/Nomor Sertifikat/Nomor Registrasi Guru/Nomor Peserta Sertifikasi WAJIB diisi apabila Status Sertifikasi "Sudah", dan Keterangan WAJIB diisi apabila Status Keaktifan "Tidak Aktif".'
        );
        $sheet->getStyle($rentang)->applyFromArray([
            'font' => ['italic' => true, 'bold' => true, 'color' => ['rgb' => '9A3412']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'wrapText' => true],
        ]);
        $sheet->getRowDimension(self::BARIS_KETERANGAN)->setRowHeight(45);
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
        // SEJAK 2026-10-20: rentang digeser dari KOLOM_STATUS_KEAKTIFAN
        // ('Y') ke KOLOM_KETERANGAN ('Z') supaya garis bawah "baris
        // selesai" ikut mencakup kolom Keterangan yang baru ditambah
        // (kolom Z kini menjadi kolom paling kanan pada tabel).
        $rentang = self::KOLOM_PERTAMA.$barisMulai.':'.self::KOLOM_KETERANGAN.$barisSelesai;

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
     * Penanda visual (warna merah muda) untuk field yang WAJIB diisi
     * SECARA BERSYARAT (permintaan user 2026-10-20 poin 4 & 7) - beda
     * dari kolom kuning (yang artinya "wajib pilih dari dropdown"):
     * - Kolom Bidang Studi s.d. Nomor Peserta Sertifikasi (R:V): wajib
     *   diisi HANYA pada baris yang kolom Status Sertifikasi (Q)
     *   bernilai "Sudah" - dikosongkan dulu atau isi "-" tetap boleh
     *   pada baris Status Sertifikasi "Belum" (lihat DataPtk::
     *   terapkanAturanSertifikasi()).
     * - Kolom Keterangan (Z): wajib diisi HANYA pada baris yang kolom
     *   Status Keaktifan (Y) bernilai "Tidak Aktif" (permintaan user
     *   poin 5).
     * Dibuat DINAMIS lewat Conditional Formatting (bukan warna statis)
     * - pola sama persis dengan tulisGarisBarisSelesai() di atas -
     * supaya warnanya otomatis menyesuaikan tiap baris sesuai isian
     * Status Sertifikasi/Status Keaktifan baris tsb masing-masing,
     * termasuk pada baris buffer kosong di bawah data (belum berwarna
     * sampai diisi "Sudah"/"Tidak Aktif").
     */
    private function tulisWarnaKondisionalWajib(Worksheet $sheet): void
    {
        $barisMulai = self::BARIS_HEADER_TABEL + 1;
        $barisSelesai = $this->barisAkhirValidasi();

        $this->terapkanWarnaKondisionalWajib(
            $sheet,
            'R'.$barisMulai.':V'.$barisSelesai,
            '$Q'.$barisMulai.'="'.DataPtk::STATUS_SERTIFIKASI_SUDAH.'"'
        );

        $this->terapkanWarnaKondisionalWajib(
            $sheet,
            self::KOLOM_KETERANGAN.$barisMulai.':'.self::KOLOM_KETERANGAN.$barisSelesai,
            '$'.self::KOLOM_STATUS_KEAKTIFAN.$barisMulai.'="Tidak Aktif"'
        );
    }

    private function terapkanWarnaKondisionalWajib(Worksheet $sheet, string $rentang, string $formula): void
    {
        $kondisi = new Conditional();
        $kondisi->setConditionType(Conditional::CONDITION_EXPRESSION);
        $kondisi->addCondition($formula);
        $kondisi->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEE2E2');

        $gayaKondisional = $sheet->getStyle($rentang)->getConditionalStyles();
        $gayaKondisional[] = $kondisi;
        $sheet->getStyle($rentang)->setConditionalStyles($gayaKondisional);
    }

    /**
     * Dropdown (Data Validation Excel) untuk ke-9 kolom "pilihan" supaya
     * penulisannya selalu sama persis dengan pilihan yang ada di
     * aplikasi saat file ini diisi/diedit lalu diimport kembali. Daftar
     * sumber tiap kolom ditulis ke kolom tersembunyi mulai AA (SEJAK
     * 2026-10-20: sebelumnya mulai Z, digeser 1 kolom karena kolom Z
     * sekarang dipakai kolom Keterangan yang baru ditambah, lihat
     * KOLOM_KETERANGAN) - pola sama seperti ProfilSekolahExport/
     * Lampiran2aExport.
     */
    private function tulisDaftarPilihanTersembunyi(Worksheet $sheet): void
    {
        $daftarPilihan = [
            'G' => array_values(DataPtk::jabatanOptions()),
            'I' => array_values(DataPtk::pangkatGolonganOptions()),
            'J' => array_values(DataPtk::statusKepegawaianOptions()),
            'K' => array_values(DataPtk::jenisPtkOptions()),
            'M' => array_values(DataPtk::pendidikanTerakhirOptions()),
            'Q' => array_values(DataPtk::statusSertifikasiOptions()),
            'W' => ProfilSekolah::urutStandar()->pluck('nama_sekolah')->all(),
            'X' => array_values(DataPtk::statusDapodikOptions()),
            'Y' => array_values(DataPtk::statusKeaktifanOptions()),
        ];

        $indeksKolomTersembunyi = Coordinate::columnIndexFromString('AA');

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

            // Warna kuning (permintaan user 2026-10-18, lihat
            // tulisKeteranganKolomPilihan()) - menandai kolom ini WAJIB
            // dipilih dari dropdown, diterapkan ke rentang data yang
            // sama dengan dropdown-nya (tidak termasuk baris header).
            $sheet->getStyle($kolomData.(self::BARIS_HEADER_TABEL + 1).':'.$kolomData.$this->barisAkhirValidasi())
                ->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
                ]);

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
