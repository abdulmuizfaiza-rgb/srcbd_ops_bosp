<?php

namespace App\Services;

use App\Models\ProfilSekolah;
use App\Models\VerifikasiDokumenQr;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;

/**
 * Melayani mode "Dengan Tanda Tangan & Cap berupa QR Code" pada fitur
 * Unduh PDF (Fase 2 Tanda Tangan Kepala Sekolah & Cap Sekolah, permintaan
 * user 2026-11-14 - lihat progress-log.md utk konteks lengkap Fase 1 &
 * Fase 2).
 *
 * Generator QR Code SENGAJA pakai bacon/bacon-qr-code backend SVG murni
 * PHP (BUKAN PNG lewat GD/Imagick) - meniru PERSIS pola yang sudah ada di
 * App\Services\GoogleAuthenticatorService::svgBarcode() (fitur Google
 * Authenticator, 2026-09-27) supaya konsisten & tetap aman dari masalah
 * dependensi native/khusus-OS yang pernah dialami aplikasi ini. SVG ini
 * ditempel LANGSUNG (inline <svg>, bukan <img src="data:...">) di
 * template PDF dompdf lewat {!! !!} - dompdf mendukung elemen <svg>
 * inline dengan baik (berbeda dari <img> yg merujuk data URI SVG, yang
 * dukungannya tidak konsisten).
 */
class TandaTanganDigitalService
{
    /**
     * Catat satu baris riwayat baru (satu kali unduhan dokumen mode QR
     * Code) lalu kembalikan ['svg' => <markup SVG QR inline>, 'url' =>
     * <url verifikasi>, 'dokumen' => VerifikasiDokumenQr]. $tahun boleh
     * null untuk dokumen yang tidak terikat Tahun Anggaran tertentu.
     */
    public function siapkanQrUntukDokumen(ProfilSekolah $sekolah, string $jenisDokumen, ?int $tahun = null): array
    {
        $dokumen = VerifikasiDokumenQr::create([
            'uuid' => (string) Str::uuid(),
            'jenis_dokumen' => $jenisDokumen,
            'profil_sekolah_id' => $sekolah->id,
            'nama_sekolah' => $sekolah->nama_sekolah,
            'nama_kepala_sekolah' => $sekolah->nama_kepala_sekolah,
            'tahun' => $tahun,
            'dibuat_oleh' => auth()->id(),
        ]);

        $url = route('verifikasi-dokumen.show', $dokumen->uuid);

        return [
            'svg' => $this->svgQr($url),
            'url' => $url,
            'dokumen' => $dokumen,
        ];
    }

    /** Gambar QR Code mentah (markup SVG) yang meng-encode $isi (biasanya URL verifikasi). */
    public function svgQr(string $isi, int $ukuranPx = 90): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($ukuranPx),
            new SvgImageBackEnd(),
        );

        return (new Writer($renderer))->writeString($isi);
    }
}
