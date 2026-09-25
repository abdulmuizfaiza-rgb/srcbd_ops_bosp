<?php

namespace Database\Factories;

use App\Models\Lampiran2a;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lampiran2a>
 */
class Lampiran2aFactory extends Factory
{
    protected $model = Lampiran2a::class;

    public function definition(): array
    {
        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'triwulan' => 1,
            'tahun' => now()->year,
            'nrg' => $this->faker->numerify('############'),
            'nuptk' => $this->faker->numerify('################'),
            'nama_ptk' => $this->faker->name(),
            'status_kepegawaian' => 'PNS',
            'gaji_pokok_januari' => $this->faker->numberBetween(3000000, 6000000),
            'npwp' => $this->faker->numerify('###############'),
        ];
    }
}
