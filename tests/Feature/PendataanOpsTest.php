<?php

namespace Tests\Feature;

use App\Livewire\PendataanOps\Index;
use App\Models\PendataanOps;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PendataanOpsTest extends TestCase
{
    use RefreshDatabase;

    protected function dataLengkap(array $overrides = []): array
    {
        return array_merge([
            'nama' => 'Budi Operator',
            'jk' => 'L',
            'status_kepegawaian' => 'PNS',
            'pendidikan_terakhir' => 'S1',
            'jurusan' => 'Sistem Informasi',
            'nama_perguruan_tinggi' => 'Universitas Contoh',
            'no_whatsapp' => '081234567890',
        ], $overrides);
    }

    public function test_admin_ops_bisa_mengisi_identitas_sekolahnya_sendiri(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('isi', $sekolah->id);

        foreach ($this->dataLengkap() as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseHas('pendataan_ops', [
            'profil_sekolah_id' => $sekolah->id,
            'nama' => 'Budi Operator',
            'jurusan' => 'Sistem Informasi',
        ]);
    }

    public function test_admin_ops_tidak_bisa_mengisi_identitas_sekolah_lain(): void
    {
        $sekolahLain = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('isi', $sekolahLain->id)
            ->assertForbidden();
    }

    public function test_jurusan_wajib_diisi_untuk_pendidikan_s1_ke_atas(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('isi', $sekolah->id);

        foreach ($this->dataLengkap(['jurusan' => '', 'nama_perguruan_tinggi' => '']) as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasErrors(['jurusan', 'nama_perguruan_tinggi']);
    }

    public function test_jurusan_otomatis_dikosongkan_untuk_pendidikan_sma(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('isi', $sekolah->id);

        foreach ($this->dataLengkap(['pendidikan_terakhir' => 'SMA']) as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseHas('pendataan_ops', [
            'profil_sekolah_id' => $sekolah->id,
            'pendidikan_terakhir' => 'SMA',
            'jurusan' => null,
            'nama_perguruan_tinggi' => null,
        ]);
    }

    public function test_no_whatsapp_harus_angka_maksimal_12_digit(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('isi', $sekolah->id);

        foreach ($this->dataLengkap(['no_whatsapp' => '0812345678901234']) as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasErrors(['no_whatsapp']);
    }

    public function test_nuptk_harus_16_digit_angka(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        $component = Livewire::actingAs($adminOps)->test(Index::class)->call('isi', $sekolah->id);

        foreach ($this->dataLengkap(['nuptk' => '12345']) as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasErrors(['nuptk']);

        $component2 = Livewire::actingAs($adminOps)->test(Index::class)->call('isi', $sekolah->id);
        foreach ($this->dataLengkap(['nuptk' => '1234567890123456']) as $field => $value) {
            $component2->set($field, $value);
        }
        $component2->call('simpan')->assertHasNoErrors(['nuptk']);

        $this->assertDatabaseHas('pendataan_ops', [
            'profil_sekolah_id' => $sekolah->id,
            'nuptk' => '1234567890123456',
        ]);
    }

    public function test_nip_harus_18_digit_untuk_status_asn(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        foreach (['PNS', 'ASN PPPK', 'ASN PPPK-PW'] as $status) {
            $component = Livewire::actingAs($adminOps)->test(Index::class)->call('isi', $sekolah->id);
            foreach ($this->dataLengkap(['status_kepegawaian' => $status, 'nip' => '12345']) as $field => $value) {
                $component->set($field, $value);
            }
            $component->call('simpan')->assertHasErrors(['nip']);

            $component2 = Livewire::actingAs($adminOps)->test(Index::class)->call('isi', $sekolah->id);
            foreach ($this->dataLengkap(['status_kepegawaian' => $status, 'nip' => '196501011990031001']) as $field => $value) {
                $component2->set($field, $value);
            }
            $component2->call('simpan')->assertHasNoErrors(['nip']);
        }
    }

    public function test_nip_harus_tanda_strip_untuk_status_honorer_atau_ptt_yayasan(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        foreach (['Honorer', 'PTT Yayasan'] as $status) {
            // NIP berupa angka (bahkan 18 digit persis) DITOLAK untuk status
            // Non-ASN - satu-satunya nilai yang diterima adalah "-".
            $component = Livewire::actingAs($adminOps)->test(Index::class)->call('isi', $sekolah->id);
            foreach ($this->dataLengkap(['status_kepegawaian' => $status, 'nip' => '196501011990031001']) as $field => $value) {
                $component->set($field, $value);
            }
            $component->call('simpan')->assertHasErrors(['nip']);

            $component2 = Livewire::actingAs($adminOps)->test(Index::class)->call('isi', $sekolah->id);
            foreach ($this->dataLengkap(['status_kepegawaian' => $status, 'nip' => '-']) as $field => $value) {
                $component2->set($field, $value);
            }
            $component2->call('simpan')->assertHasNoErrors(['nip']);

            $this->assertDatabaseHas('pendataan_ops', [
                'profil_sekolah_id' => $sekolah->id,
                'nip' => '-',
            ]);
        }
    }

    public function test_superadmin_bisa_mengedit_identitas_sekolah_manapun(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        PendataanOps::factory()->create(['profil_sekolah_id' => $sekolah->id, 'nama' => 'Nama Lama']);
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        $component = Livewire::actingAs($superadmin)->test(Index::class)->call('isi', $sekolah->id);

        foreach ($this->dataLengkap(['nama' => 'Nama Baru']) as $field => $value) {
            $component->set($field, $value);
        }

        $component->call('simpan')->assertHasNoErrors();

        $this->assertDatabaseHas('pendataan_ops', [
            'profil_sekolah_id' => $sekolah->id,
            'nama' => 'Nama Baru',
        ]);

        $this->assertDatabaseCount('pendataan_ops', 1);
    }

    public function test_admin_ops_hanya_melihat_sekolahnya_sendiri(): void
    {
        $sekolahSaya = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak Saya']);
        $sekolahLain = ProfilSekolah::factory()->create(['nama_sekolah' => 'SR Cibadak Lain']);
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolahSaya->id,
        ]);

        $this->actingAs($adminOps)
            ->get('/pendataan-ops')
            ->assertOk()
            ->assertSee('SR Cibadak Saya')
            ->assertDontSee('SR Cibadak Lain');
    }
}
