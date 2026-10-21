<?php

namespace App\Exports;

use App\Models\ProfilSekolah;
use App\Models\RekapCairTpgBulan;
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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Data Cair-Belum TPG" pada Excel menu Unduhan (Pendataan OPS >
 * Unduhan, BARU 2026-10-16, permintaan user: tambah sheet ini sbg SHEET
 * TERAKHIR, setelah Lampiran 2a/2b/2c - lihat App\Exports\
 * UnduhanLampiranExport::sheets()). Judul sheet "Data Cair-Belum TPG"
 * (pakai "-" bukan "/") krn Excel tidak mengizinkan karakter "/" pada
 * nama sheet.
 *
 * Polanya mengikuti App\Exports\LembarValidasiTpgExport (lihat
 * docblock class tsb utk penjelasan lengkap pola judul/tabel/tanda
 * tangan 3-kolom & kenapa rekap gabungan semua sekolah melewati
 * Catatan Hasil Verifikasi + tanda tangan).
 *
 * SEJAK 2026-10-21 (permintaan user: "tambahkan kolom No" pada hasil
 * unduhan Excel & PDF Lembar Validasi dan Data Cair-Belum TPG - PDF
 * ("Format Surat Rekomendasi & Pembatalan TPG", lihat resources/views/
 * pdf/partials/surat-tpg-rekap-cair-isi.blade.php) SUDAH punya kolom
 * No sejak awal, jadi perubahan round ini KHUSUS Excel): kolom BARU
 * "No" ditambahkan sebagai kolom PERTAMA - kolom "Nama Sekolah"
 * (opsional)/"Nama PTK"/"NIP PTK"/12 kolom bulan semuanya bergeser 1
 * kolom ke kanan. Nomor urut mengikuti urutan baris pada koleksi yang
 * diterima (SupportCollection asli dari RekapCairTpgUnduhanData::ambil()).
 */
class RekapCairTpgExport implements FromCollection, ShouldAutoSize, WithCustomStartCell, WithCustomValueBinder, WithEvents, WithHeadings, WithMapping, WithTitle
{
    private const BARIS_HEADER_TABEL = 5;

    private const BARIS_KOSONG_SEBELUM_TANDA_TANGAN = 2;

    private int $nomorBerikutnya = 1;

    /**
     * @param  SupportCollection<int, array<string, mixed>>  $baris  dari App\Support\RekapCairTpgUnduhanData::ambil()
     */
    public function __construct(
        protected SupportCollection $baris,
        protected int $triwulan,
        protected int $tahun,
        protected ?ProfilSekolah $sekolah = null,
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
        return 'Data Cair-Belum TPG';
    }

    /** @return array<string, string> nama kolom bulan -> label, URUTAN SAMA dgn surat-tpg-rekap-cair-isi.blade.php. */
    private function labelBulan(): array
    {
        return [
            'bulan_januari' => 'Januari',
            'bulan_pebruari' => 'Pebruari',
            'bulan_maret' => 'Maret',
            'bulan_april' => 'April',
            'bulan_mei' => 'Mei',
            'bulan_juni' => 'Juni',
            'bulan_juli' => 'Juli',
            'bulan_agustus' => 'Agustus',
            'bulan_september' => 'September',
            'bulan_oktober' => 'Oktober',
            'bulan_nopember' => 'Nopember',
            'bulan_desember' => 'Desember',
        ];
    }

    public function headings(): array
    {
        $kolom = ['No'];
        if ($this->sekolah === null) {
            $kolom[] = 'Nama Sekolah';
        }
        $kolom[] = 'Nama PTK';
        $kolom[] = 'NIP PTK';

        return array_merge($kolom, array_map(fn ($label) => 'Bulan '.$label, array_values($this->labelBulan())));
    }

    /** @param  array<string, mixed>  $baris */
    public function map($baris): array
    {
        $kolom = [$this->nomorBerikutnya++];
        if ($this->sekolah === null) {
            $kolom[] = $baris['nama_sekolah'];
        }
        $kolom[] = $baris['nama_ptk'];
        $kolom[] = $baris['nip'] ?: '-';

        $opsiStatus = RekapCairTpgBulan::STATUS_OPTIONS;
        foreach (array_keys($this->labelBulan()) as $nama) {
            $kolom[] = $baris[$nama] ? ($opsiStatus[$baris[$nama]] ?? '') : '';
        }

        return $kolom;
    }

    /**
     * Kolom "NIP PTK" dipaksa bertipe TEKS (bukan Angka) - kalau dibiarkan
     * memakai tipe Angka bawaan Excel, NIP 18 digit bisa tampil beda
     * (notasi ilmiah/presisi) & angka berawalan "0" bisa TERPOTONG - harus
     * sama persis dengan yang tampil
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

    /** @return string[] kolom "NIP PTK" - posisinya bergeser kalau ada kolom "Nama Sekolah" (rekap gabungan Superadmin) & kolom "No", lihat headings()/map(). */
    private function kolomDipaksaTeks(): array
    {
        return [Coordinate::stringFromColumnIndex(1 + ($this->sekolah === null ? 1 : 0) + 2)];
    }

    private function jumlahKolom(): int
    {
        return 1 + ($this->sekolah === null ? 1 : 0) + 2 + count($this->labelBulan());
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

        $judul1 = 'REKAPITULASI DATA PTK YANG SUDAH / BELUM CAIR TPG';
        if ($this->sekolah === null) {
            $judul1 .= ' - REKAP SELURUH SEKOLAH';
        }

        $sheet->setCellValue('A1', $judul1);
        $sheet->setCellValue('A2', 'TRIWULAN '.$this->triwulan.' TAHUN ANGGARAN '.$this->tahun);

        foreach ([1, 2] as $baris) {
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
     * Pengawas/Kepala Sekolah) - "sama dengan konsep lembar validasi"
     * (permintaan user verbatim saat fitur tab ini dibuat), dilewati
     * saat rekap gabungan semua sekolah (lihat docblock class ini).
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
