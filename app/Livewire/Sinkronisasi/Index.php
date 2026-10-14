<?php

namespace App\Livewire\Sinkronisasi;

use App\Livewire\Concerns\HasZoomTampilan;
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
    use HasZoomTampilan;

    public string $urlOnline = '';

    public string $tokenOnlineBaru = '';

    public ?string $tokenBaruDitampilkan = null;

    public bool $confirmingSinkronisasi = false;

    public bool $confirmingTarikData = false;

    /** Filter tanggal SPESIFIK (Y-m-d, kosong = tidak difilter) utk "Riwayat Sinkronisasi Terakhir" - permintaan user 2026-10-14. */
    public string $filterTanggalRiwayat = '';

    /**
     * State tombol Hapus "Riwayat Sinkronisasi Terakhir" (permintaan
     * user 2026-10-14) - pola SAMA dengan $dipilihLoginGagal/
     * $dipilihAksesData di App\Livewire\CekDatabaseAplikasi\Index:
     * diisi ulang via array_intersect() tiap render() supaya ID dari
     * filter sebelumnya tidak "nyangkut".
     *
     * @var array<int, int>
     */
    public array $dipilihRiwayat = [];

    public bool $confirmingHapusTerpilihRiwayat = false;

    public bool $confirmingHapusSemuaRiwayat = false;

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
        $this->urlOnline = trim($this->urlOnline);

        $this->validate(['urlOnline' => ['required', 'url']]);

        SyncSetting::current()->update(['url_online' => rtrim($this->urlOnline, '/')]);

        session()->flash('sinkronisasi_success', 'URL Online disimpan.');
    }

    public function simpanTokenOnline(): void
    {
        // trim() - token yang ditempel dari clipboard seringkali ikut
        // membawa spasi/baris-baru tak sengaja, yang membuat hash token
        // tidak cocok saat dicek App\Models\SyncToken::cocok() di sisi
        // Online meski token aslinya benar (ditemukan saat uji coba
        // Tes Koneksi 2026-10-14).
        $this->tokenOnlineBaru = trim($this->tokenOnlineBaru);

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
        $this->dispatch('open-modal', 'sinkronisasi-konfirmasi-kirim');
    }

    public function batalSinkronisasi(): void
    {
        $this->confirmingSinkronisasi = false;
        $this->dispatch('close-modal', 'sinkronisasi-konfirmasi-kirim');
    }

    public function jalankanSinkronisasi(SyncEngine $syncEngine, SyncClient $syncClient): void
    {
        $this->confirmingSinkronisasi = false;
        $this->dispatch('close-modal', 'sinkronisasi-konfirmasi-kirim');

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
        } finally {
            // Memberi tahu browser (lewat Alpine di Blade) bahwa proses
            // sudah selesai (berhasil ATAU gagal), supaya progress bar +
            // penghitung waktu berjalan di modal bisa berhenti - lihat
            // resources/views/livewire/sinkronisasi/index.blade.php.
            $this->dispatch('sinkronisasi-proses-selesai');
        }
    }

    public function konfirmasiTarikData(): void
    {
        $this->confirmingTarikData = true;
        $this->dispatch('open-modal', 'sinkronisasi-konfirmasi-tarik');
    }

    public function batalTarikData(): void
    {
        $this->confirmingTarikData = false;
        $this->dispatch('close-modal', 'sinkronisasi-konfirmasi-tarik');
    }

    public function jalankanTarikData(SyncEngine $syncEngine, SyncClient $syncClient): void
    {
        $this->confirmingTarikData = false;
        $this->dispatch('close-modal', 'sinkronisasi-konfirmasi-tarik');

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
        } finally {
            $this->dispatch('tarik-data-proses-selesai');
        }
    }

    /**
     * Query dasar "Riwayat Sinkronisasi Terakhir" (sudah termasuk filter
     * tanggal aktif, TANPA urutan/limit) - dipakai bareng oleh render(),
     * toggleSemuaRiwayat(), dan hapusSemuaRiwayat() (permintaan user
     * 2026-10-14), supaya logika filter HANYA ditulis 1 kali di 1
     * tempat - pola sama dengan App\Livewire\CekDatabaseAplikasi\Index
     * ::queryLoginGagal().
     */
    private function queryRiwayat()
    {
        return SyncLog::query()
            ->when($this->filterTanggalRiwayat !== '', fn ($q) => $q->whereDate('created_at', $this->filterTanggalRiwayat));
    }

    public function updatedFilterTanggalRiwayat(): void
    {
        $this->dipilihRiwayat = [];
    }

    /**
     * Centang/batal-centang SEMUA baris yang SEDANG TAMPIL (latest 15
     * sesuai filter aktif - menu ini tidak memakai pagination, jadi
     * "halaman ini" = seluruh baris yang tampil). Pola sama dengan
     * App\Livewire\CekDatabaseAplikasi\Index::toggleSemuaLoginGagal().
     */
    public function toggleSemuaRiwayat(): void
    {
        $idTampil = $this->queryRiwayat()->latest()->limit(15)->pluck('id')->all();

        if (count($idTampil) > 0 && count(array_diff($idTampil, $this->dipilihRiwayat)) === 0) {
            $this->dipilihRiwayat = array_values(array_diff($this->dipilihRiwayat, $idTampil));
        } else {
            $this->dipilihRiwayat = array_values(array_unique(array_merge($this->dipilihRiwayat, $idTampil)));
        }
    }

    public function konfirmasiHapusTerpilihRiwayat(): void
    {
        if (empty($this->dipilihRiwayat)) {
            return;
        }

        $this->confirmingHapusTerpilihRiwayat = true;
        $this->dispatch('open-modal', 'sinkronisasi-riwayat-hapus-terpilih');
    }

    public function batalHapusTerpilihRiwayat(): void
    {
        $this->confirmingHapusTerpilihRiwayat = false;
        $this->dispatch('close-modal', 'sinkronisasi-riwayat-hapus-terpilih');
    }

    public function hapusTerpilihRiwayat(): void
    {
        $jumlah = SyncLog::whereIn('id', $this->dipilihRiwayat)->count();
        SyncLog::whereIn('id', $this->dipilihRiwayat)->delete();

        $this->dipilihRiwayat = [];
        $this->confirmingHapusTerpilihRiwayat = false;
        $this->dispatch('close-modal', 'sinkronisasi-riwayat-hapus-terpilih');
        session()->flash('sinkronisasi_success', "Berhasil menghapus {$jumlah} baris riwayat sinkronisasi yang dipilih.");
    }

    public function konfirmasiHapusSemuaRiwayat(): void
    {
        $this->confirmingHapusSemuaRiwayat = true;
        $this->dispatch('open-modal', 'sinkronisasi-riwayat-hapus-semua');
    }

    public function batalHapusSemuaRiwayat(): void
    {
        $this->confirmingHapusSemuaRiwayat = false;
        $this->dispatch('close-modal', 'sinkronisasi-riwayat-hapus-semua');
    }

    /**
     * Hapus SEMUA baris Riwayat Sinkronisasi yang cocok dengan filter
     * tanggal AKTIF saat ini (bukan hanya latest 15 yang tampil - kalau
     * tidak ada filter aktif, berarti benar-benar semua baris). Pola
     * sama dengan App\Livewire\CekDatabaseAplikasi\Index::
     * hapusSemuaLoginGagal() - reorder() dulu sebelum delete() karena
     * DELETE tidak mendukung ORDER BY.
     */
    public function hapusSemuaRiwayat(): void
    {
        $jumlah = $this->queryRiwayat()->count();
        $this->queryRiwayat()->reorder()->delete();

        $this->dipilihRiwayat = [];
        $this->confirmingHapusSemuaRiwayat = false;
        $this->dispatch('close-modal', 'sinkronisasi-riwayat-hapus-semua');
        session()->flash('sinkronisasi_success', "Berhasil menghapus {$jumlah} baris riwayat sinkronisasi.");
    }

    public function render()
    {
        $riwayat = $this->queryRiwayat()->with(['backupPengaman', 'dilakukanOleh'])->latest()->limit(15)->get();

        $idTampil = $riwayat->pluck('id')->all();
        $this->dipilihRiwayat = array_values(array_intersect($this->dipilihRiwayat, $idTampil));
        $semuaTerpilihRiwayat = count($idTampil) > 0 && count(array_diff($idTampil, $this->dipilihRiwayat)) === 0;

        return view('livewire.sinkronisasi.index', [
            'pengaturan' => SyncSetting::current(),
            'riwayat' => $riwayat,
            'semuaTerpilihRiwayat' => $semuaTerpilihRiwayat,
            'tokenAktif' => SyncToken::query()->whereNull('dicabut_pada')->latest()->first(),
        ]);
    }
}
