<?php

namespace App\Services;

use setasign\Fpdi\Fpdf\Fpdi;

/**
 * Menggabungkan file PDF Rekening Koran Triwulan 1-4 (sudah diupload
 * per sekolah lewat App\Livewire\PendataanBosp\RekeningKoran\Index)
 * menjadi SATU file PDF multi-halaman - dipakai khusus Superadmin lewat
 * tombol "Gabungkan" (permintaan user 2026-11-14).
 *
 * Memakai setasign/fpdi-fpdf (paket Composer BARU - lihat
 * progress-log.md untuk instruksi `composer require` yang perlu
 * dijalankan user sendiri) - BUKAN barryvdh/laravel-dompdf yang sudah
 * ada di proyek ini, karena dompdf hanya bisa merender HTML menjadi
 * PDF, TIDAK bisa mengimpor/menggabungkan file PDF yang SUDAH ADA apa
 * adanya. FPDI murni mengimpor halaman PDF asli (vektor, teks tetap
 * bisa diseleksi/dicari, ukuran file tidak membengkak) - BUKAN
 * merender ulang jadi gambar, supaya kualitas dokumen resmi tetap
 * terjaga.
 *
 * Mengimpor SEMUA halaman tiap file (bukan cuma halaman pertama) -
 * berjaga-jaga kalau suatu saat ada sekolah yang upload rekening koran
 * lebih dari 1 halaman per Triwulan.
 */
class RekeningKoranMergeService
{
    /**
     * @param  array<int, string>  $pathAbsolutTw1ke4  Path ABSOLUT (bukan path relatif disk) ke file PDF TW1-4, berurutan.
     * @return string  Isi biner PDF hasil gabungan.
     */
    public function gabungkan(array $pathAbsolutTw1ke4): string
    {
        $pdf = new Fpdi;

        foreach ($pathAbsolutTw1ke4 as $path) {
            $jumlahHalaman = $pdf->setSourceFile($path);

            for ($halaman = 1; $halaman <= $jumlahHalaman; $halaman++) {
                $templateId = $pdf->importPage($halaman);
                $ukuran = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($ukuran['orientation'], [$ukuran['width'], $ukuran['height']]);
                $pdf->useTemplate($templateId);
            }
        }

        return $pdf->Output('S');
    }
}
