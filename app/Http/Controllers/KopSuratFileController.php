<?php

namespace App\Http\Controllers;

use App\Models\ProfilSekolah;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan gambar Kop Surat (permintaan user 2026-09-24, round
 * kesembilan belas - diupload manual oleh Admin OPS lewat menu "Format
 * Surat Rekomendasi & Pembatalan TPG") langsung lewat route aplikasi,
 * BUKAN lewat URL statis symlink "public/storage" (yang dibangun dari
 * config APP_URL) - mengikuti pola baku yang SAMA seperti
 * TampilanBackgroundController & PendataanOpsFileController (lihat
 * komentar lengkap di TampilanBackgroundController untuk penjelasan akar
 * masalah APP_URL yang dihindari lewat pola ini).
 *
 * HANYA dipakai untuk tampilan di LAYAR (preview Livewire, dibuka lewat
 * browser dengan sesi login) - untuk PDF (dompdf) & file Word (.doc),
 * gambar disisipkan langsung sebagai data URI base64 lewat
 * App\Models\ProfilSekolah::kopSuratDataUri() (lihat docblock method itu),
 * BUKAN lewat route ini, krn kedua output tersebut dirender di server
 * tanpa lewat request HTTP/sesi browser.
 */
class KopSuratFileController extends Controller
{
    public function show(ProfilSekolah $profilSekolah): StreamedResponse|Response
    {
        $user = auth()->user();
        abort_unless(
            $user->isSuperadmin() || $user->profil_sekolah_id === $profilSekolah->id,
            403
        );

        $path = $profilSekolah->kop_surat;

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
