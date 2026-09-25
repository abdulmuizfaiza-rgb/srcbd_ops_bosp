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

    public function test_superadmin_dapat_menambah_sekolah_baru(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(\App\Livewire\ProfilSekolah\Index::class)
            ->call('tambah')
            ->set('npsn', '12345678')
            ->set('nama_sekolah', 'SR Cibadak 1')
            ->set('status', ProfilSekolah::STATUS_NEGERI)
            ->set('kecamatan', 'Cibadak')
            ->set('nama_kepala_sekolah', 'Budi Santoso')
            ->set('nip_kepala_sekolah', '196501011990031001')
            ->set('alamat_sekolah', 'Jl. Cibadak No. 1')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('profil_sekolah', [
            'npsn' => '12345678',
            'nama_sekolah' => 'SR Cibadak 1',
            'status' => 'negeri',
            'kecamatan' => 'Cibadak',
            'nama_kepala_sekolah' => 'Budi Santoso',
        ]);
    }

    public function test_admin_ops_hanya_melihat_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak 1', 'status' => ProfilSekolah::STATUS_NEGERI, 'kecamatan' => 'Cibadak']);
        $sekolahLain = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak Lain']);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        $this->actingAs($adminOps)
            ->get('/profil-sekolah')
            ->assertOk()
            ->assertSee('SR Cibadak 1')
            ->assertDontSee('SR Cibadak Lain');
    }

    public function test_superadmin_tetap_melihat_semua_sekolah(): void
    {
        ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak 1']);
        ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak Lain']);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $this->actingAs($superadmin)
            ->get('/profil-sekolah')
            ->assertOk()
            ->assertSee('SR Cibadak 1')
            ->assertSee('SR Cibadak Lain');
    }

    public function test_admin_ops_tidak_bisa_menghapus_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(\App\Livewire\ProfilSekolah\Index::class)
            ->call('konfirmasiHapus', $sekolahSaya->id)
            ->assertForbidden();
    }

    public function test_admin_ops_tidak_bisa_menambah_sekolah_baru(): void
    {
        $adminOps = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        Livewire::actingAs($adminOps)
            ->test(\App\Livewire\ProfilSekolah\Index::class)
            ->call('tambah')
            ->assertForbidden();
    }

    public function test_admin_ops_tidak_bisa_mengedit_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        Livewire::actingAs($adminOps)
            ->test(\App\Livewire\ProfilSekolah\Index::class)
            ->call('edit', $sekolahLain->id)
            ->assertForbidden();
    }

    public function test_admin_ops_bisa_mengedit_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(\App\Livewire\ProfilSekolah\Index::class)
            ->call('edit', $sekolahSaya->id)
            ->set('nama_kepala_sekolah', 'Siti Aminah')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('profil_sekolah', [
            'id' => $sekolahSaya->id,
            'nama_kepala_sekolah' => 'Siti Aminah',
        ]);
    }

    public function test_nip_bendahara_asn_wajib_18_digit(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(\App\Livewire\ProfilSekolah\Index::class)
            ->call('edit', $sekolahSaya->id)
            ->set('status_kepegawaian_bendahara', 'PNS')
            ->set('nip_bendahara', '12345')
            ->call('simpan')
            ->assertHasErrors(['nip_bendahara']);

        Livewire::actingAs($adminOps)
            ->test(\App\Livewire\ProfilSekolah\Index::class)
            ->call('edit', $sekolahSaya->id)
            ->set('status_kepegawaian_bendahara', 'PNS')
            ->set('nip_bendahara', '196501011990031001')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('profil_sekolah', [
            'id' => $sekolahSaya->id,
            'nip_bendahara' => '196501011990031001',
        ]);
    }

    public function test_nip_bendahara_non_asn_harus_tanda_strip(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(\App\Livewire\ProfilSekolah\Index::class)
            ->call('edit', $sekolahSaya->id)
            ->set('status_kepegawaian_bendahara', 'Honorer')
            ->set('nip_bendahara', '196501011990031001')
            ->call('simpan')
            ->assertHasErrors(['nip_bendahara']);

        Livewire::actingAs($adminOps)
            ->test(\App\Livewire\ProfilSekolah\Index::class)
            ->call('edit', $sekolahSaya->id)
            ->set('status_kepegawaian_bendahara', 'Honorer')
            ->set('nip_bendahara', '-')
            ->call('simpan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('profil_sekolah', [
            'id' => $sekolahSaya->id,
            'nip_bendahara' => '-',
        ]);
    }
}
