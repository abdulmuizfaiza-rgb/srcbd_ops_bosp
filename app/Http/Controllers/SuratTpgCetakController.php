<?php

namespace App\Http\Controllers;

use App\Models\AksesDataLog;
use App\Models\DataPtk;
use App\Models\Lampiran2a;
use App\Models\LembarValidasiTpg;
use App\Models\LembarValidasiTpgCeklist;
use App\Models\ProfilSekolah;
use App\Models\RekapCairTpg;
use App\Models\RekapCairTpgBulan;
use App\Models\SuratTpg;
use App\Services\TandaTanganDigitalService;
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
 *
 * Fase 2 (2026-11-14): query parameter BARU `mode_ttd` (pola SAMA seperti
 * `kertas`/`margin_*` yang sudah ada) - 'tanpa' (default) | 'gambar' |
 * 'qrcode', dipilih user lewat dropdown sebelum link "Cetak"/"Unduh PDF"
 * dibuka (lihat App\Livewire\PendataanOps\SuratTpg\Index). HANYA berlaku
 * utk jenis surat yang memang punya tanda tangan Kepala Sekolah (lihat
 * JENIS_ADA_TTD_KEPSEK di bawah) - dikonfirmasi lewat pembacaan tiap
 * partial PDF-nya satu per satu: JENIS_PERNYATAAN TIDAK termasuk karena
 * penandatangannya Pengawas, BUKAN Kepala Sekolah (lihat docblock
 * surat-tpg-pernyataan-isi.blade.php). Mode 'qrcode' mencatat baris baru
 * ke tabel verifikasi_dokumen_qr (App\Services\TandaTanganDigitalService)
 * supaya QR Code di PDF-nya bisa diverifikasi publik di
 * /verifikasi-dokumen/{uuid}.
 */
class SuratTpgCetakController extends Controller
{
    /** Jenis Surat TPG yang memang punya tanda tangan Kepala Sekolah (lihat docblock kelas). */
    private const JENIS_ADA_TTD_KEPSEK = [
        SuratTpg::JENIS_REKOMENDASI,
        SuratTpg::JENIS_PENGHENTIAN,
        SuratTpg::JENIS_LEMBAR_VALIDASI,
        SuratTpg::JENIS_REKAP_CAIR_TPG,
    ];

    /** Label dokumen (utk dicatat di verifikasi_dokumen_qr saat mode QR Code). */
    private const LABEL_DOKUMEN = [
        SuratTpg::JENIS_REKOMENDASI => 'Surat Rekomendasi TPG',
        SuratTpg::JENIS_PENGHENTIAN => 'Surat Penghentian TPG',
        SuratTpg::JENIS_LEMBAR_VALIDASI => 'Lembar Validasi TPG',
        SuratTpg::JENIS_REKAP_CAIR_TPG => 'Rekap Cair TPG',
    ];

