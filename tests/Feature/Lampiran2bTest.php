<?php

namespace Tests\Feature;

use App\Livewire\PendataanOps\Lampiran2b\Index;
use App\Models\DataPtk;
use App\Models\Lampiran2b;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Lampiran2bTest extends TestCase
{
    use RefreshDatabase;

    protected function dataForm(array $overrides = []): array
    {
        return array_merge([
            'nama_ptk' => 'Ahmad Fauzi',
            'keterangan' => 'Mutasi dari sekolah lain',
            'tmt' => '2026-01-01',
        ], $overrides);
    }

    public function test_admin_ops_bisa_menambah_data_untuk_sekolahnya_sendiri(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        DataPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'nama_ptk' => 'Ahmad Fauzi',
            'status_keaktifan' => 'Tidak Aktif',
            'status_sertifikasi' => DataPtk::STATUS_SERTIFIKASI_SUDAH,
            'nomor_registrasi_guru' => '123456789012',
            'nuptk' => '1234567890123456',
        ]);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('tambah');

        foreach ($this->dataForm() as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseHas('lampiran_2b', [
            'profil_sekolah_id' => $sekolah->id,
            'triwulan' => 1,
            'nama_ptk' => 'Ahmad Fauzi',
            'nrg' => '123456789012',
            'nuptk' => '1234567890123456',
            'keterangan' => 'Mutasi dari sekolah lain',
        ]);
    }

    public function test_tahun_otomatis_terisi_dengan_tahun_sekarang_saat_simpan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        DataPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'nama_ptk' => 'Ahmad Fauzi',
            'status_keaktifan' => 'Tidak Aktif',
            'status_sertifikasi' => DataPtk::STATUS_SERTIFIKASI_SUDAH,
        ]);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('tambah');

        foreach ($this->dataForm() as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseHas('lampiran_2b', [
            'profil_sekolah_id' => $sekolah->id,
            'nama_ptk' => 'Ahmad Fauzi',
            'tahun' => now()->year,
        ]);
    }

    public function test_nrg_nuptk_otomatis_terisi_setelah_pilih_nama_ptk(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        DataPtk::factory()->create([
            'profil_sekolah_id' => $sekolah->id,
            'nama_ptk' => 'Siti Aminah',
            'status_keaktifan' => 'Tidak Aktif',
            'status_sertifikasi' => DataPtk::STATUS_SERTIFIKASI_SUDAH,
            'nomor_registrasi_guru' => '111122223333',
            'nuptk' => '4444555566667777',
        ]);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('tambah')
            ->set('nama_ptk', 'Siti Aminah')
            ->assertSet('nrg', '111122223333')
            ->assertSet('nuptk', '4444555566667777');
    }

    public function test_simpan_gagal_jika_nama_ptk_tidak_ada_di_data_ptk(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('tambah');

        foreach ($this->dataForm(['nama_ptk' => 'Nama Tidak Ada']) as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasErrors(['nama_ptk']);
        $this->assertDatabaseMissing('lampiran_2b', ['nama_ptk' => 'Nama Tidak Ada']);
    }

    public function test_admin_ops_tidak_bisa_menyisipkan_data_untuk_sekolah_lain(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create();
        $sekolahLain = ProfilSekolah::factory()->create();
        DataPtk::factory()->create([
            'profil_sekolah_id' => $sekolahSaya->id,
            'nama_ptk' => 'Ahmad Fauzi',
            'status_keaktifan' => 'Tidak Aktif',
            'status_sertifikasi' => DataPtk::STATUS_SERTIFIKASI_SUDAH,
        ]);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        // profil_sekolah_id sengaja di-set duluan (mensimulasikan manipulasi
        // langsung ke komponen) supaya nama_ptk yang diisi setelahnya tidak
        // ikut ke-reset oleh updatedProfilSekolahId().
        $component = Livewire::actingAs($adminOps)->test(Index::class)
            ->call('tambah')
            ->set('profil_sekolah_id', $sekolahLain->id);

        foreach ($this->dataForm() as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseHas('lampiran_2b', [
            'profil_sekolah_id' => $sekolahSaya->id,
            'nama_ptk' => 'Ahmad Fauzi',
        ]);
        $this->assertDatabaseMissing('lampiran_2b', [
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

        Lampiran2b::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 1, 'nama_ptk' => 'PTK Triwulan 1']);
        Lampiran2b::factory()->create(['profil_sekolah_id' => $sekolah->id, 'triwulan' => 2, 'nama_ptk' => 'PTK Triwulan 2']);

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

        Lampiran2b::factory()->create(['profil_sekolah_id' => $sekolahSaya->id, 'triwulan' => 1, 'nama_ptk' => 'PTK Sekolah Saya']);
        Lampiran2b::factory()->create(['profil_sekolah_id' => $sekolahLain->id, 'triwulan' => 1, 'nama_ptk' => 'PTK Sekolah Lain']);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->assertSee('PTK Sekolah Saya')
            ->assertDontSee('PTK Sekolah Lain');
    }

    public function test_admin_ops_tidak_bisa_mengedit_data_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $baris = Lampiran2b::factory()->create(['profil_sekolah_id' => $sekolahLain->id, 'triwulan' => 1]);
        $adminOps = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('edit', $baris->id)
            ->assertForbidden();
    }

}
