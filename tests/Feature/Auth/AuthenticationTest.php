<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login');
    }

    /**
     * Round ketujuh belas (2026-09-24, permintaan user): tombol
     * "Kembali ke Beranda" (landing page publik, route "beranda") harus
     * tampil di halaman login - lihat
     * resources/views/livewire/pages/auth/login.blade.php.
     */
    public function test_halaman_login_punya_tombol_kembali_ke_beranda(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Kembali ke Beranda')
            ->assertSee(route('beranda'), false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.username', $user->username)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.username', $user->username)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $component
            ->assertHasErrors()
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_navigation_menu_can_be_rendered(): void
    {
        // Superadmin dipakai supaya test ini murni menguji navigasi
        // Breeze, tidak ikut kena gate onboarding Profil Sekolah/Identitas
        // yang berlaku untuk Admin OPS/Admin BOSP (lihat OnboardingGateTest).
        $user = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response
            ->assertOk()
            ->assertSeeVolt('layout.navigation');
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('layout.navigation');

        $component->call('logout');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
