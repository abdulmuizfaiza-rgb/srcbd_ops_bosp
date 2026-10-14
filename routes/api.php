<?php

use App\Http\Controllers\Api\SuperadminCheckController;
use App\Http\Controllers\Api\SyncPullController;
use App\Http\Controllers\Api\SyncPushController;
use Illuminate\Support\Facades\Route;

/**
 * API khusus fitur "Sinkronisasi dan Tarik Data" (permintaan user
 * 2026-10-14 poin 3) + endpoint tambahan "Cek Kecocokan Data" akun
 * Superadmin (permintaan user 2026-10-14, lanjutan). Diautentikasi
 * dengan token API (lihat App\Http\Middleware\EnsureSyncTokenValid),
 * BUKAN sesi login biasa - selalu dipanggil DARI Lokal KE Online (lihat
 * App\Services\SyncClient), tidak pernah sebaliknya.
 */
Route::middleware('sync.token')->group(function () {
    Route::get('sync/pull', SyncPullController::class)->name('api.sync.pull');
    Route::post('sync/push', SyncPushController::class)->name('api.sync.push');
    Route::get('sync/superadmin-check', SuperadminCheckController::class)->name('api.sync.superadmin-check');
});
