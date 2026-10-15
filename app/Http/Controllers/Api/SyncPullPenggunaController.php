<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SyncLog;
use App\Services\SyncEngine;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Dipanggil oleh Lokal lewat App\Services\SyncClient::tarikPengguna()
 * saat Superadmin menekan tombol "Tarik Data Pengguna" (BARU
 * 2026-10-16, permintaan user: "saya ingin bisa menarik data pengguna
 * ... baik sebagai superadmin, admin ops, admin bosp") - TERPISAH dari
 * tombol "Tarik Data" data sekolah (App\Http\Controllers\Api\
 * SyncPullController), dikonfirmasi lewat AskUserQuestion. HANYA
 * berjalan berarti di sisi ONLINE (melayani), sama pola dgn
 * SyncPullController.
 */
class SyncPullPenggunaController extends Controller
{
    public function __invoke(SyncEngine $syncEngine): JsonResponse
    {
        try {
            $payload = $syncEngine->kumpulkanPenggunaKeluar();

            SyncLog::create([
                'arah' => SyncLog::ARAH_SAJIKAN_PENGGUNA,
                'status' => SyncLog::STATUS_BERHASIL,
                'ringkasan' => ['jumlah_pengguna' => count($payload['pengguna'])],
            ]);

            return response()->json($payload);
        } catch (Throwable $e) {
            SyncLog::create([
                'arah' => SyncLog::ARAH_SAJIKAN_PENGGUNA,
                'status' => SyncLog::STATUS_GAGAL,
                'pesan_error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Gagal menyiapkan data akun Pengguna untuk ditarik: '.$e->getMessage()], 500);
        }
    }
}
