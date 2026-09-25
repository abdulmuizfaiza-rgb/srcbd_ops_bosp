<?php

namespace App\Http\Controllers;

use App\Models\PanduanAplikasi;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Tombol "Unduh Semua" (round DUA PULUH ENAM, 2026-09-24, poin 3,
 * permintaan user "ketika superadmin mau unduh hasil upload nya ada
 * pilihan unduh semua file upload nya") - mengunduh SEMUA file yang
 * tersimpan di bawah satu Judul Book Manual sekaligus sebagai satu file
 * .zip, supaya Superadmin tidak perlu mengklik "Unduh" satu per satu kalau
 * Judul itu py banyak file (lihat App\Models\PanduanAplikasi::files(),
 * round 25).
 *
 * Dibuat sbg controller TERPISAH dari
 * App\Http\Controllers\PanduanAplikasiFileController (yang mengunduh SATU
 * file) krn responsnya beda jenis: file zip ini dibuat SEMENTARA di disk
 * lokal (BUKAN file yang sudah ada di storage, tapi digabung on-the-fly
 * dari beberapa file yang ADA), makanya memakai
 * `response()->download()->deleteFileAfterSend()` (BinaryFileResponse -
 * file sementara ini otomatis dihapus setelah terkirim ke browser), BUKAN
 * `Storage::disk()->download()` (StreamedResponse, dipakai controller lain
 * yang mengunduh SATU file yang sudah ada apa adanya di storage).
 *
 * Route ini dibungkus middleware 'can:akses-panduan-aplikasi' (HANYA
 * Superadmin, lihat App\Providers\AppServiceProvider) - sama seperti akses
 * menu Panduan Aplikasi & PanduanAplikasiFileController.
 */
class PanduanAplikasiUnduhSemuaController extends Controller
{
    public function unduh(PanduanAplikasi $panduanAplikasi): BinaryFileResponse
    {
        $files = $panduanAplikasi->files;

        abort_if($files->isEmpty(), 404);

        $namaZip = Str::slug($panduanAplikasi->judul) ?: 'panduan-aplikasi';
        $pathSementara = storage_path('app/private/tmp/panduan-aplikasi-'.$panduanAplikasi->id.'-'.uniqid().'.zip');

        File::ensureDirectoryExists(dirname($pathSementara));

        $zip = new ZipArchive;

        abort_unless($zip->open($pathSementara, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true, 500);

        $namaTerpakai = [];

        foreach ($files as $file) {
            if (! Storage::disk('public')->exists($file->file_path)) {
                continue;
            }

            $namaDiZip = $this->namaUnikDiZip($file->file_nama_asli ?: basename($file->file_path), $namaTerpakai);

            $zip->addFile(Storage::disk('public')->path($file->file_path), $namaDiZip);
        }

        $zip->close();

        return response()->download($pathSementara, $namaZip.'.zip')->deleteFileAfterSend(true);
    }

    /** Hindari 2 file dgn nama asli sama menimpa satu sama lain di dalam zip (mis. dua "panduan.pdf" yang berbeda) - tambahkan angka urut di belakang nama kalau sudah terpakai. */
    private function namaUnikDiZip(string $nama, array &$namaTerpakai): string
    {
        $namaAsli = $nama;
        $i = 1;

        while (in_array($nama, $namaTerpakai, true)) {
            $info = pathinfo($namaAsli);
            $ekstensi = isset($info['extension']) ? '.'.$info['extension'] : '';
            $nama = $info['filename'].' ('.$i.')'.$ekstensi;
            $i++;
        }

        $namaTerpakai[] = $nama;

        return $nama;
    }
}
