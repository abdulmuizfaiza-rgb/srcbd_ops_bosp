<?php

namespace App\Exports;

use App\Models\Lampiran2b;
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
 * Export data Lampiran 2b (satu triwulan, satu sekolah) ke Excel.
 *
 * Formatnya mengikuti "Lampiran Surat Rekomendasi tentang Usulan Penerima
 * Tunjangan Profesi Guru": judul di baris 1-3 (merge sampai kolom F),
 * tabel mulai baris 5, dan lembar tanda tangan Pengawas (kolom B, tanda
 * tangan & NIP diisi manual) & Kepala Sekolah (kolom E, Nama & NIP
 * otomatis dari Profil Sekolah) di bagian bawah.
 *
 * 4 baris kosong (bergaris tabel, mengikuti lebar tabel) disediakan
 * setelah baris data terakhir sebelum lembar tanda tangan Pengawas &
 * Kepala Sekolah (baris tanda tangan sendiri TIDAK bergaris tabel).
 *
 * Kolom TMT ditulis sebagai tanggal Excel asli (bukan teks) dengan format
 * kalender dd-mm-yyyy, supaya sama dengan input tanggal (kalender) yang
 * dipakai di aplikasi.
 *
 * Urutan kolom data WAJIB mengikuti: NRG, NUPTK, Nama PTK, Nama Sekolah,
 * Keterangan, TMT - jangan diubah urutannya tanpa persetujuan.
 */
class Lampiran2bExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_HEADER_TABEL = 5;

    private const BARIS_KOSONG_SEBELUM_TANDA_TANGAN = 4;

    /**
     * @param  SupportCollection<int, Lampiran2b>  $baris
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
        return 'Lampiran 2b.TW'.$this->triwulan;
    }

    public function headings(): array
    {
        return [
            'NRG',
            'NUPTK',
            'Nama PTK',
            'Nama Sekolah',
            'Keterangan',
            'TMT',
        ];
    }

    public function map($baris): array
    {
        return [
            $baris->nrg,
            $baris->nuptk,
            $baris->nama_ptk,
            $baris->profilSekolah->nama_sekolah ?? $this->sekolah?->nama_sekolah,
            $baris->keterangan,
            optional($baris->tmt)->format('d-m-Y'),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->tulisJudul($sheet);
                $this->rapikanTabel($sheet);
                $this->tulisLembarTandaTangan($sheet);
                $this->tulisTanggalTmt($sheet);
            },
        ];
    }

    /**
     * Tulis ulang kolom TMT sebagai tanggal Excel asli (numeric date value +
     * format kalender dd-mm-yyyy), supaya inputannya sama dengan tanggal
     * (kalender) yang dipakai di aplikasi - bukan cuma teks "dd-mm-yyyy".
     */
    private function tulisTanggalTmt(Worksheet $sheet): void
    {
        $baris = self::BARIS_HEADER_TABEL + 1;

        foreach ($this->baris as $item) {
            if ($item->tmt) {
                $sheet->setCellValue('F'.$baris, Date::dateTimeToExcel($item->tmt));
                $sheet->getStyle('F'.$baris)->getNumberFormat()->setFormatCode('dd-mm-yyyy');
            }

            $baris++;
        }
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
            $rentang = 'A'.$baris.':F'.$baris;
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
        $rentangHeader = 'A'.self::BARIS_HEADER_TABEL.':F'.self::BARIS_HEADER_TABEL;

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
        $sheet->getStyle('A'.self::BARIS_HEADER_TABEL.':F'.($barisTerakhir + self::BARIS_KOSONG_SEBELUM_TANDA_TANGAN))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheet->freezePane('A'.(self::BARIS_HEADER_TABEL + 1));
        $sheet->setAutoFilter($rentangHeader);
        $sheet->getRowDimension(self::BARIS_HEADER_TABEL)->setRowHeight(20);
    }

    /**
     * Lembar "Mengetahui/Menyetujui" Pengawas (kolom B, Nama & NIP otomatis
     * dari Profil Sekolah) & "Kepala Sekolah" (kolom E, Nama & NIP otomatis
     * dari Profil Sekolah) - 4 baris kosong (bergaris tabel) setelah baris
     * terakhir tabel, lalu jabatan, lalu 2 baris kosong (tempat tanda
     * tangan basah, TIDAK bergaris tabel), lalu baris tanda tangan/nama,
     * lalu baris NIP.
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
        $barisTandaTangan = $barisJabatan + 3;
        $barisNip = $barisTandaTangan + 1;

        $sheet->setCellValue('B'.$barisMengetahui, 'Mengetahui/Menyetujui :');
        $sheet->setCellValue('E'.$barisMengetahui, 'Sukabumi, ................................ '.$this->tahun);

        $sheet->setCellValue('B'.$barisJabatan, 'Pengawas');
        $sheet->setCellValue('E'.$barisJabatan, 'Kepala Sekolah');

        $sheet->setCellValue('B'.$barisTandaTangan, $this->sekolah->nama_pengawas ?: '................................');
        $sheet->setCellValue('E'.$barisTandaTangan, $this->sekolah->nama_kepala_sekolah ?: '................................');

        $sheet->setCellValue('B'.$barisNip, 'NIP. '.($this->sekolah->nip_pengawas ?: '...................................'));
        $sheet->setCellValue('E'.$barisNip, 'NIP. '.($this->sekolah->nip_kepala_sekolah ?: '...................................'));

        foreach ([$barisMengetahui, $barisJabatan, $barisTandaTangan, $barisNip] as $baris) {
            $sheet->getStyle('B'.$baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('E'.$baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->mergeCells('B'.$baris.':D'.$baris);
            $sheet->mergeCells('E'.$baris.':F'.$baris);
        }
    }
}
