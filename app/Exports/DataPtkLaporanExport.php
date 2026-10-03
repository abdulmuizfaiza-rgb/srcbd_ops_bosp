<?php

namespace App\Exports;

use App\Models\DataPtk;
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
 */
class DataPtkLaporanExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_JUDUL_1 = 1;

    private const BARIS_JUDUL_2 = 2;

    private const BARIS_HEADER_TABEL = 4;

    private const KOLOM_TANGGAL_LAHIR = 'G';

    private const KOLOM_TMT_SEKOLAH_INDUK = 'L';

    private const JUMLAH_KOLOM = 24;

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
        protected ProfilSekolah $sekolah,
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
            null,
            $p->jabatan,
            $p->pangkat_golongan,
            $p->status_kepegawaian,
            $p->jenis_ptk,
            null,
            $p->pendidikan_terakhir,
            $p->jurusan_prodi,
            $p->tahun_lulus_ijazah,
            $p->status_sertifikasi,
            $p->bidang_studi_sertifikasi,
            $p->tahun_lulus_sertifikasi,
            $p->nomor_sertifikat_sertifikasi,
            $p->nomor_registrasi_guru,
            $p->nomor_peserta_sertifikasi,
            $p->profilSekolah->nama_sekolah ?? $this->sekolah->nama_sekolah,
            $p->status_dapodik,
            $p->status_keaktifan,
        ];
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
        $sheet->setCellValue('A'.self::BARIS_JUDUL_2, $this->sekolah->nama_sekolah);

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
