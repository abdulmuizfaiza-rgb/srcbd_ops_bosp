<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Mengunduh paket backup (round DUA PULUH LIMA, 2026-09-24, poin 2) lewat
 * route aplikasi & disk 'local' (BUKAN 'public' - paket backup berisi kode
 * aplikasi & dump database, TIDAK boleh punya URL publik sama sekali,
 * hanya bisa diakses lewat route ter-Gate 'akses-backup', lihat
 * App\Providers\AppServiceProvider).
 *
 * Memakai Storage::disk('local')->download() (StreamedResponse) - pola
 * yang sama dgn App\Http\Controllers\PanduanAplikasiFileController/
 * PendataanOpsFileController, supaya konsisten & tidak terjebak masalah
 * APP_URL yang sama seperti keduanya.
 */
class BackupFileController extends Controller
{
    public function unduh(Backup $backup): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($backup->path), 404);

        return Storage::disk('local')->download($backup->path, $backup->nama_file);
    }
}
