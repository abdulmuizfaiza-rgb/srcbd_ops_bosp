<?php

namespace App\Http\Controllers;

use App\Models\ProfilSekolah;
use App\Support\SuratTpgGabunganData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tombol "Cetak" (preview PDF GABUNGAN ketiga surat: Rekomendasi,
 * Penghentian, & Pernyataan sekaligus) - BARU round kedua puluh satu,
 * permintaan user "surat rekomendasi, Surat Penghentian TPG dan Surat
 * Pernyataan di buat dalam 1 halaman supaya lebih irit kertas". Lihat
 * docblock lengkap App\Support\SuratTpgGabunganData &
 * App\Livewire\PendataanOps\Unduhan\Index utk konteks keputusan "1
 * halaman" = 1 dokumen/1 kali proses gabungan (BUKAN benar2 dipepetkan
 * jadi 1 lembar fisik).
 *
 * DIPINDAHKAN round kedua puluh dua (2026-09-24): tombol "Cetak" yang
 * memanggil controller ini SEKARANG ada di menu Unduhan (sebelumnya di
 * menu "Format Surat Rekomendasi & Pembatalan TPG") - permintaan user
 * poin 3 "untuk cetak Gabungan ketiga surat (irit kertas) di pindah ke
 * menu Unduhan". Controller ini sendiri (route/logika/otorisasi) TIDAK
 * berubah, hanya SUMBER pemanggilannya yang pindah tempat.
 *
 * Controller TERPISAH dari SuratTpgCetakController (bukan diperluas di
 * sana) krn TIDAK menerima parameter `jenis` tunggal - selalu mengambil
 * & menggabungkan ketiga jenis surat sekaligus. Pola stream/otorisasi
 * SAMA persis dgn SuratTpgCetakController.
 */
class SuratTpgCetakSemuaController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tahun = (int) $request->query('tahun');
        $triwulan = (int) $request->query('triwulan');
        $profilSekolahId = (int) $request->query('profil_sekolah_id');

        abort_unless($tahun > 0 && $triwulan >= 1 && $triwulan <= 4 && $profilSekolahId > 0, 404);

        $user = auth()->user();
        abort_unless(
            $user->isSuperadmin() || $user->profil_sekolah_id === $profilSekolahId,
            403
        );

        $sekolah = ProfilSekolah::findOrFail($profilSekolahId);

        $kertas = $request->query('kertas') === 'f4' ? 'f4' : 'a4';
        $margin = [
            'kiri' => $this->batasMargin($request->query('margin_kiri', 2.5)),
            'kanan' => $this->batasMargin($request->query('margin_kanan', 2.5)),
            'atas' => $this->batasMargin($request->query('margin_atas', 3)),
            'bawah' => $this->batasMargin($request->query('margin_bawah', 2.5)),
        ];

        $data = SuratTpgGabunganData::ambil($sekolah->id, $tahun, $triwulan);
        $data['margin'] = $margin;

        $ukuranKertas = $kertas === 'f4' ? 'folio' : 'a4';

        $pdf = Pdf::loadView('pdf.surat-tpg-gabungan', $data)->setPaper($ukuranKertas, 'portrait');

        return $pdf->stream('preview-surat-tpg-gabungan.pdf');
    }

    /** Batasi margin ke rentang wajar (0.5cm - 5cm) supaya input aneh tidak merusak PDF. */
    private function batasMargin(mixed $nilai): float
    {
        return max(0.5, min(5, (float) $nilai));
    }
}
