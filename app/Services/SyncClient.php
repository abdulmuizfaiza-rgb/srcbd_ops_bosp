<?php

namespace App\Services;

use App\Models\SyncSetting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Dipanggil HANYA di sisi LOKAL (config('sync.peran') === 'lokal') untuk
 * menghubungi API sisi Online lewat HTTP - permintaan user 2026-10-14
 * poin 3. Memakai Http facade bawaan Laravel (Guzzle) - TIDAK perlu
 * paket Composer tambahan apapun.
 *
 * URL & token Online diambil dari App\Models\SyncSetting (diisi
 * Superadmin di menu Sinkronisasi, DISALIN dari Token API yang
 * ditampilkan di menu Sinkronisasi sisi Online - lihat App\Models\
 * SyncToken::buatBaru()).
 */
class SyncClient
{
    /**
     * Kirim (push) data keluar Lokal ke Online - dipanggil saat tombol
     * "Sinkronisasi" ditekan. Mengembalikan ringkasan hasil dari Online.
     */
    public function kirim(array $payload): array
    {
        $pengaturan = SyncSetting::current();
        $this->pastikanTerkonfigurasi($pengaturan);

        $respons = Http::withToken($pengaturan->token_online)
            ->timeout(config('sync.timeout_detik'))
            ->asJson()
            ->post($this->urlApi($pengaturan, 'sync/push'), ['payload' => $payload]);

        if ($respons->failed()) {
            throw new RuntimeException(
                'Online menolak/gagal menerima kiriman data: '.($respons->json('message') ?? $respons->status())
            );
        }

        return $respons->json('ringkasan') ?? [];
    }

    /**
     * Ambil (pull) data dari Online - dipanggil saat tombol "Tarik
     * Data" ditekan. Mengembalikan payload LENGKAP dari Online (siap
     * diterapkan lewat App\Services\SyncEngine::terapkanDataMasuk()).
     */
    public function tarik(): array
    {
        $pengaturan = SyncSetting::current();
        $this->pastikanTerkonfigurasi($pengaturan);

        $respons = Http::withToken($pengaturan->token_online)
            ->timeout(config('sync.timeout_detik'))
            ->get($this->urlApi($pengaturan, 'sync/pull'));

        if ($respons->failed()) {
            throw new RuntimeException(
                'Online menolak/gagal menyajikan data untuk ditarik: '.($respons->json('message') ?? $respons->status())
            );
        }

        return $respons->json() ?? [];
    }

    /**
     * Tes koneksi sederhana (dipakai tombol "Tes Koneksi" di menu
     * Sinkronisasi) - TIDAK mengubah data apapun, hanya memastikan URL +
     * token valid dengan memanggil endpoint tarik (paling ringan: tidak
     * menulis apapun di sisi Online, cuma GET).
     */
    public function tesKoneksi(): bool
    {
        try {
            $this->tarik();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    protected function pastikanTerkonfigurasi(SyncSetting $pengaturan): void
    {
        if (empty($pengaturan->url_online) || empty($pengaturan->token_online)) {
            throw new RuntimeException('URL Online & Token API Online belum diisi di menu Sinkronisasi dan Tarik Data.');
        }
    }

    protected function urlApi(SyncSetting $pengaturan, string $path): string
    {
        return rtrim($pengaturan->url_online, '/').'/api/'.$path;
    }
}
