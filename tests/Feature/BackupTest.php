<?php

namespace Tests\Feature;

use App\Livewire\Backup\Index;
use App\Models\Backup;
use App\Models\PendataanOps;
use App\Models\ProfilSekolah;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

/**
 * Menu "Backup" (BARU, round DUA PULUH LIMA, 2026-09-24, poin 2,
 * permintaan user "buatkan menu backup berdasarkan tahun untuk semua data
 * yang ada pada aplikasi yang terdiri dari Aplikasi nya dan database nya
 * yang terbaru yang berhubungan dengan aplikasinya") - HANYA Superadmin
 * (Gate 'akses-backup'), lihat App\Services\BackupService & App\Livewire\
 * Backup\Index utk detail keputusan bisnis (jawaban AskUserQuestion
 * 2026-09-24).
 */
class BackupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin OPS yang sudah melewati EnsureOnboardingComplete - pola sama
     * dgn PanduanAplikasiTest/TimelinePekerjaanTest.
     */
    private function adminOpsLengkap(): User
    {
        $sekolah = ProfilSekolah::factory()->create();
        $sekolah->update([
            'nama_kepala_sekolah' => 'Kepsek', 'nip_kepala_sekolah' => '1', 'no_whatsapp_kepala_sekolah' => '0812',
            'status_kepegawaian_kepsek' => 'PNS', 'nama_pengawas' => 'P', 'nip_pengawas' => '2',
            'nama_bendahara' => 'B', 'nip_bendahara' => '3', 'status_kepegawaian_bendahara' => 'PNS',
            'alamat_sekolah' => 'Jl. X',
        ]);
        $admin = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);
        PendataanOps::factory()->create(['profil_sekolah_id' => $sekolah->id]);

        return $admin;
    }

    public function test_hanya_superadmin_bisa_akses_menu_backup(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $this->actingAs($superadmin)->get(route('backup.index'))->assertOk();
    }

    public function test_admin_ops_tidak_bisa_akses_menu_backup(): void
    {
        $adminOps = $this->adminOpsLengkap();

        $this->actingAs($adminOps)->get(route('backup.index'))->assertForbidden();
    }

    public function test_menu_backup_tidak_tampil_di_sidebar_utk_admin_ops(): void
    {
        $adminOps = $this->adminOpsLengkap();

        $this->actingAs($adminOps)
            ->get(route('pendataan-ops.index'))
            ->assertOk()
            ->assertDontSee('Backup');
    }

    public function test_buat_backup_membuat_file_zip_dan_baris_riwayat(): void
    {
        Storage::fake('local');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('buatBackup');

        $this->assertDatabaseCount('backups', 1);
        $backup = Backup::firstOrFail();
        $this->assertSame((int) now()->format('Y'), $backup->tahun);
        $this->assertSame($superadmin->id, $backup->dibuat_oleh_id);
        $this->assertGreaterThan(0, $backup->ukuran);
        Storage::disk('local')->assertExists($backup->path);
    }

    public function test_gagal_membuat_backup_menampilkan_pesan_error_tanpa_membuat_baris_riwayat(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $mock = \Mockery::mock(BackupService::class);
        $mock->shouldReceive('buat')->once()->andThrow(new RuntimeException('Gagal menjalankan pg_dump: simulasi gagal.'));
        $this->app->instance(BackupService::class, $mock);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('buatBackup');

        $this->assertDatabaseCount('backups', 0);
    }

    public function test_filter_riwayat_backup_per_tahun(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        Backup::factory()->create(['tahun' => 2025, 'nama_file' => 'backup-2025-lama.zip']);
        Backup::factory()->create(['tahun' => 2026, 'nama_file' => 'backup-2026-baru.zip']);

        $component = Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('filterTahun', '2025');

        $component->assertSee('backup-2025-lama.zip')->assertDontSee('backup-2026-baru.zip');

        $component->set('filterTahun', '2026');
        $component->assertSee('backup-2026-baru.zip')->assertDontSee('backup-2025-lama.zip');

        $component->set('filterTahun', '');
        $component->assertSee('backup-2025-lama.zip')->assertSee('backup-2026-baru.zip');
    }

    public function test_hapus_backup_menghapus_file_dan_baris_riwayat(): void
    {
        Storage::fake('local');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        Storage::disk('local')->put('backup/2026/dihapus.zip', 'isi-zip-palsu');
        $backup = Backup::factory()->create(['path' => 'backup/2026/dihapus.zip']);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('hapus', $backup->id);

        $this->assertDatabaseMissing('backups', ['id' => $backup->id]);
        Storage::disk('local')->assertMissing('backup/2026/dihapus.zip');
    }

    public function test_unduh_backup_berhasil_utk_superadmin(): void
    {
        Storage::fake('local');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        Storage::disk('local')->put('backup/2026/unduh-ini.zip', 'isi-zip-palsu');
        $backup = Backup::factory()->create(['path' => 'backup/2026/unduh-ini.zip', 'nama_file' => 'unduh-ini.zip']);

        $this->actingAs($superadmin)->get(route('backup.unduh', $backup))->assertOk();
    }

    public function test_unduh_backup_ditolak_utk_admin_ops(): void
    {
        Storage::fake('local');
        $adminOps = $this->adminOpsLengkap();
        Storage::disk('local')->put('backup/2026/rahasia.zip', 'isi-zip-palsu');
        $backup = Backup::factory()->create(['path' => 'backup/2026/rahasia.zip']);

        $this->actingAs($adminOps)->get(route('backup.unduh', $backup))->assertForbidden();
    }

    public function test_unduh_backup_yang_tidak_ada_di_storage_menghasilkan_404(): void
    {
        Storage::fake('local');
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $backup = Backup::factory()->create(['path' => 'backup/2026/tidak-ada.zip']);

        $this->actingAs($superadmin)->get(route('backup.unduh', $backup))->assertNotFound();
    }

    public function test_dump_pgsql_menjalankan_pg_dump_dan_mengembalikan_output(): void
    {
        Process::fake(['*pg_dump*' => Process::result(output: 'DUMP PGSQL CONTENT')]);
        config(['database.connections.pgsql' => ['host' => '127.0.0.1', 'port' => 5432, 'username' => 'u', 'password' => 'p', 'database' => 'd']]);

        $method = new ReflectionMethod(BackupService::class, 'dumpPgsql');
        $method->setAccessible(true);
        $hasil = $method->invoke(new BackupService);

        $this->assertSame('DUMP PGSQL CONTENT', rtrim($hasil));
        Process::assertRan(fn ($process) => str_contains(is_array($process->command) ? implode(' ', $process->command) : $process->command, 'pg_dump'));
    }

    public function test_dump_pgsql_gagal_melempar_exception(): void
    {
        Process::fake(['*pg_dump*' => Process::result(output: '', errorOutput: 'connection refused', exitCode: 1)]);
        config(['database.connections.pgsql' => ['host' => '127.0.0.1', 'port' => 5432, 'username' => 'u', 'password' => 'p', 'database' => 'd']]);

        $method = new ReflectionMethod(BackupService::class, 'dumpPgsql');
        $method->setAccessible(true);

        $this->expectException(RuntimeException::class);
        $method->invoke(new BackupService);
    }

    public function test_dump_mysql_menjalankan_mysqldump_dan_mengembalikan_output(): void
    {
        Process::fake(['*mysqldump*' => Process::result(output: 'DUMP MYSQL CONTENT')]);
        config(['database.connections.mysql' => ['host' => '127.0.0.1', 'port' => 3306, 'username' => 'u', 'password' => 'p', 'database' => 'd']]);

        $method = new ReflectionMethod(BackupService::class, 'dumpMysql');
        $method->setAccessible(true);
        $hasil = $method->invoke(new BackupService);

        $this->assertSame('DUMP MYSQL CONTENT', rtrim($hasil));
    }

    /**
     * Round DUA PULUH TUJUH (2026-09-24) - perbaikan error "'pg_dump' is
     * not recognized" di Windows (PATH sistem belum terdaftar): pgsql &
     * mysql sekarang mencari binary lewat resolveBinary() sebelum
     * menjalankan Process. Tes berikut memverifikasi urutan pencarian:
     * config .env -> auto-deteksi glob -> fallback nama command polos.
     */
    public function test_resolve_binary_memakai_path_terkonfigurasi_kalau_file_ada(): void
    {
        $pathPalsu = tempnam(sys_get_temp_dir(), 'pg_dump_');

        $method = new ReflectionMethod(BackupService::class, 'resolveBinary');
        $method->setAccessible(true);
        $hasil = $method->invoke(new BackupService, 'pg_dump', $pathPalsu, []);

        unlink($pathPalsu);

        $this->assertSame($pathPalsu, $hasil);
    }

    public function test_resolve_binary_mengabaikan_path_terkonfigurasi_yang_filenya_tidak_ada(): void
    {
        $method = new ReflectionMethod(BackupService::class, 'resolveBinary');
        $method->setAccessible(true);
        $hasil = $method->invoke(new BackupService, 'pg_dump', '/lokasi/tidak/ada/pg_dump.exe', []);

        $this->assertSame('pg_dump', $hasil);
    }

    public function test_resolve_binary_memakai_hasil_auto_deteksi_glob_kalau_tidak_dikonfigurasi(): void
    {
        $folderPalsu = sys_get_temp_dir().'/backuptest_pgsql_'.uniqid();
        mkdir($folderPalsu.'/16/bin', 0777, true);
        touch($folderPalsu.'/16/bin/pg_dump.exe');

        $method = new ReflectionMethod(BackupService::class, 'resolveBinary');
        $method->setAccessible(true);
        $hasil = $method->invoke(new BackupService, 'pg_dump', null, [$folderPalsu.'/*/bin/pg_dump.exe']);

        unlink($folderPalsu.'/16/bin/pg_dump.exe');
        rmdir($folderPalsu.'/16/bin');
        rmdir($folderPalsu.'/16');
        rmdir($folderPalsu);

        $this->assertSame($folderPalsu.'/16/bin/pg_dump.exe', $hasil);
    }

    public function test_resolve_binary_fallback_ke_nama_polos_kalau_semua_tidak_ketemu(): void
    {
        $method = new ReflectionMethod(BackupService::class, 'resolveBinary');
        $method->setAccessible(true);
        $hasil = $method->invoke(new BackupService, 'pg_dump', null, ['/lokasi/tidak/ada/*/pg_dump.exe']);

        $this->assertSame('pg_dump', $hasil);
    }

    public function test_dump_pgsql_memakai_path_dari_config_backup_pg_dump_path(): void
    {
        $pathPalsu = tempnam(sys_get_temp_dir(), 'pg_dump_');
        config(['backup.pg_dump_path' => $pathPalsu]);
        config(['database.connections.pgsql' => ['host' => '127.0.0.1', 'port' => 5432, 'username' => 'u', 'password' => 'p', 'database' => 'd']]);
        Process::fake(['*' => Process::result(output: 'DUMP PGSQL CONTENT')]);

        $method = new ReflectionMethod(BackupService::class, 'dumpPgsql');
        $method->setAccessible(true);
        $method->invoke(new BackupService);

        unlink($pathPalsu);

        Process::assertRan(fn ($process) => $process->command[0] === $pathPalsu);
    }

    public function test_dump_pgsql_gagal_menyebutkan_env_backup_pg_dump_path_di_pesan_error(): void
    {
        config(['backup.pg_dump_path' => null]);
        Process::fake(['*pg_dump*' => Process::result(output: '', errorOutput: "'pg_dump' is not recognized", exitCode: 1)]);
        config(['database.connections.pgsql' => ['host' => '127.0.0.1', 'port' => 5432, 'username' => 'u', 'password' => 'p', 'database' => 'd']]);

        $method = new ReflectionMethod(BackupService::class, 'dumpPgsql');
        $method->setAccessible(true);

        try {
            $method->invoke(new BackupService);
            $this->fail('Seharusnya melempar RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('BACKUP_PG_DUMP_PATH', $e->getMessage());
        }
    }

    /**
     * Round DUA PULUH TUJUH (lanjutan, 2026-09-24) - pg_dump PostgreSQL versi
     * baru (perbaikan CVE-2025-8714) men-generate "restrict key" acak lewat
     * CryptoAPI Windows secara internal; di sebagian komputer Windows itu
     * gagal ("could not generate restrict key") - dumpPgsql() sekarang
     * otomatis mencoba ULANG SEKALI dgn restrict key yg KITA sediakan
     * sendiri (via --restrict-key) kalau kegagalan PERTAMA persis krn pesan
     * ini.
     */
    public function test_dump_pgsql_mencoba_ulang_dgn_restrict_key_kalau_gagal_generate_restrict_key(): void
    {
        Process::fake([
            '*pg_dump*' => Process::sequence()
                ->push(Process::result(output: '', errorOutput: 'pg_dump: error: could not generate restrict key', exitCode: 1))
                ->push(Process::result(output: 'DUMP PGSQL CONTENT')),
        ]);
        config(['database.connections.pgsql' => ['host' => '127.0.0.1', 'port' => 5432, 'username' => 'u', 'password' => 'p', 'database' => 'd']]);

        $method = new ReflectionMethod(BackupService::class, 'dumpPgsql');
        $method->setAccessible(true);
        $hasil = $method->invoke(new BackupService);

        $this->assertSame('DUMP PGSQL CONTENT', rtrim($hasil));
        Process::assertRan(fn ($process) => str_contains(is_array($process->command) ? implode(' ', $process->command) : $process->command, '--restrict-key'));
    }

    public function test_dump_pgsql_tetap_melempar_exception_kalau_percobaan_ulang_restrict_key_juga_gagal(): void
    {
        Process::fake(['*pg_dump*' => Process::result(output: '', errorOutput: 'pg_dump: error: could not generate restrict key', exitCode: 1)]);
        config(['database.connections.pgsql' => ['host' => '127.0.0.1', 'port' => 5432, 'username' => 'u', 'password' => 'p', 'database' => 'd']]);

        $method = new ReflectionMethod(BackupService::class, 'dumpPgsql');
        $method->setAccessible(true);

        $this->expectException(RuntimeException::class);
        $method->invoke(new BackupService);
    }

    /**
     * Round DUA PULUH TUJUH (lanjutan/bagian 3, 2026-09-24) - laporan user:
     * di lapangan pesan error CryptoAPI Windows bisa TERPOTONG ("pg_dump:
     * error: " kosong, bukan "...could not generate restrict key" lengkap),
     * jadi retry diperlonggar utk dipicu oleh KEGAGALAN APAPUN di percobaan
     * pertama, bukan hanya yg teksnya cocok persis.
     */
    public function test_dump_pgsql_mencoba_ulang_dgn_restrict_key_walau_pesan_gagal_pertama_tidak_spesifik(): void
    {
        Process::fake([
            '*pg_dump*' => Process::sequence()
                ->push(Process::result(output: '', errorOutput: 'pg_dump: error: ', exitCode: 1))
                ->push(Process::result(output: 'DUMP PGSQL CONTENT')),
        ]);
        config(['database.connections.pgsql' => ['host' => '127.0.0.1', 'port' => 5432, 'username' => 'u', 'password' => 'p', 'database' => 'd']]);

        $method = new ReflectionMethod(BackupService::class, 'dumpPgsql');
        $method->setAccessible(true);
        $hasil = $method->invoke(new BackupService);

        $this->assertSame('DUMP PGSQL CONTENT', rtrim($hasil));
        Process::assertRan(fn ($process) => str_contains(is_array($process->command) ? implode(' ', $process->command) : $process->command, '--restrict-key'));
    }

    /**
     * Round DUA PULUH TUJUH (lanjutan/bagian 4, 2026-09-24) - pesan error
     * sekarang merangkum KEDUA percobaan (exit code + errorOutput masing-
     * masing) supaya kegagalan yang pesannya kosong tetap bisa didiagnosis
     * dari layar error saja, tanpa perlu tes manual lewat Command Prompt.
     */
    public function test_dump_pgsql_gagal_menyertakan_ringkasan_kedua_percobaan_di_pesan_error(): void
    {
        Process::fake([
            '*pg_dump*' => Process::sequence()
                ->push(Process::result(output: '', errorOutput: '', exitCode: 3221225477))
                ->push(Process::result(output: '', errorOutput: 'FATAL: password authentication failed for user "postgres"', exitCode: 1))
                ->push(Process::result(output: 'pg_dump (PostgreSQL) 18.0')),
        ]);
        config(['database.connections.pgsql' => ['host' => '127.0.0.1', 'port' => 5432, 'username' => 'u', 'password' => 'p', 'database' => 'd']]);

        $method = new ReflectionMethod(BackupService::class, 'dumpPgsql');
        $method->setAccessible(true);

        try {
            $method->invoke(new BackupService);
            $this->fail('Seharusnya melempar RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Percobaan 1 (exit code 3221225477, stdout 0 byte)', $e->getMessage());
            $this->assertStringContainsString('pesan error kosong dari proses', $e->getMessage());
            $this->assertStringContainsString('Percobaan 2 (dgn --restrict-key) (exit code 1, stdout 0 byte)', $e->getMessage());
            $this->assertStringContainsString('password authentication failed', $e->getMessage());
        }
    }

    public function test_dump_pgsql_gagal_menyertakan_environment_proses_php_di_pesan_error(): void
    {
        Process::fake([
            '*pg_dump*' => Process::sequence()
                ->push(Process::result(output: '', errorOutput: 'pg_dump: error: could not generate restrict key', exitCode: 1))
                ->push(Process::result(output: '', errorOutput: '', exitCode: 1))
                ->push(Process::result(output: 'pg_dump (PostgreSQL) 18.0')),
        ]);
        config(['database.connections.pgsql' => ['host' => '127.0.0.1', 'port' => 5432, 'username' => 'u', 'password' => 'p', 'database' => 'd']]);

        $method = new ReflectionMethod(BackupService::class, 'dumpPgsql');
        $method->setAccessible(true);

        try {
            $method->invoke(new BackupService);
            $this->fail('Seharusnya melempar RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Environment proses PHP: TEMP=', $e->getMessage());
            $this->assertStringContainsString('TMP=', $e->getMessage());
            $this->assertStringContainsString('USERPROFILE=', $e->getMessage());
            $this->assertStringContainsString('SYSTEMROOT=', $e->getMessage());
            $this->assertStringContainsString('USERNAME=', $e->getMessage());
        }
    }

    public function test_dump_pgsql_gagal_menyertakan_path_binary_yang_dipakai_di_pesan_error(): void
    {
        $pathPalsu = tempnam(sys_get_temp_dir(), 'pg_dump_');
        config(['backup.pg_dump_path' => $pathPalsu]);
        Process::fake([
            '*pg_dump*' => Process::sequence()
                ->push(Process::result(output: '', errorOutput: 'pg_dump: error: could not generate restrict key', exitCode: 1))
                ->push(Process::result(output: '', errorOutput: '', exitCode: 1))
                ->push(Process::result(output: 'pg_dump (PostgreSQL) 18.0')),
        ]);
        config(['database.connections.pgsql' => ['host' => '127.0.0.1', 'port' => 5432, 'username' => 'u', 'password' => 'p', 'database' => 'd']]);

        $method = new ReflectionMethod(BackupService::class, 'dumpPgsql');
        $method->setAccessible(true);

        try {
            $method->invoke(new BackupService);
            $this->fail('Seharusnya melempar RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Binary pg_dump yg dipakai aplikasi: '.$pathPalsu, $e->getMessage());
        } finally {
            unlink($pathPalsu);
        }
    }

    /**
     * Round DUA PULUH TUJUH (lanjutan/bagian 8, 2026-09-24/25) - kalau
     * kedua percobaan dump gagal, aplikasi otomatis mencoba "pg_dump
     * --version" (paling sederhana, tanpa koneksi database/restrict-key)
     * lewat jalur pemanggilan proses yg sama, & menyertakan hasilnya di
     * pesan error - supaya bisa dibedakan apakah masalahnya di level
     * menjalankan proses itu sendiri atau spesifik ke dump yg lebih rumit.
     */
    public function test_dump_pgsql_gagal_menyertakan_hasil_tes_versi_binary_di_pesan_error(): void
    {
        Process::fake([
            '*pg_dump*' => Process::sequence()
                ->push(Process::result(output: '', errorOutput: 'pg_dump: error: could not generate restrict key', exitCode: 1))
                ->push(Process::result(output: '', errorOutput: '', exitCode: 1))
                ->push(Process::result(output: 'pg_dump (PostgreSQL) 18.0', errorOutput: '', exitCode: 0)),
        ]);
        config(['database.connections.pgsql' => ['host' => '127.0.0.1', 'port' => 5432, 'username' => 'u', 'password' => 'p', 'database' => 'd']]);

        $method = new ReflectionMethod(BackupService::class, 'dumpPgsql');
        $method->setAccessible(true);

        try {
            $method->invoke(new BackupService);
            $this->fail('Seharusnya melempar RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Tes "pg_dump --version" lewat aplikasi: exit code 0', $e->getMessage());
            $this->assertStringContainsString('output: pg_dump (PostgreSQL) 18.0', $e->getMessage());
        }
    }

    public function test_dump_pgsql_gagal_menyertakan_hasil_tes_versi_binary_gagal_di_pesan_error(): void
    {
        Process::fake([
            '*pg_dump*' => Process::sequence()
                ->push(Process::result(output: '', errorOutput: 'pg_dump: error: could not generate restrict key', exitCode: 1))
                ->push(Process::result(output: '', errorOutput: '', exitCode: 1))
                ->push(Process::result(output: '', errorOutput: '', exitCode: 1)),
        ]);
        config(['database.connections.pgsql' => ['host' => '127.0.0.1', 'port' => 5432, 'username' => 'u', 'password' => 'p', 'database' => 'd']]);

        $method = new ReflectionMethod(BackupService::class, 'dumpPgsql');
        $method->setAccessible(true);

        try {
            $method->invoke(new BackupService);
            $this->fail('Seharusnya melempar RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Tes "pg_dump --version" lewat aplikasi: exit code 1', $e->getMessage());
            $this->assertStringContainsString('output: (kosong)', $e->getMessage());
        }
    }

    public function test_kutip_argumen_windows_membungkus_dgn_tanda_kutip_ganda(): void
    {
        $method = new ReflectionMethod(BackupService::class, 'kutipArgumenWindows');
        $method->setAccessible(true);
        $service = new BackupService;

        $this->assertSame('"C:\\Program Files\\PostgreSQL\\18\\bin\\pg_dump.exe"', $method->invoke($service, 'C:\\Program Files\\PostgreSQL\\18\\bin\\pg_dump.exe'));
        $this->assertSame('"srcbd_ops_bosp"', $method->invoke($service, 'srcbd_ops_bosp'));
        $this->assertSame('"ada ""kutip"" di dalam"', $method->invoke($service, 'ada "kutip" di dalam'));
    }

    /**
     * Round DUA PULUH TUJUH (lanjutan/bagian 7, 2026-09-24) - jalankanViaRedirectFile()
     * dites LANGSUNG (bukan lewat dumpPgsql(), krn method itu hanya masuk
     * jalur ini kalau PHP_OS_FAMILY === 'Windows', yg TIDAK true di
     * lingkungan testing Linux ini) - memverifikasi: (1) command line yg
     * dikirim ke shell memuat redirect ">"/"2>" ke file sementara, (2)
     * exit code hasil PROSES SHELL (bukan pg_dump asli, krn di-fake) tetap
     * diteruskan dgn benar, (3) file sementara dibuat lalu DIHAPUS lagi
     * setelah selesai (tidak menumpuk file sampah).
     */
    public function test_jalankan_via_redirect_file_menyertakan_redirect_output_ke_file_sementara(): void
    {
        Process::fake(['*pg_dump*' => Process::result(exitCode: 0)]);

        $method = new ReflectionMethod(BackupService::class, 'jalankanViaRedirectFile');
        $method->setAccessible(true);

        $jumlahFileTempSebelum = count(glob(sys_get_temp_dir().'/srcbd_pgdump_*'));

        $hasil = $method->invoke(new BackupService, ['pg_dump', '-h', '127.0.0.1'], ['PGPASSWORD' => 'rahasia']);

        $this->assertSame(0, $hasil->exitCode());
        $this->assertTrue($hasil->successful());
        $this->assertSame('', $hasil->output());
        $this->assertSame('', $hasil->errorOutput());

        Process::assertRan(function ($process) {
            $command = is_array($process->command) ? implode(' ', $process->command) : $process->command;

            return str_contains($command, '"pg_dump"')
                && str_contains($command, ' > ')
                && str_contains($command, ' 2> ')
                && str_contains($command, 'srcbd_pgdump_out_')
                && str_contains($command, 'srcbd_pgdump_err_');
        });

        $jumlahFileTempSesudah = count(glob(sys_get_temp_dir().'/srcbd_pgdump_*'));
        $this->assertSame($jumlahFileTempSebelum, $jumlahFileTempSesudah, 'File sementara harus dihapus lagi setelah selesai.');
    }

    public function test_jalankan_via_redirect_file_meneruskan_exit_code_gagal(): void
    {
        Process::fake(['*pg_dump*' => Process::result(exitCode: 1)]);

        $method = new ReflectionMethod(BackupService::class, 'jalankanViaRedirectFile');
        $method->setAccessible(true);

        $hasil = $method->invoke(new BackupService, ['pg_dump'], ['PGPASSWORD' => 'rahasia']);

        $this->assertSame(1, $hasil->exitCode());
        $this->assertTrue($hasil->failed());
    }

    public function test_finder_kode_aplikasi_mengecualikan_env_vendor_dan_node_modules(): void
    {
        $method = new ReflectionMethod(BackupService::class, 'finderKodeAplikasi');
        $method->setAccessible(true);
        $finder = $method->invoke(new BackupService);

        $pathRelatif = collect(iterator_to_array($finder))->map(fn ($f) => str_replace('\\', '/', $f->getRelativePathname()));

        $this->assertFalse($pathRelatif->contains('.env'));
        $this->assertFalse($pathRelatif->contains(fn ($p) => str_starts_with($p, 'vendor/')));
        $this->assertFalse($pathRelatif->contains(fn ($p) => str_starts_with($p, 'node_modules/')));
        $this->assertFalse($pathRelatif->contains(fn ($p) => str_starts_with($p, 'storage/logs/')));
        $this->assertTrue($pathRelatif->contains('composer.json'));
    }
}
