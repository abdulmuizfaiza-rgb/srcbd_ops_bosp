<?php

namespace App\Services;

use App\Models\Backup;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use ZipArchive;

/**
 * Membuat SATU paket backup (round DUA PULUH LIMA, 2026-09-24, poin 2,
 * permintaan user "buatkan menu backup berdasarkan tahun untuk semua data
 * yang ada pada aplikasi yang terdiri dari Aplikasi nya dan database nya
 * yang terbaru"). Dipakai oleh App\Livewire\Backup\Index::buatBackup().
 *
 * Keputusan bisnis via AskUserQuestion (2026-09-24) - lihat juga docblock
 * migration create_backups_table:
 * - "Cakupan Backup" -> paket .zip berisi DUA hal: seluruh kode aplikasi
 *   (folder "aplikasi/" di dalam zip) & dump database terbaru (folder
 *   "database/" di dalam zip yang sama, berisi database.sql).
 * - "Penyimpanan & Akses" -> disimpan lewat disk 'local' (private, TIDAK
 *   lewat symlink public) di bawah "backup/{tahun}/...".
 *
 * Pengecualian folder/file dari zip kode aplikasi adalah keputusan TEKNIS
 * (bukan bisnis) - vendor/node_modules dikecualikan krn bisa dibuat ulang
 * lewat "composer install"/"npm install" (ukurannya besar, memperlambat &
 * memperbesar zip tanpa perlu), storage/logs & storage/framework berisi
 * file sementara/cache yang tidak relevan utk dibackup, storage/app/backup
 * dikecualikan supaya backup LAMA tidak ikut ter-zip berulang ke dalam
 * backup BARU, & file .env dikecualikan krn berisi kredensial rahasia
 * (password database, APP_KEY dst) yang TIDAK BOLEH ikut di file yang bisa
 * diunduh.
 *
 * Dump database menyesuaikan driver yang aktif (config('database.default')
 * - aplikasi ini memakai PostgreSQL/SQLite, lihat CLAUDE.md): pgsql lewat
 * binary "pg_dump", mysql lewat "mysqldump" (harus tersedia di server),
 * sqlite dibuat manual lewat PDO (tidak perlu binary eksternal, sekalian
 * mendukung database sqlite ":memory:" yang dipakai lingkungan testing).
 *
 * Round DUA PULUH TUJUH (2026-09-24) - permintaan user memperbaiki error
 * "'pg_dump' is not recognized..." yang muncul di server Windows kalau
 * PostgreSQL command-line tools belum terdaftar di PATH sistem: pgsql &
 * mysql sekarang lewat resolveBinary() yang urutan pencariannya (1) path
 * dari config('backup.pg_dump_path')/('backup.mysqldump_path') kalau
 * user mengisinya di .env, (2) auto-deteksi ke lokasi umum install
 * PostgreSQL/MySQL di Windows lewat glob(), (3) fallback ke nama command
 * polos spt sebelumnya (mengandalkan PATH sistem) - jadi server yang
 * PATH-nya sudah benar TIDAK terpengaruh sama sekali.
 */
class BackupService
{
    /** Nama folder yang DIKECUALIKAN sama sekali dari zip kode aplikasi (dicocokkan di level mana pun). */
    protected const FOLDER_DIKECUALIKAN = ['vendor', 'node_modules', '.git', 'bootstrap/cache'];

    /** Pola path (relatif dari root aplikasi) yang DIKECUALIKAN dari zip kode aplikasi. */
    protected const PATH_DIKECUALIKAN = ['#^storage/logs#', '#^storage/framework#', '#^storage/app/backup#'];

    public function buat(?int $dibuatOlehId = null): Backup
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $tahun = (int) now()->format('Y');
        $namaFile = 'backup-'.$tahun.'-'.now()->format('YmdHis').'.zip';
        $pathRelatif = 'backup/'.$tahun.'/'.$namaFile;
        $pathAbsolut = Storage::disk('local')->path($pathRelatif);

        File::ensureDirectoryExists(dirname($pathAbsolut));

