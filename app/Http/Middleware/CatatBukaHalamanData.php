<?php

namespace App\Http\Middleware;

use App\Models\AksesDataLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencatat setiap kali akun yang sedang login MEMBUKA salah satu halaman
 * menu data di bawah ini (jenis_aksi='buka_halaman') - permintaan user
 * 2026-09-27 (menu baru "Cek Database dan Aplikasi") sebagai kontrol
 * keamanan: "mengetahui data apa saja yang diambil" oleh siapa.
 *
 * SENGAJA memakai daftar putih (whitelist) $labelMenu di bawah, BUKAN
 * daftar hitam (blacklist) route yang dikecualikan - supaya route baru
 * yang lupa ditambahkan ke sini otomatis TIDAK tercatat (aman/gagal-diam)
 * daripada otomatis ikut tercatat dengan label yang salah. Route
 * pengunduhan file (controller unduh/cetak) SENGAJA TIDAK dimasukkan ke
 * daftar ini - route tersebut nanti dicatat terpisah sebagai
 * jenis_aksi='unduh' langsung dari controller/komponennya masing-masing,
 * supaya tidak tercatat dobel sebagai "buka halaman" sekaligus "unduh".
 *
 * Middleware ini HANYA membaca request yang sedang berjalan (tidak pernah
 * mengubah/membatalkan apa pun) - kalau pencatatan gagal, request tetap
 * diteruskan seperti biasa (lihat AksesDataLog::catat() yang membungkus
 * try/catch sendiri).
 */
class CatatBukaHalamanData
{
    /**
     * @var array<string, string>
     */
    private static array $labelMenu = [
        'dashboard' => 'Dashboard',
        'profile' => 'Profil Akun',
        'profil-sekolah.index' => 'Profil Sekolah',
        'pendataan-ops.index' => 'Pendataan OPS',
        'pendataan-ops.lampiran-2a' => 'Lampiran 2A',
        'pendataan-ops.lampiran-2b' => 'Lampiran 2B',
        'pendataan-ops.lampiran-2c' => 'Lampiran 2C',
        'pendataan-ops.surat-tpg' => 'Surat TPG',
        'pendataan-ops.unduhan' => 'Unduhan (Pendataan OPS)',
        'pendataan-bosp.index' => 'Pendataan BOSP',
        'pendataan-bosp.rekap-rkas' => 'Rekap RKAS',
        'pendataan-bosp.dana-bosp-tahap' => 'Dana BOSP Tahap',
        'pendataan-bosp.penerimaan-honor-ptk' => 'Penerimaan Honor PTK',
        'pendataan-bosp.langganan-daya-jasa' => 'Langganan Daya dan Jasa',
        'pendataan-bosp.belanja-pemeliharaan-bangunan' => 'Belanja Pemeliharaan Bangunan',
        'pendataan-bosp.belanja-pemeliharaan-pc' => 'Belanja Pemeliharaan PC',
        'pendataan-bosp.biaya-pendaftaran-lomba' => 'Biaya Pendaftaran Lomba',
        'pendataan-bosp.belanja-honor-kegiatan' => 'Belanja Honor Kegiatan',
        'pendataan-bosp.rincian-belanja-modal' => 'Rincian Belanja Modal',
        'pendataan-bosp.rincian-belanja-barang-habis-pakai' => 'Rincian Belanja Barang Habis Pakai',
        'pendataan-bosp.pajak-bosp-reguler' => 'Pajak BOSP Reguler',
        'pendataan-bosp.laporan-realisasi-bosp' => 'Laporan Realisasi BOSP',
        'pendataan-bosp.formulir-bos-k7' => 'Formulir BOS K7',
        'timeline-pekerjaan.index' => 'Timeline Pekerjaan',
        'pengguna.index' => 'Pengguna',
        'tampilan.index' => 'Tampilan',
        'panduan-aplikasi.index' => 'Panduan Aplikasi',
        'backup.index' => 'Backup',
        'pengumuman.index' => 'Pengumuman',
        'cek-database-aplikasi.index' => 'Cek Database dan Aplikasi',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('get') && $request->user()) {
            $nama = $request->route()?->getName();

            if ($nama !== null && isset(self::$labelMenu[$nama])) {
                AksesDataLog::catat(AksesDataLog::JENIS_BUKA_HALAMAN, self::$labelMenu[$nama]);
            }
        }

        return $response;
    }
}
