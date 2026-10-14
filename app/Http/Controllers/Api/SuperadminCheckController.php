<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * API BARU (permintaan user 2026-10-14, lanjutan poin "Cek Kecocokan
 * Data"): mengembalikan daftar akun Superadmin di INSTALASI INI (email,
 * username, status disetujui), dipanggil dari sisi Lokal lewat
 * App\Services\SyncClient::cekSuperadmin() untuk dibandingkan dengan
 * daftar Superadmin sisi Lokal sendiri.
 *
 * KENAPA endpoint TERPISAH dari sync/pull (bukan numpang di situ): akun
 * Pengguna (termasuk Superadmin) SENGAJA TIDAK ikut lingkup Sinkronisasi
 * poin 3 (lihat App\Services\SyncEngine - hanya Profil Sekolah +
 * Pendataan OPS/BOSP + Data PTK) - jadi endpoint sync/pull yang sudah
 * ada TIDAK mengandung info Pengguna sama sekali. Dipakai middleware
 * 'sync.token' yang SAMA (Token API yang sama dengan menu Sinkronisasi),
 * supaya Superadmin tidak perlu mengatur token terpisah lagi.
 *
 * HANYA mengembalikan kolom yang tidak sensitif (TIDAK PERNAH password,
 * google2fa_secret, kata_sandi_pemulihan_hash, dst - konsisten dengan
 * prinsip keamanan kredensial proyek ini).
 */
class SuperadminCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $daftar = User::query()
            ->where('level_akses', User::LEVEL_SUPERADMIN)
            ->orderBy('email')
            ->get(['username', 'email', 'is_approved'])
            ->map(fn (User $user) => [
                'username' => $user->username,
                'email' => $user->email,
                'is_approved' => (bool) $user->is_approved,
            ])
            ->values()
            ->all();

        return response()->json(['superadmin' => $daftar]);
    }
}
