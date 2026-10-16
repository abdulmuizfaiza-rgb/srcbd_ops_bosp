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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Tombol "Unduh Excel" pada menu Data Sekolah > tab Profil Sekolah
 * (permintaan user 2026-10-16: "tambahkan tombol unduh excel dan unduh
 * pdf dengan field yang sama dengan yang ada pada aplikasi dan sudah
 * disusun rapih dengan garis hitam").
 *
 * BERBEDA dari ProfilSekolahExport/tombol "Export Excel" (hanya 4 kolom:
 * NPSN, Nama Sekolah, Status, Kecamatan - khusus untuk re-import) - ini
 * laporan RAPI berisi SEMUA field profil sekolah (jawaban AskUserQuestion
 * "Semua field profil sekolah (lebih lengkap)"), bergaris tiap kolom &
 * baris, meniru pola DataPtkLaporanExport.
 *
 * Field gambar/file (logo_sekolah, logo_pemda, kop_surat) TIDAK
 * disertakan (bukan data teks, tidak relevan untuk laporan tabel).
 *
 * $daftarSekolah yang dioper ke constructor SUDAH mengikuti
 * filter/pencarian yang sedang aktif pada tabel (jawaban AskUserQuestion
 * "Ikuti filter/pencarian yang sedang aktif") - lihat
 * ProfilSekolah\Index::queryDasarSekolah().
 */
class ProfilSekolahLaporanExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_JUDUL_1 = 1;

    private const BARIS_HEADER_TABEL = 3;

    private const JUMLAH_KOLOM = 20;

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
        return 'Profil Sekolah';
    }

    public function headings(): array
    {
        return [
            'No', 'NPSN', 'Kode UPB', 'Nama Sekolah', 'Status', 'Kecamatan', 'Subrayon',
            'Nama Kepala Sekolah', 'NIP Kepala Sekolah', 'No WhatsApp Kepala Sekolah', 'Status Kepegawaian Kepala Sekolah',
            'Nama Pengawas', 'NIP Pengawas',
            'Nama Kasie KPML', 'NIP Kasie KPML',
            'Nama Bendahara', 'NIP Bendahara', 'Status Kepegawaian Bendahara',
            'Alamat Sekolah', 'Nama Yayasan',
        ];
    }

    /**
     * @param  \App\Models\ProfilSekolah  $p
     */
    public function map($p): array
    {
        $this->nomorBaris++;

        return [
            $this->nomorBaris,
            $p->npsn,
            $p->kode_upb,
            $p->nama_sekolah,
            $p->status_label,
            $p->kecamatan,
            $p->subrayon,
            $p->nama_kepala_sekolah,
            $p->nip_kepala_sekolah,
            $p->no_whatsapp_kepala_sekolah,
            $p->status_kepegawaian_kepsek,
            $p->nama_pengawas,
            $p->nip_pengawas,
            $p->nama_kasie_kpml,
            $p->nip_kasie_kpml,
            $p->nama_bendahara,
            $p->nip_bendahara,
            $p->status_kepegawaian_bendahara,
            $p->alamat_sekolah,
            $p->nama_yayasan,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->tulisJudul($sheet);
                $this->rapikanTabel($sheet);
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

        $sheet->setCellValue('A'.self::BARIS_JUDUL_1, 'DAFTAR PROFIL SEKOLAH');

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
}
