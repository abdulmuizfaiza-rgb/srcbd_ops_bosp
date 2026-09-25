<?php

namespace App\Http\Controllers;

use App\Models\PanduanAplikasiFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyajikan/mengunduh file Panduan Aplikasi lewat route aplikasi, BUKAN
 * lewat URL statis symlink "public/storage" (yang dibangun dari config
 * APP_URL) - masalah yang sama persis dengan alasan
 * App\Http\Controllers\PendataanOpsFileController dibuat (lihat docblock
 * file itu untuk penjelasan lengkap): Storage::disk('public')->url()
 * membangun URL memakai APP_URL apa adanya, yang gagal kalau tidak persis
 * sama dengan host+port yang benar-benar dipakai browser.
 *
 * Round DUA PULUH LIMA (2026-09-24), poin 1: parameter route berubah dari
 * PanduanAplikasi (1 baris = 1 file, round 24) menjadi PanduanAplikasiFile
 * (1 Judul sekarang boleh punya banyak file - lihat App\Models\
 * PanduanAplikasi::files()).
 *
 * Route ini dibungkus middleware 'can:akses-panduan-aplikasi' (HANYA
 * Superadmin, lihat App\Providers\AppServiceProvider) - sama seperti akses
 * menu Panduan Aplikasi itu sendiri.
 */
class PanduanAplikasiFileController extends Controller
{
    public function unduh(PanduanAplikasiFile $panduanAplikasiFile): StreamedResponse
    {
        abort_unless(
            Storage::disk('public')->exists($panduanAplikasiFile->file_path),
            404
        );

        return Storage::disk('public')->download(
            $panduanAplikasiFile->file_path,
            $panduanAplikasiFile->file_nama_asli ?: basename($panduanAplikasiFile->file_path)
        );
    }
}
