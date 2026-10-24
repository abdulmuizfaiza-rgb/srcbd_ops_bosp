<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // routes/api.php ditambahkan khusus untuk fitur "Sinkronisasi dan
        // Tarik Data" (permintaan user 2026-10-14 poin 3) - lihat
        // App\Http\Middleware\EnsureSyncTokenValid untuk autentikasinya
        // (token API, BUKAN sesi login seperti routes/web.php).
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Audit 2026-10-04: percayai reverse proxy (Nginx) di VPS supaya Laravel
        // mendeteksi skema HTTPS dengan benar lewat header X-Forwarded-Proto
        // (tanpa ini, url()/redirect() bisa menghasilkan link http:// walau
        // koneksi sebenarnya sudah https://). "*" aman untuk setup 1 server
        // (Nginx + PHP-FPM di mesin yang sama, satu-satunya pintu masuk) -
        // BUKAN untuk arsitektur multi-layer load balancer pihak ketiga.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'sync.token' => \App\Http\Middleware\EnsureSyncTokenValid::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\EnsureGoogleAuthenticatorVerified::class,
            // Pemilihan Tahun Anggaran (permintaan user 2026-10-24) -
            // SENGAJA didaftarkan tepat SETELAH 2FA (lihat docblock
            // App\Http\Middleware\EnsureTahunSesiDipilih) supaya langkah
            // ini baru muncul SETELAH 2FA selesai.
            \App\Http\Middleware\EnsureTahunSesiDipilih::class,
            \App\Http\Middleware\ForcePasswordChange::class,
            \App\Http\Middleware\EnsureOnboardingComplete::class,
            \App\Http\Middleware\CatatBukaHalamanData::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
