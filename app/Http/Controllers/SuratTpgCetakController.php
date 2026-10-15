<?php

namespace App\Http\Controllers;

use App\Models\AksesDataLog;
use App\Models\Lampiran2a;
use App\Models\LembarValidasiTpg;
use App\Models\LembarValidasiTpgCeklist;
use App\Models\ProfilSekolah;
use App\Models\SuratTpg;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tombol "Cetak" (preview PDF) pada menu "Format Surat Rekomendasi &
 * Pembatalan TPG" (Pendataan OPS, permintaan user 2026-09-24, round
 * kedelapan belas) - meniru pola FormulirBosK7CetakController persis:
 * single __invoke, men-STREAM PDF (Content-Disposition: inline) supaya
 * viewer PDF bawaan browser tampil sebagai "preview", dibuka lewat
 * <a target="_blank"> dari App\Livewire\PendataanOps\SuratTpg\Index
 * (BUKAN wire:click, krn Livewire tidak bisa membuka tab baru).
 *
 * Otorisasi sama seperti FormulirBosK7CetakController - Superadmin boleh
 * untuk semua sekolah, user biasa hanya boleh untuk sekolahnya sendiri.
 *
 * Perbaikan 2026-09-24 (round kedua puluh satu): mendukung jenis=pernyataan
 * (tab 3 "Surat Pernyataan" BARU) - lihat App\Livewire\PendataanOps\
 * SuratTpg\Index docblock utk konteks lengkap. Utk preview GABUNGAN
 * ketiga surat sekaligus ("Cetak Semua"), lihat SuratTpgCetakSemuaController
 * (controller TERPISAH, bukan diperluas di sini, krn bentuk query
 * parameter & datanya beda - tidak ada `jenis` tunggal).
 */
