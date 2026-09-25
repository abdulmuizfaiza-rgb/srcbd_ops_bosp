<?php

namespace Tests\Feature;

use App\Livewire\ProfilSekolah\Index;
use App\Models\ProfilSekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Menguji field baru "No Whatsapp Kepala Sekolah" pada menu Profil
 * Sekolah - wajib diisi supaya status Profil Sekolah dianggap "Lengkap"
 * (gate onboarding), sama seperti field Kepala Sekolah lainnya.
 *
 * Format (2026-09-05, lanjutan): harus format nomor WhatsApp Indonesia -
 * diawali "08" dan total panjang 10-13 digit angka (bukan lagi
 * persis-12-digit tanpa syarat awalan).
 */
class ProfilSekolahNoWhatsappKepsekTest extends TestCase
{
    use RefreshDatabase;

    public static function nomorTidakValidProvider(): array
    {
        return [
            'terlalu pendek (9 digit)' => ['081234567'],
            'terlalu panjang (14 digit)' => ['08123456789012'],
            'tidak diawali 08' => ['021234567890'],
            'bukan angka' => ['08123abc4567'],
        ];
    }

    #[DataProvider('nomorTidakValidProvider')]
    public function test_no_whatsapp_kepala_sekolah_ditolak_kalau_format_salah(string $nomor): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        Livewire::actingAs($adminOps)
            ->test(Index::class)
            ->call('edit', $sekolah->id)
            ->set('no_whatsapp_kepala_sekolah', $nomor)
            ->call('simpan')
            ->assertHasErrors(['no_whatsapp_kepala_sekolah']);
    }

    public function test_no_whatsapp_kepala_sekolah_diterima_kalau_format_08xx_10_sampai_13_digit(): void
    {
        $sekolah = ProfilSekolah::factory()->create();
        $adminOps = User::factory()->create([
            'level_akses' => User::LEVEL_ADMIN_OPS,
            'profil_sekolah_id' => $sekolah->id,
        ]);

        foreach (['0812345678', '081234567890', '0812345678901'] as $nomor) {
            Livewire::actingAs($adminOps)
                ->test(Index::class)
                ->call('edit', $sekolah->id)
                ->set('no_whatsapp_kepala_sekolah', $nomor)
                ->call('simpan')
                ->assertHasNoErrors(['no_whatsapp_kepala_sekolah']);

            $this->assertDatabaseHas('profil_sekolah', [
                'id' => $sekolah->id,
                'no_whatsapp_kepala_sekolah' => $nomor,
            ]);
        }
    }

    public function test_profil_belum_lengkap_kalau_no_whatsapp_kepala_sekolah_kosong(): void
    {
        $sekolah = ProfilSekolah::factory()->create(['no_whatsapp_kepala_sekolah' => null]);

        $this->assertFalse($sekolah->isLengkap());

        $sekolah->no_whatsapp_kepala_sekolah = '081234567890';
        $this->assertTrue($sekolah->isLengkap());
    }
}
