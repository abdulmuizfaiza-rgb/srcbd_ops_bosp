<?php

namespace App\Exports;

use App\Models\Lampiran2c;
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
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export data Lampiran 2c (Daftar Penyesuaian Gaji Pokok, satu sekolah) ke
 * Excel.
 *
 * Formatnya: judul "DAFTAR PENYESUAIAN GAJI POKOK" di baris 1, "KECAMATAN :"
 * (otomatis dari data baris pertama) di baris 2, "SUBRAYON : CIBADAK" di
 * baris 3 (semua pada kolom A), baris judul kolom di baris 5-6: kolom G:H
 * dinaungi judul grup "Pangkat & Masa Kerja yang dimiliki" dan kolom I:J
 * dinaungi "TMT SK Terbaru yang dimiliki" (baris 5 merge horizontal, baris
 * 6 judul per-field Golongan/Masa Kerja/Pangkat-Berkala/TMT); kolom lain
 * (yang tidak punya judul grup) di-merge cell vertikal baris 5:6 supaya
 * tinggi judulnya seragam. Tabel data mulai baris 7, dan lembar tanda
 * tangan Pengawas (kolom B, diisi manual) & Kepala Sekolah (kolom K,
 * otomatis dari Profil Sekolah) di bagian bawah.
 *
 * Kolom Tempat Tugas, Jenis Kepangkatan, Golongan, & Pangkat/Berkala
 * diberi dropdown (Data Validation Excel) supaya penulisannya selalu sama
 * dengan pilihan yang ada di aplikasi - daftar sumbernya ditulis ke kolom
 * tersembunyi N/O/P/Q.
 *
 * 4 baris kosong (bergaris tabel, mengikuti lebar tabel) disediakan
 * setelah baris data terakhir sebelum lembar tanda tangan Pengawas &
 * Kepala Sekolah (baris tanda tangan sendiri TIDAK bergaris tabel).
 *
 * Kolom Gaji Pokok Lama & Gaji Pokok Baru diformat Rupiah (mis.
 * "Rp 1.000.000"). Kolom TMT ditulis sebagai tanggal Excel asli (bukan
 * teks) dengan format kalender dd-mm-yyyy, supaya sama dengan input
 * tanggal (kalender) yang dipakai di aplikasi.
 *
 * Urutan kolom data WAJIB mengikuti: NRG, NUPTK, Nama PTK, Tempat Tugas,
 * Kecamatan, Jenis Kepangkatan, Golongan, Masa Kerja, Pangkat/Berkala, TMT,
 * Gaji Pokok Lama, Gaji Pokok Baru, Keterangan - jangan diubah urutannya
 * tanpa persetujuan.
 */
