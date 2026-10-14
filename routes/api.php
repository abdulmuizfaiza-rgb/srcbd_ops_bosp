<?php

use App\Http\Controllers\Api\SyncPullController;
use App\Http\Controllers\Api\SyncPushController;
use Illuminate\Support\Facades\Route;

/**
 * API khusus fitur "Sinkronisasi dan Tarik Data" (permintaan user
 * 2026-10-14 poin 3) - SATU-SATUNYA isi routes/api.php di aplikasi ini
 * sampai saat ini. Diautentikasi dengan token API (lihat
 * App\Http\Middleware\EnsureSyncTokenValid), BUKAN sesi login biasa -
 * selalu dipanggil DARI Lokal KE Online (lihat App\Services\
 * SyncClient), tidak pernah sebaliknya.
 */
Route::middleware('sync.token')->group(function () {
    Route::get('sync/pull', SyncPullController::class)->name('api.sync.pull');
    Route::post('sync/push', SyncPushController::class)->name('api.sync.push');
});
