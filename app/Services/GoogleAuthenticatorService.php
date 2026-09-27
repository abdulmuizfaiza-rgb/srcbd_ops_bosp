<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

/**
 * Membungkus paket pragmarx/google2fa (hitung & verifikasi kode TOTP 6 digit)
 * dan bacon/bacon-qr-code (gambar barcode berbentuk SVG murni PHP - TANPA
 * ekstensi GD/Imagick) untuk fitur keamanan Google Authenticator (permintaan
 * user 2026-09-27).
 *
 * SENGAJA pilih backend SVG (bukan PNG lewat GD) supaya konsisten dengan
 * sejarah aplikasi ini yang beberapa kali bermasalah dengan dependensi
 * native/khusus-OS (mis. kasus pg_dump.exe di Windows pada fitur Backup) -
 * SVG murni teks, jalan sama persis di Windows (lokal) maupun Linux (VPS)
 * tanpa ekstensi PHP tambahan apa pun.
 */
class GoogleAuthenticatorService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Buat kunci rahasia TOTP baru (belum dikaitkan ke user mana pun -
     * pemanggil yang bertanggung jawab menyimpannya ke kolom
     * users.google2fa_secret).
     */
    public function buatSecretBaru(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Gambar barcode (QR code) berbentuk teks SVG, siap ditampilkan langsung
     * di halaman Blade (inline lewat {!! !!}) maupun di-embed ke email
     * sebagai data URI base64.
     */
    public function svgBarcode(User $user, string $secret): string
    {
        $urlOtpAuth = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email ?: $user->username,
            $secret,
        );

        $renderer = new ImageRenderer(
            new RendererStyle(240),
            new SvgImageBackEnd(),
        );

        return (new Writer($renderer))->writeString($urlOtpAuth);
    }

    /**
     * Versi data URI dari svgBarcode() - dipakai di email (Mailable markdown
     * tidak bisa menyisipkan tag <svg> mentah dengan aman, jadi dibungkus
     * jadi <img src="data:image/svg+xml;base64,...">).
     */
    public function svgBarcodeDataUri(User $user, string $secret): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svgBarcode($user, $secret));
    }

    /**
     * Periksa apakah kode 6 digit yang diketik pengguna cocok dengan secret-nya.
     * $window = 1 memberi toleransi ±30 detik pergeseran jam HP, wajar untuk
     * TOTP standar (default paket ini).
     */
    public function kodeValid(string $secret, string $kode): bool
    {
        return $this->google2fa->verifyKey($secret, $kode);
    }
}
