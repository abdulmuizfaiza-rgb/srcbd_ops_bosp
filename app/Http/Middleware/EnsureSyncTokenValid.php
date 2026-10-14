<?php

namespace App\Http\Middleware;

use App\Models\SyncToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autentikasi khusus API Sinkronisasi (permintaan user 2026-10-14 poin
 * 3) - TIDAK memakai sesi/cookie (yang hanya berlaku di browser yang
 * sama), melainkan header "Authorization: Bearer <token>" yang
 * dicocokkan ke App\Models\SyncToken (token API yang dibuat di menu
 * Sinkronisasi sisi Online). Dipasang HANYA pada routes/api.php
 * (routes/web.php tetap pakai middleware 'auth' seperti biasa, TIDAK
 * tersentuh sama sekali oleh perubahan ini).
 */
class EnsureSyncTokenValid
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = SyncToken::cocok($request->bearerToken());

        if ($token === null) {
            return response()->json([
                'message' => 'Token API Sinkronisasi tidak valid, kosong, atau sudah dicabut.',
            ], 401);
        }

        $token->tandaiDipakai();

        return $next($request);
    }
}
