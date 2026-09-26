<?php

use App\Http\Controllers\PendataanOpsFileController;
use App\Http\Controllers\TampilanBackgroundController;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\PendataanBosp\BelanjaHonorKegiatan\Index as BelanjaHonorKegiatanIndex;
use App\Livewire\PendataanBosp\BelanjaPemeliharaanBangunan\Index as BelanjaPemeliharaanBangunanIndex;
use App\Livewire\PendataanBosp\BelanjaPemeliharaanPc\Index as BelanjaPemeliharaanPcIndex;
use App\Livewire\PendataanBosp\BiayaPendaftaranLomba\Index as BiayaPendaftaranLombaIndex;
use App\Livewire\PendataanBosp\DanaBospTahap\Index as DanaBospTahapIndex;
use App\Livewire\PendataanBosp\Index as PendataanBospIndex;
use App\Livewire\PendataanBosp\LanggananDayaJasa\Index as LanggananDayaJasaIndex;
use App\Livewire\PendataanBosp\PajakBospReguler\Index as PajakBospRegulerIndex;
use App\Livewire\PendataanBosp\PenerimaanHonorPtk\Index as PenerimaanHonorPtkIndex;
use App\Livewire\PendataanBosp\RekapRkas\Index as RekapRkasIndex;
use App\Livewire\PendataanBosp\RincianBelanjaBarangHabisPakai\Index as RincianBelanjaBarangHabisPakaiIndex;
use App\Livewire\PendataanBosp\RincianBelanjaModal\Index as RincianBelanjaModalIndex;
use App\Livewire\PendataanOps\Index as PendataanOpsIndex;
use App\Livewire\PendataanOps\Lampiran2a\Index as Lampiran2aIndex;
use App\Livewire\PendataanOps\Lampiran2b\Index as Lampiran2bIndex;
use App\Livewire\PendataanOps\Lampiran2c\Index as Lampiran2cIndex;
use App\Livewire\PendataanOps\Unduhan\Index as UnduhanIndex;
use App\Livewire\Pengguna\Index as PenggunaIndex;
use App\Livewire\ProfilSekolah\Index as ProfilSekolahIndex;
use App\Livewire\Tampilan\Index as TampilanIndex;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Sengaja TANPA middleware "auth" - gambar latar ini juga tampil di
// halaman pemilihan akses & form login (sebelum user login).
Route::get('tampilan/background/{jenis}', [TampilanBackgroundController::class, 'show'])
    ->where('jenis', 'landing|login')
    ->name('tampilan.background');

Route::redirect('/', '/dashboard');

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
});

Route::middleware(['auth', 'can:akses-pengguna'])->group(function () {
    Route::get('pengguna', PenggunaIndex::class)->name('pengguna.index');
});

Route::middleware(['auth', 'can:akses-tampilan'])->group(function () {
    Route::get('tampilan', TampilanIndex::class)->name('tampilan.index');
});

require __DIR__.'/auth.php';
