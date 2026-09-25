<?php

namespace App\Http\Controllers;

use App\Models\FormulirBosK7;
use App\Models\ProfilSekolah;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tombol "Cetak" pada menu Formulir BOS K7b & K7c (permintaan user
 * 2026-09-23, round ketiga: "tombol cetak dengan preview terlebih
 * dahulu"). Route TERPISAH dari tombol "PDF" yang sudah ada (jawaban
 * AskUserQuestion: "Tombol baru, terpisah") - tombol PDF tetap memaksa
 * download seperti sebelumnya, route ini men-STREAM PDF langsung ke
 * browser (Content-Disposition: inline) supaya viewer PDF bawaan
 * browser tampil sebagai "preview", lalu user mencetak lewat tombol
 * print viewer PDF itu sendiri.
 *
 * Dibuka lewat <a target="_blank"> dari komponen Livewire (BUKAN
 * wire:click), karena Livewire tidak bisa langsung membuka tab baru -
 * lihat Index::urlCetak() untuk cara membangun URL-nya (selalu
 * membawa jenis kertas & margin yang sedang dipilih user di formulir).
 *
 * Otorisasi mengikuti pola yang sama seperti
 * PendataanOpsFileController::show() - Superadmin boleh untuk semua
 * sekolah, user biasa hanya boleh untuk sekolahnya sendiri.
 */
class FormulirBosK7CetakController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $tab = $request->query('tab') === 'k7c' ? 'k7c' : 'k7b';
        $tahun = (int) $request->query('tahun');
        $bulan = (int) $request->query('bulan');
        $profilSekolahId = (int) $request->query('profil_sekolah_id');

        abort_unless($tahun > 0 && $bulan >= 1 && $bulan <= 12 && $profilSekolahId > 0, 404);

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

        $formulir = FormulirBosK7::query()
            ->where('profil_sekolah_id', $sekolah->id)
            ->where('tahun', $tahun)
            ->where('bulan', $bulan)
            ->first();

        $rincian = $formulir ? $formulir->toArray() : [];

        $tanggalPenutupan = FormulirBosK7::tanggalPenutupanKas($tahun, $bulan);
        $tanggalPenutupanLalu = FormulirBosK7::tanggalPenutupanKasBulanLalu($tahun, $bulan);

        $data = [
            'sekolah' => $sekolah,
            'formulir' => $formulir,
            'tanggalPenutupan' => $tanggalPenutupan,
            'tanggalPenutupanLalu' => $tanggalPenutupanLalu,
            'subJumlahKertas' => FormulirBosK7::hitungSubJumlahUangKertas($rincian),
            'subJumlahLogam' => FormulirBosK7::hitungSubJumlahUangLogam($rincian),
            'saldoKasTunai' => FormulirBosK7::hitungSaldoKasTunai($rincian),
            'saldoBku' => FormulirBosK7::hitungSaldoBku($rincian),
            'jumlahB' => FormulirBosK7::hitungJumlahB($rincian),
            'perbedaan' => FormulirBosK7::hitungPerbedaan($rincian),
            'penjelasanPerbedaan' => $formulir->penjelasan_perbedaan ?? '',
            'noSkKepalaSekolah' => $formulir->no_sk_kepala_sekolah ?? '',
            'tanggalSkKepalaSekolah' => $formulir->tanggal_sk_kepala_sekolah ?? null,
            'noSkBendahara' => $formulir->no_sk_bendahara ?? '',
            'tanggalSkBendahara' => $formulir->tanggal_sk_bendahara ?? null,
            'narasiTanggalK7c' => FormulirBosK7::terbilangTanggalNarasi($tanggalPenutupan),
            'tahun' => $tahun,
            'bulan' => $bulan,
            'labelBulan' => FormulirBosK7::BULAN_OPTIONS[$bulan] ?? '',
            'margin' => $margin,
        ];

        $view = $tab === 'k7c' ? 'pdf.formulir-bos-k7c' : 'pdf.formulir-bos-k7b';
        $ukuranKertas = $kertas === 'f4' ? 'folio' : 'a4';

        $pdf = Pdf::loadView($view, $data)->setPaper($ukuranKertas, 'portrait');

        return $pdf->stream('preview-formulir-bos-'.$tab.'.pdf');
    }

    /** Batasi margin ke rentang wajar (0.5cm - 5cm) supaya input aneh tidak merusak PDF. */
    private function batasMargin(mixed $nilai): float
    {
        return max(0.5, min(5, (float) $nilai));
    }
}
