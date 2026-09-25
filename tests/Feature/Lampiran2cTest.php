<?php

namespace Tests\Feature;

use App\Livewire\PendataanOps\Lampiran2c\Index;
use App\Models\Lampiran2c;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Lampiran2cTest extends TestCase
{
    use RefreshDatabase;

    protected function dataForm(array $overrides = []): array
    {
        return array_merge([
            'nrg' => '123456789012',
            'nuptk' => '1234567890123456',
            'nama_ptk' => 'Ahmad Fauzi',
            'kecamatan' => 'Cibadak',
            'jenis_kepangkatan' => 'Pangkat',
            'golongan' => 'Penata Muda III/a',
            'masa_kerja' => '5 Tahun',
            'pangkat_berkala' => 'Pangkat',
            'tmt' => '2026-01-01',
            'gaji_pokok_lama' => '3000000',
            'gaji_pokok_baru' => '3500000',
            'keterangan' => 'Kenaikan gaji berkala',
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

        foreach ($this->dataForm() as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseHas('lampiran_2c', [
            'profil_sekolah_id' => $sekolah->id,
            'triwulan' => 1,
            'nama_ptk' => 'Ahmad Fauzi',
            'nrg' => '123456789012',
            'nuptk' => '1234567890123456',
            'gaji_pokok_lama' => 3000000,
            'gaji_pokok_baru' => 3500000,
        ]);
    }

    public function test_tahun_otomatis_terisi_dengan_tahun_sekarang_saat_simpan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('tambah');

        foreach ($this->dataForm() as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseHas('lampiran_2c', [
            'profil_sekolah_id' => $sekolah->id,
            'nama_ptk' => 'Ahmad Fauzi',
            'tahun' => now()->year,
        ]);
    }

    public function test_simpan_gagal_jika_nrg_bukan_12_digit(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('tambah');

        foreach ($this->dataForm(['nrg' => '123']) as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasErrors(['nrg']);
    }

    public function test_admin_ops_tidak_bisa_menyisipkan_data_untuk_sekolah_lain(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class)
            ->call('tambah')
            ->set('profil_sekolah_id', $sekolahLain->id);

        foreach ($this->dataForm() as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseHas('lampiran_2c', [
            'profil_sekolah_id' => $sekolahSaya->id,
            'nama_ptk' => 'Ahmad Fauzi',
        ]);
        $this->assertDatabaseMissing('lampiran_2c', [
            'profil_sekolah_id' => $sekolahLain->id,
        ]);
    }

    public function test_data_terpisah_per_triwulan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Lampiran2c::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1, 'nama_ptk' => 'PTK Triwulan 1']);
        Lampiran2c::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 2, 'nama_ptk' => 'PTK Triwulan 2']);

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

        Lampiran2c::factory()->create(['profil_sekolah_id' => $sekolahSaya->id, 'triwulan' => 1, 'nama_ptk' => 'PTK Sekolah Saya']);
        Lampiran2c::factory()->create(['profil_sekolah_id' => $sekolahLain->id, 'triwulan' => 1, 'nama_ptk' => 'PTK Sekolah Lain']);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->assertSee('PTK Sekolah Saya')
            ->assertDontSee('PTK Sekolah Lain');
    }

    public function test_admin_ops_tidak_bisa_mengedit_data_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $baris = Lampiran2c::factory()->create(['profil_sekolah_id' => $sekolahLain->id, 'triwulan' => 1]);
        $adminOps = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('edit', $baris->id)
            ->assertForbidden();
    }

    public function test_superadmin_bisa_export_data(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        Lampiran2c::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('filterSekolahId', $sekolah->id)
            ->call('export')
            ->assertFileDownloaded('lampiran-2c-triwulan-1.xlsx');
    }

    public function test_superadmin_wajib_pilih_sekolah_dulu_sebelum_export(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        Lampiran2c::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1]);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->call('export')
            ->assertSet('errorExport', fn ($pesan) => ! empty($pesan));
    }

    public function test_admin_ops_bisa_export_tanpa_pilih_sekolah_karena_sudah_terkunci(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        Lampiran2c::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1]);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('export')
            ->assertFileDownloaded('lampiran-2c-triwulan-1.xlsx');
    }

}
