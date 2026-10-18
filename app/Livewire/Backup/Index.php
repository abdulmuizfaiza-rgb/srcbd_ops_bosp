<?php

namespace App\Livewire\Backup;

use App\Models\Backup;
use App\Models\GoogleDriveSetting;
use App\Models\RestoreLog;
use App\Services\BackupService;
use App\Services\GoogleDriveService;
use App\Services\RestoreService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
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
 *
 * "Restore dari File di Laptop/PC" (BARU, 2026-10-18, permintaan user
 * "saya ingin tombol restore ada pilihan restore dari backup yang di
 * simpan di Laptop/PC komputer bukan hanya yang tersimpan di riwayat
 * backup") - lihat App\Services\BackupService::simpanUnggahan() untuk
 * keputusan bisnis/teknis lengkap (jawaban AskUserQuestion 2026-10-18).
 * Method "uploadBackupDariPc" di bawah HANYA menyimpan file yang
 * diunggah sebagai 1 baris Backup baru (ditandai `asal` = 'diunggah') -
 * TIDAK langsung menjalankan Restore, supaya tetap lewat 1 jalur
 * konfirmasi yang sama (ketik ulang nama file) seperti backup biasa.
 */
#[Layout('layouts.app')]
#[Title('Backup & Restore')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public const TAB_BACKUP = 'backup';

    public const TAB_RESTORE = 'restore';

    #[Url(as: 'tab')]
    public string $tabUtama = self::TAB_BACKUP;

    public string $linkDriveBaru = '';

    public function mount(): void
    {
        $this->restoreTahun = (string) ($this->daftarTahunOptions()[0] ?? '');
        $this->linkDriveBaru = GoogleDriveSetting::current()->folder_url ?? '';
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

    /**
     * Filter tanggal SPESIFIK (format Y-m-d, kosong = tidak difilter
     * tanggal) utk tabel "Riwayat Backup" - permintaan user 2026-10-14
     * "pencarian berdasarkan tanggal backup dan tanggal restore", jawaban
     * AskUserQuestion "Pilih 1 tanggal spesifik (kalender) (Recommended)".
     * Berlaku BERSAMA $filterTahun (kedua filter boleh aktif sekaligus).
     */
    public string $filterTanggalBackup = '';

    public function updatedFilterTanggalBackup(): void
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
        } finally {
            // Progress bar animasi (permintaan user 2026-10-14 "tambahkan
            // progres bar yang bergaya animasi untuk memastikan file backup
            // ... sukses terkirim") - event ini dipicu BERHASIL maupun
            // GAGAL, supaya Alpine di index.blade.php (lihat x-on:...-selesai
            // di <div> pembungkus utama) berhenti menghitung baik di jalur
            // sukses maupun gagal.
            $this->dispatch('backup-proses-selesai');
        }
    }

    // =========================================================================
    // Pengaturan Google Drive (BARU, permintaan user 2026-10-14) - lihat
    // App\Services\GoogleDriveService & App\Models\GoogleDriveSetting
    // untuk keputusan bisnis/teknis lengkap. Unggah OTOMATIS ke Drive utk
    // SETIAP backup baru sebenarnya terjadi di dalam BackupService (bukan
    // di sini) - 2 method di bawah ini HANYA mengurus pengaturan link
    // folder & tombol "Kirim Sekarang" (uji coba manual).
    // =========================================================================

    public function simpanLinkDrive(GoogleDriveService $driveService): void
    {
        abort_unless(auth()->user()->isSuperadmin(), 403);

        $this->linkDriveBaru = trim($this->linkDriveBaru);

        if ($this->linkDriveBaru === '') {
            GoogleDriveSetting::current()->update(['folder_url' => null, 'folder_id' => null]);
            session()->flash('status', 'Link Google Drive dikosongkan - unggah otomatis ke Drive dinonaktifkan.');

            return;
        }

        $folderId = $driveService->ekstrakFolderId($this->linkDriveBaru);

        if ($folderId === null) {
            $this->addError('linkDriveBaru', 'Link folder Google Drive tidak dikenali. Tempel link folder (hasil klik "Bagikan" pada folder di Google Drive), contoh: https://drive.google.com/drive/folders/xxxxxxxxxxxxxxxxxxxx');

            return;
        }

        GoogleDriveSetting::current()->update([
            'folder_url' => $this->linkDriveBaru,
            'folder_id' => $folderId,
        ]);

        session()->flash('status', 'Link Google Drive disimpan. Backup baru akan otomatis terunggah ke folder ini.');
    }

    /**
     * Tombol "Kirim Sekarang" - uji coba manual mengunggah backup PALING
     * BARU yang sudah ada ke Google Drive, tanpa perlu menunggu/membuat
     * backup baru lagi. Berguna utk memastikan pengaturan (link folder +
     * kredensial Service Account di server) sudah benar.
     */
    public function kirimSekarangKeGoogleDrive(GoogleDriveService $driveService): void
    {
        abort_unless(auth()->user()->isSuperadmin(), 403);

        $pengaturan = GoogleDriveSetting::current();

        if (! $pengaturan->sudahTerhubung()) {
            session()->flash('errorBackup', 'Hubungkan Google Drive terlebih dahulu (klik tombol "Hubungkan Google Drive" di atas) sebelum mengirim.');

            return;
        }

        if (empty($pengaturan->folder_id)) {
            session()->flash('errorBackup', 'Isi & simpan link folder Google Drive terlebih dahulu sebelum mengirim.');

            return;
        }

        $backupTerbaru = Backup::query()->orderByDesc('created_at')->first();

        if (! $backupTerbaru) {
            session()->flash('errorBackup', 'Belum ada file backup sama sekali - buat backup dulu lewat tombol "Buat Backup Sekarang" di atas.');

            return;
        }

        try {
            // Nama file DIBERI NOMOR URUT juga di jalur "Kirim Sekarang"
            // manual ini (konsisten dgn unggahan otomatis di
            // App\Services\BackupService) - lihat docblock
            // GoogleDriveService::namaBernomor().
            $nomor = $pengaturan->ambilNomorUrutBerikutnya();
            $namaDiDrive = $driveService->namaBernomor($nomor, $backupTerbaru->nama_file);

            $driveService->unggahFile(
                Storage::disk('local')->path($backupTerbaru->path),
                $namaDiDrive,
                $pengaturan->folder_id,
                $pengaturan->refresh_token
            );

            $pengaturan->update([
                'terakhir_kirim_pada' => now(),
                'terakhir_kirim_berhasil' => true,
                'pesan_error_terakhir' => null,
            ]);

            session()->flash('status', 'Berhasil mengirim "'.$backupTerbaru->nama_file.'" ke Google Drive.');
        } catch (Throwable $e) {
            report($e);

            $pengaturan->update([
                'terakhir_kirim_pada' => now(),
                'terakhir_kirim_berhasil' => false,
                'pesan_error_terakhir' => $e->getMessage(),
            ]);

            session()->flash('errorBackup', 'Gagal mengirim ke Google Drive: '.$e->getMessage());
        }
    }

    /**
     * Tombol "Putuskan Koneksi" - menghapus refresh_token yang tersimpan
     * (akun Google TIDAK diubah/dihapus apa pun di sisi Google, hanya
     * aplikasi ini yang "lupa" koneksinya). Backup baru TIDAK akan
     * terunggah otomatis lagi sampai "Hubungkan Google Drive" diulang.
     */
    public function putuskanKoneksiDrive(): void
    {
        abort_unless(auth()->user()->isSuperadmin(), 403);

        GoogleDriveSetting::current()->update([
            'refresh_token' => null,
            'email_terhubung' => null,
            'terhubung_pada' => null,
        ]);

        session()->flash('status', 'Koneksi Google Drive diputuskan.');
    }

    /**
     * Tombol "Urutkan Ulang Nomor File di Drive" - permintaan EKSPLISIT
     * user 2026-10-14 (jawaban AskUserQuestion "Beri nomor ulang ke SEMUA
     * file yang sudah ada juga", BUKAN pilihan default/Recommended) utk
     * memberi/menata ulang nomor urut pada file yang SUDAH ADA di Google
     * Drive (termasuk file yang diunggah SEBELUM fitur penomoran ini ada).
     * Mengurutkan berdasarkan waktu dibuat PALING LAMA -> PALING BARU,
     * lalu menomori 0001, 0002, dst - IDEMPOTEN (aman diklik berkali-kali,
     * lihat GoogleDriveService::stripNomorPrefix()). Counter
     * nomor_urut_berikutnya ikut disamakan di akhir supaya unggahan
     * BERIKUTNYA melanjutkan dari nomor yang benar.
     */
    public function urutkanUlangNomorDrive(GoogleDriveService $driveService): void
    {
        abort_unless(auth()->user()->isSuperadmin(), 403);

        $pengaturan = GoogleDriveSetting::current();

        if (! $pengaturan->sudahDikonfigurasi()) {
            session()->flash('errorBackup', 'Hubungkan Google Drive & isi link folder terlebih dahulu sebelum mengurutkan ulang.');

            return;
        }

        try {
            $daftarFile = $driveService->daftarFileDiFolder($pengaturan->folder_id, $pengaturan->refresh_token);

            $nomor = 1;

            foreach ($daftarFile as $file) {
                $namaBersih = $driveService->stripNomorPrefix($file['name']);
                $namaBaru = $driveService->namaBernomor($nomor, $namaBersih);

                if ($namaBaru !== $file['name']) {
                    $driveService->gantiNamaFile($file['id'], $namaBaru, $pengaturan->refresh_token);
                }

                $nomor++;
            }

            $pengaturan->update(['nomor_urut_berikutnya' => $nomor]);

            session()->flash('status', 'Berhasil mengurutkan ulang nomor '.count($daftarFile).' file di Google Drive.');
        } catch (Throwable $e) {
            report($e);
            session()->flash('errorBackup', 'Gagal mengurutkan ulang nomor file di Google Drive: '.$e->getMessage());
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

    // =========================================================================
    // "Restore dari File di Laptop/PC" (BARU, permintaan user 2026-10-18:
    // "saya ingin tombol restore ada pilihan restore dari backup yang di
    // simpan di Laptop/PC komputer bukan hanya yang tersimpan di riwayat
    // backup") - lihat App\Services\BackupService::simpanUnggahan() untuk
    // keputusan bisnis/teknis lengkap. File yang diunggah di sini TIDAK
    // langsung di-restore - cukup disimpan sebagai 1 baris Backup biasa
    // (muncul di daftar "Pilih Backup untuk Di-Restore" di bawah), supaya
    // proses Restore sesungguhnya TETAP lewat 1 jalur konfirmasi yang
    // SAMA (konfirmasiRestore()/jalankanRestore()) utk SEMUA backup, baik
    // yang dibuat aplikasi maupun yang diunggah - tidak ada jalur restore
    // terpisah/pintasan yang melewati langkah ketik-ulang-nama-file.
    // =========================================================================

    /** File .zip yang sedang diunggah Superadmin dari Laptop/PC-nya (Livewire WithFileUploads). */
    public $fileBackupUnggahan = null;

    public function uploadBackupDariPc(BackupService $backupService): void
    {
        abort_unless(auth()->user()->isSuperadmin(), 403);

        $this->resetErrorBag('fileBackupUnggahan');

        $this->validate([
            'fileBackupUnggahan' => ['required', 'file', 'max:512000', 'extensions:zip'],
        ], [], ['fileBackupUnggahan' => 'File backup']);

        try {
            $backup = $backupService->simpanUnggahan($this->fileBackupUnggahan, auth()->id());
        } catch (Throwable $e) {
            report($e);
            session()->flash('errorBackup', 'Gagal mengunggah file backup: '.$e->getMessage());
            $this->fileBackupUnggahan = null;
            $this->dispatch('backup-proses-selesai');

            return;
        }

        $this->fileBackupUnggahan = null;
        // Supaya baris yang baru saja diunggah langsung terlihat tanpa
        // user perlu mengganti filter tahun/tanggal secara manual.
        $this->restoreTahun = (string) $backup->tahun;
        $this->restoreTanggalBackup = '';
        session()->flash('status', 'File backup berhasil diunggah: '.$backup->nama_file.' ('.$backup->ukuranManusiawi().'). Silakan klik "Restore dari File Ini" pada baris barunya di bawah untuk melanjutkan proses Restore.');
        $this->dispatch('backup-proses-selesai');
    }

    /** Filter tanggal SPESIFIK (Y-m-d, kosong = tidak difilter) utk daftar "Pilih Backup untuk Di-Restore" - independen dari $filterTanggalBackup milik Tab Backup. */
    public string $restoreTanggalBackup = '';

    /** Filter tanggal SPESIFIK (Y-m-d, kosong = tidak difilter) utk tabel "Riwayat Restore Terakhir" - berdasarkan tanggal RESTORE dilakukan, bukan tanggal backup-nya. */
    public string $filterTanggalRestoreLog = '';

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
            $this->dispatch('restore-proses-selesai');

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
            $this->dispatch('restore-proses-selesai');

            return;
        }

        // Progress bar animasi (permintaan user 2026-10-14) - dipicu juga di
        // jalur SUKSES ini meski redirect ke halaman login akan langsung
        // menggantikan halaman ini; tetap dipicu demi konsistensi/keamanan
        // kalau redirect gagal/lambat.
        $this->dispatch('restore-proses-selesai');

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
            ->when($this->filterTanggalBackup !== '', fn ($q) => $q->whereDate('created_at', $this->filterTanggalBackup))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        $backupUntukRestore = Backup::query()
            ->with('dibuatOleh')
            ->when($this->restoreTahun !== '', fn ($q) => $q->where('tahun', $this->restoreTahun))
            ->when($this->restoreTanggalBackup !== '', fn ($q) => $q->whereDate('created_at', $this->restoreTanggalBackup))
            ->orderByDesc('created_at')
            ->get();

        $riwayatRestore = RestoreLog::query()
            ->with(['backup', 'backupPengaman', 'dilakukanOleh'])
            ->when($this->filterTanggalRestoreLog !== '', fn ($q) => $q->whereDate('created_at', $this->filterTanggalRestoreLog))
            ->latest()
            ->limit(10)
            ->get();

        return view('livewire.backup.index', [
            'backup' => $backup,
            'daftarTahun' => $this->daftarTahunOptions(),
            'backupUntukRestore' => $backupUntukRestore,
            'riwayatRestore' => $riwayatRestore,
            'backupDikonfirmasiRestore' => $this->confirmingRestoreId ? Backup::find($this->confirmingRestoreId) : null,
            'pengaturanDrive' => GoogleDriveSetting::current(),
        ]);
    }
}
