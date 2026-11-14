<?php

namespace App\Http\Controllers;

use App\Models\RekeningKoran;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan file PDF Rekening Koran TW1-4 langsung lewat route
 * aplikasi (BUKAN lewat URL statis symlink "public/storage") - pola
 * SAMA PERSIS seperti App\Http\Controllers\PendataanOpsFileController
 * (lihat docblock lengkap di file itu untuk alasan teknisnya).
 *
 * Berisi data keuangan sekolah - rute ini WAJIB login, dan hanya boleh
 * diakses oleh Superadmin atau Admin BOSP pemilik data itu sendiri
 * (sekolah yang sama).
 */
class RekeningKoranFileController extends Controller
{
    public function show(int $tw, RekeningKoran $rekeningKoran): StreamedResponse|Response
    {
        abort_unless(in_array($tw, [1, 2, 3, 4], true), 404);

        $user = auth()->user();
        abort_unless(
            $user->isSuperadmin() || $user->profil_sekolah_id === $rekeningKoran->profil_sekolah_id,
            403
        );

        $kolom = RekeningKoran::kolomTw($tw);
        $path = $rekeningKoran->{$kolom};

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
