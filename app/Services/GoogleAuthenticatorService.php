<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
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
     *
     * Dibungkus try/catch (fitur "Samakan Kunci Authenticator" 2026-10-13):
     * kalau $secret datang dari ketikan manual Superadmin (bukan hasil
     * buatSecretBaru() sendiri), bisa saja mengandung karakter di luar
     * Base32 atau kepanjangan/kependekan - paket google2fa melempar
     * exception utk kasus itu, bukan return false biasa. Di jalur normal
     * (aktivasi/login harian, $secret selalu dari server) exception ini
     * tidak akan pernah terjadi, jadi perubahan ini aman utk alur lama.
     */
    public function kodeValid(string $secret, string $kode): bool
    {
        try {
            return (bool) $this->google2fa->verifyKey($secret, $kode);
        } catch (IncompatibleWithGoogleAuthenticatorException|InvalidCharactersException|SecretKeyTooShortException $e) {
            return false;
        }
    }

    /**
     * Bersihkan kunci manual yang ditempel Superadmin (fitur "Samakan Kunci
     * Authenticator" 2026-10-13): huruf besarkan & buang spasi, karena
     * sebagian aplikasi Authenticator / email menampilkan kunci manual
     * dikelompokkan per 4 karakter dengan spasi (mis. "ABCD EFGH ...") yang
     * kalau ditempel apa adanya akan dianggap karakter tidak valid oleh
     * paket google2fa.
     */
    public function rapikanKunciManual(string $kunci): string
    {
        return strtoupper(preg_replace('/\s+/', '', $kunci));
    }
}
