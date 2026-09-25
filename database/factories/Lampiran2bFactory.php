<?php

namespace Database\Factories;

use App\Models\Lampiran2b;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lampiran2b>
 */
class Lampiran2bFactory extends Factory
{
    protected $model = Lampiran2b::class;

    public function definition(): array
    {
        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'triwulan' => 1,
            'tahun' => now()->year,
            'nrg' => $this->faker->numerify('############'),
            'nuptk' => $this->faker->numerify('################'),
            'nama_ptk' => $this->faker->name(),
            'keterangan' => $this->faker->sentence(),
            'tmt' => $this->faker->date(),
        ];
    }
}
