<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\RestoreLog;
use App\Services\Concerns\ResolvesExternalBinary;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Restore DATABASE (HANYA database - TIDAK menyentuh kode/source aplikasi
 * sama sekali) dari salah satu paket backup yang pernah dibuat
 * App\Services\BackupService - menu "Backup & Restore" tab "Restore Data
 * & Aplikasi" (permintaan user 2026-10-14 poin 2: "menu backup di rubah
 * menjadi Backup & Restore ... Tab 2 Restore Data & Aplikasi yang dibuat
 * berdasarkan Tahun").
 *
 * Keputusan bisnis via AskUserQuestion (2026-10-14):
 * - "Scope Restore" -> "Database saja (Recommended)": TIDAK memulihkan
 *   kode aplikasi dari dalam zip backup (folder "aplikasi/" di dalamnya
 *   diabaikan sama sekali di sini) - menimpa file aplikasi yang SEDANG
 *   BERJALAN dari dalam aplikasi itu sendiri dianggap terlalu berisiko.
 * - "Pengamanan Restore" -> "Backup otomatis dulu + ketik konfirmasi":
 *   method restore() SELALU membuat 1 backup baru (lewat BackupService)
 *   dari kondisi SAAT INI SEBELUM menyentuh database sama sekali, sebagai
 *   jaring pengaman - kalau pembuatan backup pengaman ini SENDIRI gagal,
 *   seluruh proses restore DIBATALKAN TOTAL (exception dilempar SEBELUM
 *   baris kode manapun yang mengubah database dijalankan). Konfirmasi
 *   ketik ulang nama file dicek di App\Livewire\Backup\Index (lapisan
 *   UI), BUKAN di sini - method restore() ini SENDIRI tidak melakukan
 *   konfirmasi apa pun, murni eksekusi.
 * - "Arti Restore per Tahun" -> dikonfirmasi: 1 file backup = snapshot
 *   UTUH seluruh database (bukan data khusus 1 tahun) - restore SELALU
 *   mengganti TOTAL seluruh isi database saat ini dengan isi dump di
 *   dalam file backup yang dipilih (lewat DROP SCHEMA public CASCADE +
 *   CREATE SCHEMA public, SEBELUM menjalankan ulang dump-nya) - BUKAN
 *   restore sebagian/data tahun tertentu saja.
 *
 * PENTING (dicatat di semua panduan update terkait juga): karena tabel
 * `migrations` ikut di-restore ke kondisi SAAT backup dibuat, kalau ada
 * migration BARU yang berjalan SETELAH backup itu dibuat, migration-
 * migration itu TIDAK akan tercatat lagi setelah restore (skema database
 * ikut kembali ke versi lama) - Superadmin WAJIB jalankan
 * `php artisan migrate` lagi setelah restore kalau kode aplikasi saat ini
 * sudah lebih baru dari saat backup itu dibuat.
 *
 * KEAMANAN SESI: DROP SCHEMA ikut menghapus tabel `sessions` (driver
 * session aplikasi ini = 'database', lihat config/session.php) - sesi
 * login Superadmin yang SEDANG menjalankan restore ini otomatis TIDAK
 * BISA dipercaya lagi setelah database diganti total. App\Livewire\
 * Backup\Index SELALU memaksa logout + redirect ke halaman login setelah
 * restore berhasil (lihat jalankanRestore()) - BUKAN tanggung jawab
 * service ini.
 */
class RestoreService
{
    use ResolvesExternalBinary;

    public function __construct(protected BackupService $backupService) {}

    public function restore(Backup $backup, ?int $dilakukanOlehId = null): RestoreLog
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        if (! Storage::disk('local')->exists($backup->path)) {
            throw new RuntimeException('File backup tidak ditemukan di server (mungkin sudah dipindah/dihapus manual): '.$backup->path);
        }

