<?php

namespace App\Http\Controllers;

use App\Models\PendataanOps;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan file Photo OPS & SK OPS langsung lewat route aplikasi, BUKAN
 * lewat URL statis symlink "public/storage" (yang dibangun dari config
 * APP_URL) - akar masalah yang sama persis dengan bug "background Tampilan
 * tidak muncul" yang sudah pernah diperbaiki sebelumnya lewat
 * TampilanBackgroundController (lihat komentar di file itu untuk penjelasan
 * lengkap): Storage::disk('public')->url() membangun URL memakai APP_URL
 * apa adanya, yang gagal kalau tidak persis sama dengan host+port yang
 * benar-benar dipakai browser (mis. Laragon dengan port/virtual host
 * berbeda) - inilah sebabnya kolom Photo OPS & SK OPS pada menu Identitas
 * OPS "tidak muncul" (ikon gambar rusak / link 404) walau filenya sendiri
 * tersimpan benar di server.
 *
 * Beda dengan gambar latar (yang tampil sebelum login, jadi rute-nya bebas
 * middleware auth), file Photo OPS & SK OPS berisi data pribadi Admin OPS -
 * jadi rute ini WAJIB login, dan hanya boleh diakses oleh Superadmin atau
 * Admin OPS pemilik data itu sendiri (sekolah yang sama).
 */
class PendataanOpsFileController extends Controller
{
    public function show(string $jenis, PendataanOps $pendataanOps): StreamedResponse|Response
    {
        abort_unless(in_array($jenis, ['foto', 'sk'], true), 404);

        $user = auth()->user();
        abort_unless(
            $user->isSuperadmin() || $user->profil_sekolah_id === $pendataanOps->profil_sekolah_id,
            403
        );

        $path = $jenis === 'foto' ? $pendataanOps->foto_ops : $pendataanOps->sk_ops;

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
