<?php

namespace App\Exports;

use App\Models\LanggananDayaJasa;
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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export data Langganan Daya dan Jasa (satu triwulan, satu sekolah) ke
 * Excel - sesuai jawaban AskUserQuestion 2026-09-10 ("Sama seperti
 * Penerimaan Honor PTK, judul disesuaikan"): layout & lembar tanda
 * tangan SAMA PERSIS seperti PenerimaanHonorPtkExport, hanya judul &
 * kolom data yang disesuaikan dengan menu ini - judul "DAFTAR
 * LANGGANAN DAYA DAN JASA" + baris kedua "TRIWULAN {n}, TAHUN ANGGARAN
 * {tahun}", lembar tanda tangan "Bendahara BOSP" (kolom B) & "Kepala
 * Sekolah," (kolom F), otomatis dari Profil Sekolah.
 *
 * Urutan kolom data WAJIB mengikuti: NPSN, Nama Sekolah, Uraian
 * Pembayaran, Volume, Satuan, Tarif Harga, Jumlah, Tanggal Bayar -
 * sesuai urutan field pada gambar contoh tabel yang diupload user.
 *
 * PENTING (permintaan user 2026-09-23): lembar tanda tangan "Mengetahui/
 * Menyetujui" Bendahara BOSP/Kepala Sekolah SUDAH DIHAPUS - hanya judul
 * tabel (tulisJudul()) yang dimunculkan. Syarat pilih 1 sekolah (kalau
 * ada di Livewire Index) SENGAJA TIDAK diubah (jawaban AskUserQuestion
 * 2026-09-23: "Tetap dipertahankan").
 */
class LanggananDayaJasaExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_HEADER_TABEL = 5;

    private const BARIS_KOSONG_SEBELUM_TANDA_TANGAN = 4;

    /**
     * @param  SupportCollection<int, LanggananDayaJasa>  $baris
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
        return 'Daya Jasa TW'.$this->triwulan;
    }

    public function headings(): array
    {
        return [
            'NPSN',
            'Nama Sekolah',
            'Uraian Pembayaran',
            'Volume',
            'Satuan',
            'Tarif Harga',
            'Jumlah',
            'Tanggal Bayar',
        ];
    }

    public function map($baris): array
    {
        return [
            $baris->profilSekolah->npsn ?? $this->sekolah?->npsn,
            $baris->profilSekolah->nama_sekolah ?? $this->sekolah?->nama_sekolah,
            $baris->uraian_pembayaran,
            $baris->volume,
            $baris->satuan,
            $baris->tarif_harga,
            $baris->jumlah,
            $baris->tanggal_bayar?->format('d-m-Y'),
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
        $judul1 = 'DAFTAR LANGGANAN DAYA DAN JASA';
        if ($this->sekolah === null) {
            $judul1 .= ' - REKAP SELURUH SEKOLAH';
        }

        $sheet->setCellValue('A1', $judul1);
        $sheet->setCellValue('A2', 'TRIWULAN '.$this->triwulan.', TAHUN ANGGARAN '.$this->tahun);

        foreach ([1, 2] as $baris) {
            $rentang = 'A'.$baris.':H'.$baris;
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
        $rentangHeader = 'A'.self::BARIS_HEADER_TABEL.':H'.self::BARIS_HEADER_TABEL;

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
        $sheet->getStyle('A'.self::BARIS_HEADER_TABEL.':H'.($barisTerakhir + self::BARIS_KOSONG_SEBELUM_TANDA_TANGAN))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        if ($this->baris->count() > 0) {
            // Format Rupiah (mis. "Rp 1.000.000") pada kolom Tarif Harga
            // (F) & Jumlah (G), supaya sama dengan tampilan di aplikasi.
            foreach (['F', 'G'] as $kolom) {
                $sheet->getStyle($kolom.(self::BARIS_HEADER_TABEL + 1).':'.$kolom.$barisTerakhir)
                    ->getNumberFormat()->setFormatCode('"Rp" #,##0');
            }
        }

        $sheet->freezePane('A'.(self::BARIS_HEADER_TABEL + 1));
        $sheet->setAutoFilter($rentangHeader);
        $sheet->getRowDimension(self::BARIS_HEADER_TABEL)->setRowHeight(20);
    }
}
