<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penguncian tahun anggaran per sesi login (permintaan user 2026-10-24) -
 * meniru POLA PERSIS App\Http\Middleware\EnsureGoogleAuthenticatorVerified
 * (lihat docblock di sana), tapi SENGAJA didaftarkan SETELAH middleware
 * itu di bootstrap/app.php supaya langkah ini baru muncul SETELAH 2FA
 * selesai diverifikasi - urutan alur lengkap jadi: login -> (2FA kalau
 * perlu) -> pilih tahun (HANYA kalau akun punya >1 pilihan tahun, lihat
 * halaman pilih-tahun.blade.php) -> aplikasi.
 *
 * HANYA berlaku untuk Admin OPS & Admin BOSP (Superadmin TIDAK dibatasi
 * tahun sama sekali - dropdown Tahun di menu Dana BOSP Tahap/Rekap RKAS
 * untuk Superadmin tetap bebas seperti sebelumnya).
 *
 * Session 'tahun_sesi' diisi oleh halaman pilih-tahun.blade.php (atau
 * otomatis langsung ke Tahun Anggaran Aktif kalau akun itu memang belum
 * punya pilihan lain, lihat mount() di halaman tsb) begitu akun berhasil
 * login, dan WAJIB dihapus (session()->forget('tahun_sesi')) setiap kali
 * ada login/pemulihan BARU (lihat 3 titik di login.blade.php, persis
 * sama seperti 'google2fa_terverifikasi' yang juga dihapus di titik yang
 * sama) - supaya pilihan tahun selalu ditanyakan ulang tiap login, tidak
 * "nyangkut" dari sesi browser sebelumnya.
 */
class EnsureTahunSesiDipilih
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
        'pilih-tahun',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! in_array($user->level_akses, [User::LEVEL_ADMIN_OPS, User::LEVEL_ADMIN_BOSP], true)) {
            return $next($request);
        }

        if ($request->routeIs(self::RUTE_DIKECUALIKAN)) {
            return $next($request);
        }

        if (session('tahun_sesi') === null) {
            return redirect()->route('pilih-tahun');
        }

        return $next($request);
    }
}
