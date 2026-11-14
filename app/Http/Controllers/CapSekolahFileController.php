<?php

namespace App\Http\Controllers;

use App\Models\ProfilSekolah;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan gambar Cap Sekolah (permintaan user 2026-11-14 - field
 * UPLOAD biasa, cap/stempel yang sudah ada di-scan/difoto oleh Admin
 * OPS/Admin BOSP, BUKAN dibuat otomatis oleh sistem) langsung lewat
 * route aplikasi, BUKAN lewat URL statis symlink "public/storage" -
 * mengikuti pola baku yang SAMA PERSIS seperti App\Http\Controllers\
 * LogoSekolahFileController (lihat komentar lengkap di sana untuk
 * penjelasan akar masalah APP_URL yang dihindari lewat pola ini).
 *
 * HANYA dipakai untuk tampilan di LAYAR (preview Livewire di menu Profil
 * Sekolah) - untuk PDF (dompdf) nanti (Fase 2, belum dikerjakan), gambar
 * akan disisipkan lewat App\Models\ProfilSekolah::capSekolahDataUri(),
 * BUKAN lewat route ini.
 */
class CapSekolahFileController extends Controller
{
    public function show(ProfilSekolah $profilSekolah): StreamedResponse|Response
    {
        $user = auth()->user();
        abort_unless(
            $user->isSuperadmin() || $user->profil_sekolah_id === $profilSekolah->id,
            403
        );

        $path = $profilSekolah->cap_sekolah;

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
