<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Memverifikasi hak akses tiap level: Superadmin, Admin OPS, Admin BOSP.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $level): User
    {
        return User::factory()->create(['level_akses' => $level]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/profil-sekolah')->assertRedirect('/login');
        $this->get('/pendataan-ops')->assertRedirect('/login');
        $this->get('/pendataan-bosp')->assertRedirect('/login');
        $this->get('/pengguna')->assertRedirect('/login');
    }

    public function test_superadmin_can_access_all_menus(): void
    {
        $user = $this->makeUser(User::LEVEL_SUPERADMIN);

        $this->actingAs($user);

        $this->get('/dashboard')->assertOk();
        $this->get('/profil-sekolah')->assertOk();
        $this->get('/pendataan-ops')->assertOk();
        $this->get('/pendataan-bosp')->assertOk();
        $this->get('/pengguna')->assertOk();
    }

    public function test_admin_ops_can_only_access_pendataan_ops(): void
    {
        $user = $this->makeUser(User::LEVEL_ADMIN_OPS);

        $this->actingAs($user);

        $this->get('/dashboard')->assertOk();
        $this->get('/pendataan-ops')->assertOk();
        $this->get('/profil-sekolah')->assertForbidden();
        $this->get('/pendataan-bosp')->assertForbidden();
        $this->get('/pengguna')->assertForbidden();
    }

    public function test_admin_bosp_can_only_access_pendataan_bosp(): void
    {
        $user = $this->makeUser(User::LEVEL_ADMIN_BOSP);

        $this->actingAs($user);

        $this->get('/dashboard')->assertOk();
        $this->get('/pendataan-bosp')->assertOk();
        $this->get('/profil-sekolah')->assertForbidden();
        $this->get('/pendataan-ops')->assertForbidden();
        $this->get('/pengguna')->assertForbidden();
    }
}
