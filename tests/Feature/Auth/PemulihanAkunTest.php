<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PemulihanAkunTest extends TestCase
{
    use RefreshDatabase;

    public function test_tombol_pemulihan_muncul_setelah_dua_kali_gagal_login(): void
    {
        User::factory()->create(['username' => 'superadmin', 'level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Volt::test('pages.auth.login')
            ->set('form.username', 'superadmin')
            ->set('form.password', 'salah-1')
            ->call('login');

        $this->assertSame(1, $component->get('form.percobaanGagal'));

        $component->set('form.password', 'salah-2')->call('login');

        $this->assertSame(2, $component->get('form.percobaanGagal'));
    }

    public function test_pemulihan_superadmin_berhasil_dengan_kata_sandi_default(): void
    {
        $user = User::factory()->create(['username' => 'superadmin', 'level_akses' => User::LEVEL_SUPERADMIN]);

        Volt::test('pages.auth.login')
            ->set('form.username', 'superadmin')
            ->set('form.password', 'salah')
            ->call('login')
            ->set('form.password', 'salah-lagi')
            ->call('login')
            ->set('form.password', 'superadmin')
            ->call('pemulihan')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_pemulihan_admin_ops_gagal_jika_kata_sandi_tidak_sesuai(): void
    {
        User::factory()->create(['username' => 'ops-sekolah-a', 'level_akses' => User::LEVEL_ADMIN_OPS]);

        Volt::test('pages.auth.login')
            ->set('form.username', 'ops-sekolah-a')
            ->set('form.password', 'adminbosp')
            ->call('pemulihan')
            ->assertHasErrors(['form.username']);

        $this->assertGuest();
    }

    /**
     * Regresi (2026-09-05): jalur pemulihan mandiri (kata sandi default)
     * SENGAJA dinonaktifkan total untuk Admin OPS/Admin BOSP - satu-satunya
     * cara reset password kedua level ini sekarang lewat tombol "Reset
     * Password" milik Superadmin di menu Kelola Pengguna (lihat
     * PenggunaResetPasswordTest). Kata sandi pemulihan default lama
     * ('adminbosp') TIDAK BOLEH bisa dipakai lagi dengan cara apapun,
     * walau diketik dengan benar.
     */
    public function test_pemulihan_admin_bosp_ditolak_karena_jalur_pemulihan_mandiri_dinonaktifkan(): void
    {
        $user = User::factory()->create(['username' => 'bosp-sekolah-a', 'level_akses' => User::LEVEL_ADMIN_BOSP]);

        Volt::test('pages.auth.login')
            ->set('form.username', 'bosp-sekolah-a')
            ->set('form.password', 'adminbosp')
            ->call('pemulihan')
            ->assertHasErrors(['form.username']);

        $this->assertGuest();
        $this->assertFalse($user->fresh()->must_change_password);
    }

    /**
     * Sama seperti di atas, untuk Admin OPS.
     */
    public function test_pemulihan_admin_ops_ditolak_karena_jalur_pemulihan_mandiri_dinonaktifkan(): void
    {
        $user = User::factory()->create(['username' => 'ops-sekolah-b', 'level_akses' => User::LEVEL_ADMIN_OPS]);

        Volt::test('pages.auth.login')
            ->set('form.username', 'ops-sekolah-b')
            ->set('form.password', 'adminops')
            ->call('pemulihan')
            ->assertHasErrors(['form.username']);

        $this->assertGuest();
        $this->assertFalse($user->fresh()->must_change_password);
    }

    public function test_pengguna_wajib_ganti_password_sebelum_bisa_akses_menu_lain(): void
    {
        $user = User::factory()->create([
            'level_akses' => User::LEVEL_SUPERADMIN,
            'must_change_password' => true,
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('ganti-password-wajib'));
    }

    public function test_setelah_ganti_password_bisa_akses_menu_lain(): void
    {
        $user = User::factory()->create([
            'level_akses' => User::LEVEL_SUPERADMIN,
            'must_change_password' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages.ganti-password-wajib')
            ->set('password', 'PasswordBaru123!')
            ->set('password_confirmation', 'PasswordBaru123!')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertFalse($user->fresh()->must_change_password);

        $this->actingAs($user->fresh())
            ->get('/dashboard')
            ->assertOk();
    }

    /**
     * Password baru wajib minimal 6 karakter + kombinasi huruf besar,
     * huruf kecil, angka, dan simbol - sama seperti form Update Password
     * di halaman Profil.
     */
    public function test_password_baru_wajib_ganti_ditolak_kalau_tidak_sesuai_ketentuan_kekuatan(): void
    {
        $user = User::factory()->create([
            'level_akses' => User::LEVEL_SUPERADMIN,
            'must_change_password' => true,
        ]);

        Livewire::actingAs($user)
            ->test('pages.ganti-password-wajib')
            ->set('password', 'lemah')
            ->set('password_confirmation', 'lemah')
            ->call('simpan')
            ->assertHasErrors(['password']);

        $this->assertTrue($user->fresh()->must_change_password);
    }

    /**
     * Regresi bug nyata: sebelumnya, form "Ganti Password Wajib" TIDAK
     * PERNAH bisa disimpan sama sekali (tombol "Simpan Password Baru"
     * terlihat seperti tidak berfungsi) - root cause: request AJAX
     * Livewire yang memproses tombol Simpan itu sendiri (POST ke
     * "livewire/update", BUKAN ke route "ganti-password-wajib") ikut
     * dibelokkan oleh middleware `ForcePasswordChange` (karena
     * `must_change_password` memang masih true SEBELUM method simpan()
     * sempat mengubahnya), sehingga method simpan() TIDAK PERNAH sempat
     * dijalankan.
     *
     * PENTING: test Livewire biasa (`Livewire::test()->call('simpan')`,
     * seperti test di atas) TIDAK mendeteksi bug jenis ini sama sekali -
     * cara itu tidak benar-benar melewati middleware "web" seperti
     * request AJAX sungguhan dari browser. Makanya test ini SENGAJA
     * mengirim request HTTP asli ke endpoint "livewire/update" (sama
     * seperti yang dilakukan browser sungguhan lewat wire:submit) untuk
     * memastikan middleware TIDAK ikut membelokkannya.
     */
    public function test_endpoint_ajax_livewire_tidak_ikut_dialihkan_walau_wajib_ganti_password(): void
    {
        $user = User::factory()->create([
            'level_akses' => User::LEVEL_SUPERADMIN,
            'must_change_password' => true,
        ]);

        $response = $this->actingAs($user)->post('/livewire/update', []);

        // Middleware TIDAK BOLEH membelokkan request ajax ini ke halaman
        // "Ganti Password Wajib" - kalau ikut dibelokkan, response-nya akan
        // berupa redirect (302) persis ke URL route itu.
        $this->assertNotEquals(
            route('ganti-password-wajib'),
            $response->headers->get('Location'),
            'Endpoint ajax Livewire seharusnya TIDAK ikut dialihkan oleh middleware ForcePasswordChange.'
        );
    }
}
