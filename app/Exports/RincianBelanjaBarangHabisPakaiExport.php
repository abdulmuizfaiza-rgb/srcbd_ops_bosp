<?php

namespace App\Exports;

use App\Models\ProfilSekolah;
use App\Models\RincianBelanjaBarangHabisPakai;
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
 * Export data Rincian Belanja Barang Habis Pakai (satu triwulan, satu
 * sekolah) ke Excel - layout & lembar tanda tangan SAMA PERSIS seperti
 * RincianPemeliharaanExport/RincianBelanjaModalExport, hanya judul & nama
 * file yang disesuaikan dengan menu ini. Menu ini HANYA SATU jenis
 * (tidak ada tab utama), jadi TIDAK ADA parameter $jenis di sini (beda
 * dengan RincianPemeliharaanExport/RincianBelanjaModalExport).
 *
 * Urutan kolom data mengikuti urutan field pada gambar contoh tabel yang
 * diupload user (tanpa kolom "No", sama seperti export menu lain di
 * aplikasi ini): Kode UPB, NPSN, Nama Sekolah, Nama Barang, Nama Merk
 * Barang, Volume, Satuan, Harga Satuan, Total Harga, Asal Usul, Tanggal,
 * Keterangan.
 *
 * PENTING (permintaan user 2026-09-23): lembar tanda tangan "Mengetahui/
 * Menyetujui" Bendahara BOSP/Kepala Sekolah SUDAH DIHAPUS - hanya judul
 * tabel (tulisJudul()) yang dimunculkan. Syarat pilih 1 sekolah (kalau
 * ada di Livewire Index) SENGAJA TIDAK diubah (jawaban AskUserQuestion
 * 2026-09-23: "Tetap dipertahankan").
 */
class RincianBelanjaBarangHabisPakaiExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_HEADER_TABEL = 5;

    private const BARIS_KOSONG_SEBELUM_TANDA_TANGAN = 4;

    private const KOLOM_TERAKHIR = 'L';

    /**
     * @param  SupportCollection<int, RincianBelanjaBarangHabisPakai>  $baris
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
        return 'Barang Habis Pakai TW'.$this->triwulan;
    }

    public function headings(): array
    {
        return [
            'Kode UPB',
            'NPSN',
            'Nama Sekolah',
            'Nama Barang',
            'Nama Merk Barang',
            'Volume',
            'Satuan',
            'Harga Satuan',
            'Total Harga',
            'Asal Usul',
            'Tanggal',
            'Keterangan',
        ];
    }

    public function map($baris): array
    {
        return [
            $baris->kode_upb,
            $baris->profilSekolah->npsn ?? $this->sekolah?->npsn,
            $baris->profilSekolah->nama_sekolah ?? $this->sekolah?->nama_sekolah,
            $baris->nama_barang,
            $baris->nama_merk_barang,
            $baris->volume,
            $baris->satuan,
            $baris->harga_satuan,
            $baris->total_harga,
            $baris->asal_usul,
            $baris->tanggal?->format('d-m-Y'),
            $baris->keterangan,
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

    private function tulisJudul(Worksheet $sheet): void
    {
        $judul1 = 'DAFTAR RINCIAN BELANJA BARANG HABIS PAKAI';
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

        if ($this->baris->count() > 0) {
            // Format Rupiah (mis. "Rp 1.000.000") pada kolom Harga Satuan
            // (H) & Total Harga (I), supaya sama dengan tampilan di
            // aplikasi.
            foreach (['H', 'I'] as $kolom) {
                $sheet->getStyle($kolom.(self::BARIS_HEADER_TABEL + 1).':'.$kolom.$barisTerakhir)
                    ->getNumberFormat()->setFormatCode('"Rp" #,##0');
            }
        }

        $sheet->freezePane('A'.(self::BARIS_HEADER_TABEL + 1));
        $sheet->setAutoFilter($rentangHeader);
        $sheet->getRowDimension(self::BARIS_HEADER_TABEL)->setRowHeight(20);
    }
}
