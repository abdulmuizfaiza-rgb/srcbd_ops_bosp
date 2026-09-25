<?php

use App\Http\Controllers\BackupFileController;
use App\Http\Controllers\FormulirBosK7CetakController;
use App\Http\Controllers\KopSuratFileController;
use App\Http\Controllers\PanduanAplikasiFileController;
use App\Http\Controllers\PanduanAplikasiUnduhSemuaController;
use App\Http\Controllers\PendataanOpsFileController;
use App\Http\Controllers\SuratTpgCetakController;
use App\Http\Controllers\SuratTpgCetakSemuaController;
use App\Http\Controllers\TampilanBackgroundController;
use App\Livewire\Backup\Index as BackupIndex;
use App\Livewire\Beranda\Index as BerandaIndex;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\PanduanAplikasi\Index as PanduanAplikasiIndex;
use App\Livewire\PendataanBosp\BelanjaHonorKegiatan\Index as BelanjaHonorKegiatanIndex;
use App\Livewire\PendataanBosp\BelanjaPemeliharaanBangunan\Index as BelanjaPemeliharaanBangunanIndex;
use App\Livewire\PendataanBosp\BelanjaPemeliharaanPc\Index as BelanjaPemeliharaanPcIndex;
use App\Livewire\PendataanBosp\BiayaPendaftaranLomba\Index as BiayaPendaftaranLombaIndex;
use App\Livewire\PendataanBosp\DanaBospTahap\Index as DanaBospTahapIndex;
use App\Livewire\PendataanBosp\FormulirBosK7\Index as FormulirBosK7Index;
use App\Livewire\PendataanBosp\Index as PendataanBospIndex;
use App\Livewire\PendataanBosp\LanggananDayaJasa\Index as LanggananDayaJasaIndex;
use App\Livewire\PendataanBosp\LaporanRealisasiBosp\Index as LaporanRealisasiBospIndex;
use App\Livewire\PendataanBosp\PajakBospReguler\Index as PajakBospRegulerIndex;
use App\Livewire\PendataanBosp\PenerimaanHonorPtk\Index as PenerimaanHonorPtkIndex;
use App\Livewire\PendataanBosp\RekapRkas\Index as RekapRkasIndex;
use App\Livewire\PendataanBosp\RincianBelanjaBarangHabisPakai\Index as RincianBelanjaBarangHabisPakaiIndex;
use App\Livewire\PendataanBosp\RincianBelanjaModal\Index as RincianBelanjaModalIndex;
use App\Livewire\PendataanOps\Index as PendataanOpsIndex;
use App\Livewire\PendataanOps\Lampiran2a\Index as Lampiran2aIndex;
use App\Livewire\PendataanOps\Lampiran2b\Index as Lampiran2bIndex;
use App\Livewire\PendataanOps\Lampiran2c\Index as Lampiran2cIndex;
use App\Livewire\PendataanOps\SuratTpg\Index as SuratTpgIndex;
use App\Livewire\PendataanOps\Unduhan\Index as UnduhanIndex;
use App\Livewire\Pengguna\Index as PenggunaIndex;
use App\Livewire\ProfilSekolah\Index as ProfilSekolahIndex;
use App\Livewire\Tampilan\Index as TampilanIndex;
use App\Livewire\TimelinePekerjaan\Index as TimelinePekerjaanIndex;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Sengaja TANPA middleware "auth" - gambar latar ini juga tampil di
// halaman pemilihan akses & form login (sebelum user login).
Route::get('tampilan/background/{jenis}', [TampilanBackgroundController::class, 'show'])
    ->where('jenis', 'landing|login')
    ->name('tampilan.background');

