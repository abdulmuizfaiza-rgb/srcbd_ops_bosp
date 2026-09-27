<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keamanan 2FA Google Authenticator (permintaan user 2026-09-27).
 *
 * Didaftarkan PALING PERTAMA di bootstrap/app.php (sebelum ForcePasswordChange
 * & EnsureOnboardingComplete) supaya Superadmin/Admin OPS/Admin BOSP yang
 * SUDAH lolos username+password TIDAK BISA membuka halaman aplikasi apa pun
 * sebelum menyelesaikan verifikasi kode 6 digit - TIDAK ADA pengecualian
 * role, Superadmin ikut wajib (dikonfirmasi user lewat AskUserQuestion).
 *
 * Alur:
 * - Pengguna belum PERNAH mengaktifkan Authenticator (kolom
 *   google2fa_aktif_at masih null) -> diarahkan ke halaman aktivasi
 *   (scan barcode + verifikasi kode pertama kali).
 * - Pengguna SUDAH aktif tapi BELUM verifikasi kode pada sesi login SAAT
 *   INI (flag session 'google2fa_terverifikasi' belum ada) -> diarahkan
 *   ke halaman verifikasi kode. Flag ini SENGAJA dihapus setiap kali ada
 *   login baru (lihat login.blade.php) supaya 2FA selalu diminta ulang
 *   tiap login, bukan cuma sekali seumur hidup akun.
 *
 * Pola middleware ini meniru ForcePasswordChange/EnsureOnboardingComplete
 * yang sudah ada di aplikasi ini - daftar rute pengecualian WAJIB memuat
 * rute Livewire bersama (default.livewire.update, dst.) supaya form di
 * halaman 2FA itu sendiri tidak ikut ke-redirect terus-menerus.
 */
class EnsureGoogleAuthenticatorVerified
{
    /**
     * @var array<int, string>
     */
    private const RUTE_DIKECUALIKAN = [
        'logout',
        'default.livewire.update',
        'livewire.upload-file',
        'livewire.preview-file',
        'authenticator.aktivasi',
        'authenticator.verifikasi',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($request->routeIs(self::RUTE_DIKECUALIKAN)) {
            return $next($request);
        }

        if (! $user->authenticatorAktif()) {
            return redirect()->route('authenticator.aktivasi');
        }

        if (! session('google2fa_terverifikasi')) {
            return redirect()->route('authenticator.verifikasi');
        }

        return $next($request);
    }
}
