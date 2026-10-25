<?php

namespace App\Exports\Lpj\Concerns;

use App\Models\ProfilSekolah;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Trait bersama untuk sheet-sheet pada file gabungan "Unduhan LPJ BOSP"
 * (menu baru, permintaan user 2026-10-25) - dipakai lewat kelas anonim
 * yang MEWARISI (extends) kelas Export per-menu yang SUDAH ADA & SUDAH
 * BENAR (PenerimaanHonorPtkExport, LanggananDayaJasaExport, dst - lihat
 * App\Exports\LpjBospExport::bungkus()), supaya grid tabel/data/border
 * yang sudah ada TIDAK disentuh/diubah SAMA SEKALI. Trait ini HANYA
 * menambah:
 *
 * 1. Blok judul baru DI ATAS (Triwulan, Tahun Anggaran, Nama Sekolah,
 *    Kecamatan, Sub Rayon : Cibadak - permintaan user) lewat
 *    insertNewRowBefore(1, 5) - SELURUH isi sheet asli (judul tabel
 *    sendiri, merge cell, border, freeze pane, autofilter) OTOMATIS
 *    ikut turun/bergeser 5 baris, TIDAK PERLU tahu posisi baris apapun
 *    dari kelas aslinya (sengaja dipilih supaya "jangan sampai salah
 *    penempatan" - tidak ada satupun baris/konstanta privat milik kelas
 *    asli yang disentuh). Perilaku insertNewRowBefore() ini SUDAH
 *    diverifikasi lewat skrip uji PhpSpreadsheet nyata 2026-10-25:
 *    freezePane/autoFilter/mergeCells/border semuanya ikut bergeser
 *    otomatis.
 * 2. Page setup kertas Legal/HVS (F4) orientation Landscape, supaya
 *    SELURUH sheet pada file gabungan konsisten rapi (permintaan user).
 *
 * PENTING: trait ini TIDAK PERNAH dipakai langsung pada kelas Export
 * asli (PenerimaanHonorPtkExport dkk) - kelas aslinya SAMA SEKALI
 * TIDAK DIUBAH, supaya tombol unduh/export yang sudah ada di menu
 * masing-masing (1 sheet, bukan LPJ gabungan) tetap berjalan persis
 * seperti sebelumnya.
 */
trait TerapkanJudulLpj
{
    /**
     * Dipanggil dari dalam registerEvents() milik kelas anonim pembungkus
     * (lihat App\Exports\LpjBospExport::bungkus()), SETELAH event
     * AfterSheet kelas asli selesai dijalankan - supaya seluruh isi
     * sheet asli sudah lengkap sebelum baris judul LPJ disisipkan di
     * paling atas.
     */
    protected function tambahkanJudulDanLayoutLpj(
        Worksheet $sheet,
        string $judulSheet,
        int $triwulan,
        int $tahun,
        ?ProfilSekolah $sekolah,
        string $kolomTerakhir,
        /**
         * BARU (perbaikan tata letak Formulir BOS K7b/K7c, permintaan
         * user 2026-10-25): default true = perilaku SAMA PERSIS seperti
         * sebelumnya (Legal/Landscape, dipakai 17 sheet tabel lebar).
         * false = LEWATI pengaturan halaman di sini, dipakai KHUSUS oleh
         * FormulirBosK7bLpjSheet/FormulirBosK7cLpjSheet karena formulir
         * itu SUDAH diatur Portrait + kertas & margin sesuai menu
         * aslinya sendiri (lihat FormulirBosK7bSheetExport::
         * pengaturanHalaman()) - jangan ditimpa jadi Landscape/Legal.
         */
        bool $aturPageSetup = true,
    ): void {
        $jumlahBarisJudul = 5;
        $sheet->insertNewRowBefore(1, $jumlahBarisJudul);

        $rentang = static fn (int $baris): string => 'A'.$baris.':'.$kolomTerakhir.$baris;

        $sheet->setCellValue('A1', $judulSheet);
        $sheet->setCellValue('A2', 'TRIWULAN '.$triwulan.', TAHUN ANGGARAN '.$tahun);
        $sheet->setCellValue('A3', 'Nama Sekolah : '.($sekolah?->nama_sekolah ?? 'Semua Sekolah'));
        $sheet->setCellValue('A4', 'Kecamatan : '.($sekolah?->kecamatan ?? 'Seluruh Kecamatan'));
        $sheet->setCellValue('A5', 'Sub Rayon : Cibadak');

        for ($baris = 1; $baris <= $jumlahBarisJudul; $baris++) {
            $sheet->mergeCells($rentang($baris));
        }

        $sheet->getStyle($rentang(1))->applyFromArray([
            'font' => ['bold' => true, 'size' => 13],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle($rentang(2))->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        foreach ([3, 4, 5] as $baris) {
            $sheet->getStyle($rentang($baris))->applyFromArray([
                'font' => ['bold' => false, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]);
        }

        if ($aturPageSetup) {
            $this->terapkanPageSetupLpj($sheet);
        }
    }

    protected function terapkanPageSetupLpj(Worksheet $sheet): void
    {
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_LEGAL)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $sheet->getPageMargins()
            ->setTop(0.5)
            ->setBottom(0.5)
            ->setLeft(0.4)
            ->setRight(0.4)
            ->setHeader(0.3)
            ->setFooter(0.3);
    }
}
