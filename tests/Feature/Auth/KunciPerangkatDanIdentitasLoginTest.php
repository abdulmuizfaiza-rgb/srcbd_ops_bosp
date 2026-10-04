<?php

namespace Tests\Feature\Auth;

use App\Models\FailedLoginAttempt;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Test untuk 2 fitur keamanan di App\Livewire\Forms\LoginForm yang
 * ditemukan BELUM punya test otomatis lewat Audit Aplikasi 2026-10-04:
 *
 * 1. Kunci 1 perangkat per akun untuk Admin OPS/Admin BOSP (permintaan
 *    user 2026-10-03, Superadmin dikecualikan) - lihat
 *    LoginForm::cariSesiLainAktif()/paksaLogoutPerangkatLain().
 * 2. Login (& Pemulihan Akun) wajib dilakukan oleh akun yang SAMA dengan
 *    yang baru lolos gerbang verifikasi email+OTP (permintaan user
 *    2026-10-04) - lihat LoginForm::pastikanSesuaiEmailTerverifikasi().
 *
 * Simulasi "perangkat lain" di bawah ini dilakukan dengan membuat
 * langsung baris login_histories + sessions lewat DB (bukan lewat 2 sesi
 * HTTP paralel sungguhan, yang tidak praktis dilakukan di PHPUnit) -
 * ini meniru PERSIS kondisi yang dicek cariSesiLainAktif(): baris
 * login_histories dengan logout_at NULL + session_id yang cocok dengan
 * baris sessions yang last_activity-nya masih dalam batas waktu.
 */
class KunciPerangkatDanIdentitasLoginTest extends TestCase
{
    use RefreshDatabase;

    private function buatSesiAktifDiPerangkatLain(User $user, string $sessionId = 'sesi-perangkat-lain', ?int $lastActivity = null): void
    {
        LoginHistory::create([
            'user_id' => $user->id,
            'level_akses' => $user->level_akses,
            'email' => $user->email,
            'nama_sekolah' => $user->nama_sekolah,
            'login_at' => now()->subMinutes(5),
            'ip_address' => '10.0.0.1',
            'session_id' => $sessionId,
        ]);

        DB::table('sessions')->insert([
            'id' => $sessionId,
            'user_id' => $user->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Perangkat Lain',
            'payload' => base64_encode(serialize([])),
            'last_activity' => $lastActivity ?? now()->getTimestamp(),
        ]);
    }

    // --- Kunci 1 Perangkat ---------------------------------------------

    public function test_admin_ops_ditolak_login_kedua_kalinya_saat_perangkat_lain_masih_aktif(): void
    {
        $user = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);
        $this->buatSesiAktifDiPerangkatLain($user);

        session(['gerbang_akses_login_user_id' => $user->id]);

        $component = Volt::test('pages.auth.login')
            ->set('form.username', $user->username)
            ->set('form.password', 'password')
            ->call('login');