        $dumpSql = $this->dumpDatabase();

        $zip = new ZipArchive;

        if ($zip->open($pathAbsolut, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat file zip backup.');
        }

        $zip->addFromString('database/database.sql', $dumpSql);

        foreach ($this->finderKodeAplikasi() as $fileInfo) {
            $zip->addFile($fileInfo->getRealPath(), 'aplikasi/'.$fileInfo->getRelativePathname());
        }

        $zip->close();

        return Backup::create([
            'tahun' => $tahun,
            'nama_file' => $namaFile,
            'path' => $pathRelatif,
            'ukuran' => filesize($pathAbsolut) ?: null,
            'dibuat_oleh_id' => $dibuatOlehId,
        ]);
    }

    protected function finderKodeAplikasi(): Finder
    {
        $finder = (new Finder)
            ->files()
            ->in(base_path())
            ->ignoreDotFiles(false)
            ->exclude(self::FOLDER_DIKECUALIKAN)
            ->notName('/^\.env(\..+)?$/');

        foreach (self::PATH_DIKECUALIKAN as $pola) {
            $finder->notPath($pola);
        }

        return $finder;
    }

    protected function dumpDatabase(): string
    {
        $driver = config('database.default');

        return match ($driver) {
            'pgsql' => $this->dumpPgsql(),
            'mysql' => $this->dumpMysql(),
            'sqlite' => $this->dumpSqlite(),
            default => throw new RuntimeException("Driver database '{$driver}' belum didukung untuk backup otomatis."),
        };
    }

