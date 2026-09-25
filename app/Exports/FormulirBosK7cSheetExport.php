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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Formulir BOS K7c" (Berita Acara Pemeriksaan Kas) - meniru layout
 * gambar contoh yang diupload user 2026-09-23 (kotak referensi "FORMULIR
 * BOS K7c" pojok kanan atas, judul "BERITA ACARA PEMERIKSAAN KAS" +
 * "PRIODE : {bulan} {tahun}", narasi otomatis + Nomor/Tanggal SK, blok
 * a/b/Jumlah/Saldo BKU/Perbedaan, blok tanda tangan).
 *
 * Pola sama seperti FormulirBosK7bSheetExport (AfterSheet manual-write,
 * rumus SELALU diterima sudah-dihitung dari App\Models\FormulirBosK7).
 */
class FormulirBosK7cSheetExport implements FromArray, ShouldAutoSize, WithTitle, WithEvents
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
     * 2.5cm kalau $pengaturanCetak tidak dikirim. PhpSpreadsheet menyimpan
     * margin dalam INCI, bukan cm - dikonversi di sini (1 inci = 2.54 cm).
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
        return 'K7c '.FormulirBosK7::BULAN_OPTIONS[$this->bulan].' '.$this->tahun;
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

    private function tulis(Worksheet $sheet, int $baris, string $isi, bool $bold = false): int
    {
        $sheet->setCellValue('A'.$baris, $isi);
        $sheet->mergeCells('A'.$baris.':'.self::KOLOM_TERAKHIR.$baris);
        $sheet->getStyle('A'.$baris)->applyFromArray([
            'font' => ['bold' => $bold],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP],
        ]);

        return $baris + 1;
    }

    private function tulisFormulir(Worksheet $sheet): void
    {
        $this->pengaturanHalaman($sheet);

        $d = $this->data;
        $labelBulan = FormulirBosK7::BULAN_OPTIONS[$this->bulan];

        $sheet->setCellValue('E1', 'FORMULIR BOS K7c');
        $sheet->mergeCells('E1:F1');
        $sheet->getStyle('E1:F1')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
        ]);

        $sheet->setCellValue('A3', 'BERITA ACARA PEMERIKSAAN KAS');
        $sheet->mergeCells('A3:'.self::KOLOM_TERAKHIR.'3');
        $sheet->setCellValue('A4', 'PRIODE : '.$labelBulan.' '.$this->tahun);
        $sheet->mergeCells('A4:'.self::KOLOM_TERAKHIR.'4');
        $sheet->getStyle('A3:'.self::KOLOM_TERAKHIR.'4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $baris = 6;

        $noSkKepsek = (string) ($d['noSkKepalaSekolah'] ?: '_______________');
        $tglSkKepsek = $d['tanggalSkKepalaSekolah'] ? $d['tanggalSkKepalaSekolah']->translatedFormat('d F Y') : '_______________';

        $baris = $this->tulis($sheet, $baris, 'Pada hari ini '.$d['narasiTanggalK7c'].' yang bertanda tangan di bawah ini, Saya Kepala Sekolah yang ditunjuk berdasarkan Surat Keputusan Nomor : '.$noSkKepsek.' tanggal '.$tglSkKepsek.'.');
        $baris++;

        $baris = $this->tulis($sheet, $baris, 'Nama    : '.$this->sekolah->nama_kepala_sekolah);
        $baris = $this->tulis($sheet, $baris, 'Jabatan : Kepala Sekolah');
        $baris++;

        $baris = $this->tulis($sheet, $baris, 'Melakukan pemeriksaan KAS kepada :');
        $baris++;
        $baris = $this->tulis($sheet, $baris, 'Nama    : '.$this->sekolah->nama_bendahara);
        $baris = $this->tulis($sheet, $baris, 'Jabatan : Bendahara BOS / Pemegang KAS');
        $baris++;

        $noSkBendahara = (string) ($d['noSkBendahara'] ?: '_______________');
        $tglSkBendahara = $d['tanggalSkBendahara'] ? $d['tanggalSkBendahara']->translatedFormat('d F Y') : '_______________';
        $baris = $this->tulis($sheet, $baris, 'Yang berdasarkan Surat Keputusan Nomor : '.$noSkBendahara.' tanggal '.$tglSkBendahara.' ditugaskan dengan pengurusan uang BOSP. Berdasarkan pemeriksaan kas serta bukti-bukti dalam pengurusan itu, kami menemui kenyataan sebagai berikut :');
        $baris += 2;

        $baris = $this->tulis($sheet, $baris, 'Jumlah uang yang dihitung dihadapan Bendahara/ Pemegang Kas adalah :');
        $baris++;

        $sheet->setCellValue('A'.$baris, 'a  Saldo KAS (Uang kertas dan uang logam)');
        $sheet->setCellValue('E'.$baris, ':  Rp');
        $sheet->setCellValue('F'.$baris, $this->rp($d['saldoKasTunai']));
        $baris++;
        $sheet->setCellValue('A'.$baris, 'b  Saldo Bank');
        $sheet->setCellValue('E'.$baris, ':  Rp');
        $sheet->setCellValue('F'.$baris, $this->rp((int) ($d['formulir']->saldo_rekening_bank ?? 0)));
        $baris++;
        $sheet->setCellValue('A'.$baris, 'Jumlah');
        $sheet->setCellValue('E'.$baris, ':  Rp');
        $sheet->setCellValue('F'.$baris, $this->rp($d['jumlahB']));
        $sheet->getStyle('A'.$baris.':'.self::KOLOM_TERAKHIR.$baris)->getFont()->setBold(true);
        $baris += 2;

        $sheet->setCellValue('A'.$baris, 'Saldo menurut Buku Kas Umum (BKU)');
        $sheet->setCellValue('E'.$baris, ':  Rp');
        $sheet->setCellValue('F'.$baris, $this->rp($d['saldoBku']));
        $baris++;
        $sheet->setCellValue('A'.$baris, 'Perbedaan Antara Saldo KAS dan Kas Umum');
        $sheet->setCellValue('E'.$baris, ':  Rp');
        $sheet->setCellValue('F'.$baris, $this->rp($d['perbedaan']));
        $baris += 3;

        // Perbaikan 2026-09-23 (round kelima, permintaan user): Bendahara
        // dipindah lagi dari kolom B ("kolom 2", round ketiga) ke kolom A
        // ("kolom ke 1") dengan teks rata TENGAH (bukan rata kiri) -
        // kolom A otomatis lebar (ShouldAutoSize, dipakai narasi/label
        // panjang di atas) sehingga teks pendek tanda tangan Bendahara
        // terlihat di tengah-tengah blok kiri halaman. Kepala Sekolah
        // TIDAK diubah (tetap kolom D, tidak diminta).
        $barisTtdMulai = $baris;
        $sheet->setCellValue('A'.$baris, 'Bendahara / Pemegang KAS');
        $sheet->setCellValue('D'.$baris, 'Kepala Sekolah');
        $baris++;
        $sheet->setCellValue('D'.$baris, $this->sekolah->nama_sekolah);
        // Perbaikan 2026-09-23 (round keenam, permintaan user): ruang
        // kosong tanda tangan diperbesar lagi (+2 baris, dari +3 jadi +5)
        // supaya baris Nama Bendahara+NIP & Nama Kepala Sekolah+NIP turun
        // 2 baris dari posisi round kelima (baris 33/34 -> 35/36).
        $baris += 5;

        $sheet->setCellValue('A'.$baris, $this->sekolah->nama_bendahara);
        $sheet->setCellValue('D'.$baris, $this->sekolah->nama_kepala_sekolah);
        $sheet->getStyle('A'.$baris)->applyFromArray(['font' => ['bold' => true, 'underline' => true]]);
        $sheet->getStyle('D'.$baris.':'.self::KOLOM_TERAKHIR.$baris)->applyFromArray(['font' => ['bold' => true, 'underline' => true]]);
        $baris++;
        $sheet->setCellValue('A'.$baris, 'NIP. '.$this->sekolah->nip_bendahara);
        $sheet->setCellValue('D'.$baris, 'NIP. '.$this->sekolah->nip_kepala_sekolah);

        $sheet->getStyle('A'.$barisTtdMulai.':A'.$baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
