<?php

namespace Database\Factories;

use App\Models\ProfilSekolah;
use App\Models\SuratTpg;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SuratTpg>
 */
class SuratTpgFactory extends Factory
{
    protected $model = SuratTpg::class;

    public function definition(): array
    {
        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'tahun' => now()->year,
            'triwulan' => 1,
            'jenis' => SuratTpg::JENIS_REKOMENDASI,
            'nomor_surat' => $this->faker->numerify('###/SR-CBD/####'),
            'tanggal_surat' => now()->toDateString(),
        ];
    }
}
