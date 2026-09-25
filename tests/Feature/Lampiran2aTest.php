<?php

namespace Tests\Feature;

use App\Livewire\PendataanOps\Lampiran2a\Index;
use App\Models\Lampiran2a;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Lampiran2aTest extends TestCase
{
    use RefreshDatabase;

    protected function dataLengkap(array $overrides = []): array
    {
        return array_merge([
            'nrg' => '123456789012',
            'nuptk' => '1234567890123456',
            'nama_ptk' => 'Ahmad Fauzi',
            'status_kepegawaian' => 'PNS',
            'gaji_pokok_januari' => '4500000',
            'npwp' => '123456789012345',
        ], $overrides);
    }

    public function test_admin_ops_bisa_menambah_data_untuk_sekolahnya_sendiri(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('tambah');

        foreach ($this->dataLengkap() as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseHas('lampiran_2a', [
            'profil_sekolah_id' => $sekolah->id,
            'triwulan' => 1,
            'nama_ptk' => 'Ahmad Fauzi',
        ]);
    }

    public function test_admin_ops_tidak_bisa_menyisipkan_data_untuk_sekolah_lain(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('tambah');

        foreach ($this->dataLengkap(['profil_sekolah_id' => $sekolahLain->id]) as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasNoErrors();

        // Selalu dipaksa ke sekolah sendiri, terlepas dari nilai yang dikirim.
        $this->assertDatabaseHas('lampiran_2a', [
            'profil_sekolah_id' => $sekolahSaya->id,
            'nama_ptk' => 'Ahmad Fauzi',
        ]);
        $this->assertDatabaseMissing('lampiran_2a', [
            'profil_sekolah_id' => $sekolahLain->id,
        ]);
    }

    public function test_validasi_nrg_nuptk_npwp(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('tambah');
        foreach ($this->dataLengkap(['nrg' => '123']) as $field => $value) {
            $component->set($field, $value);
        }
        $component->call('simpan')->assertHasErrors(['nrg']);

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('tambah');
        foreach ($this->dataLengkap(['nuptk' => '123']) as $field => $value) {
            $component->set($field, $value);
        }
        $component->call('simpan')->assertHasErrors(['nuptk']);

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('tambah');
        foreach ($this->dataLengkap(['npwp' => '123']) as $field => $value) {
            $component->set($field, $value);
        }
        $component->call('simpan')->assertHasErrors(['npwp']);
    }

    public function test_npwp_15_atau_16_digit_diterima(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('tambah');
        foreach ($this->dataLengkap(['npwp' => '1234567890123456']) as $field => $value) {
            $component->set($field, $value);
        }
        $component->call('simpan')->assertHasNoErrors();
    }

    public function test_data_terpisah_per_triwulan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1, 'nama_ptk' => 'PTK Triwulan 1']);
        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 2, 'nama_ptk' => 'PTK Triwulan 2']);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->assertSee('PTK Triwulan 1')
            ->assertDontSee('PTK Triwulan 2')
            ->call('pindahTab', 2)
            ->assertSee('PTK Triwulan 2')
            ->assertDontSee('PTK Triwulan 1');
    }

    public function test_admin_ops_hanya_melihat_data_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolahSaya->id, 'triwulan' => 1, 'nama_ptk' => 'PTK Sekolah Saya']);
        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolahLain->id, 'triwulan' => 1, 'nama_ptk' => 'PTK Sekolah Lain']);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->assertSee('PTK Sekolah Saya')
            ->assertDontSee('PTK Sekolah Lain');
    }

    public function test_admin_ops_tidak_bisa_mengedit_data_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $baris = Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolahLain->id, 'triwulan' => 1]);
        $adminOps = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('edit', $baris->id)
            ->assertForbidden();
    }

    public function test_superadmin_bisa_export_data(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('filterSekolahId', $sekolah->id)
            ->call('export')
            ->assertFileDownloaded('lampiran-2a-triwulan-1.xlsx');
    }

    public function test_superadmin_wajib_pilih_sekolah_dulu_sebelum_export(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('export')
            ->assertSet('errorExport', fn ($pesan) => ! empty($pesan));
    }

    public function test_admin_ops_bisa_export_tanpa_pilih_sekolah_karena_sudah_terkunci(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        Lampiran2a::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1]);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('export')
            ->assertFileDownloaded('lampiran-2a-triwulan-1.xlsx');
    }
}