// ROUND KEENAM BELAS (2026-09-24, bagian 2): permintaan user "landing
// page yang muncul sebelum halaman form login" - menggantikan
// `Route::redirect('/', '/dashboard')` yang sebelumnya UNCONDITIONAL
// utk SEMUA pengunjung. Dibungkus middleware bawaan Laravel "guest"
// (alias yang SUDAH DIPAKAI utk /login & /register di routes/auth.php)
// supaya user yang SUDAH login TETAP otomatis diarahkan ke /dashboard
// (perilaku lama utk mereka TIDAK berubah - "guest" middleware bawaan
// Laravel memang redirect ke route "dashboard" kalau sudah ada,
// PERSIS yang dibutuhkan di sini, bukan logika baru) - HANYA pengunjung
// yang BELUM login yang sekarang melihat landing page baru ini. Lihat
// docblock App\Livewire\Beranda\Index utk detail lengkap.
Route::middleware('guest')->group(function () {
    Route::get('/', BerandaIndex::class)->name('beranda');
});

Volt::route('ganti-password-wajib', 'pages.ganti-password-wajib')
    ->middleware(['auth'])
    ->name('ganti-password-wajib');

Route::get('dashboard', DashboardIndex::class)
    ->middleware(['auth'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth', 'can:akses-profil-sekolah'])->group(function () {
    Route::get('profil-sekolah', ProfilSekolahIndex::class)->name('profil-sekolah.index');
});

Route::middleware(['auth', 'can:akses-pendataan-ops'])->group(function () {
    Route::get('pendataan-ops', PendataanOpsIndex::class)->name('pendataan-ops.index');
    Route::get('pendataan-ops/lampiran-2a', Lampiran2aIndex::class)->name('pendataan-ops.lampiran-2a');
    Route::get('pendataan-ops/lampiran-2b', Lampiran2bIndex::class)->name('pendataan-ops.lampiran-2b');
    Route::get('pendataan-ops/lampiran-2c', Lampiran2cIndex::class)->name('pendataan-ops.lampiran-2c');
    Route::get('pendataan-ops/surat-tpg', SuratTpgIndex::class)->name('pendataan-ops.surat-tpg');
    Route::get('pendataan-ops/surat-tpg/cetak', SuratTpgCetakController::class)->name('pendataan-ops.surat-tpg.cetak');
    Route::get('pendataan-ops/surat-tpg/cetak-semua', SuratTpgCetakSemuaController::class)->name('pendataan-ops.surat-tpg.cetak-semua');
    Route::get('pendataan-ops/surat-tpg/kop-surat/{profilSekolah}', [KopSuratFileController::class, 'show'])
        ->name('pendataan-ops.surat-tpg.kop-surat');
    Route::get('pendataan-ops/unduhan', UnduhanIndex::class)->name('pendataan-ops.unduhan');
    Route::get('pendataan-ops/file/{jenis}/{pendataanOps}', [PendataanOpsFileController::class, 'show'])
        ->where('jenis', 'foto|sk')
        ->name('pendataan-ops.file');
});

