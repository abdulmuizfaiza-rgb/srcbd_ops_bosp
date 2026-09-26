<?php

namespace App\Livewire\Actions;

use App\Models\LoginHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke(): void
    {
        // Permintaan user (2026-09-26, menu Pengguna > tab Riwayat Login):
        // tandai waktu akhir login HANYA lewat jalur logout eksplisit ini
        // (bukan session timeout otomatis) - cari baris riwayat login
        // TERBARU milik user ini yang logout_at-nya masih kosong (sesi yang
        // sedang berjalan), lalu isi logout_at-nya sekarang. Diambil SEBELUM
        // Auth::guard('web')->logout() supaya auth()->id() masih tersedia.
        $user = Auth::guard('web')->user();

        if ($user) {
            LoginHistory::where('user_id', $user->id)
                ->whereNull('logout_at')
                ->latest('login_at')
                ->first()
                ?->update(['logout_at' => now()]);
        }

        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();
    }
}