class SuratTpgCetakController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $jenis = match ($request->query('jenis')) {
            SuratTpg::JENIS_PENGHENTIAN => SuratTpg::JENIS_PENGHENTIAN,
            SuratTpg::JENIS_PERNYATAAN => SuratTpg::JENIS_PERNYATAAN,
            SuratTpg::JENIS_COVER_SMPN => SuratTpg::JENIS_COVER_SMPN,
            SuratTpg::JENIS_COVER_SMP_S => SuratTpg::JENIS_COVER_SMP_S,
            SuratTpg::JENIS_LEMBAR_VALIDASI => SuratTpg::JENIS_LEMBAR_VALIDASI,
            default => SuratTpg::JENIS_REKOMENDASI,
        };

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

        AksesDataLog::catat(AksesDataLog::JENIS_UNDUH, 'Surat TPG (Cetak)', $jenis.' - '.$sekolah->nama_sekolah);

        $kertas = $request->query('kertas') === 'f4' ? 'f4' : 'a4';
        $margin = [
            'kiri' => $this->batasMargin($request->query('margin_kiri', 2.5)),
            'kanan' => $this->batasMargin($request->query('margin_kanan', 2.5)),
            'atas' => $this->batasMargin($request->query('margin_atas', 3)),
            'bawah' => $this->batasMargin($request->query('margin_bawah', 2.5)),
        ];

        $surat = SuratTpg::query()
            ->where('profil_sekolah_id', $sekolah->id)
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->where('jenis', $jenis)
            ->first();

        $data = [
            'editable' => false,
            'nomorSurat' => $surat->nomor_surat ?? null,
            'tanggalSurat' => $surat->tanggal_surat ?? null,
            'triwulan' => $triwulan,
            'tahun' => $tahun,
            'namaKepsek' => $sekolah->nama_kepala_sekolah,
            'nipKepsek' => $sekolah->nip_kepala_sekolah,
            'namaSekolah' => $sekolah->nama_sekolah,
            'alamatSekolah' => $sekolah->alamat_sekolah,
            'namaPengawas' => $sekolah->nama_pengawas,
            'nipPengawas' => $sekolah->nip_pengawas,
            'tahunPelajaran' => $surat->tahun_pelajaran ?? null,
            'kopSuratSrc' => $sekolah->kopSuratDataUri(),
            // Logo Sekolah & $sekolah (BARU 2026-10-15, tab Cover SMPN/
            // Cover SMP-S - lihat pdf.partials.surat-tpg-cover-isi).
            'logoSekolahSrc' => $sekolah->logoSekolahDataUri(),
            'sekolah' => $sekolah,
            'margin' => $margin,
        ];

        // Data tambahan khusus tab "Lembar Validasi TPG" (BARU
        // 2026-10-16) - $surat di atas TETAP null utk jenis ini (tabel
        // SuratTpg tidak dipakai, datanya di lembar_validasi_tpg /
        // lembar_validasi_tpg_ceklist - lihat SuratTpg::JENIS_LEMBAR_VALIDASI
        // docblock). Logika query SENGAJA diduplikasi dari
        // App\Livewire\PendataanOps\SuratTpg\Index::muatLembarValidasi()
        // (method protected pada komponen Livewire, tidak bisa dipanggil
        // dari controller terpisah ini - sama seperti pola duplikasi
        // data yang sudah ada di controller ini utk jenis2 lainnya).
        if ($jenis === SuratTpg::JENIS_LEMBAR_VALIDASI) {
            $headerValidasi = LembarValidasiTpg::query()
                ->where('profil_sekolah_id', $sekolah->id)
                ->where('tahun', $tahun)
                ->where('triwulan', $triwulan)
                ->first();

            $daftarLampiran = Lampiran2a::query()
                ->where('profil_sekolah_id', $sekolah->id)
                ->where('tahun', $tahun)
                ->where('triwulan', $triwulan)
                ->orderBy('id')
                ->get(['nuptk', 'nama_ptk']);

            $ceklistTersimpan = LembarValidasiTpgCeklist::query()
                ->where('profil_sekolah_id', $sekolah->id)
                ->where('tahun', $tahun)
                ->where('triwulan', $triwulan)
                ->whereIn('nuptk', $daftarLampiran->pluck('nuptk'))
                ->get()
                ->keyBy('nuptk');

            $daftarPtkValidasi = [];
            foreach ($daftarLampiran as $ptk) {
                $ceklist = $ceklistTersimpan->get($ptk->nuptk);
                $baris = ['nama_ptk' => $ptk->nama_ptk];
                foreach (LembarValidasiTpgCeklist::KOLOM_CEKLIST as $kolom) {
                    $baris[$kolom] = (bool) ($ceklist->{$kolom} ?? false);
                }
                $daftarPtkValidasi[$ptk->nuptk] = $baris;
            }

            $data['tanggalVerifikasi'] = $headerValidasi?->tanggal_verifikasi;
            $data['catatanHasilVerifikasi'] = $headerValidasi->catatan_hasil_verifikasi ?? null;
            $data['daftarPtkValidasi'] = $daftarPtkValidasi;
            $data['namaKasieKpml'] = $sekolah->nama_kasie_kpml;
            $data['nipKasieKpml'] = $sekolah->nip_kasie_kpml;
            $data['labelTpgStatus'] = $sekolah->status === ProfilSekolah::STATUS_NEGERI ? 'TPGASN' : 'TPGNON-ASN';
        }

        $view = match ($jenis) {
            SuratTpg::JENIS_PENGHENTIAN => 'pdf.surat-tpg-penghentian',
            SuratTpg::JENIS_PERNYATAAN => 'pdf.surat-tpg-pernyataan',
            SuratTpg::JENIS_COVER_SMPN => 'pdf.surat-tpg-cover-smpn',
            SuratTpg::JENIS_COVER_SMP_S => 'pdf.surat-tpg-cover-smp-s',
            SuratTpg::JENIS_LEMBAR_VALIDASI => 'pdf.surat-tpg-lembar-validasi',
            default => 'pdf.surat-tpg-rekomendasi',
        };
        $ukuranKertas = $kertas === 'f4' ? 'folio' : 'a4';

        $pdf = Pdf::loadView($view, $data)->setPaper($ukuranKertas, 'portrait');

        return $pdf->stream('preview-surat-tpg-'.$jenis.'.pdf');
    }

    /** Batasi margin ke rentang wajar (0.5cm - 5cm) supaya input aneh tidak merusak PDF. */
    private function batasMargin(mixed $nilai): float
    {
        return max(0.5, min(5, (float) $nilai));
    }
}
