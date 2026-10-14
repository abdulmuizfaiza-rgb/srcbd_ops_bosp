<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SyncLog;
use App\Models\SyncToken;
use App\Services\SyncEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Dipanggil oleh Lokal lewat App\Services\SyncClient saat Superadmin
 * menekan tombol "Sinkronisasi" (kirim ke Online) - permintaan user
 * 2026-10-14 poin 3. HANYA berjalan berarti di sisi ONLINE (menerima).
 */
class SyncPushController extends Controller
{
    public function __invoke(Request $request, SyncEngine $syncEngine): JsonResponse
    {
        $payload = $request->input('payload');

        if (! is_array($payload)) {
            return response()->json(['message' => 'Payload sinkronisasi tidak valid (bukan array/JSON yang benar).'], 422);
        }

        // Dilakukan oleh = pembuat token (bukan auth()->id(), karena
        // request ini TIDAK membawa sesi login - lihat App\Http\
        // Middleware\EnsureSyncTokenValid).
        $pelakuId = SyncToken::cocok($request->bearerToken())?->dibuat_oleh_id;

        try {
            $hasil = $syncEngine->terapkanDataMasuk($payload, $pelakuId, 'Sinkronisasi (menerima kiriman dari Lokal)');

            SyncLog::create([
                'arah' => SyncLog::ARAH_TERIMA,
                'status' => SyncLog::STATUS_BERHASIL,
                'ringkasan' => $hasil['ringkasan'],
                'backup_pengaman_id' => $hasil['backup_pengaman_id'],
                'dilakukan_oleh_id' => $pelakuId,
            ]);

            return response()->json(['ringkasan' => $hasil['ringkasan']]);
        } catch (Throwable $e) {
            SyncLog::create([
                'arah' => SyncLog::ARAH_TERIMA,
                'status' => SyncLog::STATUS_GAGAL,
                'pesan_error' => $e->getMessage(),
                'dilakukan_oleh_id' => $pelakuId,
            ]);

            return response()->json(['message' => 'Gagal menerapkan data yang dikirim: '.$e->getMessage()], 500);
        }
    }
}
