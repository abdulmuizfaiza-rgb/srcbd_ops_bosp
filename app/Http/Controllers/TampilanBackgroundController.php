<?php

namespace App\Http\Controllers;

use App\Models\PengaturanTampilan;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan gambar latar (background landing/login) langsung lewat route
 * aplikasi, BUKAN lewat URL statis symlink "public/storage" (yang dibangun
 * dari config APP_URL).
 *
 * Alasan: Storage::disk('public')->url() membangun URL memakai nilai
 * APP_URL di .env secara APA ADANYA (protokol+host+port) - kalau nilai itu
 * tidak persis sama dengan alamat yang benar-benar dipakai browser untuk
 * membuka aplikasi (mis. beda port, beda hostname Laragon, atau APP_URL
 * belum diupdate), gambar jadi 404/gagal dimuat meski filenya sendiri
 * tersimpan dengan benar di server - inilah yang menyebabkan background
 * yang sudah diupload "tidak muncul" (ikon gambar rusak). Route Laravel
 * biasa (route()/url()) otomatis mengikuti host+port yang sedang benar-benar
 * dipakai browser saat itu (tidak bergantung APP_URL), jadi cara ini lebih
 * tahan terhadap kondisi environment apapun di sisi user - baik APP_URL
 * salah maupun symlink "storage:link" belum/tidak berhasil dibuat.
 */
class TampilanBackgroundController extends Controller
{
    public function show(string $jenis): StreamedResponse|Response
    {
        abort_unless(in_array($jenis, ['landing', 'login'], true), 404);

        $tampilan = PengaturanTampilan::current();

        $path = $jenis === 'landing' ? $tampilan->background_landing : $tampilan->background_login;

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
