<?php

namespace App\Exports;

use App\Models\ProfilSekolah;
use App\Models\RincianBelanjaModalBmd;
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
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export data tab "BMD" (satu triwulan, opsional satu sekolah - $sekolah
 * boleh null sejak 2026-09-22, lihat catatan export() gate di
 * App\Livewire\PendataanBosp\RincianBelanjaModal\Index) ke Excel - urutan
 * kolom & jumlah kolom (35 kolom) mengikuti gambar acuan "DAFTAR BELANJA
 * MODAL TAHUN ANGGARAN 2026" yang diupload user 2026-09-22.
 *
 * SEJAK 2026-09-22 (perubahan keputusan, permintaan user "untuk tanda
 * tangan kepala sekolah dan bendahara di hapus pada hasil format excel
 * nya"): export ini TIDAK LAGI punya lembar tanda tangan Bendahara
 * BOSP/Kepala Sekolah di bawah tabel (BEDA dengan RincianBelanjaModalExport
 * tab "jenis" yang TETAP punya lembar tanda tangan, TIDAK diubah) - buffer
 * baris otomatis (barisAkhirOtomatis()) karena itu bisa dilebarkan lagi ke
 * pola +300 baris seperti export lain di codebase ini (ProfilSekolahExport
 * dkk), TIDAK perlu lagi dibatasi ketat 4 baris untuk menghindari
 * benturan dengan lembar tanda tangan.
 *
 * Urutan kolom PERSIS mengikuti urutan field pada permintaan user:
 * NPSN, Lokasi, Subrayon, Bentuk Kontrak/Transaksi, Program, Kegiatan,
 * Kode Sub Kegiatan, Nama Sub Kegiatan, Atribusi, Jumlah Termin, PPK,
 * Nomor Dokumen, Tgl. Perolehan, Penyedia, Kode Belanja, Rekening
 * Belanja, Jenis Aset (KIB), Sub Sub Rincian Objek, Jumlah, Satuan,
 * Harga Satuan, Total, No BAST, Tgl. BAST, Keterangan (BOS .../REGULER),
 * Nomor Surat Pernyataan, Tgl. Surat Pernyataan, Nama Pengurus Barang,
 * Jabatan, Pejabat Penata Usaha, Nama Barang, Spesifikasi Nama Barang,
 * Spesifikasi Lain (Serial Nomor), Merk/Pengarang, Keterangan (BOSP
 * TRIWULAN ...) - field otomatis (Jenis Aset, Total, kedua Keterangan)
 * & field konstan (Program/Kegiatan/Kode Sub Kegiatan/Nama Sub Kegiatan)
 * TETAP ditulis apa adanya, TIDAK BISA diubah lewat file Excel (lihat
 * App\Imports\RincianBelanjaModalBmdImport).
 *
 * Sesuai permintaan user 2026-09-22 ("apabila field nya otomatis maka di
 * excel pun harus sudah otomatis sesuai aplikasi ... apabila di aplikasi
 * field nya berupa pilihan maka di hasil export excel nya pun harus
 * berupa pilihan"), hasil export ini DISAMAKAN PERSIS dengan perilaku
 * field di aplikasi (lihat terapkanFieldOtomatis()):
 * - Bentuk Kontrak/Transaksi & Rekening Belanja: dropdown Excel (Data
 *   Validation) berisi pilihan yang SAMA PERSIS dengan aplikasi, sumber
 *   daftarnya ditulis ke kolom tersembunyi AK/AL/AM (pola sama seperti
 *   App\Exports\ProfilSekolahExport).
 * - Jenis Aset (KIB): FORMULA Excel (VLOOKUP terhadap kolom Rekening
 *   Belanja) - otomatis berubah kalau Rekening Belanja diganti di Excel,
 *   sama seperti perilaku live di aplikasi.
 * - Total: FORMULA Excel (= Jumlah x Harga Satuan) - otomatis berubah
 *   kalau Jumlah/Harga Satuan diedit di Excel.
 * - Kedua kolom Keterangan & Program/Kegiatan/Kode Sub Kegiatan/Nama Sub
 *   Kegiatan: nilai konstan (tidak bergantung pada kolom lain di baris
 *   yang sama), ditulis otomatis di SETIAP baris termasuk baris kosong
 *   buffer, jadi admin BOSP tidak perlu isi manual.
 * - PPK: SEJAK 2026-09-22 juga OTOMATIS (bukan manual lagi) - nilainya
 *   sudah tersimpan di database (RincianBelanjaModalBmd::ppkOtomatis(),
 *   diisi dari Nama Kepala Sekolah), jadi kolom K cukup ditulis apa
 *   adanya lewat map() & diberi warna kuning yang sama seperti kolom
 *   otomatis lainnya (TIDAK perlu VLOOKUP - nilainya sudah pasti per
 *   baris, tidak seperti Jenis Aset yang bergantung pilihan Rekening
 *   Belanja terbaru).
 * - NPSN/Lokasi/Subrayon (A/B/C): SAMA seperti sebelumnya (selalu
 *   otomatis dari relasi profilSekolah()), warna kuning ditambahkan
 *   sejak 2026-09-22 supaya visualnya konsisten dgn kolom otomatis
 *   lain (sebelumnya kolom ini TIDAK diberi warna sama sekali).
 * - Tgl. Perolehan/Tgl BAST/Tgl. Surat Pernyataan: SEJAK 2026-09-22
 *   ditulis sebagai TANGGAL Excel sungguhan (Date::PHPToExcel(), BUKAN
 *   teks "d-m-Y" seperti sebelumnya), diberi format tampilan
 *   "dd-mm-yyyy" & Data Validation bertipe TANGGAL - supaya kalau
 *   dibuka di Excel kolomnya benar-benar dikenali sebagai tanggal
 *   (bisa diurutkan/dihitung sebagai tanggal, muncul date-picker mini
 *   Excel bawaan saat sel diklik pada Excel versi yang mendukungnya).
 *   CATATAN KETERBATASAN: PhpSpreadsheet TIDAK BISA membuat widget
 *   kalender popup interaktif (ActiveX/Form Control) yang portabel
 *   lintas versi Excel - solusi ini (sel bertipe tanggal + Data
 *   Validation) adalah yang PALING DEKAT dengan "otomatis berupa
 *   kalender" yang bisa dicapai lewat generate file .xlsx murni.
 * Field otomatis di atas (Jenis Aset/Total/kedua Keterangan/Program dkk)
 * TETAP diabaikan & dihitung ULANG oleh App\Imports\RincianBelanjaModalBmdImport
 * saat file diimport kembali (PPK & tanggal JUGA - lihat import tsb) -
 * formula/dropdown/format tanggal ini murni untuk kenyamanan & keakuratan
 * tampilan saat file dibuka/diisi di Excel, BUKAN sumber data saat import.
 */
class RincianBelanjaModalBmdExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_HEADER_TABEL = 5;

    private const BARIS_KOSONG_SEBELUM_TANDA_TANGAN = 4;

    private const KOLOM_TERAKHIR = 'AI';

    // Kolom field otomatis dari relasi profilSekolah()/database (SEJAK
    // 2026-09-22 diberi highlight kuning, sebelumnya tidak).
    private const KOLOM_NPSN = 'A';

    private const KOLOM_LOKASI = 'B';

    private const KOLOM_SUBRAYON = 'C';

    private const KOLOM_PPK = 'K';

    // Kolom field "pilihan" (dropdown Excel).
    private const KOLOM_BENTUK_KONTRAK = 'D';

    private const KOLOM_REKENING_BELANJA = 'P';

    // Kolom field konstan (Program/Kegiatan/dst.) - otomatis, sama untuk
    // setiap baris.
    private const KOLOM_PROGRAM = 'E';

    private const KOLOM_KEGIATAN = 'F';

    private const KOLOM_KODE_SUB_KEGIATAN = 'G';

    private const KOLOM_NAMA_SUB_KEGIATAN = 'H';

    // Kolom field hasil perhitungan/relasi (formula Excel).
    private const KOLOM_JENIS_ASET = 'Q';

    private const KOLOM_JUMLAH = 'S';

    private const KOLOM_HARGA_SATUAN = 'U';

    private const KOLOM_TOTAL = 'V';

    private const KOLOM_KETERANGAN_BOS = 'Y';

    private const KOLOM_KETERANGAN_BOSP = 'AI';

    // Kolom tanggal - SEJAK 2026-09-22 ditulis sebagai tanggal Excel
    // sungguhan (bukan teks), lihat docblock kelas di atas.
    private const KOLOM_TGL_PEROLEHAN = 'M';

    private const KOLOM_TGL_BAST = 'X';

    private const KOLOM_TGL_SURAT_PERNYATAAN = 'AA';

    // Kolom BANTU tersembunyi (di luar KOLOM_TERAKHIR) - sumber daftar
    // dropdown & tabel rujukan VLOOKUP Jenis Aset, tidak pernah tampil.
    private const KOLOM_BANTU_BENTUK_KONTRAK = 'AK';

    private const KOLOM_BANTU_REKENING_BELANJA = 'AL';

    private const KOLOM_BANTU_JENIS_ASET = 'AM';

    /**
     * @param  SupportCollection<int, RincianBelanjaModalBmd>  $baris
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
        return 'BMD TW'.$this->triwulan;
    }

    public function headings(): array
    {
        return [
            'NPSN',
            'Lokasi',
            'Subrayon',
            'Bentuk Kontrak / Transaksi',
            'Program',
            'Kegiatan',
            'Kode Sub Kegiatan',
            'Nama Sub Kegiatan',
            'Atribusi',
            'Jumlah Termin',
            'PPK',
            'Nomor Dokumen',
            'Tgl. Perolehan',
            'Penyedia',
            'Kode Belanja',
            'Rekening Belanja',
            'Jenis Aset (KIB)',
            'Sub Sub Rincian Objek',
            'Jumlah',
            'Satuan',
            'Harga Satuan',
            'Total',
            'No BAST',
            'Tgl BAST',
            'Keterangan',
            'Nomor Surat Pernyataan',
            'Tgl. Surat Pernyataan',
            'Nama Pengurus Barang',
            'Jabatan',
            'Pejabat Penata Usaha',
            'Nama Barang',
            'Spesifikasi Nama Barang',
            'Spesifikasi Lain (Serial Nomor)',
            'Merk/Pengarang',
            'Keterangan',
        ];
    }

    public function map($baris): array
    {
        return [
            $baris->profilSekolah->npsn ?? $this->sekolah?->npsn,
            $baris->profilSekolah->nama_sekolah ?? $this->sekolah?->nama_sekolah,
            $baris->profilSekolah->subrayon ?? $this->sekolah?->subrayon,
            $baris->bentuk_kontrak,
            RincianBelanjaModalBmd::PROGRAM,
            RincianBelanjaModalBmd::KEGIATAN,
            RincianBelanjaModalBmd::KODE_SUB_KEGIATAN,
            RincianBelanjaModalBmd::NAMA_SUB_KEGIATAN,
            $baris->atribusi,
            $baris->jumlah_termin,
            $baris->ppk,
            $baris->nomor_dokumen,
            $baris->tanggal_perolehan ? Date::PHPToExcel($baris->tanggal_perolehan) : null,
            $baris->penyedia,
            $baris->kode_belanja,
            $baris->rekening_belanja,
            $baris->jenis_aset,
            $baris->sub_sub_rincian_objek,
            $baris->jumlah,
            $baris->satuan,
            $baris->harga_satuan,
            $baris->total,
            $baris->no_bast,
            $baris->tanggal_bast ? Date::PHPToExcel($baris->tanggal_bast) : null,
            $baris->keterangan_bos,
            $baris->nomor_surat_pernyataan,
            $baris->tanggal_surat_pernyataan ? Date::PHPToExcel($baris->tanggal_surat_pernyataan) : null,
            $baris->nama_pengurus_barang,
            $baris->jabatan,
            $baris->pejabat_penata_usaha,
            $baris->nama_barang,
            $baris->spesifikasi_nama_barang,
            $baris->spesifikasi_lain,
            $baris->merk_pengarang,
            $baris->keterangan_bosp,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->tulisJudul($sheet);
                $this->rapikanTabel($sheet);
                $this->terapkanFieldOtomatis($sheet);
            },
        ];
    }

    private function tulisJudul(Worksheet $sheet): void
    {
        $judul1 = 'DAFTAR BELANJA MODAL TAHUN ANGGARAN '.$this->tahun;
        if ($this->sekolah === null) {
            $judul1 .= ' - REKAP SELURUH SEKOLAH';
        }

        $sheet->setCellValue('A1', $judul1);
        $sheet->setCellValue('A2', 'TRIWULAN '.$this->triwulan.', TAHUN ANGGARAN '.$this->tahun);

        foreach ([1, 2] as $baris) {
            $rentang = 'A'.$baris.':'.self::KOLOM_TERAKHIR.$baris;
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
        $rentangHeader = 'A'.self::BARIS_HEADER_TABEL.':'.self::KOLOM_TERAKHIR.self::BARIS_HEADER_TABEL;

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
        $sheet->getStyle('A'.self::BARIS_HEADER_TABEL.':'.self::KOLOM_TERAKHIR.($barisTerakhir + self::BARIS_KOSONG_SEBELUM_TANDA_TANGAN))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->freezePane('A'.(self::BARIS_HEADER_TABEL + 1));
        $sheet->setAutoFilter($rentangHeader);
        $sheet->getRowDimension(self::BARIS_HEADER_TABEL)->setRowHeight(20);
    }

    /**
     * Baris terakhir tempat field otomatis/formula/dropdown diterapkan -
     * SEJAK 2026-09-22 memakai buffer +300 baris (pola sama seperti
     * ProfilSekolahExport/Lampiran2aExport/Lampiran2cExport), TIDAK LAGI
     * dibatasi ketat ke BARIS_KOSONG_SEBELUM_TANDA_TANGAN (4 baris) -
     * batas ketat itu sebelumnya WAJIB supaya tidak bentrok dengan lembar
     * tanda tangan, tapi lembar tanda tangan itu sendiri sudah DIHAPUS
     * dari export ini (lihat docblock kelas), jadi kendalanya sudah tidak
     * ada lagi.
     */
    private function barisAkhirOtomatis(): int
    {
        return $this->barisTerakhirTabel() + 300;
    }

    /**
     * Menerapkan seluruh field "otomatis" & "pilihan" supaya PERSIS sama
     * perilakunya dengan aplikasi (permintaan user 2026-09-22): dropdown
     * Excel untuk field pilihan, formula Excel untuk field hasil
     * perhitungan/relasi, & nilai konstan otomatis untuk field yang
     * selalu sama - diterapkan ke SETIAP baris (data yang sudah ada
     * MAUPUN baris kosong buffer) supaya kalau admin BOSP menambah baris
     * baru langsung di Excel, field otomatis/dropdown-nya tetap
     * berfungsi tanpa perlu diisi manual.
     */
    private function terapkanFieldOtomatis(Worksheet $sheet): void
    {
        $barisMulai = self::BARIS_HEADER_TABEL + 1;
        $barisSelesai = $this->barisAkhirOtomatis();

        $this->tulisDaftarPilihanTersembunyi($sheet);

        for ($baris = $barisMulai; $baris <= $barisSelesai; $baris++) {
            // Program/Kegiatan/Kode Sub Kegiatan/Nama Sub Kegiatan - nilai
            // konstan, sama persis dengan aplikasi (RincianBelanjaModalBmd::PROGRAM dkk).
            $sheet->setCellValue(self::KOLOM_PROGRAM.$baris, RincianBelanjaModalBmd::PROGRAM);
            $sheet->setCellValue(self::KOLOM_KEGIATAN.$baris, RincianBelanjaModalBmd::KEGIATAN);
            $sheet->setCellValue(self::KOLOM_KODE_SUB_KEGIATAN.$baris, RincianBelanjaModalBmd::KODE_SUB_KEGIATAN);
            $sheet->setCellValue(self::KOLOM_NAMA_SUB_KEGIATAN.$baris, RincianBelanjaModalBmd::NAMA_SUB_KEGIATAN);

            // Jenis Aset (KIB) - FORMULA, otomatis mengikuti pilihan
            // Rekening Belanja pada baris yang sama (VLOOKUP terhadap
            // tabel rujukan tersembunyi), sama seperti Alpine.js di
            // aplikasi (lihat _form-bmd.blade.php).
            $sheet->setCellValue(
                self::KOLOM_JENIS_ASET.$baris,
                '=IFERROR(VLOOKUP('.self::KOLOM_REKENING_BELANJA.$baris.',$'.self::KOLOM_BANTU_REKENING_BELANJA.'$1:$'.self::KOLOM_BANTU_JENIS_ASET.'$'.count(RincianBelanjaModalBmd::REKENING_BELANJA_OPTIONS).',2,0),"")'
            );

            // Total - FORMULA, = Jumlah x Harga Satuan (RincianBelanjaModalBmd::hitungTotal()).
            $sheet->setCellValue(
                self::KOLOM_TOTAL.$baris,
                '='.self::KOLOM_JUMLAH.$baris.'*'.self::KOLOM_HARGA_SATUAN.$baris
            );

            // Kedua kolom Keterangan - nilai konstan (hanya bergantung
            // triwulan+tahun export ini, BUKAN kolom lain di baris yang
            // sama - lihat RincianBelanjaModalBmd::keteranganBosOtomatis()/keteranganBospOtomatis()).
            $sheet->setCellValue(self::KOLOM_KETERANGAN_BOS.$baris, RincianBelanjaModalBmd::keteranganBosOtomatis($this->triwulan, $this->tahun));
            $sheet->setCellValue(self::KOLOM_KETERANGAN_BOSP.$baris, RincianBelanjaModalBmd::keteranganBospOtomatis($this->triwulan, $this->tahun));
        }

        $this->terapkanValidasiPilihan(
            $sheet,
            self::KOLOM_BENTUK_KONTRAK,
            '=$'.self::KOLOM_BANTU_BENTUK_KONTRAK.'$1:$'.self::KOLOM_BANTU_BENTUK_KONTRAK.'$'.count(RincianBelanjaModalBmd::BENTUK_KONTRAK_OPTIONS),
            $barisMulai,
            $barisSelesai
        );
        $this->terapkanValidasiPilihan(
            $sheet,
            self::KOLOM_REKENING_BELANJA,
            '=$'.self::KOLOM_BANTU_REKENING_BELANJA.'$1:$'.self::KOLOM_BANTU_REKENING_BELANJA.'$'.count(RincianBelanjaModalBmd::REKENING_BELANJA_OPTIONS),
            $barisMulai,
            $barisSelesai
        );

        $this->terapkanFormatTanggal($sheet, $barisMulai, $barisSelesai);

        // Format Rupiah (mis. "Rp 1.000.000") pada kolom Harga Satuan &
        // Total, supaya sama dengan tampilan di aplikasi - diteruskan
        // sampai baris buffer supaya baris baru yang diisi tetap
        // terformat otomatis.
        foreach ([self::KOLOM_HARGA_SATUAN, self::KOLOM_TOTAL] as $kolom) {
            $sheet->getStyle($kolom.$barisMulai.':'.$kolom.$barisSelesai)
                ->getNumberFormat()->setFormatCode('"Rp" #,##0');
        }

        // Warna kuning (sama seperti kotak "otomatis/read-only" di
        // aplikasi) pada kolom hasil perhitungan/relasi, konstan, & yang
        // otomatis dari relasi/database (NPSN/Lokasi/Subrayon/PPK, sejak
        // 2026-09-22), supaya admin BOSP langsung tahu kolom ini TIDAK
        // perlu diisi manual.
        $kolomOtomatis = [
            self::KOLOM_NPSN, self::KOLOM_LOKASI, self::KOLOM_SUBRAYON,
            self::KOLOM_PROGRAM, self::KOLOM_KEGIATAN, self::KOLOM_KODE_SUB_KEGIATAN, self::KOLOM_NAMA_SUB_KEGIATAN,
            self::KOLOM_PPK, self::KOLOM_JENIS_ASET, self::KOLOM_TOTAL, self::KOLOM_KETERANGAN_BOS, self::KOLOM_KETERANGAN_BOSP,
        ];
        foreach ($kolomOtomatis as $kolom) {
            $sheet->getStyle($kolom.$barisMulai.':'.$kolom.$barisSelesai)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFBEB']],
            ]);
        }
    }

    /**
     * Menerapkan format tampilan tanggal ("dd-mm-yyyy") & Data Validation
     * bertipe TANGGAL pada kolom Tgl. Perolehan/Tgl BAST/Tgl. Surat
     * Pernyataan - SEJAK 2026-09-22 (permintaan user "otomatis berupa
     * kalender supaya sama dengan yang di aplikasi"). Sel-selnya ditulis
     * sebagai tanggal Excel SUNGGUHAN lewat Date::PHPToExcel() di map()
     * (BUKAN teks), jadi bisa diurutkan/dihitung sebagai tanggal & Excel
     * versi yang mendukung akan menampilkan mini date-picker bawaan saat
     * sel diklik. CATATAN: ini BUKAN widget kalender popup interaktif
     * (ActiveX/Form Control) - PhpSpreadsheet tidak bisa membuat itu
     * secara portabel lintas versi Excel, jadi sel bertipe tanggal + Data
     * Validation ini adalah pendekatan PALING DEKAT yang bisa dicapai.
     */
    private function terapkanFormatTanggal(Worksheet $sheet, int $barisMulai, int $barisSelesai): void
    {
        $kolomTanggal = [self::KOLOM_TGL_PEROLEHAN, self::KOLOM_TGL_BAST, self::KOLOM_TGL_SURAT_PERNYATAAN];

        foreach ($kolomTanggal as $kolom) {
            $sheet->getStyle($kolom.$barisMulai.':'.$kolom.$barisSelesai)
                ->getNumberFormat()->setFormatCode('dd-mm-yyyy');

            for ($baris = $barisMulai; $baris <= $barisSelesai; $baris++) {
                $validasi = new DataValidation;
                $validasi->setType(DataValidation::TYPE_DATE);
                $validasi->setErrorStyle(DataValidation::STYLE_STOP);
                $validasi->setAllowBlank(true);
                $validasi->setShowInputMessage(true);
                $validasi->setShowErrorMessage(true);
                $validasi->setErrorTitle('Tanggal tidak valid');
                $validasi->setError('Silakan isi dengan tanggal yang valid (format dd-mm-yyyy).');
                $validasi->setPromptTitle('Isi tanggal');
                $validasi->setPrompt('Silakan isi dengan tanggal (format dd-mm-yyyy).');
                $validasi->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL);
                $validasi->setFormula1('01-01-2000');

                $sheet->getCell($kolom.$baris)->setDataValidation($validasi);
            }
        }
    }

    /**
     * Menulis daftar sumber dropdown Excel (Bentuk Kontrak/Transaksi &
     * Rekening Belanja) & tabel rujukan VLOOKUP Jenis Aset ke kolom
     * tersembunyi AK/AL/AM (pola sama seperti
     * App\Exports\ProfilSekolahExport::tulisDaftarPilihanTersembunyi()) -
     * kolom AL (Rekening Belanja) dipakai DUA KALI: sebagai sumber
     * dropdown DAN sebagai kolom kunci VLOOKUP Jenis Aset, supaya
     * datanya selalu konsisten satu sama lain.
     */
    private function tulisDaftarPilihanTersembunyi(Worksheet $sheet): void
    {
        foreach (array_values(RincianBelanjaModalBmd::BENTUK_KONTRAK_OPTIONS) as $indeks => $nilai) {
            $sheet->setCellValue(self::KOLOM_BANTU_BENTUK_KONTRAK.($indeks + 1), $nilai);
        }

        $indeks = 0;
        foreach (RincianBelanjaModalBmd::REKENING_BELANJA_OPTIONS as $rekeningBelanja => $jenisAset) {
            $baris = $indeks + 1;
            $sheet->setCellValue(self::KOLOM_BANTU_REKENING_BELANJA.$baris, $rekeningBelanja);
            $sheet->setCellValue(self::KOLOM_BANTU_JENIS_ASET.$baris, $jenisAset);
            $indeks++;
        }

        foreach ([self::KOLOM_BANTU_BENTUK_KONTRAK, self::KOLOM_BANTU_REKENING_BELANJA, self::KOLOM_BANTU_JENIS_ASET] as $kolom) {
            $sheet->getColumnDimension($kolom)->setVisible(false);
        }
    }

    /**
     * Menerapkan dropdown Excel (Data Validation) pada satu kolom untuk
     * seluruh rentang baris - pola SAMA PERSIS seperti
     * App\Exports\ProfilSekolahExport::terapkanValidasiPilihan().
     */
    private function terapkanValidasiPilihan(Worksheet $sheet, string $kolom, string $referensiRentang, int $barisMulai, int $barisSelesai): void
    {
        for ($baris = $barisMulai; $baris <= $barisSelesai; $baris++) {
            $validasi = new DataValidation;
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