    protected function dumpPgsql(): string
    {
        // Round 28 (2026-09-26) - Windows TIDAK LAGI memakai pg_dump.exe
        // sama sekali, lihat dumpPgsqlViaPdo() utk penjelasan lengkap.
        // Server ONLINE (Linux) tidak terpengaruh - lanjut ke kode pg_dump
        // asli di bawah persis seperti sebelumnya (permintaan eksplisit
        // user 2026-09-26: perubahan ini tidak boleh mempengaruhi aplikasi
        // online yang pg_dump-nya sudah berjalan normal).
        if (PHP_OS_FAMILY === 'Windows') {
            return $this->dumpPgsqlViaPdo();
        }

        $c = config('database.connections.pgsql');

        $binary = $this->resolveBinary(
            'pg_dump',
            config('backup.pg_dump_path'),
            [
                'C:\\Program Files\\PostgreSQL\\*\\bin\\pg_dump.exe',
                'C:\\Program Files (x86)\\PostgreSQL\\*\\bin\\pg_dump.exe',
            ]
        );

        $percobaan1 = $this->jalankanPgDump($binary, $c);
        $hasil = $percobaan1;
        $percobaan2 = null;

        // Round DUA PULUH TUJUH (lanjutan/bagian 3, 2026-09-24) - PostgreSQL
        // 17.6+/16.10+/dst (perbaikan CVE-2025-8714) membuat pg_dump
        // menyisipkan "restrict key" acak ke output dump demi keamanan,
        // di-generate pg_dump SENDIRI lewat CryptoAPI Windows. Di sebagian
        // komputer Windows CryptoAPI itu gagal ("pg_dump: error: could not
        // generate restrict key" - SAMA SEKALI TIDAK terkait Laravel/PATH).
        // Awalnya retry di bawah ini HANYA dipicu kalau pesan error PERSIS
        // mengandung teks itu - tapi di lapangan (laporan user, 2026-09-24)
        // ternyata pesan errornya BISA TERPOTONG tidak konsisten (kadang
        // "pg_dump: error: could not generate restrict key" lengkap, kadang
        // cuma "pg_dump: error: " kosong) tergantung seberapa sempat proses
        // Windows-nya menulis ke stderr sebelum keburu berhenti - jadi
        // pengecekan `str_contains` bisa GAGAL mendeteksi kegagalan yang
        // SEBENARNYA sama. Diperlonggar: SEKARANG retry SEKALI dgn restrict
        // key yg KITA sediakan sendiri (via random_bytes() PHP, jalur acak
        // yg BERBEDA dari CryptoAPI yg bermasalah, dikirim lewat opsi resmi
        // pg_dump "--restrict-key" - hanya berlaku utk dump plain-text, yg
        // memang format yg dipakai di sini, lihat "-F p" di bawah) UNTUK
        // KEGAGALAN APAPUN di percobaan pertama, bukan hanya yg pesannya
        // cocok persis. Ini aman utk kasus lain (mis. password/koneksi
        // benar-benar salah) krn percobaan ulang ini TETAP akan gagal lagi
        // dgn alasan yg sama, jadi tetap berakhir di exception di bawah -
        // hanya menambah satu percobaan ekstra, tidak mengubah hasil akhir.
        // Server yg pg_dump-nya SUDAH normal (mayoritas, mis. Linux) TIDAK
        // PERNAH masuk jalur retry ini - percobaan pertama selalu identik
        // dgn perilaku SEBELUM perubahan ini.
        if ($hasil->failed()) {
            $percobaan2 = $this->jalankanPgDump($binary, $c, bin2hex(random_bytes(16)));
            $hasil = $percobaan2;
        }

        if ($hasil->failed()) {
            // Round DUA PULUH TUJUH (lanjutan/bagian 4, 2026-09-24) - pesan
            // error sebelumnya cuma menampilkan errorOutput dari percobaan
            // TERAKHIR, & di komputer user pesan itu sering KOSONG tanpa
            // keterangan sama sekali (proses Windows-nya berhenti sebelum
            // sempat menulis alasan lengkap ke stderr) - sehingga tidak ada
            // petunjuk sama sekali soal PENYEBAB SEBENARNYA (mis. exit code
            // yg bisa mengindikasikan proses di-crash/diblokir antivirus).
            // Sekarang pesan error menyertakan RINGKASAN KEDUA percobaan
            // (exit code + errorOutput masing-masing, percobaan 2 hanya ada
            // kalau percobaan 1 gagal) supaya lebih mudah didiagnosis tanpa
            // perlu bolak-balik tes manual lewat Command Prompt lagi.
            $ringkasPercobaan = fn (ProcessResult $p, string $label): string => $label.' (exit code '.($p->exitCode() ?? '?').', stdout '.strlen($p->output()).' byte): '
                .(trim($p->errorOutput()) !== '' ? trim($p->errorOutput()) : '(pesan error kosong dari proses)');

            $detail = $ringkasPercobaan($percobaan1, 'Percobaan 1');
            if ($percobaan2 !== null) {
                $detail .= ' | '.$ringkasPercobaan($percobaan2, 'Percobaan 2 (dgn --restrict-key)');
            }

            // Round DUA PULUH TUJUH (lanjutan/bagian 5, 2026-09-24) - dugaan
            // antivirus/Windows Defender aktif memblokir sudah DIPERIKSA
            // langsung bersama user & TIDAK terbukti (Defender ternyata
            // NONAKTIF di komputer itu, bukan aktif memblokir apa pun).
            // Kandidat berikutnya (environment proses PHP - TEMP/TMP/
            // USERPROFILE/SYSTEMROOT/USERNAME) SUDAH dicek & ternyata semua
            // TERISI NORMAL (bukan kosong/rusak) - jadi TIDAK menjelaskan
            // kegagalan. Tetap disertakan di pesan error sbg referensi.
            $env = 'TEMP='.(getenv('TEMP') ?: '(kosong)')
                .', TMP='.(getenv('TMP') ?: '(kosong)')
                .', USERPROFILE='.(getenv('USERPROFILE') ?: '(kosong)')
                .', SYSTEMROOT='.(getenv('SYSTEMROOT') ?: '(kosong)')
                .', USERNAME='.(getenv('USERNAME') ?: '(kosong)');

            // Round DUA PULUH TUJUH (lanjutan/bagian 6, 2026-09-24) - dgn
            // environment proses PHP TERBUKTI normal, kandidat penyebab
            // berikutnya yg PALING mungkin: binary pg_dump.exe yg dipakai
            // APLIKASI (lewat resolveBinary() - bisa dari BACKUP_PG_DUMP_PATH
            // di .env, ATAU dari auto-deteksi glob() ke folder install
            // PostgreSQL, ATAU fallback nama polos "pg_dump" yg mengandalkan
            // PATH) BISA JADI BUKAN file .exe yg SAMA dgn yang dipakai user
            // saat tes manual di Command Prompt (yg mengandalkan PATH sistem
            // apa adanya, TANPA melalui resolveBinary()) - mis. kalau ada
            // LEBIH DARI SATU instalasi PostgreSQL di komputer user (versi
            // berbeda, atau bundle dari aplikasi lain spt pgAdmin), kedua
            // jalur ini bisa² memilih pg_dump.exe yg BERBEDA VERSI/BUILD,
            // yang bisa berperilaku beda soal CryptoAPI/restrict-key
            // walau parameter yg dikirim identik. Disertakan PATH LENGKAP
            // binary yg dipakai aplikasi supaya bisa dibandingkan LANGSUNG
            // dgn hasil "where pg_dump" (dijalankan user manual).
            // Round DUA PULUH TUJUH (lanjutan/bagian 8, 2026-09-24/25) -
            // percobaan "redirect ke file" (round27g) TERNYATA masih gagal
            // identik, walau tes manual dgn pola SAMA PERSIS terbukti
            // berhasil - jadi masih ada perbedaan yg belum ketahuan antara
            // proses yg dipanggil APLIKASI vs manual. Sebelum menyelidiki
            // lebih jauh lewat eksperimen manual (yg beresiko salah ketik
            // spt sebelumnya), ditambahkan SATU tes diagnostik OTOMATIS yg
            // TIDAK perlu tindakan tambahan dari user sama sekali: coba
            // jalankan "pg_dump --version" (paling sederhana, TANPA koneksi
            // database, TANPA restrict-key sama sekali) lewat jalur
            // pemanggilan proses yg SAMA persis dgn yg dipakai aplikasi.
            // Kalau INI SAJA sudah gagal, itu membuktikan masalahnya ada di
            // LEVEL MENJALANKAN PROSES ITU SENDIRI (bukan spesifik ke
            // restrict-key/koneksi database) - mempersempit pencarian scr
            // signifikan tanpa perlu user mengetik perintah apa pun lagi.
            $cekVersi = $this->cekVersiBinary($binary);
            $versi = 'Tes "pg_dump --version" lewat aplikasi: exit code '.($cekVersi->exitCode() ?? '?')
                .', output: '.(trim($cekVersi->output()) !== '' ? trim($cekVersi->output()) : '(kosong)')
                .(trim($cekVersi->errorOutput()) !== '' ? ', error: '.trim($cekVersi->errorOutput()) : '');

            throw new RuntimeException(
                'Gagal menjalankan pg_dump. '.$detail
                .' | Binary pg_dump yg dipakai aplikasi: '.$binary
                .' | Environment proses PHP: '.$env
                .' | '.$versi
                .' Kalau pg_dump belum terdaftar di PATH Windows, isi BACKUP_PG_DUMP_PATH di file .env dengan path lengkap ke pg_dump.exe (biasanya "C:\\Program Files\\PostgreSQL\\<versi>\\bin\\pg_dump.exe").'
            );
        }

        return $hasil->output();
    }

