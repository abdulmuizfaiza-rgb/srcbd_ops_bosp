<?php

namespace App\Exports;

use App\Models\Lampiran2a;
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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export data Lampiran 2a (satu triwulan, satu sekolah) ke Excel.
 *
 * Formatnya mengikuti "Lampiran Surat Rekomendasi tentang Usulan Penerima
 * Tunjangan Profesi Guru": judul di baris 1-3, tabel mulai baris 5, dan
 * lembar tanda tangan Pengawas (kolom B, NIP diisi manual) & Kepala
 * Sekolah (kolom E, NIP otomatis dari Profil Sekolah) di bagian bawah.
 *
 * SEJAK 2026-10-15: Status Kepegawaian Lampiran 2a sekarang diambil
 * OTOMATIS dari Data PTK (permintaan user) begitu baris terhubung ke
 * sebuah PTK - lihat App\Livewire\PendataanOps\Lampiran2a\Index::
 * updatedDataPtkId().
 *
 * SEJAK 2026-10-16 (permintaan user): kolom Status Kepegawaian TIDAK
 * LAGI diberi dropdown (Data Validation Excel) di hasil export ini -
 * menu Unduhan isinya data yang SUDAH tersimpan (bukan template untuk
 * diisi lalu diimport ulang seperti DataPtkExport/ProfilSekolahExport),
 * jadi yang ditampilkan cukup hasil inputan apa adanya (teks biasa).
 *
 * 4 baris kosong (bergaris tabel, mengikuti lebar tabel) disediakan
 * setelah baris data terakhir sebelum lembar tanda tangan Pengawas &
 * Kepala Sekolah (baris tanda tangan sendiri TIDAK bergaris tabel).
 *
 * Urutan kolom data WAJIB mengikuti: NRG, NUPTK, Nama PTK, Status
 * Kepegawaian, Nama Sekolah, Gaji Pokok Bulan Januari, NPWP - jangan
 * diubah urutannya tanpa persetujuan.
 */