Route::middleware(['auth', 'can:akses-pendataan-bosp'])->group(function () {
    Route::get('pendataan-bosp', PendataanBospIndex::class)->name('pendataan-bosp.index');
    Route::get('pendataan-bosp/rekap-rkas', RekapRkasIndex::class)->name('pendataan-bosp.rekap-rkas');
    Route::get('pendataan-bosp/dana-bosp-tahap', DanaBospTahapIndex::class)->name('pendataan-bosp.dana-bosp-tahap');
    Route::get('pendataan-bosp/penerimaan-honor-ptk', PenerimaanHonorPtkIndex::class)->name('pendataan-bosp.penerimaan-honor-ptk');
    Route::get('pendataan-bosp/langganan-daya-jasa', LanggananDayaJasaIndex::class)->name('pendataan-bosp.langganan-daya-jasa');
    Route::get('pendataan-bosp/belanja-pemeliharaan-bangunan', BelanjaPemeliharaanBangunanIndex::class)->name('pendataan-bosp.belanja-pemeliharaan-bangunan');
    Route::get('pendataan-bosp/belanja-pemeliharaan-pc', BelanjaPemeliharaanPcIndex::class)->name('pendataan-bosp.belanja-pemeliharaan-pc');
    Route::get('pendataan-bosp/biaya-pendaftaran-lomba', BiayaPendaftaranLombaIndex::class)->name('pendataan-bosp.biaya-pendaftaran-lomba');
    Route::get('pendataan-bosp/belanja-honor-kegiatan', BelanjaHonorKegiatanIndex::class)->name('pendataan-bosp.belanja-honor-kegiatan');
    Route::get('pendataan-bosp/rincian-belanja-modal', RincianBelanjaModalIndex::class)->name('pendataan-bosp.rincian-belanja-modal');
    Route::get('pendataan-bosp/rincian-belanja-barang-habis-pakai', RincianBelanjaBarangHabisPakaiIndex::class)->name('pendataan-bosp.rincian-belanja-barang-habis-pakai');
    Route::get('pendataan-bosp/pajak-bosp-reguler', PajakBospRegulerIndex::class)->name('pendataan-bosp.pajak-bosp-reguler');
    Route::get('pendataan-bosp/laporan-realisasi-bosp', LaporanRealisasiBospIndex::class)->name('pendataan-bosp.laporan-realisasi-bosp');
    Route::get('pendataan-bosp/formulir-bos-k7', FormulirBosK7Index::class)->name('pendataan-bosp.formulir-bos-k7');
    Route::get('pendataan-bosp/formulir-bos-k7/cetak', FormulirBosK7CetakController::class)->name('pendataan-bosp.formulir-bos-k7.cetak');
});

Route::middleware(['auth', 'can:akses-timeline-pekerjaan'])->group(function () {
    Route::get('timeline-pekerjaan', TimelinePekerjaanIndex::class)->name('timeline-pekerjaan.index');
});

Route::middleware(['auth', 'can:akses-pengguna'])->group(function () {
    Route::get('pengguna', PenggunaIndex::class)->name('pengguna.index');
});

Route::middleware(['auth', 'can:akses-tampilan'])->group(function () {
    Route::get('tampilan', TampilanIndex::class)->name('tampilan.index');
});

// Menu "Panduan Aplikasi" (BARU 2026-09-24, round kedua puluh empat, poin
// 6; direstrukturisasi round kedua puluh lima poin 1 - satu Judul boleh
// punya banyak file, lihat App\Models\PanduanAplikasi::files()) - HANYA
// Superadmin (Gate 'akses-panduan-aplikasi', lihat
// App\Providers\AppServiceProvider).
Route::middleware(['auth', 'can:akses-panduan-aplikasi'])->group(function () {
    Route::get('panduan-aplikasi', PanduanAplikasiIndex::class)->name('panduan-aplikasi.index');
    Route::get('panduan-aplikasi/file/{panduanAplikasiFile}/unduh', [PanduanAplikasiFileController::class, 'unduh'])
        ->name('panduan-aplikasi.file.unduh');
    // Round kedua puluh enam, poin 3: "Unduh Semua" - mengunduh SEMUA file
    // di bawah satu Judul sekaligus sbg 1 file .zip.
    Route::get('panduan-aplikasi/{panduanAplikasi}/unduh-semua', [PanduanAplikasiUnduhSemuaController::class, 'unduh'])
        ->name('panduan-aplikasi.unduh-semua');
});

// Menu "Backup" (BARU, round kedua puluh lima, 2026-09-24, poin 2,
// permintaan user "buatkan menu backup berdasarkan tahun... Aplikasi nya
// dan database nya yang terbaru") - HANYA Superadmin (Gate
// 'akses-backup', lihat App\Providers\AppServiceProvider).
Route::middleware(['auth', 'can:akses-backup'])->group(function () {
    Route::get('backup', BackupIndex::class)->name('backup.index');
    Route::get('backup/{backup}/unduh', [BackupFileController::class, 'unduh'])->name('backup.unduh');
});

require __DIR__.'/auth.php';
