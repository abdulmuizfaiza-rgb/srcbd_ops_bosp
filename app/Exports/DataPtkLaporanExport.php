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
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Tombol "Unduh" Data PTK (menu Data Sekolah > tab Data PTK) - laporan
 * RAPI untuk 1 sekolah, berjudul "DATA PTK" + nama sekolah, bergaris
 * tiap kolom & baris (permintaan user eksplisit 2026-10-01) - berbeda
 * dari DataPtkExport/tombol "Export Excel" (polos, untuk re-import -
 * jawaban AskUserQuestion "Dua fungsi berbeda"). Laporan ini TIDAK
 * mengandung dropdown (bukan untuk diimport ulang).
 *
 * Urutan kolom SAMA PERSIS dengan DataPtkExport (lihat catatan di sana),
 * TERMASUK kolom "No" di paling depan (permintaan user 2026-10-03,
 * jawaban AskUserQuestion "Export Excel & Unduh, keduanya").
 *
 * KOLOM NPWP ditambahkan 2026-10-15 (permintaan user: supaya file
 * "Unduh" juga sama dengan "Export Excel" yang sudah ditambahkan NPWP
 * di round sebelumnya hari ini) - posisi SAMA PERSIS dengan
 * DataPtkExport: setelah Tahun Lulus Ijazah, sebelum Status
 * Sertifikasi. JUMLAH_KOLOM berubah dari 24 jadi 25 (kolom terakhir
 * X -> Y). Field "pilihan" lain (Jabatan, Pangkat/Golongan, Status
 * Kepegawaian, dst) TIDAK perlu diubah apa pun - nilainya otomatis
 * ikut sinkron karena langsung membaca $p->field, dan laporan ini
 * SENGAJA tanpa dropdown Excel (lihat catatan di atas).
 *
 * KOLOM NIP DIGESER 2026-10-16 (permintaan user, sepasang dengan
 * pergeseran yang sama di DataPtkExport - lihat catatan lengkap di
 * sana): NIP pindah dari kolom D (sebelum Nama PTK) ke kolom H (tepat
 * setelah Jabatan). Kolom I (Pangkat/Golongan) dan seterusnya TIDAK
 * berubah huruf. KOLOM_TANGGAL_LAHIR berubah dari 'G' jadi 'F'.
 * KOLOM_TMT_SEKOLAH_INDUK ('L') TIDAK berubah. JUMLAH_KOLOM tetap 25
 * (jumlah kolom tidak berubah, hanya urutannya).
 *
 * MODE "SEMUA SEKOLAH" ditambahkan 2026-10-16 (permintaan user, jawaban
 * AskUserQuestion "1 sheet gabungan, data semua sekolah digabung
 * berurutan") - $sekolah SEKARANG boleh null (dipanggil dari
 * ProfilSekolah\Index::unduhPtk() saat Superadmin memilih "Semua
 * Sekolah" pada filter): judul baris ke-2 jadi "SEMUA SEKOLAH" (bukan
 * nama 1 sekolah), & $daftar yang dioper sudah diurutkan Status ->
 * Nama Sekolah -> Kecamatan (lihat ProfilSekolah\Index::
 * terapkanUrutanPtk()) sebelum sampai ke sini - kolom "Nama Sekolah"
 * yang SUDAH ADA di tiap baris (lihat map()) otomatis cukup untuk
 * membedakan asal data tiap baris, tidak perlu pengelompokan/sheet
 * terpisah per sekolah.
 *
 * SEJAK permintaan user 2026-10-18 ("field yang inputannya berupa angka
 * ... tidak berubah sesuai inputan dan tidak terpotong angkanya"):
 * kolom NIK/NUPTK/NIP/Tahun Lulus Ijazah/NPWP/Tahun Lulus Sertifikasi/
 * Nomor Sertifikat Sertifikasi/Nomor Registrasi Guru/Nomor Peserta
 * Sertifikasi dipaksa bertipe TEKS lewat WithCustomValueBinder (lihat
 * bindValue()/kolomDipaksaTeks()) - pola sama persis dengan
 * App\Exports\DataPtkExport (kolom sama persis, lihat catatan di sana)
 * & App\Exports\Lampiran2aExport/Lampiran2bExport/Lampiran2cExport yang
 * sudah menerapkan ini sejak 2026-10-16.
 */
class DataPtkLaporanExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_JUDUL_1 = 1;

    private const BARIS_JUDUL_2 = 2;

    private const BARIS_HEADER_TABEL = 4;

    private const KOLOM_TANGGAL_LAHIR = 'F';

    private const KOLOM_TMT_SEKOLAH_INDUK = 'L';

    private const JUMLAH_KOLOM = 25;

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
        protected ?ProfilSekolah $sekolah,
    ) {}

    public function collection(): SupportCollection
    {
        return $this->daftar;
    }

    public function startCell(): string
    {
        return 'A'.self::BARIS_HEADER_TABEL;
    }

    public function title(): string
    {
        return 'Data PTK';
    }

    public function headings(): array
    {
        return [
            'No', 'NIK', 'NUPTK', 'Nama PTK', 'Tempat Lahir', 'Tanggal Lahir',
            'Jabatan', 'NIP', 'Pangkat / Golongan', 'Status Kepegawaian', 'Jenis PTK', 'TMT Di Sekolah Induk',
            'Pendidikan Terakhir', 'Jurusan / Prodi Sesuai Ijazah Terakhir', 'Tahun Lulus Ijazah', 'NPWP',
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
            $p->nama_ptk,
            $p->tempat_lahir,
            null,
            $p->jabatan,
            $p->nip,
            $p->pangkat_golongan,
            $p->status_kepegawaian,
            $p->jenis_ptk,
            null,
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
            $p->profilSekolah->nama_sekolah ?? $this->sekolah?->nama_sekolah ?? '-',
            $p->status_dapodik,
            $p->status_keaktifan,
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
     * PERSIS dengan App\Exports\DataPtkExport & App\Exports\
     * Lampiran2aExport/Lampiran2bExport/Lampiran2cExport::bindValue()
     * yang sudah ada sejak 2026-10-16.
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

                $this->tulisJudul($sheet);
                $this->rapikanTabel($sheet);
                $this->tulisTanggal($sheet);
            },
        ];
    }

    private function hurufKolomTerakhir(): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(self::JUMLAH_KOLOM);
    }

    private function tulisJudul(Worksheet $sheet): void
    {
        $kolomTerakhir = $this->hurufKolomTerakhir();

        $sheet->setCellValue('A'.self::BARIS_JUDUL_1, 'DATA PTK');
        $sheet->setCellValue('A'.self::BARIS_JUDUL_2, $this->sekolah->nama_sekolah ?? 'SEMUA SEKOLAH');

        foreach ([self::BARIS_JUDUL_1, self::BARIS_JUDUL_2] as $baris) {
            $rentang = 'A'.$baris.':'.$kolomTerakhir.$baris;
            $sheet->mergeCells($rentang);
            $sheet->getStyle($rentang)->applyFromArray([
                'font' => ['bold' => true, 'size' => $baris === self::BARIS_JUDUL_1 ? 14 : 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }
    }

    private function barisTerakhirTabel(): int
    {
        return self::BARIS_HEADER_TABEL + $this->daftar->count();
    }

    private function rapikanTabel(Worksheet $sheet): void
    {
        $kolomTerakhir = $this->hurufKolomTerakhir();
        $barisTerakhir = max($this->barisTerakhirTabel(), self::BARIS_HEADER_TABEL);
        $rentangHeader = 'A'.self::BARIS_HEADER_TABEL.':'.$kolomTerakhir.self::BARIS_HEADER_TABEL;

        $sheet->getStyle($rentangHeader)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        // Garis tabel (tiap kolom & baris) dari header sampai baris data
        // terakhir - permintaan user eksplisit.
        $sheet->getStyle('A'.self::BARIS_HEADER_TABEL.':'.$kolomTerakhir.$barisTerakhir)
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->freezePane('A'.(self::BARIS_HEADER_TABEL + 1));
        $sheet->setAutoFilter($rentangHeader);
        $sheet->getRowDimension(self::BARIS_HEADER_TABEL)->setRowHeight(30);
    }

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
}
