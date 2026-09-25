<?php

namespace App\Exports;

use App\Models\ProfilSekolah;
use App\Models\RincianBelanjaModal;
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
 * Export data Rincian Belanja Modal (satu jenis, satu triwulan, satu
 * sekolah) ke Excel - layout SAMA PERSIS seperti RincianPemeliharaanExport,
 * hanya judul & nama file yang disesuaikan dengan menu ini. Judul berubah
 * sesuai $jenis: "DAFTAR RINCIAN BELANJA MODAL PERALATAN & MESIN (KIB B)"
 * atau "DAFTAR RINCIAN BELANJA MODAL ASET TETAP LAINNYA (KIB E)" - lihat
 * RincianBelanjaModal::JENIS_OPTIONS.
 *
 * Urutan kolom data mengikuti urutan field pada gambar contoh tabel yang
 * diupload user (tanpa kolom "No", sama seperti export menu lain di
 * aplikasi ini): Kode UPB, NPSN, Nama Sekolah, Nama Barang, Nama Merk
 * Barang, Volume, Satuan, Harga Satuan, Total Harga, Asal Usul, Tanggal,
 * Keterangan.
 *
 * PENTING (permintaan user 2026-09-23): lembar tanda tangan "Mengetahui/
 * Menyetujui" Bendahara BOSP/Kepala Sekolah SUDAH DIHAPUS dari hasil
 * export ini - hanya judul tabel (tulisJudul()) yang dimunculkan di atas
 * tabel. Syarat pilih 1 sekolah dulu sebelum Export Excel (kalau ada di
 * Livewire\PendataanBosp\RincianBelanjaModal\Index) SENGAJA TIDAK diubah
 * (jawaban AskUserQuestion 2026-09-23: "Tetap dipertahankan") - beda
 * dengan perubahan tab BMD sebelumnya yang syaratnya ikut dihapus.
 */
class RincianBelanjaModalExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_HEADER_TABEL = 5;

    private const BARIS_KOSONG_SEBELUM_TANDA_TANGAN = 4;

    private const KOLOM_TERAKHIR = 'L';

    /**
     * @param  SupportCollection<int, RincianBelanjaModal>  $baris
     */
    public function __construct(
        protected SupportCollection $baris,
        protected string $jenis,
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
        $singkatan = $this->jenis === RincianBelanjaModal::JENIS_ASET_TETAP_LAINNYA ? 'Aset Lainnya' : 'Peralatan Mesin';

        return 'Belanja Modal '.$singkatan.' TW'.$this->triwulan;
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
        $judul1 = strtoupper('DAFTAR '.RincianBelanjaModal::JENIS_OPTIONS[$this->jenis]);
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
