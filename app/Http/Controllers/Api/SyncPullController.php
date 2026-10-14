<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SyncLog;
use App\Services\SyncEngine;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Dipanggil oleh Lokal lewat App\Services\SyncClient saat Superadmin
 * menekan tombol "Tarik Data" (permintaan user 2026-10-14 poin 3).
 * HANYA berjalan berarti di sisi ONLINE (melayani) - kalau dipanggil di
 * instalasi Lokal, tetap akan merespons (tidak diblok), tapi secara
 * desain tidak pernah dipanggil sendiri oleh Lokal (lihat
 * config/sync.php - tombol Tarik Data di Lokal memanggil URL Online,
 * BUKAN URL dirinya sendiri).
 */
class SyncPullController extends Controller
{
    public function __invoke(SyncEngine $syncEngine): JsonResponse
    {
        try {
            $payload = $syncEngine->kumpulkanDataKeluar();

            SyncLog::create([
                'arah' => SyncLog::ARAH_SAJIKAN,
                'status' => SyncLog::STATUS_BERHASIL,
                'ringkasan' => ['jumlah_tabel' => count($payload['tabel']), 'jumlah_tombstone' => count($payload['tombstones'])],
            ]);

            return response()->json($payload);
        } catch (Throwable $e) {
            SyncLog::create([
                'arah' => SyncLog::ARAH_SAJIKAN,
                'status' => SyncLog::STATUS_GAGAL,
                'pesan_error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Gagal menyiapkan data untuk ditarik: '.$e->getMessage()], 500);
        }
    }
}
