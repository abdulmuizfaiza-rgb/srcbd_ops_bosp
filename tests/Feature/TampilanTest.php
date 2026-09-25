<?php

namespace Tests\Feature;

use App\Livewire\Tampilan\Index;
use App\Models\PengaturanTampilan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Menu Tampilan (khusus Superadmin): mengatur warna, huruf, dan gambar
 * latar halaman pemilihan akses / form login.
 */
class TampilanTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_ops_dan_admin_bosp_tidak_bisa_membuka_komponen_tampilan(): void
    {
        $adminOps = User::factory()->create(['level_akses' => User::LEVEL_ADMIN_OPS]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->assertForbidden();
    }

    public function test_superadmin_dapat_menyimpan_warna_dan_huruf(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('warnaHalaman', '#112233')
            ->set('warnaMenu', '#445566')
            ->set('ukuranHurufHalaman', 'besar')
            ->set('ukuranHurufMenu', 'kecil')
            ->set('jenisHurufHalaman', 'Poppins')
            ->set('jenisHurufMenu', 'Roboto')
            ->call('simpan')
            ->assertHasNoErrors();

        $tampilan = PengaturanTampilan::current();

        $this->assertSame('#112233', $tampilan->warna_halaman);
        $this->assertSame('#445566', $tampilan->warna_menu);
        $this->assertSame('besar', $tampilan->ukuran_huruf_halaman);
        $this->assertSame('kecil', $tampilan->ukuran_huruf_menu);
        $this->assertSame('Poppins', $tampilan->jenis_huruf_halaman);
        $this->assertSame('Roboto', $tampilan->jenis_huruf_menu);
    }

    /**
     * Warna/ukuran/jenis huruf form Registrasi Admin OPS/Admin BOSP
     * (2026-09-05) - lihat juga RegistrationTest untuk pengujian CSS var-
     * nya benar-benar diterapkan di halaman registrasi.
     */
    public function test_superadmin_dapat_menyimpan_huruf_form_registrasi(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('warnaHurufRegistrasi', '#123456')
            ->set('ukuranHurufRegistrasi', 'besar')
            ->set('jenisHurufRegistrasi', 'Nunito')
            ->call('simpan')
            ->assertHasNoErrors();

        $tampilan = PengaturanTampilan::current();

        $this->assertSame('#123456', $tampilan->warna_huruf_registrasi);
        $this->assertSame('besar', $tampilan->ukuran_huruf_registrasi);
        $this->assertSame('Nunito', $tampilan->jenis_huruf_registrasi);
    }

    public function test_warna_huruf_registrasi_harus_format_kode_hex(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('warnaHurufRegistrasi', 'bukan-warna')
            ->call('simpan')
            ->assertHasErrors(['warnaHurufRegistrasi']);
    }

    public function test_warna_harus_format_kode_hex(): void
    {
        $superadmin = User::factory()->create(['level_akses' => User::LEVEL_SUPERADMIN]);

        Livewire::actingAs($superadmin)
            ->test(Index::class)
            ->set('warnaHalaman', 'bukan-warna')
            ->call('simpan')
            ->assertHasErrors(['warnaHalaman']);
    }
}
