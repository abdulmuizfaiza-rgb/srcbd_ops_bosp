<?php

namespace Tests\Feature;

use App\Models\PendataanBosp;
use App\Models\PendataanOps;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Memverifikasi hak akses tiap level: Superadmin, Admin OPS, Admin BOSP.
 *
 * Admin OPS/Admin BOSP diberi Profil Sekolah yang sudah lengkap dan
 * Identitas OPS/BOSP yang sudah diisi, supaya test ini murni menguji hak
 * akses per-role (Gate) dan tidak ikut ter-redirect oleh gate onboarding
 * (lihat OnboardingGateTest untuk pengujian gate onboarding itu sendiri).
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $level): User
    {
        $sekolah = ProfilSekolah::factory()->create();

        $user = User::factory()->create([
            'level_akses' => $level,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        if ($level === User::LEVEL_ADMIN_OPS) {
            PendataanOps::factory()->create(['profil_sekolah_id' => $sekolah->id]);
        }

        if ($level === User::LEVEL_ADMIN_BOSP) {
            PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id]);
        }

        return $user;
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
        $this->get('/pendataan-ops/lampiran-2a')->assertOk();
        $this->get('/pendataan-ops/lampiran-2b')->assertOk();
        $this->get('/pendataan-ops/lampiran-2c')->assertOk();
        $this->get('/pendataan-ops/unduhan')->assertOk();
        $this->get('/pendataan-bosp')->assertOk();
        $this->get('/pengguna')->assertOk();
        $this->get('/tampilan')->assertOk();
    }

    public function test_admin_ops_can_access_pendataan_ops_and_profil_sekolah(): void
    {
        $user = $this->makeUser(User::LEVEL_ADMIN_OPS);

        $this->actingAs($user);

        $this->get('/dashboard')->assertOk();
        $this->get('/pendataan-ops')->assertOk();
        $this->get('/pendataan-ops/lampiran-2a')->assertOk();
        $this->get('/pendataan-ops/lampiran-2b')->assertOk();
        $this->get('/pendataan-ops/lampiran-2c')->assertOk();
        $this->get('/pendataan-ops/unduhan')->assertOk();
        $this->get('/profil-sekolah')->assertOk();
        $this->get('/pendataan-bosp')->assertForbidden();
        $this->get('/pengguna')->assertForbidden();
        $this->get('/tampilan')->assertForbidden();
    }

    public function test_admin_bosp_can_access_pendataan_bosp_and_profil_sekolah(): void
    {
        $user = $this->makeUser(User::LEVEL_ADMIN_BOSP);

        $this->actingAs($user);

        $this->get('/dashboard')->assertOk();
        $this->get('/pendataan-bosp')->assertOk();
        $this->get('/profil-sekolah')->assertOk();
        $this->get('/pendataan-ops')->assertForbidden();
        $this->get('/pendataan-ops/unduhan')->assertForbidden();
        $this->get('/pengguna')->assertForbidden();
        $this->get('/tampilan')->assertForbidden();
    }
}
