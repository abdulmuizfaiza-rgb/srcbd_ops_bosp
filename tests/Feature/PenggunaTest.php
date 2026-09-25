<?php

namespace Tests\Feature;

use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PenggunaTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_dapat_menambah_admin_ops(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $sekolah = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak']);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Pengguna\Index::class)
            ->set('level_akses', User::LEVEL_ADMIN_OPS)
            ->set('profil_sekolah_id', $sekolah->id)
            ->set('username', 'ops.sekolah@contoh.sch.id')
            ->set('password', 'rahasia123')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'username' => 'ops.sekolah@contoh.sch.id',
            'level_akses' => 'admin_ops',
            'profil_sekolah_id' => $sekolah->id,
            'nama_sekolah' => 'SR Cibadak',
            'jabatan' => 'Operator Sekolah',
            'is_approved' => 1,
        ]);
    }

    public function test_username_admin_ops_harus_email(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $sekolah = ProfilSekolah::factory()->create();

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Pengguna\Index::class)
            ->set('level_akses', User::LEVEL_ADMIN_OPS)
            ->set('profil_sekolah_id', $sekolah->id)
            ->set('username', 'bukan-email')
            ->set('password', 'rahasia123')
            ->call('simpan')
            ->assertHasErrors(['username']);
    }

    public function test_username_harus_unik(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $sekolah = ProfilSekolah::factory()->create();
        User::factory()->create(['username' => 'dipakai@contoh.sch.id']);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Pengguna\Index::class)
            ->set('level_akses', User::LEVEL_ADMIN_OPS)
            ->set('profil_sekolah_id', $sekolah->id)
            ->set('username', 'dipakai@contoh.sch.id')
            ->set('password', 'rahasia123')
            ->call('simpan')
            ->assertHasErrors(['username']);
    }

    public function test_satu_sekolah_tidak_bisa_punya_dua_admin_ops(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $sekolah = ProfilSekolah::factory()->create();
        User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Pengguna\Index::class)
            ->set('level_akses', User::LEVEL_ADMIN_OPS)
            ->set('profil_sekolah_id', $sekolah->id)
            ->set('username', 'ops.kedua@contoh.sch.id')
            ->set('password', 'rahasia123')
            ->call('simpan')
            ->assertHasErrors(['profil_sekolah_id']);
    }

    public function test_satu_sekolah_boleh_punya_admin_ops_dan_admin_bosp_sekaligus(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $sekolah = ProfilSekolah::factory()->create();
        User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Pengguna\Index::class)
            ->set('level_akses', User::LEVEL_ADMIN_BOSP)
            ->set('profil_sekolah_id', $sekolah->id)
            ->set('username', 'bosp.sekolah@contoh.sch.id')
            ->set('password', 'rahasia123')
            ->call('simpan')
            ->assertHasNoErrors();
    }

    public function test_superadmin_dapat_menyetujui_akun_yang_menunggu(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $pending = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'is_approved' => false,
        ]);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Pengguna\Index::class)
            ->set('tab', User::LEVEL_ADMIN_OPS)
            ->call('setujui', $pending->id);

        $this->assertTrue($pending->fresh()->is_approved);
    }

    public function test_tab_hanya_menampilkan_pengguna_sesuai_level(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS, 'nama_sekolah' => 'Sekolah OPS Unik']);
        User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP, 'nama_sekolah' => 'Sekolah BOSP Unik']);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Pengguna\Index::class)
            ->set('tab', User::LEVEL_ADMIN_OPS)
            ->assertSee('Sekolah OPS Unik')
            ->assertDontSee('Sekolah BOSP Unik');
    }

    public function test_superadmin_dapat_menghapus_pengguna_lain(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $target = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Pengguna\Index::class)
            ->set('tab', User::LEVEL_ADMIN_OPS)
            ->call('konfirmasiHapus', $target->id)
            ->call('hapus');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }
}
