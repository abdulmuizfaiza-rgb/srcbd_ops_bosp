<?php

namespace Tests\Feature;

use App\Livewire\PendataanBosp\Index;
use App\Models\PendataanBosp;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PendataanBospTest extends TestCase
{
    use RefreshDatabase;

    protected function dataLengkap(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Siti Bendahara BOSP',
            'jk' => 'P',
            'status_kepegawaian' => 'Honorer',
            'pendidikan_terakhir' => 'SMA',
            'no_whatsapp' => '081234567890',
        ], $overrides);
    }

    public function test_admin_bosp_bisa_mengisi_identitas_sekolahnya_sendiri(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class)->call('isi', $sekolah->id);

        foreach ($this->dataLengkap() as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseHas('pendataan_bosp', [
            'profil_sekolah_id' => $sekolah->id,
            'nama' => 'Siti Bendahara BOSP',
        ]);
    }

    public function test_admin_bosp_tidak_bisa_mengisi_identitas_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_BOSP]);

        Livewire::actingAs($adminBosp)
            ->test(Index::class)
            ->call('isi', $sekolahLain->id)
            ->assertForbidden();
    }

    public function test_jurusan_wajib_untuk_pendidikan_s2(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminBosp)->test(Index::class)->call('isi', $sekolah->id);

        foreach ($this->dataLengkap(['pendidikan_terakhir' => 'S2']) as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasErrors(['jurusan', 'nama_perguruan_tinggi']);
    }

    public function test_superadmin_bisa_mengedit_identitas_sekolah_manapun(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PendataanBosp::factory()->create(['profil_sekolah_id' => $sekolah->id, 'nama' => 'Nama Lama']);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('isi', $sekolah->id);

        foreach ($this->dataLengkap(['nama' => 'Nama Baru']) as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseHas('pendataan_bosp', [
            'profil_sekolah_id' => $sekolah->id,
            'nama' => 'Nama Baru',
        ]);

        $this->assertDatabaseCount('pendataan_bosp', 1);
    }

    public function test_admin_bosp_hanya_melihat_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak Saya']);
        $sekolahLain = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak Lain']);
        $adminBosp = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_BOSP,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        $this->actingAs($adminBosp)
            ->get('/pendataan-bosp')
            ->assertOk()
            ->assertSee('SR Cibadak Saya')
            ->assertDontSee('SR Cibadak Lain');
    }
}
