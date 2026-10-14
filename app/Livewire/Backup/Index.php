<?php

namespace App\Livewire\Backup;

use App\Models\Backup;
use App\Models\RestoreLog;
use App\Services\BackupService;
use App\Services\RestoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
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
 *
 * TAB "Restore Data & Aplikasi" (BARU, permintaan user 2026-10-14 poin 2:
 * "menu backup di rubah menjadi Backup & Restore ... Tab 2 Restore Data &
 * Aplikasi yang dibuat berdasarkan Tahun") - lihat App\Services\
 * RestoreService untuk seluruh keputusan bisnis & teknis lengkap (scope
 * restore, pengamanan, alasan kenapa sesi login dipaksa logout sesudahnya
 * dst). Method-method di bawah ("konfirmasiRestore", "batalRestore",
 * "jalankanRestore") HANYA mengurus lapisan UI (buka/tutup modal,
 * validasi ketik-ulang nama file) - eksekusi sesungguhnya didelegasikan
 * ke RestoreService::restore().
 */
#[Layout('layouts.app')]
#[Title('Backup & Restore')]
class Index extends Component
{
    use WithPagination;

    public const TAB_BACKUP = 'backup';

    public const TAB_RESTORE = 'restore';

    #[Url(as: 'tab')]
    public string $tabUtama = self::TAB_BACKUP;

    public function mount(): void
    {
        $this->restoreTahun = (string) ($this->daftarTahunOptions()[0] ?? '');
    }

    public function pindahTabUtama(string $tab): void
    {
        if (! in_array($tab, [self::TAB_BACKUP, self::TAB_RESTORE], true)) {
            return;
        }

        $this->tabUtama = $tab;
    }

    /** Filter tahun (kosong = tampilkan semua tahun). */
    public string $filterTahun = '';

    public function updatedFilterTahun(): void
    {
        $this->resetPage();
    }

    public int $perPage = 10;

    public function updatedPerPage(): void
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

    // =========================================================================
    // TAB "Restore Data & Aplikasi" (BARU) - lihat docblock kelas & App\
    // Services\RestoreService untuk keputusan bisnis/teknis lengkap.
    // =========================================================================

    /** Tahun yang dipilih di Tab Restore (daftar backup difilter berdasarkan ini) - independen dari $filterTahun milik Tab Backup. */
    public string $restoreTahun = '';

    /** ID backup yang SEDANG dikonfirmasi utk di-restore (modal konfirmasi terbuka) - null kalau tidak ada. */
    public ?int $confirmingRestoreId = null;

    /** Nilai ketikan user pada kotak "ketik ulang nama file" - harus PERSIS sama dgn nama_file backup yg dipilih sebelum tombol Restore bisa dijalankan. */
    public string $konfirmasiNamaFile = '';

    public function konfirmasiRestore(int $id): void
    {
        abort_unless(auth()->user()->isSuperadmin(), 403);

        $this->confirmingRestoreId = $id;
        $this->konfirmasiNamaFile = '';
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'backup-konfirmasi-restore');
    }

    public function batalRestore(): void
    {
        $this->confirmingRestoreId = null;
        $this->konfirmasiNamaFile = '';
        $this->resetErrorBag();
        $this->dispatch('close-modal', 'backup-konfirmasi-restore');
    }

    /**
     * Jalankan restore SESUNGGUHNYA (lewat RestoreService) SETELAH
     * memastikan user mengetik ulang nama file dengan PERSIS benar -
     * lapisan konfirmasi terakhir sebelum tindakan yang berpotensi
     * sangat merusak/tidak bisa dibatalkan ini benar-benar dijalankan.
     */
    public function jalankanRestore(RestoreService $restoreService): void
    {
        abort_unless(auth()->user()->isSuperadmin(), 403);

        $backup = Backup::findOrFail($this->confirmingRestoreId);

        if (trim($this->konfirmasiNamaFile) !== $backup->nama_file) {
            $this->addError('konfirmasiNamaFile', 'Nama file yang Anda ketik tidak sama persis dengan "'.$backup->nama_file.'". Ketik ulang dengan benar untuk melanjutkan.');

            return;
        }

        try {
            $restoreService->restore($backup, auth()->id());
        } catch (Throwable $e) {
            report($e);
            session()->flash('errorBackup', 'Proses Restore dibatalkan/gagal: '.$e->getMessage());
            $this->confirmingRestoreId = null;
            $this->konfirmasiNamaFile = '';
            $this->dispatch('close-modal', 'backup-konfirmasi-restore');

            return;
        }

        // PENTING: database (termasuk tabel sessions) baru saja diganti
        // TOTAL oleh restore di atas - sesi login yang sedang berjalan
        // ini tidak lagi bisa dipercaya kondisinya. Paksa logout &
        // redirect ke halaman login (lihat docblock App\Services\
        // RestoreService bagian "KEAMANAN SESI").
        Auth::guard('web')->logout();
        Session::invalidate();
        Session::regenerateToken();

        session()->flash('status', 'Restore dari "'.$backup->nama_file.'" berhasil. Backup otomatis (jaring pengaman) dari kondisi SEBELUM restore sudah dibuat & tersimpan di Tab "Backup Data & Aplikasi". Silakan login kembali - kalau sejak backup ini dibuat ada migrasi database terbaru pada kode aplikasi, jalankan "php artisan migrate" setelah login kembali.');

        $this->redirect(route('login'), navigate: false);
    }

    public function render()
    {
        $backup = Backup::query()
            ->with('dibuatOleh')
            ->when($this->filterTahun !== '', fn ($q) => $q->where('tahun', $this->filterTahun))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        $backupUntukRestore = Backup::query()
            ->with('dibuatOleh')
            ->when($this->restoreTahun !== '', fn ($q) => $q->where('tahun', $this->restoreTahun))
            ->orderByDesc('created_at')
            ->get();

        $riwayatRestore = RestoreLog::query()
            ->with(['backup', 'backupPengaman', 'dilakukanOleh'])
            ->latest()
            ->limit(10)
            ->get();

        return view('livewire.backup.index', [
            'backup' => $backup,
            'daftarTahun' => $this->daftarTahunOptions(),
            'backupUntukRestore' => $backupUntukRestore,
            'riwayatRestore' => $riwayatRestore,
            'backupDikonfirmasiRestore' => $this->confirmingRestoreId ? Backup::find($this->confirmingRestoreId) : null,
        ]);
    }
}
