<?php

namespace App\Http\Controllers;

use App\Models\ProfilSekolah;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan gambar Logo Sekolah (permintaan user 2026-10-15, dipakai
 * otomatis di tengah halaman tab "Cover SMPN"/"Cover SMP-S" menu Format
 * Surat Rekomendasi & Pembatalan TPG, serta ditampilkan di menu Profil
 * Sekolah saat upload/edit) langsung lewat route aplikasi, BUKAN lewat
 * URL statis symlink "public/storage" - mengikuti pola baku yang SAMA
 * PERSIS seperti KopSuratFileController (lihat komentar lengkap di sana
 * untuk penjelasan akar masalah APP_URL yang dihindari lewat pola ini).
 *
 * HANYA dipakai untuk tampilan di LAYAR (preview Livewire di menu Profil
 * Sekolah MAUPUN menu Format Surat Rekomendasi & Pembatalan TPG, dibuka
 * lewat browser dengan sesi login) - untuk PDF (dompdf) & file Word
 * (.doc), gambar disisipkan langsung sebagai data URI base64 lewat
 * App\Models\ProfilSekolah::logoSekolahDataUri(), BUKAN lewat route ini.
 *
 * Route ini sengaja ditaruh di grup middleware `can:akses-profil-sekolah`
 * (gate yang selalu true untuk user yang sudah login - lihat
 * AppServiceProvider::boot()) supaya bisa diakses dari KEDUA menu
 * tsb tanpa terhalang gate yang lebih sempit; otorisasi SEBENARNYA tetap
 * dicek manual di bawah (sama seperti KopSuratFileController).
 */
class LogoSekolahFileController extends Controller
{
    public function show(ProfilSekolah $profilSekolah): StreamedResponse|Response
    {
        $user = auth()->user();
        abort_unless(
            $user->isSuperadmin() || $user->profil_sekolah_id === $profilSekolah->id,
            403
        );

        $path = $profilSekolah->logo_sekolah;

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
