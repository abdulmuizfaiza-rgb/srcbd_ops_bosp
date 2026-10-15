<?php

namespace App\Exports;

use App\Models\LembarValidasiTpgCeklist;
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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Lembar Validasi TPG" pada Excel menu Unduhan (Pendataan OPS >
 * Unduhan, BARU 2026-10-16, permintaan user: tambah sheet ini sbg SHEET
 * PERTAMA, sebelum Lampiran 2a/2b/2c - lihat App\Exports\UnduhanLampiranExport::sheets()).
 *
 * Polanya mengikuti App\Exports\Lampiran2aExport (judul di baris atas,
 * tabel data, lembar tanda tangan di bawahnya), TAPI blok tanda
 * tangannya memakai konsep 3-kolom (Kasie KPML/Pengawas/Kepala Sekolah)
 * sesuai desain final resources/views/pdf/partials/surat-tpg-lembar-validasi-isi.blade.php,
 * BUKAN 2-kolom (Pengawas/Kepala Sekolah) spt Lampiran2aExport.
 *
 * $sekolah null = rekap gabungan semua sekolah (khusus Superadmin) -
 * kolom "Nama Sekolah" ditambahkan, Tanggal Verifikasi/Catatan Hasil
 * Verifikasi/lembar tanda tangan DILEWATI (lihat docblock App\Support\
 * LembarValidasiTpgUnduhanData utk alasannya - sama pola dgn
 * Lampiran2aExport::tulisLembarTandaTangan() saat rekap gabungan).
 */
class LembarValidasiTpgExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_HEADER_TABEL = 5;

    private const BARIS_KOSONG_SEBELUM_TANDA_TANGAN = 2;

    /**
     * @param  SupportCollection<int, array<string, mixed>>  $baris  dari App\Support\LembarValidasiTpgUnduhanData::ambil()
     */
    public function __construct(
        protected SupportCollection $baris,
        protected int $triwulan,
        protected int $tahun,
        protected ?ProfilSekolah $sekolah = null,
        protected ?string $tanggalVerifikasi = null,
        protected ?string $catatanHasilVerifikasi = null,
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
        return 'Lembar Validasi TPG';
    }

    /** @return array<string, string> nama kolom ceklist -> label, URUTAN SAMA dgn surat-tpg-lembar-validasi-isi.blade.php. */
    private function labelKolom(): array
    {
        return [
            'memiliki_sertifikat_pendidik' => 'Memiliki sertifikat pendidik',
            'berstatus_guru_asn' => 'Berstatus Guru ASN',
            'aktif_melaksanakan_tugas_dapodik' => 'Aktif Melaksanakan tugas dan tercatat pada dapodik',
            'memiliki_nuptk_nrg' => 'Memiliki NUPTK & NRG',
            'memenuhi_beban_kerja' => 'Memenuhi beban kerja',
            'mengajar_linier_sertifikat' => 'Mengajar Linier sesuai dengan Sertifikat Pendidik',
            'bukan_pegawai_tetap_instansi_lain' => 'Bukan Pegawai Tetap Instansi lain',
            'berstatus_valid_info_gtk' => 'Berstatus valid dalam Info GTK',
        ];
    }

    public function headings(): array
    {
        $kolom = $this->sekolah === null ? ['Nama Sekolah'] : [];
        $kolom[] = 'Nama PTK';

        return array_merge($kolom, array_values($this->labelKolom()));
    }

    /** @param  array<string, mixed>  $baris */
    public function map($baris): array
    {
        $kolom = $this->sekolah === null ? [$baris['nama_sekolah']] : [];
        $kolom[] = $baris['nama_ptk'];

        foreach (array_keys($this->labelKolom()) as $nama) {
            $kolom[] = $baris[$nama] ? 'Ya' : 'Tidak';
        }

        return $kolom;
    }

    private function jumlahKolom(): int
    {
        return ($this->sekolah === null ? 1 : 0) + 1 + count(LembarValidasiTpgCeklist::KOLOM_CEKLIST);
    }

    private function kolomTerakhir(): string
    {
        return Coordinate::stringFromColumnIndex($this->jumlahKolom());
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->tulisJudul($sheet);
                $this->rapikanTabel($sheet);
                $this->tulisTandaTangan($sheet);
            },
        ];
    }

    private function tulisJudul(Worksheet $sheet): void
    {
        $kolomAkhir = $this->kolomTerakhir();

        $judul1 = 'LEMBAR VALIDASI TPG';
        if ($this->sekolah === null) {
            $judul1 .= ' - REKAP SELURUH SEKOLAH';
        }

        $sheet->setCellValue('A1', $judul1);
        $sheet->setCellValue('A2', 'TRIWULAN '.$this->triwulan.' TAHUN ANGGARAN '.$this->tahun);
        $sheet->setCellValue('A3', $this->tanggalVerifikasi ? 'Tanggal Verifikasi dan Validasi: '.$this->tanggalVerifikasi : '');

        foreach ([1, 2, 3] as $baris) {
            $rentang = 'A'.$baris.':'.$kolomAkhir.$baris;
            $sheet->mergeCells($rentang);
            $sheet->getStyle($rentang)->applyFromArray([
                'font' => ['bold' => true, 'size' => $baris === 1 ? 12 : 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }
    }

    private function barisTerakhirTabel(): int
    {
        return self::BARIS_HEADER_TABEL + max($this->baris->count(), 1);
    }

    private function rapikanTabel(Worksheet $sheet): void
    {
        $kolomAkhir = $this->kolomTerakhir();
        $rentangHeader = 'A'.self::BARIS_HEADER_TABEL.':'.$kolomAkhir.self::BARIS_HEADER_TABEL;

        $sheet->getStyle($rentangHeader)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        if ($this->baris->count() > 0) {
            $sheet->getStyle('A'.self::BARIS_HEADER_TABEL.':'.$kolomAkhir.$this->barisTerakhirTabel())
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        $sheet->freezePane('A'.(self::BARIS_HEADER_TABEL + 1));
        $sheet->setAutoFilter($rentangHeader);
        $sheet->getRowDimension(self::BARIS_HEADER_TABEL)->setRowHeight(30);
    }

    /**
     * Blok Catatan Hasil Verifikasi + tanda tangan 3-kolom (Kasie KPML/
     * Pengawas/Kepala Sekolah), mengikuti desain final blok tanda tangan
     * pada surat-tpg-lembar-validasi-isi.blade.php - dilewati saat rekap
     * gabungan semua sekolah (lihat docblock class ini).
     */
    private function tulisTandaTangan(Worksheet $sheet): void
    {
        if ($this->sekolah === null) {
            return;
        }

        $kolomAkhir = $this->kolomTerakhir();
        $barisCatatanLabel = $this->barisTerakhirTabel() + self::BARIS_KOSONG_SEBELUM_TANDA_TANGAN;
        $barisCatatanIsi = $barisCatatanLabel + 1;
        $barisMengetahui = $barisCatatanIsi + 2;
        $barisJabatan = $barisMengetahui + 1;
        $barisNama = $barisJabatan + 3;
        $barisNip = $barisNama + 1;

        $sheet->setCellValue('A'.$barisCatatanLabel, 'CATATAN HASIL VERIFIKASI :');
        $sheet->getStyle('A'.$barisCatatanLabel)->getFont()->setBold(true);
        $sheet->setCellValue('A'.$barisCatatanIsi, $this->catatanHasilVerifikasi ?: '');
        $sheet->mergeCells('A'.$barisCatatanIsi.':'.$kolomAkhir.$barisCatatanIsi);

        [$tiga1, $tiga2, $tiga3] = $this->rentangTigaKolom();

        $sheet->setCellValue($tiga1[0].$barisMengetahui, 'Mengetahui / Menyetujui,');
        $sheet->setCellValue($tiga2[0].$barisMengetahui, 'VERIFIKATOR :');
        $sheet->mergeCells($tiga1[0].$barisMengetahui.':'.$tiga1[1].$barisMengetahui);
        $sheet->mergeCells($tiga2[0].$barisMengetahui.':'.$tiga3[1].$barisMengetahui);

        $sheet->setCellValue($tiga1[0].$barisJabatan, 'Kepala Seksi Tata Kelola Kelembagaan dan Ketenagaan');
        $sheet->setCellValue($tiga2[0].$barisJabatan, 'Pengawas,');
        $sheet->setCellValue($tiga3[0].$barisJabatan, 'Kepala '.($this->sekolah->nama_sekolah ?? ''));

        $sheet->setCellValue($tiga1[0].$barisNama, $this->sekolah->nama_kasie_kpml ?: '...................................');
        $sheet->setCellValue($tiga1[0].$barisNip, 'NIP. '.($this->sekolah->nip_kasie_kpml ?: '...................................'));
        $sheet->setCellValue($tiga2[0].$barisNama, $this->sekolah->nama_pengawas ?: '...................................');
        $sheet->setCellValue($tiga2[0].$barisNip, 'NIP. '.($this->sekolah->nip_pengawas ?: '...................................'));
        $sheet->setCellValue($tiga3[0].$barisNama, $this->sekolah->nama_kepala_sekolah ?: '...................................');
        $sheet->setCellValue($tiga3[0].$barisNip, 'NIP. '.($this->sekolah->nip_kepala_sekolah ?: '...................................'));

        foreach ([$tiga1, $tiga2, $tiga3] as $rentang) {
            foreach ([$barisJabatan, $barisNama, $barisNip] as $baris) {
                $sheet->mergeCells($rentang[0].$baris.':'.$rentang[1].$baris);
            }
            foreach ([$barisMengetahui, $barisJabatan, $barisNama, $barisNip] as $baris) {
                $sheet->getStyle($rentang[0].$baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }
    }

    /**
     * Membagi kolom tabel jadi 3 bagian rata (utk blok tanda tangan 3
     * kolom) - mengembalikan [kolomAwal, kolomAkhir] tiap bagian.
     *
     * @return array{0: array{0: string, 1: string}, 1: array{0: string, 1: string}, 2: array{0: string, 1: string}}
     */
    private function rentangTigaKolom(): array
    {
        $total = $this->jumlahKolom();
        $lebar = (int) ceil($total / 3);

        $awal1 = 1;
        $awal2 = min(1 + $lebar, $total);
        $awal3 = min(1 + (2 * $lebar), $total);

        $akhir1 = max($awal2 - 1, $awal1);
        $akhir2 = max($awal3 - 1, $awal2);
        $akhir3 = $total;

        return [
            [Coordinate::stringFromColumnIndex($awal1), Coordinate::stringFromColumnIndex($akhir1)],
            [Coordinate::stringFromColumnIndex($awal2), Coordinate::stringFromColumnIndex($akhir2)],
            [Coordinate::stringFromColumnIndex($awal3), Coordinate::stringFromColumnIndex($akhir3)],
        ];
    }
}
