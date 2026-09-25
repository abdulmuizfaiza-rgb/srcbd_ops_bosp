<?php

namespace App\Exports;

use App\Models\FormulirBosK7;
use App\Models\ProfilSekolah;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Formulir BOS K7b" (Register Penutupan Kas) - meniru layout
 * gambar contoh yang diupload user 2026-09-23 (kotak referensi "FORMULIR
 * BOS-K7b" pojok kanan atas, judul "REGISTER PENUTUPAN KAS", blok header
 * D/K/A, rincian pecahan uang kertas & logam, Saldo Rekening Bank, B,
 * Perbedaan, Penjelasan Perbedaan, blok tanda tangan).
 *
 * Pola sama seperti PajakBospRegulerExport: SELURUH isi sheet ditulis
 * langsung lewat AfterSheet (FromArray kosong hanya syarat teknis), rumus
 * SELALU diterima sudah-dihitung dari App\Models\FormulirBosK7 (dipanggil
 * dari Livewire Index) supaya tidak dobel-tulis.
 */
class FormulirBosK7bSheetExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    private const KOLOM_TERAKHIR = 'F';

    /**
     * @param  array<string, mixed>  $data  Hasil Index::dataFormulir().
     * @param  array{kertas?: string, margin?: array{kiri: float, kanan: float, atas: float, bawah: float}}  $pengaturanCetak
     */
    public function __construct(
        protected ProfilSekolah $sekolah,
        protected int $tahun,
        protected int $bulan,
        protected array $data,
        protected array $pengaturanCetak = [],
    ) {}

    /**
     * Terapkan jenis kertas & margin halaman (permintaan user 2026-09-23,
     * round ketiga) - default 'a4' + Left/Right 2.5cm, Top 3cm, Bottom
     * 2.5cm kalau $pengaturanCetak tidak dikirim (mis. dipanggil dari
     * tempat lain / test lama). PhpSpreadsheet menyimpan margin dalam
     * INCI, bukan cm - dikonversi di sini (1 inci = 2.54 cm).
     */
    private function pengaturanHalaman(Worksheet $sheet): void
    {
        $kertas = $this->pengaturanCetak['kertas'] ?? 'a4';
        $margin = $this->pengaturanCetak['margin'] ?? ['kiri' => 2.5, 'kanan' => 2.5, 'atas' => 3, 'bawah' => 2.5];

        $sheet->getPageSetup()
            ->setPaperSize($kertas === 'f4' ? \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_FOLIO : \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);

        $sheet->getPageMargins()
            ->setLeft($margin['kiri'] / 2.54)
            ->setRight($margin['kanan'] / 2.54)
            ->setTop($margin['atas'] / 2.54)
            ->setBottom($margin['bawah'] / 2.54);
    }

    public function array(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'K7b '.FormulirBosK7::BULAN_OPTIONS[$this->bulan].' '.$this->tahun;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $this->tulisFormulir($sheet);
            },
        ];
    }

    private function rp(int $nilai): string
    {
        return 'Rp '.number_format($nilai, 0, ',', '.');
    }

    private function tulisFormulir(Worksheet $sheet): void
    {
        $this->pengaturanHalaman($sheet);

        $d = $this->data;

        // Kotak referensi pojok kanan atas.
        $sheet->setCellValue('E1', 'FORMULIR BOS-K7b');
        $sheet->mergeCells('E1:F1');
        $sheet->getStyle('E1:F1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        // Judul.
        $sheet->setCellValue('A3', 'REGISTER PENUTUPAN KAS');
        $sheet->mergeCells('A3:'.self::KOLOM_TERAKHIR.'3');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Blok header (Tanggal Penutupan, Nama Penutup KAS, D/K/A, Saldo Kas Tunai).
        $baris = 5;
        $headerRows = [
            ['Tanggal Penutupan Kas Bulan ini', $d['tanggalPenutupan']->translatedFormat('d F Y')],
            ['Nama Penutup KAS (Pemegang KAS)', $this->sekolah->nama_bendahara],
            ['Tanggal Penutupan KAS Bulan Lalu', $d['tanggalPenutupanLalu']->translatedFormat('d F Y')],
            ['Jumlah Total Penerimaan BKU (D)', $this->rp((int) ($d['formulir']->jumlah_total_penerimaan_bku ?? 0))],
            ['Jumlah Total Pengeluaran BKU (K)', $this->rp((int) ($d['formulir']->jumlah_total_pengeluaran_bku ?? 0))],
        ];
        foreach ($headerRows as [$label, $nilai]) {
            $sheet->setCellValue('A'.$baris, $label);
            $sheet->setCellValue('D'.$baris, $nilai);
            $sheet->mergeCells('D'.$baris.':'.self::KOLOM_TERAKHIR.$baris);
            $baris++;
        }

        $sheet->setCellValue('A'.$baris, 'A.  Saldo Buku Kas Umum (A=D-K)');
        $sheet->setCellValue('D'.$baris, $this->rp($d['saldoBku']));
        $sheet->mergeCells('D'.$baris.':'.self::KOLOM_TERAKHIR.$baris);
        $sheet->getStyle('A'.$baris.':'.self::KOLOM_TERAKHIR.$baris)->getFont()->setBold(true);
        $baris++;

        $sheet->setCellValue('A'.$baris, 'Saldo Kas Tunai');
        $sheet->setCellValue('D'.$baris, $this->rp($d['saldoKasTunai']));
        $sheet->mergeCells('D'.$baris.':'.self::KOLOM_TERAKHIR.$baris);
        $baris += 2;

        // 1. Lembaran uang kertas.
        $sheet->setCellValue('A'.$baris, '1.  Lembaran uang kertas');
        $sheet->getStyle('A'.$baris)->getFont()->setBold(true);
        $baris++;
        foreach (FormulirBosK7::NOMINAL_UANG_KERTAS as $nominal) {
            $jumlahLembar = (int) ($d['formulir']->{FormulirBosK7::fieldLembar($nominal)} ?? 0);
            $sheet->setCellValue('A'.$baris, 'Lembaran uang kertas');
            $sheet->setCellValue('C'.$baris, $this->rp($nominal));
            $sheet->setCellValue('D'.$baris, $jumlahLembar.' Lembar');
            $sheet->setCellValue('F'.$baris, $this->rp($nominal * $jumlahLembar));
            $baris++;
        }
        $sheet->setCellValue('A'.$baris, 'Sub Jumlah Lembar uang kertas (1)');
        $sheet->setCellValue('F'.$baris, $this->rp($d['subJumlahKertas']));
        $sheet->getStyle('A'.$baris.':'.self::KOLOM_TERAKHIR.$baris)->getFont()->setBold(true);
        $baris += 2;

        // 2. Keping uang logam.
        $sheet->setCellValue('A'.$baris, '2.  Keping uang logam');
        $sheet->getStyle('A'.$baris)->getFont()->setBold(true);
        $baris++;
        foreach (FormulirBosK7::NOMINAL_UANG_LOGAM as $nominal) {
            $jumlahKeping = (int) ($d['formulir']->{FormulirBosK7::fieldKeping($nominal)} ?? 0);
            $sheet->setCellValue('A'.$baris, 'Keping uang logam');
            $sheet->setCellValue('C'.$baris, $this->rp($nominal));
            $sheet->setCellValue('D'.$baris, $jumlahKeping.' Keping');
            $sheet->setCellValue('F'.$baris, $this->rp($nominal * $jumlahKeping));
            $baris++;
        }
        $sheet->setCellValue('A'.$baris, 'Sub Jumlah Keping uang logam (2)');
        $sheet->setCellValue('F'.$baris, $this->rp($d['subJumlahLogam']));
        $sheet->getStyle('A'.$baris.':'.self::KOLOM_TERAKHIR.$baris)->getFont()->setBold(true);
        $baris += 2;

        // 3. Saldo Rekening Bank.
        $sheet->setCellValue('A'.$baris, '3.  Saldo Rekening Bank Sub Jumlah (3)');
        $sheet->setCellValue('F'.$baris, $this->rp((int) ($d['formulir']->saldo_rekening_bank ?? 0)));
        $baris += 2;

        // B. Jumlah.
        $sheet->setCellValue('A'.$baris, 'B.  Jumlah (1+2+3)');
        $sheet->setCellValue('F'.$baris, $this->rp($d['jumlahB']));
        $sheet->getStyle('A'.$baris.':'.self::KOLOM_TERAKHIR.$baris)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
        ]);
        $baris += 2;

        // Perbedaan.
        $sheet->setCellValue('A'.$baris, 'Perbedaan (A-B)');
        $sheet->setCellValue('F'.$baris, $this->rp($d['perbedaan']));
        $baris += 2;

        // Penjelasan Perbedaan.
        $sheet->setCellValue('A'.$baris, 'Penjelasan Perbedaan');
        $baris++;
        $sheet->setCellValue('A'.$baris, (string) $d['penjelasanPerbedaan']);
        $sheet->mergeCells('A'.$baris.':'.self::KOLOM_TERAKHIR.($baris + 1));
        $sheet->getStyle('A'.$baris.':'.self::KOLOM_TERAKHIR.($baris + 1))->applyFromArray([
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $baris += 3;

        // Tanggal + blok tanda tangan.
        $sheet->setCellValue('D'.$baris, 'Tanggal, '.$d['tanggalPenutupan']->translatedFormat('d F Y'));
        $sheet->mergeCells('D'.$baris.':'.self::KOLOM_TERAKHIR.$baris);
        $sheet->getStyle('D'.$baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $baris += 2;

        // Perbaikan 2026-09-23 (round kelima, permintaan user): Bendahara
        // dipindah lagi dari kolom B ("kolom 2", round ketiga) ke kolom A
        // ("kolom ke 1") dengan teks rata TENGAH (bukan rata kiri) -
        // kolom A otomatis lebar (ShouldAutoSize, dipakai label-label
        // panjang di blok header di atas) sehingga teks pendek tanda
        // tangan Bendahara terlihat di tengah-tengah blok kiri halaman.
        // Kepala Sekolah TIDAK diubah (tetap kolom D, tidak diminta).
        $barisTtdMulai = $baris;
        $sheet->setCellValue('A'.$baris, 'Yang diperiksa,');
        $sheet->setCellValue('D'.$baris, 'Yang Memeriksa,');
        $baris++;
        $sheet->setCellValue('A'.$baris, 'Bendahara');
        $sheet->setCellValue('D'.$baris, 'Kepala Sekolah');
        $baris++;
        $sheet->setCellValue('D'.$baris, $this->sekolah->nama_sekolah);
        // Perbaikan 2026-09-23 (round keenam, permintaan user): ruang
        // kosong tanda tangan diperbesar lagi (+2 baris, dari +3 jadi +5)
        // supaya baris Nama Bendahara+NIP & Nama Kepala Sekolah+NIP turun
        // 2 baris dari posisi round kelima (baris 47/48 -> 49/50).
        $baris += 5;

        $sheet->setCellValue('A'.$baris, $this->sekolah->nama_bendahara);
        $sheet->setCellValue('D'.$baris, $this->sekolah->nama_kepala_sekolah);
        $sheet->getStyle('A'.$baris)->applyFromArray(['font' => ['bold' => true, 'underline' => true]]);
        $sheet->getStyle('D'.$baris.':'.self::KOLOM_TERAKHIR.$baris)->applyFromArray(['font' => ['bold' => true, 'underline' => true]]);
        $baris++;
        $sheet->setCellValue('A'.$baris, 'NIP. '.$this->sekolah->nip_bendahara);
        $sheet->setCellValue('D'.$baris, 'NIP. '.$this->sekolah->nip_kepala_sekolah);

        $sheet->getStyle('A'.$barisTtdMulai.':A'.$baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('A1:'.self::KOLOM_TERAKHIR.$baris)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    }
}
