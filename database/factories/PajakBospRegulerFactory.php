<?php

namespace Database\Factories;

use App\Models\PajakBospReguler;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PajakBospReguler>
 */
class PajakBospRegulerFactory extends Factory
{
    protected $model = PajakBospReguler::class;

    public function definition(): array
    {
        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'tahun' => now()->year,
            'bulan' => 1,
            'ppn_debit' => $this->faker->numberBetween(100000, 2000000),
            'pph21_debit' => $this->faker->numberBetween(100000, 2000000),
            'pph23_debit' => $this->faker->numberBetween(0, 1000000),
            'pph4_debit' => $this->faker->numberBetween(0, 1000000),
            'sspd_debit' => $this->faker->numberBetween(0, 1000000),
            'ppn_kredit' => $this->faker->numberBetween(100000, 2000000),
            'pph21_kredit' => $this->faker->numberBetween(100000, 2000000),
            'pph23_kredit' => $this->faker->numberBetween(0, 1000000),
            'pph4_kredit' => $this->faker->numberBetween(0, 1000000),
            'sspd_kredit' => $this->faker->numberBetween(0, 1000000),
        ];
    }
}
