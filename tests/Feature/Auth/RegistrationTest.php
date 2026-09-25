<?php

namespace Tests\Feature\Auth;

use App\Models\PengaturanTampilan;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Warna/ukuran/jenis huruf form Registrasi (diatur lewat menu
     * Tampilan, 2026-09-05) harus benar-benar dipakai halaman registrasi
     * lewat CSS var --warna-huruf-registrasi/--ukuran-registrasi/--font-registrasi
     * pada layout guest, dan class "form-registrasi" pada halaman itu
     * sendiri.
     */
    public function test_halaman_registrasi_memuat_css_var_huruf_registrasi(): void
    {
        $tampilan = PengaturanTampilan::current();
        $tampilan->update(['warna_huruf_registrasi' => '#ab12cd']);
        PengaturanTampilan::lupakanCache();

        $response = $this->get(route('register'));

        $response->assertOk();
        $response->assertSee('--warna-huruf-registrasi: #ab12cd', false);
        $response->assertSee('form-registrasi', false);
    }

    /**
     * Dropdown Nama Sekolah pada form Registrasi harus berurutan Status
     * (Negeri dulu, baru Swasta) -> Kecamatan -> Nama Sekolah - mengikuti
     * urutan baku yang sudah dipakai di menu Profil Sekolah & Unduhan
     * (2026-09-05, lanjutan).
     */
    public function test_dropdown_nama_sekolah_registrasi_urut_negeri_dulu_baru_swasta(): void
    {
        ProfilSekolah::factory()->create(['nama_sekolah' => 'SMP Swasta Bakti', 'status' => 'swasta', 'kecamatan' => 'Cibadak']);
        ProfilSekolah::factory()->create(['nama_sekolah' => 'SDN Contoh B', 'status' => 'negeri', 'kecamatan' => 'Caringin']);
        ProfilSekolah::factory()->create(['nama_sekolah' => 'SDN Contoh A', 'status' => 'negeri', 'kecamatan' => 'Caringin']);

        $component = Volt::test('pages.auth.register');

        $urutan = $component->viewData('sekolahOptions')->pluck('nama_sekolah')->all();

        $this->assertSame(['SDN Contoh A', 'SDN Contoh B', 'SMP Swasta Bakti'], $urutan);
    }

    public function test_link_registrasi_di_halaman_login_pakai_teks_dan_icon_baru(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Registrasi Admin OPS / Admin BOSP');
        $response->assertDontSee('Daftar sebagai Admin OPS/BOSP');
    }

    public function test_admin_ops_dapat_mendaftar_dan_menunggu_persetujuan(): void
    {
        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak', 'npsn' => '20102030']);

        Volt::test('pages.auth.register')
            ->set('level_akses', User::LEVEL_ADMIN_OPS)
            ->set('profil_sekolah_id', $sekolah->id)
            ->set('email', 'ops.cibadak@contoh.sch.id')
            ->set('password', 'rahasia123')
            ->set('password_confirmation', 'rahasia123')
            ->call('daftar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'username' => 'ops.cibadak@contoh.sch.id',
            'level_akses' => 'admin_ops',
            'profil_sekolah_id' => $sekolah->id,
            'is_approved' => 0,
            'must_change_password' => 1,
        ]);

        $this->assertGuest();
    }

    public function test_registrasi_gagal_jika_sekolah_sudah_punya_admin_ops(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Volt::test('pages.auth.register')
            ->set('level_akses', User::LEVEL_ADMIN_OPS)
            ->set('profil_sekolah_id', $sekolah->id)
            ->set('email', 'ops.baru@contoh.sch.id')
            ->set('password', 'rahasia123')
            ->set('password_confirmation', 'rahasia123')
            ->call('daftar')
            ->assertHasErrors(['profil_sekolah_id']);
    }

    public function test_akun_yang_belum_disetujui_tidak_bisa_login(): void
    {
        User::factory()->create([
            'username' => 'ops.belum@contoh.sch.id',
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'password' => bcrypt('rahasia123'),
            'is_approved' => false,
        ]);

        Volt::test('pages.auth.login')
            ->set('form.username', 'ops.belum@contoh.sch.id')
            ->set('form.password', 'rahasia123')
            ->call('login')
            ->assertHasErrors(['form.username']);

        $this->assertGuest();
    }

    public function test_akun_yang_sudah_disetujui_bisa_login(): void
    {
        $user = User::factory()->create([
            'username' => 'ops.sudah@contoh.sch.id',
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'password' => bcrypt('rahasia123'),
            'is_approved' => true,
        ]);

        Volt::test('pages.auth.login')
            ->set('form.username', 'ops.sudah@contoh.sch.id')
            ->set('form.password', 'rahasia123')
            ->call('login')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }
}
