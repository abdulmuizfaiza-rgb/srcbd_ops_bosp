<?php

namespace App\Http\Controllers;

use App\Models\GoogleDriveSetting;
use App\Services\GoogleDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Alur "Hubungkan Google Drive" (Login dengan Google / OAuth 2.0) - lihat
 * docblock lengkap App\Services\GoogleDriveService untuk latar belakang
 * kenapa desain Service Account semula (2026-10-14) diganti.
 *
 * HANYA Superadmin, konsisten dengan seluruh menu Backup & Restore (Gate
 * 'akses-backup' - lihat middleware group di routes/web.php & App\
 * Providers\AppServiceProvider) - dicek ULANG eksplisit di tiap method
 * (abort_unless) sebagai lapisan kedua, sama seperti pola yang sudah
 * dipakai App\Livewire\Backup\Index.
 */
class GoogleDriveOAuthController extends Controller
{
    /** Tombol "Hubungkan Google Drive" - redirect Superadmin ke halaman login/izin Google. */
    public function hubungkan(Request $request, GoogleDriveService $driveService): RedirectResponse
    {
        abort_unless($request->user()->isSuperadmin(), 403);

        if (! $driveService->terkonfigurasi()) {
            return redirect()->route('backup.index')
                ->with('errorBackup', 'GOOGLE_DRIVE_OAUTH_CLIENT_ID / GOOGLE_DRIVE_OAUTH_CLIENT_SECRET belum diisi di file .env server ini - lihat panduan update Google Drive.');
        }

        // $state acak disimpan di sesi & dicocokkan lagi di callback() -
        // mencegah CSRF (pihak lain memaksa akun Google-nya SENDIRI
        // ter-link ke aplikasi ini lewat link callback yang dikirim ke
        // korban).
        $state = Str::random(40);
        $request->session()->put('google_drive_oauth_state', $state);

        $url = $driveService->urlOtorisasi(route('google-drive.callback'), $state);

        return redirect()->away($url);
    }

    /** Google mengarahkan balik ke sini setelah Superadmin login & menyetujui (atau membatalkan) izin. */
    public function callback(Request $request, GoogleDriveService $driveService): RedirectResponse
    {
        abort_unless($request->user()->isSuperadmin(), 403);

        $stateTersimpan = $request->session()->pull('google_drive_oauth_state');

        if (empty($stateTersimpan) || $request->query('state') !== $stateTersimpan) {
            return redirect()->route('backup.index')
                ->with('errorBackup', 'Sesi login Google tidak valid/kedaluwarsa - silakan klik "Hubungkan Google Drive" lagi.');
        }

        if ($request->query('error')) {
            return redirect()->route('backup.index')
                ->with('errorBackup', 'Login Google dibatalkan: '.$request->query('error'));
        }

        $code = $request->query('code');

        if (empty($code)) {
            return redirect()->route('backup.index')
                ->with('errorBackup', 'Google tidak mengirim kode otorisasi - silakan coba lagi.');
        }

        try {
            $hasil = $driveService->tukarKodeOtorisasi($code, route('google-drive.callback'));

            GoogleDriveSetting::current()->update([
                'refresh_token' => $hasil['refresh_token'],
                'email_terhubung' => $hasil['email'],
                'terhubung_pada' => now(),
            ]);

            return redirect()->route('backup.index')
                ->with('status', 'Berhasil terhubung ke Google Drive'.($hasil['email'] ? ' sebagai '.$hasil['email'] : '').'.');
        } catch (RuntimeException $e) {
            report($e);

            return redirect()->route('backup.index')
                ->with('errorBackup', 'Gagal menghubungkan Google Drive: '.$e->getMessage());
        }
    }
}
