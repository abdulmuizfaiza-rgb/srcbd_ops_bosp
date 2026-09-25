<?php

namespace App\Http\Middleware;

use App\Models\PendataanBosp;
use App\Models\PendataanOps;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mewajibkan Admin OPS/Admin BOSP melengkapi data secara bertahap sebelum
 * bisa membuka menu lain (tidak berlaku untuk Superadmin):
 *
 * Tahap 1 - Profil Sekolah: NPSN/Nama Sekolah/Status/Kecamatan sudah
 * diisi Superadmin, sisanya (Kepala Sekolah, Pengawas, Bendahara, Alamat)
 * WAJIB dilengkapi/diupdate dulu oleh Admin OPS/Admin BOSP. Selama belum
 * lengkap, semua route selain menu Profil Sekolah dialihkan ke sana.
 *
 * Tahap 2 - Identitas OPS/Identitas BOSP: setelah Profil Sekolah lengkap,
 * Admin OPS wajib mengisi Identitas OPS (dan Admin BOSP wajib mengisi
 * Identitas Admin BOSP) dulu sebelum menu lain aktif.
 */
class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isSuperadmin()) {
            return $next($request);
        }

        // Route yang selalu boleh diakses supaya tidak terjadi redirect
        // loop dan supaya akun masih bisa ganti password/logout/mengatur
        // akunnya sendiri selama proses onboarding berlangsung.
        //
        // "default.livewire.update"/"livewire.upload-file"/
        // "livewire.preview-file" WAJIB selalu dikecualikan: semua
        // interaksi Livewire (klik tombol, isi form, dsb) di halaman
        // manapun selalu lewat 1 endpoint ajax yang sama ini - kalau ikut
        // di-redirect, SEMUA tombol/form Livewire (termasuk tombol Simpan
        // di menu Profil Sekolah sendiri) akan gagal berfungsi selama
        // onboarding belum selesai. Ini aman karena halaman awal (GET)
        // tempat komponen Livewire itu dimuat tetap tergate normal di
        // bawah - request ajax ini hanya bisa terjadi kalau komponennya
        // sudah berhasil dimuat sebelumnya.
        if ($request->routeIs(
            'ganti-password-wajib', 'logout', 'profile', 'password.confirm', 'profil-sekolah.*',
            'default.livewire.update', 'livewire.upload-file', 'livewire.preview-file'
        )) {
            return $next($request);
        }

        $sekolah = $user->profilSekolah;

        if (! $sekolah || ! $sekolah->isLengkap()) {
            return redirect()->route('profil-sekolah.index');
        }

        if ($user->isAdminOps() && ! $request->routeIs('pendataan-ops.index')) {
            $sudahIsiIdentitasOps = PendataanOps::where('profil_sekolah_id', $sekolah->id)->exists();

            if (! $sudahIsiIdentitasOps) {
                return redirect()->route('pendataan-ops.index');
            }
        }

        if ($user->isAdminBosp() && ! $request->routeIs('pendataan-bosp.index')) {
            $sudahIsiIdentitasBosp = PendataanBosp::where('profil_sekolah_id', $sekolah->id)->exists();

            if (! $sudahIsiIdentitasBosp) {
                return redirect()->route('pendataan-bosp.index');
            }
        }

        return $next($request);
    }
}
