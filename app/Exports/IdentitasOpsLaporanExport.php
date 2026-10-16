<?php

namespace App\Exports;

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
 * Tombol "Unduh Excel" pada menu Identitas OPS (permintaan user
 * 2026-10-16: "tambahkan tombol unduh excel dan unduh pdf dengan field
 * yang sama dengan yang ada pada aplikasi dan sudah disusun rapih
 * dengan garis hitam"), meniru pola DataPtkLaporanExport/
 * ProfilSekolahLaporanExport.
 *
 * Berisi SEMUA field Identitas OPS (jawaban AskUserQuestion "Semua
 * field identitas OPS (lebih lengkap)") KECUALI:
 * - Foto OPS (gambar, bukan data teks).
 * - "Status Data" (badge "Sudah diisi"/"Belum diisi" - indikator
 *   turunan di tampilan, bukan field tersimpan).
 * SK OPS (file PDF) ditampilkan sebagai teks "Ada"/"Tidak Ada" (bukan
 * link file - tidak relevan di laporan Excel/PDF).
 *
 * $daftarSekolah mencakup SEMUA sekolah (termasuk yang belum mengisi
 * Identitas OPS sama sekali, ditampilkan '-' - sama seperti tabel di
 * aplikasi), SUDAH mengikuti filter/pencarian yang sedang aktif
 * (jawaban AskUserQuestion "Ikuti filter/pencarian yang sedang aktif")
 * - lihat PendataanOps\Index::queryDasarIdentitasOps().
 */
class IdentitasOpsLaporanExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_JUDUL_1 = 1;

    private const BARIS_HEADER_TABEL = 3;

    private const KOLOM_TANGGAL_LAHIR = 'H';

    private const JUMLAH_KOLOM = 14;

    private int $nomorBaris = 0;

    /**
     * @param  SupportCollection<int, \App\Models\ProfilSekolah>  $daftarSekolah
     */
    public function __construct(
        protected SupportCollection $daftarSekolah,
    ) {}

    public function collection(): SupportCollection
    {
        return $this->daftarSekolah;
    }

    public function startCell(): string
    {
        return 'A'.self::BARIS_HEADER_TABEL;
    }

    public function title(): string
    {
        return 'Identitas OPS';
    }

    public function headings(): array
    {
        return [
            'No', 'Nama Sekolah', 'Nama OPS', 'NUPTK', 'NIP', 'JK', 'Tempat Lahir', 'Tanggal Lahir',
            'Status Kepegawaian', 'Pendidikan Terakhir', 'Jurusan', 'Nama Perguruan Tinggi',
            'No WhatsApp', 'SK OPS',
        ];
    }

    /**
     * @param  \App\Models\ProfilSekolah  $sekolah
     */
    public function map($sekolah): array
    {
        $this->nomorBaris++;
        $ops = $sekolah->identitasOps;

        return [
            $this->nomorBaris,
            $sekolah->nama_sekolah,
            $ops?->nama ?? '-',
            $ops?->nuptk ?? '-',
            $ops?->nip ?? '-',
            $ops?->jk ?? '-',
            $ops?->tempat_lahir ?? '-',
            null,
            $ops?->status_kepegawaian ?? '-',
            $ops?->pendidikan_terakhir ?? '-',
            $ops?->jurusan ?? '-',
            $ops?->nama_perguruan_tinggi ?? '-',
            $ops?->no_whatsapp ?? '-',
            $ops?->sk_ops ? 'Ada' : 'Tidak Ada',
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

        $sheet->setCellValue('A'.self::BARIS_JUDUL_1, 'DAFTAR IDENTITAS OPS');

        $rentang = 'A'.self::BARIS_JUDUL_1.':'.$kolomTerakhir.self::BARIS_JUDUL_1;
        $sheet->mergeCells($rentang);
        $sheet->getStyle($rentang)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    private function barisTerakhirTabel(): int
    {
        return self::BARIS_HEADER_TABEL + $this->daftarSekolah->count();
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

        $sheet->getStyle('A'.self::BARIS_HEADER_TABEL.':'.$kolomTerakhir.$barisTerakhir)
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->freezePane('A'.(self::BARIS_HEADER_TABEL + 1));
        $sheet->setAutoFilter($rentangHeader);
        $sheet->getRowDimension(self::BARIS_HEADER_TABEL)->setRowHeight(30);
    }

    private function tulisTanggal(Worksheet $sheet): void
    {
        $baris = self::BARIS_HEADER_TABEL + 1;

        foreach ($this->daftarSekolah as $sekolah) {
            $tanggalLahir = $sekolah->identitasOps?->tanggal_lahir;

            if ($tanggalLahir) {
                $sheet->setCellValue(self::KOLOM_TANGGAL_LAHIR.$baris, Date::dateTimeToExcel($tanggalLahir));
                $sheet->getStyle(self::KOLOM_TANGGAL_LAHIR.$baris)->getNumberFormat()->setFormatCode('dd-mm-yyyy');
            } else {
                $sheet->setCellValue(self::KOLOM_TANGGAL_LAHIR.$baris, '-');
            }

            $baris++;
        }
    }
}
