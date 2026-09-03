<?php

use App\Livewire\PendataanBosp\Index as PendataanBospIndex;
use App\Livewire\PendataanOps\Index as PendataanOpsIndex;
use App\Livewire\Pengguna\Index as PenggunaIndex;
use App\Livewire\ProfilSekolah\Index as ProfilSekolahIndex;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::view('dashboard', 'dashboard')
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
});

Route::middleware(['auth', 'can:akses-pendataan-bosp'])->group(function () {
    Route::get('pendataan-bosp', PendataanBospIndex::class)->name('pendataan-bosp.index');
});

Route::middleware(['auth', 'can:akses-pengguna'])->group(function () {
    Route::get('pengguna', PenggunaIndex::class)->name('pengguna.index');
});

require __DIR__.'/auth.php';