        // Jaring pengaman WAJIB (jawaban AskUserQuestion "Backup otomatis
        // dulu + ketik konfirmasi") - kalau baris ini sendiri gagal/throw,
        // method ini ikut throw & BERHENTI DI SINI JUGA - baris-baris di
        // bawah (yang mengubah database) TIDAK PERNAH dijalankan sama
        // sekali, jadi database masih 100% utuh seperti semula.
        $backupPengaman = $this->backupService->buat($dilakukanOlehId);
        $backupPengaman->forceFill([
            'catatan' => 'Backup otomatis (jaring pengaman) sebelum Restore ke "'.$backup->nama_file.'".',
        ])->save();

        try {
            $sql = $this->ekstrakDumpDariZip($backup);
            $this->restoreDatabase($sql);

            return RestoreLog::create([
                'backup_id' => $backup->id,
                'backup_pengaman_id' => $backupPengaman->id,
                'dilakukan_oleh_id' => $dilakukanOlehId,
                'status' => RestoreLog::STATUS_BERHASIL,
            ]);
        } catch (Throwable $e) {
            RestoreLog::create([
                'backup_id' => $backup->id,
                'backup_pengaman_id' => $backupPengaman->id,
                'dilakukan_oleh_id' => $dilakukanOlehId,
                'status' => RestoreLog::STATUS_GAGAL,
                'pesan_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function ekstrakDumpDariZip(Backup $backup): string
    {
        $pathAbsolut = Storage::disk('local')->path($backup->path);

        $zip = new ZipArchive;

        if ($zip->open($pathAbsolut) !== true) {
            throw new RuntimeException('Gagal membuka file backup (zip rusak/tidak valid): '.$backup->nama_file);
        }

        $sql = $zip->getFromName('database/database.sql');
        $zip->close();

        if ($sql === false || trim((string) $sql) === '') {
            throw new RuntimeException('File backup ini tidak berisi dump database yang valid ("database/database.sql" tidak ditemukan di dalam zip): '.$backup->nama_file);
        }

        return $sql;
    }

    protected function restoreDatabase(string $sql): void
    {
        $driver = config('database.default');

        match ($driver) {
            'pgsql' => $this->restorePgsql($sql),
            'mysql' => $this->restoreMysql($sql),
            'sqlite' => $this->restoreSqlite($sql),
            default => throw new RuntimeException("Driver database '{$driver}' belum didukung untuk restore otomatis."),
        };
    }

    /**
     * Sama seperti BackupService::dumpPgsql() - Windows (lokal) TIDAK
     * memakai binary psql.exe sama sekali (konsisten dengan keputusan
     * round 28 yang berhenti memakai pg_dump.exe di Windows krn masalah
     * CryptoAPI restrict-key yang tidak bisa diperbaiki dari sisi kode -
     * masalah yang sama berpotensi juga menimpa psql.exe), dump yang
     * dibuat Windows SENDIRI memakai dumpPgsqlViaPdo() (murni statement
     * SQL biasa, TANPA blok COPY FROM STDIN) jadi aman dieksekusi lewat
     * PDO::exec(). Server ONLINE (Linux) pakai psql asli - dump yang
     * dibuat pg_dump format plain (-F p) SECARA DEFAULT memakai blok
     * "COPY ... FROM STDIN" untuk data (BUKAN statement INSERT biasa) -
     * PDO::exec() TIDAK BISA menjalankan protokol COPY FROM STDIN ini,
     * HARUS lewat client psql asli yang memang mendukungnya secara
     * native.
     */
    protected function restorePgsql(string $sql): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->restorePgsqlViaPdo($sql);

            return;
        }

        $this->restorePgsqlViaPsql($sql);
    }

    /**
     * Restore lewat PDO (Windows/lokal) - DROP SCHEMA + CREATE SCHEMA +
     * seluruh isi dump dijalankan di DALAM SATU transaksi eksplisit
     * (beginTransaction/commit/rollBack) - DDL di PostgreSQL SEPENUHNYA
     * transaksional, jadi kalau ADA SAJA bagian dump yang gagal di
     * tengah jalan, SELURUHNYA (termasuk DROP SCHEMA di awal) otomatis
     * dibatalkan & database kembali ke kondisi SEBELUM restore dicoba -
     * BUKAN tertinggal dalam keadaan kosong/separuh jalan.
     */
    protected function restorePgsqlViaPdo(string $sql): void
    {
        $pdo = DB::connection()->getPdo();

        $pdo->beginTransaction();

        try {
            $pdo->exec('DROP SCHEMA public CASCADE; CREATE SCHEMA public;');
            $pdo->exec($sql);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw new RuntimeException('Gagal menjalankan restore database (PDO): '.$e->getMessage());
        }
    }

    /**
     * Restore lewat binary psql (Linux/online) - "--single-transaction"
     * membungkus SELURUH skrip (DROP SCHEMA + CREATE SCHEMA + isi dump)
     * jadi SATU transaksi, "-v ON_ERROR_STOP=1" menghentikan psql pada
     * kesalahan PERTAMA (supaya transaksinya batal/rollback otomatis,
     * bukan terus lanjut menjalankan sisa skrip yang sudah pasti gagal).
     */
    protected function restorePgsqlViaPsql(string $sql): void
    {
        $c = config('database.connections.pgsql');

        $binary = $this->resolveBinary('psql', config('backup.psql_path'), []);

        $skrip = "DROP SCHEMA public CASCADE;\nCREATE SCHEMA public;\n".$sql;

        $hasil = Process::env(['PGPASSWORD' => (string) ($c['password'] ?? '')])
            ->input($skrip)
            ->run([
                $binary,
                '-h', (string) ($c['host'] ?? '127.0.0.1'),
                '-p', (string) ($c['port'] ?? 5432),
                '-U', (string) ($c['username'] ?? ''),
                '-d', (string) ($c['database'] ?? ''),
                '-v', 'ON_ERROR_STOP=1',
                '--single-transaction',
            ]);

        if ($hasil->failed()) {
            throw new RuntimeException(
                'Gagal menjalankan psql untuk restore: '.$hasil->errorOutput()
                .' Kalau psql belum terdaftar di PATH, isi BACKUP_PSQL_PATH di file .env dengan path lengkap ke binary psql.'
            );
        }
    }

    /** @see BackupService::dumpMysql() - mysql ikut didukung untuk konsistensi, walau instalasi aplikasi ini memakai PostgreSQL. */
    protected function restoreMysql(string $sql): void
    {
        $c = config('database.connections.mysql');

        $binary = $this->resolveBinary('mysql', config('backup.mysql_path'), []);

        $hasil = Process::env(['MYSQL_PWD' => (string) ($c['password'] ?? '')])
            ->input($sql)
            ->run([
                $binary,
                '-h', (string) ($c['host'] ?? '127.0.0.1'),
                '-P', (string) ($c['port'] ?? 3306),
                '-u', (string) ($c['username'] ?? ''),
                (string) ($c['database'] ?? ''),
            ]);

        if ($hasil->failed()) {
            throw new RuntimeException('Gagal menjalankan mysql untuk restore: '.$hasil->errorOutput());
        }
    }

    /**
     * Restore manual lewat PDO (tanpa binary eksternal) - sqlite,
     * konsisten dengan BackupService::dumpSqlite(). SQLite tidak punya
     * konsep "schema" terpisah seperti PostgreSQL, jadi seluruh tabel
     * yang ADA SAAT INI di-drop satu per satu dulu (bukan DROP SCHEMA)
     * sebelum menjalankan ulang isi dump-nya.
     */
    protected function restoreSqlite(string $sql): void
    {
        $pdo = DB::connection()->getPdo();

        $pdo->beginTransaction();

        try {
            $tabel = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tabel as $namaTabel) {
                $pdo->exec('DROP TABLE IF EXISTS "'.$namaTabel.'";');
            }

            $pdo->exec($sql);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw new RuntimeException('Gagal menjalankan restore database (sqlite): '.$e->getMessage());
        }
    }
}
