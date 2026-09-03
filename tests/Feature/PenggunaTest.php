<?php

namespace Tests\Feature;

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

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Pengguna\Index::class)
            ->set('level_akses', User::LEVEL_ADMIN_OPS)
            ->set('nama_sekolah', 'SR Cibadak')
            ->set('username', '20102030')
            ->set('password', 'rahasia123')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'username' => '20102030',
            'level_akses' => 'admin_ops',
            'nama_sekolah' => 'SR Cibadak',
            'jabatan' => 'Operator Sekolah',
        ]);
    }

    public function test_username_harus_unik(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        User::factory()->create(['username' => 'dipakai']);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Pengguna\Index::class)
            ->set('level_akses', User::LEVEL_ADMIN_OPS)
            ->set('nama_sekolah', 'SR Cibadak')
            ->set('username', 'dipakai')
            ->set('password', 'rahasia123')
            ->call('simpan')
            ->assertHasErrors(['username']);
    }

    public function test_superadmin_dapat_menghapus_pengguna_lain(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);
        $target = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\Pengguna\Index::class)
            ->call('konfirmasiHapus', $target->id)
            ->call('hapus');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }
}
