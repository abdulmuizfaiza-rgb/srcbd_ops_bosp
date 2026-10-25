<?php

namespace App\Http\Controllers;

use App\Exports\LpjBospExport;
use App\Models\AksesDataLog;
use App\Models\ProfilSekolah;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unduhan Excel gabungan menu "Unduhan LPJ BOSP" (lihat App\Exports\
 * LpjBospExport) - route GET BIASA (BUKAN wire:click Livewire), pola
 * SAMA seperti SuratTpgCetakController/FormulirBosK7CetakController di
 * app ini untuk unduhan/cetak berkas besar.
 *
 * Perbaikan bug 2026-10-25: percobaan awal memakai aksi Livewire
 * (wire:click) untuk tombol unduh ini SELALU gagal dengan error 419
 * "Page Expired" di VPS produksi (terverifikasi lewat Network tab
 * browser - request POST ke /livewire/update ditolak middleware CSRF
 * Laravel dalam hitungan milidetik, konsisten setiap dicoba: tab baru,
 * incognito, setelah restart php-fpm sekalipun - menu Livewire LAIN yang
 * tidak mengunduh file tetap aman). Link unduhan GET biasa (seperti ini)
 * TIDAK PERNAH melewati validasi CSRF Laravel (Laravel hanya memvalidasi
 * CSRF utk method yang mengubah data seperti POST/PUT/PATCH/DELETE,
 * bukan GET) - jadi seluruh kelas masalah di atas otomatis hilang,
 * sekaligus mengikuti pola yang SUDAH ADA di app ini utk semua unduhan/
 * cetak berkas lain.
 */
class UnduhanLpjBospController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $triwulan = (int) $request->query('triwulan');
        $tahun = (int) $request->query('tahun');

        abort_unless($triwulan >= 1 && $triwulan <= 4 && $tahun > 0, 404);

        $user = $request->user();
        $sekolahIdDiminta = $request->query('sekolah_id') !== null && $request->query('sekolah_id') !== ''
            ? (int) $request->query('sekolah_id')
            : null;

        // Admin BOSP: SELALU sekolahnya sendiri, apa pun yang diminta di
        // query string (tidak percaya input client - pola otorisasi sama
        // seperti SuratTpgCetakController/FormulirBosK7CetakController).
        $sekolahId = $user->isSuperadmin() ? $sekolahIdDiminta : $user->profil_sekolah_id;

        $sekolah = $sekolahId ? ProfilSekolah::findOrFail($sekolahId) : null;

        $namaSekolah = $sekolah ? $sekolah->nama_sekolah : 'Semua Sekolah';
        $namaFile = trim(preg_replace(
            '/[\\\\\/:*?"<>|]/',
            '-',
            'File LPJ BOSP Triwulan '.$triwulan.' Tahun '.$tahun.'_'.$namaSekolah
        ));

        AksesDataLog::catat(AksesDataLog::JENIS_UNDUH, 'LPJ BOSP (Unduhan Gabungan)', 'Excel');

        return Excel::download(new LpjBospExport($triwulan, $tahun, $sekolah), $namaFile.'.xlsx');
    }
}