    public function __invoke(Request $request): Response
    {
        $jenis = match ($request->query('jenis')) {
            SuratTpg::JENIS_PENGHENTIAN => SuratTpg::JENIS_PENGHENTIAN,
            SuratTpg::JENIS_PERNYATAAN => SuratTpg::JENIS_PERNYATAAN,
            SuratTpg::JENIS_COVER_SMPN => SuratTpg::JENIS_COVER_SMPN,
            SuratTpg::JENIS_COVER_SMP_S => SuratTpg::JENIS_COVER_SMP_S,
            SuratTpg::JENIS_LEMBAR_VALIDASI => SuratTpg::JENIS_LEMBAR_VALIDASI,
            SuratTpg::JENIS_REKAP_CAIR_TPG => SuratTpg::JENIS_REKAP_CAIR_TPG,
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

        // Fase 2 (2026-11-14): pilihan mode tanda tangan Kepala Sekolah -
        // lihat docblock kelas. $adaTtdKepsek menentukan apakah jenis surat
        // ini memang punya tanda tangan Kepala Sekolah sama sekali (kalau
        // tidak, mode SELALU dipaksa 'tanpa' walau user iseng mengetik
        // query string mode_ttd secara manual).
        $adaTtdKepsek = in_array($jenis, self::JENIS_ADA_TTD_KEPSEK, true);
        $modeTandaTangan = $adaTtdKepsek && in_array($request->query('mode_ttd'), ['gambar', 'qrcode'], true)
            ? $request->query('mode_ttd')
            : 'tanpa';
        $tandaTanganSrc = $modeTandaTangan === 'gambar' ? $sekolah->tandaTanganKepalaSekolahDataUri() : null;
        $capSekolahSrc = $modeTandaTangan === 'gambar' ? $sekolah->capSekolahDataUri() : null;
        $qrSvg = null;
        if ($modeTandaTangan === 'qrcode') {
            $qrSvg = app(TandaTanganDigitalService::class)
                ->siapkanQrUntukDokumen($sekolah, self::LABEL_DOKUMEN[$jenis] ?? 'Surat TPG', $tahun)['svg'];
        }

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
            // Fase 2 (2026-11-14): lihat blok perhitungan $modeTandaTangan dst di atas.
            'modeTandaTangan' => $modeTandaTangan,
            'tandaTanganSrc' => $tandaTanganSrc,
            'capSekolahSrc' => $capSekolahSrc,
            'qrSvg' => $qrSvg,
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

        // Data tambahan khusus tab "Data Cair/Belum TPG" (BARU
        // 2026-10-16) - $surat di atas TETAP null utk jenis ini (sama
        // spt Lembar Validasi TPG, lihat SuratTpg::JENIS_REKAP_CAIR_TPG
        // docblock). Logika query SENGAJA diduplikasi dari
        // App\Livewire\PendataanOps\SuratTpg\Index::muatRekapCairTpg()
        // (method protected, tidak bisa dipanggil dari controller
        // terpisah ini). Daftar PTK = gabungan Lampiran 2a SEMUA
        // Triwulan (1-4) dalam Tahun aktif (dikonfirmasi AskUserQuestion
        // 2026-10-16), TAPI isian 12 bulan tetap diambil dari Triwulan
        // yang sedang dipilih SAJA.
        if ($jenis === SuratTpg::JENIS_REKAP_CAIR_TPG) {
            $headerCair = RekapCairTpg::query()
                ->where('profil_sekolah_id', $sekolah->id)
                ->where('tahun', $tahun)
                ->where('triwulan', $triwulan)
                ->first();

            $daftarLampiranCair = Lampiran2a::query()
                ->where('profil_sekolah_id', $sekolah->id)
                ->where('tahun', $tahun)
                ->orderBy('triwulan')
                ->orderBy('id')
                ->get(['nuptk', 'nama_ptk']);

            $namaPerNuptk = [];
            foreach ($daftarLampiranCair as $ptk) {
                $namaPerNuptk[$ptk->nuptk] = $ptk->nama_ptk;
            }

            $nipPerNuptk = DataPtk::query()
                ->where('profil_sekolah_id', $sekolah->id)
                ->whereIn('nuptk', array_keys($namaPerNuptk))
                ->whereNotNull('nuptk')
                ->get(['nuptk', 'nip'])
                ->keyBy('nuptk')
                ->map(fn ($ptk) => $ptk->nip);

            $bulanTersimpan = RekapCairTpgBulan::query()
                ->where('profil_sekolah_id', $sekolah->id)
                ->where('tahun', $tahun)
                ->where('triwulan', $triwulan)
                ->whereIn('nuptk', array_keys($namaPerNuptk))
                ->get()
                ->keyBy('nuptk');

            $daftarPtkCairTpg = [];
            foreach ($namaPerNuptk as $nuptk => $namaPtk) {
                $dataBulan = $bulanTersimpan->get($nuptk);
                $baris = ['nama_ptk' => $namaPtk, 'nip' => $nipPerNuptk->get($nuptk)];
                foreach (RekapCairTpgBulan::KOLOM_BULAN as $kolom) {
                    $baris[$kolom] = $dataBulan->{$kolom} ?? null;
                }
                $daftarPtkCairTpg[$nuptk] = $baris;
            }

            $data['catatanCairTpg'] = $headerCair->catatan_hasil_verifikasi ?? null;
            $data['daftarPtkCairTpg'] = $daftarPtkCairTpg;
            $data['namaKasieKpml'] = $sekolah->nama_kasie_kpml;
            $data['nipKasieKpml'] = $sekolah->nip_kasie_kpml;
        }

        $view = match ($jenis) {
            SuratTpg::JENIS_PENGHENTIAN => 'pdf.surat-tpg-penghentian',
            SuratTpg::JENIS_PERNYATAAN => 'pdf.surat-tpg-pernyataan',
            SuratTpg::JENIS_COVER_SMPN => 'pdf.surat-tpg-cover-smpn',
            SuratTpg::JENIS_COVER_SMP_S => 'pdf.surat-tpg-cover-smp-s',
            SuratTpg::JENIS_LEMBAR_VALIDASI => 'pdf.surat-tpg-lembar-validasi',
            SuratTpg::JENIS_REKAP_CAIR_TPG => 'pdf.surat-tpg-rekap-cair',
            default => 'pdf.surat-tpg-rekomendasi',
        };
        $ukuranKertas = $kertas === 'f4' ? 'folio' : 'a4';

        // Lembar Validasi TPG & Data Cair/Belum TPG dicetak LANDSCAPE
        // (permintaan user 2026-10-16, supaya kolom ceklist/bulan lebih
        // lega/rapi) - jenis surat TPG lainnya tetap portrait.
        $orientasiKertas = in_array($jenis, [SuratTpg::JENIS_LEMBAR_VALIDASI, SuratTpg::JENIS_REKAP_CAIR_TPG], true) ? 'landscape' : 'portrait';

        $pdf = Pdf::loadView($view, $data)->setPaper($ukuranKertas, $orientasiKertas);

        return $pdf->stream('preview-surat-tpg-'.$jenis.'.pdf');
    }

    /** Batasi margin ke rentang wajar (0.5cm - 5cm) supaya input aneh tidak merusak PDF. */
    private function batasMargin(mixed $nilai): float
    {
        return max(0.5, min(5, (float) $nilai));
    }
}
