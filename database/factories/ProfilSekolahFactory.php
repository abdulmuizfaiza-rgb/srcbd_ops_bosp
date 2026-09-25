<?php

namespace Database\Factories;

use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfilSekolah>
 */
class ProfilSekolahFactory extends Factory
{
    protected $model = ProfilSekolah::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Default factory langsung menghasilkan profil yang LENGKAP (semua
        // field wajib gate onboarding terisi) supaya test-test yang sudah
        // ada (yang tidak sedang menguji gate onboarding itu sendiri) tidak
        // ikut ter-redirect ke menu Profil Sekolah.
        $statusKepegawaianBendahara = fake()->randomElement(['PNS', 'ASN PPPK', 'ASN PPPK-PW', 'Honorer']);

        return [
            'npsn' => fake()->unique()->numerify('########'),
            'kode_upb' => fake()->unique()->numerify('UPB-####'),
            'nama_sekolah' => fake()->company(),
            'status' => fake()->randomElement([ProfilSekolah::STATUS_NEGERI, ProfilSekolah::STATUS_SWASTA]),
            'kecamatan' => fake()->city(),
            'subrayon' => fake()->city(),
            'nama_kepala_sekolah' => fake()->name(),
            'nip_kepala_sekolah' => fake()->numerify('####################'),
            'no_whatsapp_kepala_sekolah' => fake()->numerify('08##########'),
            'status_kepegawaian_kepsek' => fake()->randomElement(['PNS', 'ASN PPPK', 'ASN PPPK-PW', 'Honorer']),
            'nama_pengawas' => fake()->name(),
            'nip_pengawas' => fake()->numerify('####################'),
            'nama_bendahara' => fake()->name(),
            'nip_bendahara' => $statusKepegawaianBendahara === 'Honorer' ? '-' : fake()->numerify('##################'),
            'status_kepegawaian_bendahara' => $statusKepegawaianBendahara,
            'alamat_sekolah' => fake()->address(),
        ];
    }
}
