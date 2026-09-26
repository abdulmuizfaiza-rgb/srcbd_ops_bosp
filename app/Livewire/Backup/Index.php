<?php

namespace App\Livewire\Backup;

use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/**
 * Menu "Backup" (BARU, round DUA PULUH LIMA, 2026-09-24, poin 2,
 * permintaan user "buatkan menu backup berdasarkan tahun untuk semua data
 * yang ada pada aplikasi yang terdiri dari Aplikasi nya dan database nya
 * yang terbaru yang berhubungan dengan aplikasinya"). Setiap kali tombol
 * "Buat Backup Sekarang" diklik, App\Services\BackupService membuat SATU
 * paket .zip baru (kode aplikasi + dump database) & mencatatnya di sini.
 *
 * Keputusan bisnis via AskUserQuestion (2026-09-24) - lihat juga docblock
 * migration create_backups_table & App\Services\BackupService:
 * - "Cakupan Backup" -> "Kode aplikasi (di-zip) + dump database terbaru
 *   (Recommended)".
 * - "Penyimpanan & Akses" -> "Tersimpan di server, terdaftar per tahun,
 *   khusus Superadmin (Recommended)": daftar backup di sini bisa
 *   DIFILTER per tahun (filterTahun), & menu ini HANYA bisa dibuka
 *   Superadmin (Gate 'akses-backup' di App\Providers\AppServiceProvider).
 *
 * Backup dibuat SECARA SINKRON saat tombol diklik (bukan lewat antrean
 * queue) - permintaan user hanya menyebut "menu" (aksi lewat UI), bukan
 * penjadwalan otomatis, jadi TIDAK ditambahkan sistem penjadwalan/queue
 * yang tidak diminta. Utk kode aplikasi yang sangat besar, proses ini bisa
 * memakan waktu cukup lama - lihat catatan di PETUNJUK-UPDATE round ini.
 */
#[Layout('layouts.app')]
#[Title('Backup')]
class Index extends Component
{
    use WithPagination;

    /** Filter tahun (kosong = tampilkan semua tahun). */
    public string $filterTahun = '';

    public function updatedFilterTahun(): void
    {
        $this->resetPage();
    }

    public function buatBackup(BackupService $backupService): void
    {
        try {
            $backup = $backupService->buat(auth()->id());
            session()->flash('status', 'Backup berhasil dibuat: '.$backup->nama_file.' ('.$backup->ukuranManusiawi().').');
        } catch (Throwable $e) {
            report($e);
            session()->flash('errorBackup', 'Gagal membuat backup: '.$e->getMessage());
        }
    }

    /**
     * Kirim daftar URL unduh SEMUA backup yang sedang cocok dengan filter
     * Tahun aktif (BUKAN cuma yang tampil di halaman pagination saat ini)
     * ke browser lewat event, supaya JS di index.blade.php bisa memicu
     * unduhan file-file itu SATU PER SATU secara otomatis (permintaan
     * user 2026-09-26, jawaban AskUserQuestion "Unduh satu-satu otomatis
     * (multi-download)" - BUKAN digabung jadi 1 file zip di server).
     */
    public function unduhSemua(): void
    {
        $urls = Backup::query()
            ->when($this->filterTahun !== '', fn ($q) => $q->where('tahun', $this->filterTahun))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Backup $backup) => route('backup.unduh', $backup))
            ->values()
            ->all();

        if (empty($urls)) {
            return;
        }

        $this->dispatch('unduh-semua-backup', urls: $urls);
    }

    public function hapus(int $id): void
    {
        $backup = Backup::find($id);

        if (! $backup) {
            return;
        }

        Storage::disk('local')->delete($backup->path);
        $backup->delete();

        session()->flash('status', 'Backup berhasil dihapus.');
    }

    /** Daftar tahun utk opsi filter - dari data yang ada, SELALU menyertakan tahun berjalan meskipun belum ada backup-nya. */
    public function daftarTahunOptions(): array
    {
        $tahunData = Backup::query()->distinct()->orderByDesc('tahun')->pluck('tahun')->all();
        $tahunSekarang = (int) now()->format('Y');

        return collect($tahunData)->push($tahunSekarang)->unique()->sortDesc()->values()->all();
    }

    public function render()
    {
        $backup = Backup::query()
            ->with('dibuatOleh')
            ->when($this->filterTahun !== '', fn ($q) => $q->where('tahun', $this->filterTahun))
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.backup.index', [
            'backup' => $backup,
            'daftarTahun' => $this->daftarTahunOptions(),
        ]);
    }
}