class Lampiran2cExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_JUDUL_KOLOM_GRUP = 5;

    private const BARIS_HEADER_TABEL = 6;

    private const BARIS_KOSONG_SEBELUM_TANDA_TANGAN = 4;

    /**
     * @param  SupportCollection<int, Lampiran2c>  $baris
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
        return 'Lampiran 2c.TW'.$this->triwulan;
    }

    public function headings(): array
    {
        return [
            'NRG',
            'NUPTK',
            'Nama PTK',
            'Tempat Tugas',
            'Kecamatan',
            'Jenis Kepangkatan',
            'Golongan',
            'Masa Kerja',
            'Pangkat/Berkala',
            'TMT',
            'Gaji Pokok Lama',
            'Gaji Pokok Baru',
            'Keterangan',
        ];
    }

    public function map($baris): array
    {
        return [
            $baris->nrg,
            $baris->nuptk,
            $baris->nama_ptk,
            $baris->profilSekolah->nama_sekolah ?? $this->sekolah?->nama_sekolah,
            $baris->kecamatan,
            $baris->jenis_kepangkatan,
            $baris->golongan,
            $baris->masa_kerja,
            $baris->pangkat_berkala,
            optional($baris->tmt)->format('d-m-Y'),
            $baris->gaji_pokok_lama,
            $baris->gaji_pokok_baru,
            $baris->keterangan,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->tulisJudul($sheet);
                $this->tulisJudulKolomGrup($sheet);
                $this->gabungkanJudulKolomStandalone($sheet);
                $this->rapikanTabel($sheet);
                $this->tulisLembarTandaTangan($sheet);
                $this->tulisDaftarPilihanTersembunyi($sheet);
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
                $sheet->setCellValue('J'.$baris, Date::dateTimeToExcel($item->tmt));
                $sheet->getStyle('J'.$baris)->getNumberFormat()->setFormatCode('dd-mm-yyyy');
            }

            $baris++;
        }
    }

    /**
     * Kecamatan pada header diambil otomatis dari baris data pertama
     * (semua baris pada 1 kali export berasal dari 1 sekolah yang sama).
     */
    private function kecamatan(): string
    {
        return $this->baris->first()->kecamatan ?? '...................................';
    }

    private function tulisJudul(Worksheet $sheet): void
    {
        $sheet->setCellValue('A1', 'DAFTAR PENYESUAIAN GAJI POKOK');

        if ($this->sekolah === null) {
            // Rekap gabungan seluruh sekolah (Superadmin) bisa mencakup
            // lebih dari 1 kecamatan sekaligus, jadi baris 2 tidak lagi
            // menyebut 1 kecamatan tertentu - diganti keterangan rekap +
            // triwulan/tahun supaya tetap informatif.
            $sheet->setCellValue('A2', 'REKAP SELURUH SEKOLAH - TRIWULAN '.$this->triwulan.' TAHUN '.$this->tahun);
        } else {
            $sheet->setCellValue('A2', 'KECAMATAN : '.$this->kecamatan());
        }

        $sheet->setCellValue('A3', 'SUBRAYON : CIBADAK');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2:A3')->getFont()->setBold(true);
    }

    /**
     * Judul kolom grup di atas judul kolom per-field: kolom G:H "Pangkat &
     * Masa Kerja yang dimiliki" (menaungi Golongan & Masa Kerja), kolom I:J
     * "TMT SK Terbaru yang dimiliki" (menaungi Pangkat/Berkala & TMT).
     */
    private function tulisJudulKolomGrup(Worksheet $sheet): void
    {
        $baris = self::BARIS_JUDUL_KOLOM_GRUP;

        $sheet->setCellValue('G'.$baris, 'Pangkat & Masa Kerja yang dimiliki');
        $sheet->setCellValue('I'.$baris, 'TMT SK Terbaru yang dimiliki');

        $sheet->mergeCells('G'.$baris.':H'.$baris);
        $sheet->mergeCells('I'.$baris.':J'.$baris);

        $sheet->getStyle('G'.$baris.':J'.$baris)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle('G'.$baris.':J'.$baris)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getRowDimension($baris)->setRowHeight(20);
    }

    /**
     * Kolom yang TIDAK punya judul grup (semua kolom kecuali G,H,I,J yang
     * sudah di-merge oleh tulisJudulKolomGrup()) di-merge cell vertikal
     * antara baris judul grup & baris judul per-field, supaya tinggi judul
     * kolomnya seragam dengan kolom yang sudah punya judul grup.
     */
    private function gabungkanJudulKolomStandalone(Worksheet $sheet): void
    {
        $kolomSudahDigabung = ['G', 'H', 'I', 'J'];
        $barisAtas = self::BARIS_JUDUL_KOLOM_GRUP;
        $barisBawah = self::BARIS_HEADER_TABEL;

        foreach (range('A', 'M') as $kolom) {
            if (in_array($kolom, $kolomSudahDigabung, true)) {
                continue;
            }

            $nilai = $sheet->getCell($kolom.$barisBawah)->getValue();
            $sheet->setCellValue($kolom.$barisAtas, $nilai);
            $sheet->setCellValue($kolom.$barisBawah, null);
            $sheet->mergeCells($kolom.$barisAtas.':'.$kolom.$barisBawah);

            $sheet->getStyle($kolom.$barisAtas.':'.$kolom.$barisBawah)->applyFromArray([
                'font' => ['bold' => true],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);
        }
    }

    /**
     * Daftar pilihan (dropdown) pada hasil export supaya penulisan Tempat
     * Tugas, Jenis Kepangkatan, & Golongan selalu sama persis dengan yang
     * ada di aplikasi. Daftar sumbernya ditulis ke kolom tersembunyi N/O/P
     * lalu dirujuk lewat Data Validation Excel (bukan kolom biasa).
     */
    private function tulisDaftarPilihanTersembunyi(Worksheet $sheet): void
    {
        $jenisKepangkatan = array_values(Lampiran2c::JENIS_KEPANGKATAN_OPTIONS);
        foreach ($jenisKepangkatan as $indeks => $nilai) {
            $sheet->setCellValue('N'.($indeks + 1), $nilai);
        }

        $golongan = array_values(Lampiran2c::GOLONGAN_OPTIONS);
        foreach ($golongan as $indeks => $nilai) {
            $sheet->setCellValue('O'.($indeks + 1), $nilai);
        }

        $sekolah = ProfilSekolah::query()->orderBy('nama_sekolah')->pluck('nama_sekolah')->values();
        foreach ($sekolah as $indeks => $nilai) {
            $sheet->setCellValue('P'.($indeks + 1), $nilai);
        }

        $pangkatBerkala = array_values(Lampiran2c::PANGKAT_BERKALA_OPTIONS);
        foreach ($pangkatBerkala as $indeks => $nilai) {
            $sheet->setCellValue('Q'.($indeks + 1), $nilai);
        }

        foreach (['N', 'O', 'P', 'Q'] as $kolom) {
            $sheet->getColumnDimension($kolom)->setVisible(false);
        }

        $barisMulai = self::BARIS_HEADER_TABEL + 1;
        $barisSelesai = $this->barisAkhirValidasi();

        $this->terapkanValidasiPilihan($sheet, 'D', '=$P$1:$P$'.max($sekolah->count(), 1), $barisMulai, $barisSelesai);
        $this->terapkanValidasiPilihan($sheet, 'F', '=$N$1:$N$'.count($jenisKepangkatan), $barisMulai, $barisSelesai);
        $this->terapkanValidasiPilihan($sheet, 'G', '=$O$1:$O$'.count($golongan), $barisMulai, $barisSelesai);
        $this->terapkanValidasiPilihan($sheet, 'I', '=$Q$1:$Q$'.count($pangkatBerkala), $barisMulai, $barisSelesai);
    }

    /**
     * Baris terakhir tempat dropdown pilihan diterapkan - diberi buffer
     * jauh di bawah baris data terakhir supaya masih bisa dipakai kalau
     * ada baris baru yang ditambahkan manual di Excel sebelum di-import
     * lagi.
     */
    private function barisAkhirValidasi(): int
    {
        return $this->barisTerakhirTabel() + 300;
    }

    private function terapkanValidasiPilihan(Worksheet $sheet, string $kolom, string $referensiRentang, int $barisMulai, int $barisSelesai): void
    {
        for ($baris = $barisMulai; $baris <= $barisSelesai; $baris++) {
            $validasi = new DataValidation();
            $validasi->setType(DataValidation::TYPE_LIST);
            $validasi->setErrorStyle(DataValidation::STYLE_STOP);
            $validasi->setAllowBlank(true);
            $validasi->setShowInputMessage(true);
            $validasi->setShowErrorMessage(true);
            $validasi->setShowDropDown(true);
            $validasi->setErrorTitle('Nilai tidak valid');
            $validasi->setError('Silakan pilih nilai dari daftar dropdown yang tersedia.');
            $validasi->setPromptTitle('Pilih dari daftar');
            $validasi->setPrompt('Silakan pilih salah satu nilai dari daftar pilihan.');
            $validasi->setFormula1($referensiRentang);

            $sheet->getCell($kolom.$baris)->setDataValidation($validasi);
        }
    }

    private function barisTerakhirTabel(): int
    {
        return self::BARIS_HEADER_TABEL + $this->baris->count();
    }

    private function rapikanTabel(Worksheet $sheet): void
    {
        $barisTerakhir = $this->barisTerakhirTabel();
        $rentangHeader = 'A'.self::BARIS_HEADER_TABEL.':M'.self::BARIS_HEADER_TABEL;

        $sheet->getStyle($rentangHeader)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Garis tabel diteruskan sampai 4 baris kosong setelah baris data
        // terakhir (BARIS_KOSONG_SEBELUM_TANDA_TANGAN), supaya format
        // kolomnya tetap kelihatan meski baris itu belum diisi.
        $sheet->getStyle('A'.self::BARIS_JUDUL_KOLOM_GRUP.':M'.($barisTerakhir + self::BARIS_KOSONG_SEBELUM_TANDA_TANGAN))
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        if ($this->baris->count() > 0) {
            // Format Rupiah (mis. "Rp 1.000.000") supaya sama dengan tampilan
            // di aplikasi.
            $sheet->getStyle('K'.(self::BARIS_HEADER_TABEL + 1).':L'.$barisTerakhir)
                ->getNumberFormat()->setFormatCode('"Rp" #,##0');
        }

        $sheet->freezePane('A'.(self::BARIS_HEADER_TABEL + 1));
        $sheet->setAutoFilter($rentangHeader);
        $sheet->getRowDimension(self::BARIS_HEADER_TABEL)->setRowHeight(20);
    }

    /**
     * Lembar "Mengetahui/Menyetujui" Pengawas (kolom B, Nama & NIP otomatis
     * dari Profil Sekolah) & "Kepala Sekolah" (kolom K, Nama & NIP otomatis
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
        $sheet->setCellValue('K'.$barisMengetahui, 'Sukabumi, .................... '.$this->tahun);

        $sheet->setCellValue('B'.$barisJabatan, 'Pengawas');
        $sheet->setCellValue('K'.$barisJabatan, 'Kepala Sekolah');

        $sheet->setCellValue('B'.$barisTandaTangan, $this->sekolah->nama_pengawas ?: '...........................');
        $sheet->setCellValue('K'.$barisTandaTangan, $this->sekolah->nama_kepala_sekolah ?: '...........................');

        $sheet->setCellValue('B'.$barisNip, 'NIP. '.($this->sekolah->nip_pengawas ?: '...................................'));
        $sheet->setCellValue('K'.$barisNip, 'NIP. '.($this->sekolah->nip_kepala_sekolah ?: '...................................'));

        foreach ([$barisMengetahui, $barisJabatan, $barisTandaTangan, $barisNip] as $baris) {
            $sheet->getStyle('B'.$baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('K'.$baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }
    }
}