        $component->assertSet('form.tampilkanPopupPerangkatLain', true);
        $this->assertGuest();
    }

    public function test_admin_bosp_ditolak_login_kedua_kalinya_saat_perangkat_lain_masih_aktif(): void
    {
        $user = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);
        $this->buatSesiAktifDiPerangkatLain($user);

        session(['gerbang_akses_login_user_id' => $user->id]);

        $component = Volt::test('pages.auth.login')
            ->set('form.username', $user->username)
            ->set('form.password', 'password')
            ->call('login');

        $component->assertSet('form.tampilkanPopupPerangkatLain', true);
        $this->assertGuest();
    }

    public function test_superadmin_dikecualikan_dari_kunci_1_perangkat(): void
    {
        $user = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $this->buatSesiAktifDiPerangkatLain($user);

        session(['gerbang_akses_login_user_id' => $user->id]);

        Volt::test('pages.auth.login')
            ->set('form.username', $user->username)
            ->set('form.password', 'password')
            ->call('login')
            ->assertSet('form.tampilkanPopupPerangkatLain', false);

        $this->assertAuthenticatedAs($user);
    }

    public function test_sesi_yang_sudah_basi_tidak_memblokir_login_baru(): void
    {
        $user = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        // last_activity jauh lebih lama dari batas session.lifetime (default
        // 120 menit) - sesi ini seharusnya sudah dianggap basi/mati.
        $this->buatSesiAktifDiPerangkatLain(
            $user,
            'sesi-basi',
            now()->subMinutes(300)->getTimestamp()
        );

        session(['gerbang_akses_login_user_id' => $user->id]);

        Volt::test('pages.auth.login')
            ->set('form.username', $user->username)
            ->set('form.password', 'password')
            ->call('login')
            ->assertSet('form.tampilkanPopupPerangkatLain', false);

        $this->assertAuthenticatedAs($user);

        // Baris lama yang basi otomatis dibersihkan (logout_at terisi) oleh
        // cariSesiLainAktif() supaya tidak terus-menerus dicek ulang.
        $this->assertNotNull(
            LoginHistory::where('session_id', 'sesi-basi')->first()?->logout_at
        );
    }

    public function test_paksa_logout_perangkat_lain_berhasil_dengan_password_benar(): void
    {
        $user = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);
        $this->buatSesiAktifDiPerangkatLain($user);

        session(['gerbang_akses_login_user_id' => $user->id]);

        $component = Volt::test('pages.auth.login')
            ->set('form.username', $user->username)
            ->set('form.password', 'password')
            ->call('login')
            ->assertSet('form.tampilkanPopupPerangkatLain', true);

        $component
            ->set('form.passwordKonfirmasiPaksa', 'password')
            ->call('paksaLogoutPerangkatLain')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull(
            LoginHistory::where('session_id', 'sesi-perangkat-lain')->first()?->logout_at
        );
    }

    public function test_paksa_logout_perangkat_lain_gagal_jika_password_salah(): void
    {
        $user = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);
        $this->buatSesiAktifDiPerangkatLain($user);

        session(['gerbang_akses_login_user_id' => $user->id]);

        $component = Volt::test('pages.auth.login')
            ->set('form.username', $user->username)
            ->set('form.password', 'password')
            ->call('login')
            ->assertSet('form.tampilkanPopupPerangkatLain', true);

        $component
            ->set('form.passwordKonfirmasiPaksa', 'password-yang-salah')
            ->call('paksaLogoutPerangkatLain')
            ->assertHasErrors('form.passwordKonfirmasiPaksa');

        $this->assertGuest();
    }

    public function test_batalkan_popup_perangkat_lain_mengembalikan_ke_form_login(): void
    {
        $user = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);
        $this->buatSesiAktifDiPerangkatLain($user);

        session(['gerbang_akses_login_user_id' => $user->id]);

        Volt::test('pages.auth.login')
            ->set('form.username', $user->username)
            ->set('form.password', 'password')
            ->call('login')
            ->assertSet('form.tampilkanPopupPerangkatLain', true)
            ->call('batalkanPopupPerangkatLain')
            ->assertSet('form.tampilkanPopupPerangkatLain', false);

        $this->assertGuest();
    }

    // --- Login Harus Sesuai Email yang Diverifikasi ---------------------

    public function test_login_ditolak_jika_akun_tidak_sesuai_email_yang_baru_diverifikasi(): void
    {
        $akunDiverifikasi = User::factory()->create();
        $akunDicobaLogin = User::factory()->create();

        // Lolos gerbang verifikasi untuk akun A, tapi coba login pakai
        // akun B yang berbeda (kredensial akun B benar).
        session(['gerbang_akses_login_user_id' => $akunDiverifikasi->id]);

        $component = Volt::test('pages.auth.login')
            ->set('form.username', $akunDicobaLogin->username)
            ->set('form.password', 'password')
            ->call('login');

        $component->assertSet('form.tidakSesuaiEmailTerverifikasi', true);
        $this->assertGuest();

        // Gerbang verifikasi lama dihapus - wajib verifikasi ulang dari awal.
        $this->assertNull(session('gerbang_akses_login_user_id'));

        // Dihitung sebagai percobaan login gagal juga (permintaan user
        // 2026-10-04) - tercatat di log Percobaan Login Gagal.
        $this->assertSame(
            1,
            FailedLoginAttempt::where('username_dicoba', $akunDicobaLogin->username)->count()
        );
    }

    public function test_login_berhasil_jika_akun_sesuai_email_yang_diverifikasi(): void
    {
        $user = User::factory()->create();

        session(['gerbang_akses_login_user_id' => $user->id]);

        Volt::test('pages.auth.login')
            ->set('form.username', $user->username)
            ->set('form.password', 'password')
            ->call('login')
            ->assertSet('form.tidakSesuaiEmailTerverifikasi', false);

        $this->assertAuthenticatedAs($user);
    }

    public function test_pemulihan_ditolak_jika_akun_tidak_sesuai_email_yang_diverifikasi(): void
    {
        $akunDiverifikasi = User::factory()->create();
        $superadmin = User::factory()->create([
            'username' => 'superadmin',
            'level_akses' => User::LEVEL_SUPERADMIN,
        ]);

        // Lolos gerbang verifikasi untuk akun lain, tapi coba Pemulihan
        // Akun pakai akun Superadmin (kata sandi pemulihan benar).
        session(['gerbang_akses_login_user_id' => $akunDiverifikasi->id]);

        Volt::test('pages.auth.login')
            ->set('form.username', 'superadmin')
            ->set('form.password', 'superadmin')
            ->call('pemulihan')
            ->assertSet('form.tidakSesuaiEmailTerverifikasi', true);

        $this->assertGuest();
        $this->assertFalse($superadmin->fresh()->must_change_password);
    }
}
