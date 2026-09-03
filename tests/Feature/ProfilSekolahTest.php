<?php

namespace Tests\Feature;

use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfilSekolahTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_dapat_melengkapi_profil_sekolah(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\ProfilSekolah\Index::class)
            ->set('npsn', '12345678')
            ->set('nama_sekolah', 'SR Cibadak')
            ->set('nama_kepala_sekolah', 'Budi Santoso')
            ->set('nip_kepala_sekolah', '196501011990031001')
            ->set('alamat_sekolah', 'Jl. Cibadak No. 1')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('profil_sekolah', [
            'npsn' => '12345678',
            'nama_sekolah' => 'SR Cibadak',
            'nama_kepala_sekolah' => 'Budi Santoso',
        ]);
    }

    public function test_admin_ops_tidak_bisa_akses_profil_sekolah(): void
    {
        $adminOps = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        $this->actingAs($adminOps)
            ->get('/profil-sekolah')
            ->assertForbidden();
    }
}
