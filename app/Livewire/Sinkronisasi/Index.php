<?php

namespace App\Livewire\Sinkronisasi;

use App\Models\SyncLog;
use App\Models\SyncSetting;
use App\Models\SyncToken;
use App\Services\SyncClient;
use App\Services\SyncEngine;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

/**
 * Menu "Sinkronisasi dan Tarik Data" (permintaan user 2026-10-14 poin
 * 3) - khusus Superadmin (lihat Gate 'akses-sinkronisasi' di
 * AppServiceProvider), tampil di LOGIN SUPERADMIN BAIK di instalasi
 * Lokal MAUPUN Online, tapi tombol aksi HANYA aktif di Lokal (lihat
 * config('sync.peran') - jawaban AskUserQuestion sebelumnya: Online
 * tidak bisa memulai koneksi balik ke Lokal).
 *
 * - Sisi LOKAL: form URL + Token API Online, tombol Tes Koneksi,
 *   Sinkronisasi (kirim), Tarik Data (ambil).
 * - Sisi ONLINE: tombol "Buat/Ganti Token API" (ditampilkan SEKALI saat
 *   dibuat - lihat App\Models\SyncToken::buatBaru()), tombol
 *   Sinkronisasi/Tarik Data TAMPIL TAPI NONAKTIF dengan keterangan
 *   "jalankan dari Aplikasi Lokal".
 * - Riwayat Sinkronisasi (App\Models\SyncLog) tampil di KEDUA sisi.
 */
#[Layout('layouts.app')]
#[Title('Sinkronisasi dan Tarik Data')]
class Index extends Component
{
    public string $urlOnline = '';

    public string $tokenOnlineBaru = '';

    public ?string $tokenBaruDitampilkan = null;

    public bool $confirmingSinkronisasi = false;

    public bool $confirmingTarikData = false;

    public function mount(): void
    {
        $this->urlOnline = SyncSetting::current()->url_online ?? '';
    }

    public function peran(): string
    {
        return (string) config('sync.peran');
    }

    public function isLokal(): bool
    {
        return $this->peran() === 'lokal';
    }

    public function simpanUrlOnline(): void
    {
        $this->validate(['urlOnline' => ['required', 'url']]);

        SyncSetting::current()->update(['url_online' => rtrim($this->urlOnline, '/')]);

        session()->flash('sinkronisasi_success', 'URL Online disimpan.');
    }

    public function simpanTokenOnline(): void
    {
        $this->validate(['tokenOnlineBaru' => ['required', 'string', 'min:10']]);

        SyncSetting::current()->update(['token_online' => $this->tokenOnlineBaru]);
        $this->tokenOnlineBaru = '';

        session()->flash('sinkronisasi_success', 'Token API Online disimpan.');
    }

    public function tesKoneksi(SyncClient $client): void
    {
        $berhasil = $client->tesKoneksi();

        SyncSetting::current()->update([
            'terakhir_tes_koneksi_pada' => now(),
            'terakhir_tes_koneksi_berhasil' => $berhasil,
        ]);

        session()->flash(
            $berhasil ? 'sinkronisasi_success' : 'sinkronisasi_error',
            $berhasil ? 'Koneksi ke Online berhasil.' : 'Koneksi ke Online GAGAL - cek URL & Token API, lalu coba lagi.'
        );
    }

    /**
     * Hanya berarti di sisi ONLINE - buat/ganti Token API yang nanti
     * disalin Superadmin ke pengaturan sisi Lokal. Token ASLI hanya
     * tampil SEKALI di $tokenBaruDitampilkan (lihat Blade view -
     * peringatan "catat sekarang").
     */
    public function buatTokenBaru(): void
    {
        $this->tokenBaruDitampilkan = SyncToken::buatBaru(Auth::id());
    }

    public function konfirmasiSinkronisasi(): void
    {
        $this->confirmingSinkronisasi = true;
    }

    public function batalSinkronisasi(): void
    {
        $this->confirmingSinkronisasi = false;
    }

    public function jalankanSinkronisasi(SyncEngine $syncEngine, SyncClient $syncClient): void
    {
        $this->confirmingSinkronisasi = false;

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        try {
            $ringkasan = $syncClient->kirim($syncEngine->kumpulkanDataKeluar());

            SyncLog::create([
                'arah' => SyncLog::ARAH_KIRIM,
                'status' => SyncLog::STATUS_BERHASIL,
                'ringkasan' => $ringkasan,
                'dilakukan_oleh_id' => Auth::id(),
            ]);

            session()->flash('sinkronisasi_success', 'Sinkronisasi berhasil dikirim ke Online.');
        } catch (Throwable $e) {
            SyncLog::create([
                'arah' => SyncLog::ARAH_KIRIM,
                'status' => SyncLog::STATUS_GAGAL,
                'pesan_error' => $e->getMessage(),
                'dilakukan_oleh_id' => Auth::id(),
            ]);

            session()->flash('sinkronisasi_error', 'Sinkronisasi gagal: '.$e->getMessage());
        }
    }

    public function konfirmasiTarikData(): void
    {
        $this->confirmingTarikData = true;
    }

    public function batalTarikData(): void
    {
        $this->confirmingTarikData = false;
    }

    public function jalankanTarikData(SyncEngine $syncEngine, SyncClient $syncClient): void
    {
        $this->confirmingTarikData = false;

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        try {
            $payload = $syncClient->tarik();
            $hasil = $syncEngine->terapkanDataMasuk($payload, Auth::id(), 'Tarik Data (mengambil dari Online)');

            SyncLog::create([
                'arah' => SyncLog::ARAH_TARIK,
                'status' => SyncLog::STATUS_BERHASIL,
                'ringkasan' => $hasil['ringkasan'],
                'backup_pengaman_id' => $hasil['backup_pengaman_id'],
                'dilakukan_oleh_id' => Auth::id(),
            ]);

            session()->flash('sinkronisasi_success', 'Tarik Data berhasil diterapkan ke database Lokal ini.');
        } catch (Throwable $e) {
            SyncLog::create([
                'arah' => SyncLog::ARAH_TARIK,
                'status' => SyncLog::STATUS_GAGAL,
                'pesan_error' => $e->getMessage(),
                'dilakukan_oleh_id' => Auth::id(),
            ]);

            session()->flash('sinkronisasi_error', 'Tarik Data gagal: '.$e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.sinkronisasi.index', [
            'pengaturan' => SyncSetting::current(),
            'riwayat' => SyncLog::with(['backupPengaman', 'dilakukanOleh'])->latest()->limit(15)->get(),
            'tokenAktif' => SyncToken::query()->whereNull('dicabut_pada')->latest()->first(),
        ]);
    }
}
