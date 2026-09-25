<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memaksa pengguna yang baru login lewat "Pemulihan Akun" (password default)
 * untuk mengganti password-nya terlebih dahulu sebelum bisa membuka menu lain.
 */
class ForcePasswordChange
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // "default.livewire.update"/"livewire.upload-file"/"livewire.preview-file"
        // WAJIB selalu dikecualikan: submit form "Ganti Password Wajib" itu
        // sendiri terjadi lewat 1 endpoint ajax Livewire yang sama ini (BUKAN
        // lewat route "ganti-password-wajib" langsung) - kalau ikut
        // di-redirect, method simpan() di komponen itu TIDAK PERNAH sempat
        // dijalankan sama sekali (request-nya keburu dibelokkan duluan oleh
        // middleware ini, karena "must_change_password" memang masih true
        // SEBELUM simpan() sempat mengubahnya), sehingga tombol "Simpan
        // Password Baru" terlihat seperti tidak berfungsi meski sudah diisi
        // dengan benar. Pola pengecualian yang sama persis sudah dipakai di
        // EnsureOnboardingComplete untuk alasan yang sama.
        if (
            $user
            && $user->must_change_password
            && ! $request->routeIs(
                'ganti-password-wajib', 'logout',
                'default.livewire.update', 'livewire.upload-file', 'livewire.preview-file'
            )
        ) {
            return redirect()->route('ganti-password-wajib');
        }

        return $next($request);
    }
}