class Lampiran2aExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_HEADER_TABEL = 5;

    private const BARIS_KOSONG_SEBELUM_TANDA_TANGAN = 4;

    /**
     * @param  SupportCollection<int, Lampiran2a>  $baris
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
        return 'Lampiran 2a.TW'.$this->triwulan;
    }

    public function headings(): array
    {
        return [
            'NRG',
            'NUPTK',
            'Nama PTK',
            'Status Kepegawaian',
            'Nama Sekolah',
            'Gaji Pokok Bulan Januari '.$this->tahun,
            'NPWP',
        ];
    }

    public function map($baris): array
    {
        return [
            $baris->nrg,
            $baris->nuptk,
            $baris->nama_ptk,
            $baris->status_kepegawaian,
            $baris->profilSekolah->nama_sekolah ?? $this->sekolah?->nama_sekolah,
            $baris->gaji_pokok_januari,
            $baris->npwp,
        ];
    }

    /**
     * Kolom berisi NRG/NUPTK/NPWP dipaksa bertipe TEKS (bukan Angka) - kalau
     * dibiarkan memakai tipe Angka bawaan Excel, angka yang berawalan "0"
     * bisa TERPOTONG (hilang nolnya) & angka berdigit panjang bisa tampil
     * beda (notasi ilmiah) di Excel - harus sama persis dengan yang tampil
     * di aplikasi. Permintaan user 2026-10-16: "hasil nya tidak terpotong
     * atau berubah ... saya ingin sesuai dengan yang ada di aplikasi."
     */
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (in_array($cell->getColumn(), $this->kolomDipaksaTeks(), true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return (new DefaultValueBinder())->bindValue($cell, $value);
    }

    /** @return string[] kolom NRG, NUPTK, NPWP - urutan kolom tabel ini tetap, lihat headings()/map(). */
    private function kolomDipaksaTeks(): array
    {
        return ['A', 'B', 'G'];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->tulisJudul($sheet);
                $this->rapikanTabel($sheet);
                $this->tulisLembarTandaTangan($sheet);
            },
        ];
    }

    private function tulisJudul(Worksheet $sheet): void
    {
        $judul1 = 'LAMPIRAN SURAT REKOMENDASI TENTANG USULAN PENERIMA TUNJANGAN PROFESI GURU';
        if ($this->sekolah === null) {
            $judul1 .= ' - REKAP SELURUH SEKOLAH';
        }

        $sheet->setCellValue('A1', $judul1);
        $sheet->setCellValue('A2', 'TRIWULAN '.$this->triwulan.' TAHUN ANGGARAN '.$this->tahun);
        $sheet->setCellValue('A3', 'KOMISARIAT/KECAMATAN CIBADAK');

        foreach ([1, 2, 3] as $baris) {
            $rentang = 'A'.$baris.':G'.$baris;
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
        $rentangHeader = 'A'.self::BARIS_HEADER_TABEL.':G'.self::BARIS_HEADER_TABEL;

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
        $sheet->getStyle('A'.self::BARIS_HEADER_TABEL.':G'.($barisTerakhir + self::BARIS_KOSONG_SEBELUM_TANDA_TANGAN))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        if ($this->baris->count() > 0) {
            // Format Rupiah (mis. "Rp 1.000.000") supaya sama dengan tampilan
            // di aplikasi.
            $sheet->getStyle('F'.(self::BARIS_HEADER_TABEL + 1).':F'.$barisTerakhir)
                ->getNumberFormat()->setFormatCode('"Rp" #,##0');
        }

        $sheet->freezePane('A'.(self::BARIS_HEADER_TABEL + 1));
        $sheet->setAutoFilter($rentangHeader);
        $sheet->getRowDimension(self::BARIS_HEADER_TABEL)->setRowHeight(20);
    }

    /**
     * Lembar "Mengetahui/Menyetujui" Pengawas (kolom B) & "Kepala Sekolah,"
     * (kolom F) - 4 baris kosong (bergaris tabel) setelah baris terakhir
     * tabel, lalu jabatan, lalu 2 baris kosong untuk tempat tanda tangan
     * basah (TIDAK bergaris tabel). Nama & NIP Pengawas dan Nama & NIP
     * Kepala Sekolah otomatis diambil dari menu Profil Sekolah.
     */
    private function tulisLembarTandaTangan(Worksheet $sheet): void
    {
        // Lembar tanda tangan Pengawas & Kepala Sekolah hanya berlaku untuk
        // 1 sekolah - pada rekap gabungan seluruh sekolah (Superadmin,
        // $sekolah null) tidak ada satupun Pengawas/Kepala Sekolah tunggal
        // yang bisa mewakili semua sekolah, jadi lembar ini dilewati.
        if ($this->sekolah === null) {
            return;
        }

        $barisTerakhirTabel = $this->barisTerakhirTabel();
        $barisMengetahui = $barisTerakhirTabel + self::BARIS_KOSONG_SEBELUM_TANDA_TANGAN + 1;
        $barisJabatan = $barisMengetahui + 1;
        $barisNamaPengawas = $barisJabatan + 3;
        $barisNipPengawas = $barisNamaPengawas + 1;
        $barisNamaKepsek = $barisJabatan + 3;
        $barisNipKepsek = $barisNamaKepsek + 1;

        $sheet->setCellValue('B'.$barisMengetahui, 'Mengetahui/Menyetujui :');
        $sheet->setCellValue('F'.$barisMengetahui, 'Sukabumi, .................... '.$this->tahun);

        $sheet->setCellValue('B'.$barisJabatan, 'Pengawas');
        $sheet->setCellValue('F'.$barisJabatan, 'Kepala Sekolah,');

        $sheet->setCellValue('B'.$barisNamaPengawas, $this->sekolah->nama_pengawas ?: '...................................');
        $sheet->setCellValue('B'.$barisNipPengawas, 'NIP. '.($this->sekolah->nip_pengawas ?: '...................................'));
        $sheet->setCellValue('F'.$barisNamaKepsek, $this->sekolah->nama_kepala_sekolah ?: '...................................');
        $sheet->setCellValue('F'.$barisNipKepsek, 'NIP. '.($this->sekolah->nip_kepala_sekolah ?: '...................................'));

        foreach ([$barisMengetahui, $barisJabatan, $barisNamaPengawas, $barisNipPengawas] as $baris) {
            $sheet->mergeCells('B'.$baris.':D'.$baris);
        }

        foreach ([$barisMengetahui, $barisJabatan, $barisNamaKepsek, $barisNipKepsek] as $baris) {
            $sheet->mergeCells('F'.$baris.':G'.$baris);
        }
    }

}