    /**
     * Tes paling sederhana yg bisa dijalankan thd binary pg_dump - TANPA
     * koneksi database, TANPA restrict-key - lewat jalur pemanggilan proses
     * yg SAMA persis dgn dipakai aplikasi (lihat jalankanPgDump()), supaya
     * bisa dibedakan apakah masalahnya di LEVEL MENJALANKAN PROSES itu
     * sendiri atau spesifik ke argumen dump yg lebih kompleks.
     */
    private function cekVersiBinary(string $binary): ProcessResult
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return $this->jalankanViaRedirectFile([$binary, '--version'], []);
        }

        return Process::run([$binary, '--version']);
    }

    /** @param  array<string, mixed>  $c */
    private function jalankanPgDump(string $binary, array $c, ?string $restrictKey = null): ProcessResult
    {
        $argumen = [
            $binary,
            '-h', (string) ($c['host'] ?? '127.0.0.1'),
            '-p', (string) ($c['port'] ?? 5432),
            '-U', (string) ($c['username'] ?? ''),
            '--no-owner', '--no-privileges', '-F', 'p',
        ];

        if ($restrictKey !== null) {
            $argumen[] = '--restrict-key';
            $argumen[] = $restrictKey;
        }

        $argumen[] = (string) ($c['database'] ?? '');

        $env = ['PGPASSWORD' => (string) ($c['password'] ?? '')];

        // Round DUA PULUH TUJUH (lanjutan/bagian 7, 2026-09-24) - dites
        // LANGSUNG bersama user (2026-09-24): pg_dump GAGAL KONSISTEN kalau
        // outputnya "ditangkap" lewat pipe bawaan Process::run() milik PHP
        // (proc_open) - WALAU sudah dibuktikan bukan krn restrict-key (
        // percobaan ke-2 yg pakai --restrict-key sendiri pun tetap gagal
        // identik), bukan krn binary/kredensial/environment (semua sudah
        // dicek & terbukti benar), bukan krn Windows Defender/antivirus lain
        // (dicek & keduanya TIDAK aktif) - TAPI perintah yg SAMA PERSIS
        // BERHASIL SEMPURNA (dump 138KB, tanpa error) saat user menjalankan
        // manual dgn output di-redirect ke FILE lewat shell (">", "2>"),
        // bukan ke layar. Dugaan: pg_dump.exe yg dipanggil "php artisan
        // serve" adalah GRANDCHILD proses (artisan serve -> proses "php -S"
        // internal -> pg_dump.exe) - tiap proc_open Windows scr default
        // mewarisi (bInheritHandles=TRUE) semua handle yg bisa diwariskan
        // dari induknya, termasuk PIPE stdout/stderr milik PHP sendiri, &
        // pewarisan ini bisa bertumpuk makin dalam nesting-nya - beda dgn
        // proses yg dipanggil LANGSUNG dari shell interaktif (tanpa nesting
        // sama sekali, spt tes manual user). Solusi: KHUSUS Windows, tidak
        // lagi memakai pipe capture bawaan Process::run() - sebagai gantinya
        // menjalankan pg_dump lewat SHELL command dgn output di-redirect ke
        // file sementara, PERSIS spt yg terbukti berhasil di tes manual,
        // lalu file itu dibaca balik jadi string. Mengganti handle pipe PHP
        // dgn handle file milik shell (cmd.exe) sendiri utk stdout/stderr
        // pg_dump. TIDAK mengubah perilaku di server non-Windows (mis.
        // Linux, yg selama ini sudah berjalan normal) sama sekali - tetap
        // memakai Process::run() array biasa spt sebelumnya.
        if (PHP_OS_FAMILY === 'Windows') {
            return $this->jalankanViaRedirectFile($argumen, $env);
        }

        return Process::env($env)->run($argumen);
    }

    /**
     * Jalankan perintah lewat shell dgn stdout/stderr di-redirect ke file
     * sementara (bukan pipe Symfony Process langsung) - lihat penjelasan
     * lengkap di jalankanPgDump(). Tetap mengembalikan kontrak ProcessResult
     * yg sama spt Process::run() biasa, supaya kode pemanggil tidak perlu
     * tahu bedanya.
     *
     * @param  string[]  $argumen
     * @param  array<string, string>  $env
     */
    private function jalankanViaRedirectFile(array $argumen, array $env): ProcessResult
    {
        $pathOutput = tempnam(sys_get_temp_dir(), 'srcbd_pgdump_out_');
        $pathError = tempnam(sys_get_temp_dir(), 'srcbd_pgdump_err_');

        try {
            $commandLine = implode(' ', array_map(
                fn (string $bagian): string => $this->kutipArgumenWindows($bagian),
                $argumen
            )).' > '.$this->kutipArgumenWindows($pathOutput).' 2> '.$this->kutipArgumenWindows($pathError);

            $hasil = Process::env($env)->run($commandLine);

            return new HasilProsesRedirectFile(
                command: $commandLine,
                exitCode: $hasil->exitCode(),
                output: is_file($pathOutput) ? (file_get_contents($pathOutput) ?: '') : '',
                errorOutput: is_file($pathError) ? (file_get_contents($pathError) ?: '') : '',
            );
        } finally {
            if (is_file($pathOutput)) {
                @unlink($pathOutput);
            }

            if (is_file($pathError)) {
                @unlink($pathError);
            }
        }
    }

    /**
     * Bungkus SATU argumen dgn tanda kutip ganda gaya Windows cmd.exe -
     * cukup utk nilai yg kita kontrol sendiri (path binary, host, port,
     * username, nama database, path file sementara) yg TIDAK mengandung
     * tanda kutip ganda; BUKAN escaping shell serba guna.
     */
    private function kutipArgumenWindows(string $nilai): string
    {
        return '"'.str_replace('"', '""', $nilai).'"';
    }

    /**
     * Dump database PostgreSQL manual lewat PDO (TANPA binary pg_dump.exe
     * sama sekali) - KHUSUS dipakai di Windows, lihat percabangan di
     * dumpPgsql() di atas.
     *
     * Round 28 (2026-09-26) - menggantikan pendekatan pg_dump.exe dari
     * round 27/27b-27h yang SELALU gagal ("could not generate restrict
     * key") di komputer user walau sudah 8 kali dicoba diperbaiki (ganti
     * binary, restrict-key manual sendiri, redirect output ke file, dst -
     * bahkan "pg_dump --version" polos pun berhasil, tapi dump asli tetap
     * gagal). Akar masalahnya: pg_dump 18+ (proteksi CVE-2025-8714)
     * men-generate "restrict key" acak lewat Windows CryptoAPI, dan di
     * komputer ini proses tsb gagal saat dipanggil lewat PHP (walau
     * berhasil kalau dijalankan manual dari Command Prompt) - di luar
     * kendali kode aplikasi, jadi diputuskan (via AskUserQuestion,
     * 2026-09-26) utk berhenti mengandalkan pg_dump.exe di Windows sama
     * sekali & gantikan dgn dump manual spt ini.
     *
     * Server ONLINE (Linux) TIDAK terpengaruh sama sekali - tetap memakai
     * pg_dump.exe asli via dumpPgsql() (permintaan eksplisit user,
     * 2026-09-26: "kalau ganti metode berpengaruh tidak antara aplikasi
     * online dan aplikasi lokal nya" - jawaban: TIDAK, hanya Windows yang
     * kena percabangan ini).
     *
     * Cakupan: struktur tabel (kolom, tipe data, default, nullable),
     * primary key/unique/check constraint & index (lewat pg_get_constraintdef/
     * pg_indexes - Postgres sendiri yg menghasilkan teks SQL-nya, bukan
     * disusun manual, supaya presisi), sequence (termasuk nilai
     * berjalannya), foreign key (ditambahkan di paling akhir stlh semua
     * tabel ada, supaya urutan antar tabel tidak jadi masalah), & seluruh
     * data lewat INSERT. TIDAK mencakup view/function/trigger/custom type/
     * extension - dicek 2026-09-26 lewat seluruh file migration, aplikasi
     * ini TIDAK memakai satu pun dari itu (murni tabel biasa), jadi aman.
     */
    protected function dumpPgsqlViaPdo(): string
    {
        $pdo = DB::connection()->getPdo();
        $skema = 'public';
        $sql = '-- PostgreSQL dump manual lewat PDO (BackupService, khusus Windows) - '.now()->toDateTimeString()."\n\n";
        $sql .= "SET statement_timeout = 0;\nSET client_encoding = 'UTF8';\n\n";

        // 1. Sequence dulu (supaya bisa dirujuk kolom DEFAULT nextval() di tabel)
        $sequences = $pdo->query("
            SELECT sequencename, start_value, increment_by, min_value, max_value, cache_size, cycle, last_value
            FROM pg_sequences WHERE schemaname = '{$skema}'
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sequences as $s) {
            $sql .= "CREATE SEQUENCE IF NOT EXISTS \"{$s['sequencename']}\" START WITH {$s['start_value']} INCREMENT BY {$s['increment_by']} MINVALUE {$s['min_value']} MAXVALUE {$s['max_value']} CACHE {$s['cache_size']}".($s['cycle'] ? ' CYCLE' : '').";\n";
        }
        $sql .= "\n";

        // 2. Tabel satu per satu: struktur -> constraint (kecuali FK) -> index -> data
        $tabel = $pdo->query("SELECT tablename FROM pg_tables WHERE schemaname = '{$skema}' ORDER BY tablename")->fetchAll(PDO::FETCH_COLUMN);

        $semuaFk = [];

        foreach ($tabel as $namaTabel) {
            $kolom = $pdo->query('
                SELECT column_name, udt_name, character_maximum_length, numeric_precision, numeric_scale, is_nullable, column_default
                FROM information_schema.columns
                WHERE table_schema = '.$pdo->quote($skema).' AND table_name = '.$pdo->quote($namaTabel).'
                ORDER BY ordinal_position
            ')->fetchAll(PDO::FETCH_ASSOC);

            $definisiKolom = [];
            foreach ($kolom as $k) {
                $baris = "\"{$k['column_name']}\" ".$this->tipeKolomSql($k);
                if ($k['column_default'] !== null) {
                    $baris .= ' DEFAULT '.$k['column_default'];
                }
                if ($k['is_nullable'] === 'NO') {
                    $baris .= ' NOT NULL';
                }
                $definisiKolom[] = $baris;
            }

            $sql .= "CREATE TABLE \"{$namaTabel}\" (\n    ".implode(",\n    ", $definisiKolom)."\n);\n\n";

            $constraints = $pdo->query('SELECT conname, contype, pg_get_constraintdef(oid) AS def FROM pg_constraint WHERE conrelid = '.$pdo->quote('"'.$namaTabel.'"').'::regclass')->fetchAll(PDO::FETCH_ASSOC);

            foreach ($constraints as $con) {
                if ($con['contype'] === 'f') {
                    $semuaFk[] = "ALTER TABLE \"{$namaTabel}\" ADD CONSTRAINT \"{$con['conname']}\" {$con['def']};";

                    continue;
                }

                $sql .= "ALTER TABLE \"{$namaTabel}\" ADD CONSTRAINT \"{$con['conname']}\" {$con['def']};\n";
            }
            $sql .= "\n";

            $namaConstraint = array_column($constraints, 'conname');
            $indexes = $pdo->query('SELECT indexname, indexdef FROM pg_indexes WHERE schemaname = '.$pdo->quote($skema).' AND tablename = '.$pdo->quote($namaTabel))->fetchAll(PDO::FETCH_ASSOC);

            foreach ($indexes as $idx) {
                if (in_array($idx['indexname'], $namaConstraint, true)) {
                    continue;
                }

                $sql .= $idx['indexdef'].";\n";
            }
            $sql .= "\n";

            $baris = $pdo->query("SELECT * FROM \"{$namaTabel}\"")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($baris as $row) {
                $namaKolom = array_keys($row);
                $nilai = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row);
                $sql .= "INSERT INTO \"{$namaTabel}\" (\"".implode('","', $namaKolom).'") VALUES ('.implode(',', $nilai).");\n";
            }
            $sql .= "\n";
        }

        // 3. Foreign key di paling akhir (semua tabel sudah pasti ada)
        if ($semuaFk !== []) {
            $sql .= implode("\n", $semuaFk)."\n\n";
        }

        // 4. Samakan posisi sequence dgn nilai terakhirnya (supaya nextval() berikutnya tidak bentrok dgn data yg baru di-restore)
        foreach ($sequences as $s) {
            $nilaiTerakhir = $s['last_value'] ?? $s['start_value'];
            $sql .= "SELECT setval('\"{$s['sequencename']}\"', {$nilaiTerakhir}, true);\n";
        }

        return $sql;
    }

    /** @param  array<string, mixed>  $k  Satu baris hasil query information_schema.columns. */
    private function tipeKolomSql(array $k): string
    {
        return match (true) {
            in_array($k['udt_name'], ['varchar', 'bpchar'], true) && $k['character_maximum_length'] !== null => "{$k['udt_name']}({$k['character_maximum_length']})",
            $k['udt_name'] === 'numeric' && $k['numeric_precision'] !== null => "numeric({$k['numeric_precision']},{$k['numeric_scale']})",
            default => $k['udt_name'],
        };
    }

    protected function dumpMysql(): string
    {
        $c = config('database.connections.mysql');

        $binary = $this->resolveBinary(
            'mysqldump',
            config('backup.mysqldump_path'),
            [
                'C:\\xampp\\mysql\\bin\\mysqldump.exe',
                'C:\\Program Files\\MySQL\\*\\bin\\mysqldump.exe',
            ]
        );

        $hasil = Process::env(['MYSQL_PWD' => (string) ($c['password'] ?? '')])
            ->run([
                $binary,
                '-h', (string) ($c['host'] ?? '127.0.0.1'),
                '-P', (string) ($c['port'] ?? 3306),
                '-u', (string) ($c['username'] ?? ''),
                (string) ($c['database'] ?? ''),
            ]);

        if ($hasil->failed()) {
            throw new RuntimeException(
                'Gagal menjalankan mysqldump: '.$hasil->errorOutput()
                .' Kalau mysqldump belum terdaftar di PATH Windows, isi BACKUP_MYSQLDUMP_PATH di file .env dengan path lengkap ke mysqldump.exe.'
            );
        }

        return $hasil->output();
    }

    /**
     * Cari lokasi binary command-line (pg_dump/mysqldump) dgn urutan: (1) path
     * yang dikonfigurasi user lewat .env kalau file-nya memang ada, (2) pola
     * glob ke lokasi umum install di Windows (versi TERBARU yang ketemu
     * dipakai kalau ada lebih dari satu), (3) fallback ke nama command polos
     * spt sebelumnya (mengandalkan PATH sistem) - jadi server yang PATH-nya
     * sudah benar berperilaku PERSIS seperti sebelum round ini.
     *
     * @param  string[]  $polaGlobFallback
     */
    protected function resolveBinary(string $namaBinary, ?string $pathTerkonfigurasi, array $polaGlobFallback = []): string
    {
        if ($pathTerkonfigurasi !== null && $pathTerkonfigurasi !== '' && is_file($pathTerkonfigurasi)) {
            return $pathTerkonfigurasi;
        }

        foreach ($polaGlobFallback as $pola) {
            $kandidat = glob($pola) ?: [];

            if ($kandidat !== []) {
                rsort($kandidat);

                return $kandidat[0];
            }
        }

        return $namaBinary;
    }

    /** Dump manual lewat PDO (tanpa binary eksternal) - juga dipakai utk database sqlite ":memory:" (lingkungan testing). */
    protected function dumpSqlite(): string
    {
        $pdo = DB::connection()->getPdo();
        $sql = '-- SQLite dump (BackupService) - '.now()->toDateTimeString()."\n\n";

        $tabel = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tabel as $t) {
            $sql .= $t['sql'].";\n";

            $baris = $pdo->query('SELECT * FROM "'.$t['name'].'"')->fetchAll(PDO::FETCH_ASSOC);

            foreach ($baris as $row) {
                $kolom = array_keys($row);
                $nilai = array_map(fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row);
                $sql .= 'INSERT INTO "'.$t['name'].'" ("'.implode('","', $kolom).'") VALUES ('.implode(',', $nilai).");\n";
            }

            $sql .= "\n";
        }

        return $sql;
    }
}
