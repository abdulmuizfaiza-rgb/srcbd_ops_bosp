<?php

namespace Database\Factories;

use App\Models\Lampiran2c;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lampiran2c>
 */
class Lampiran2cFactory extends Factory
{
    protected $model = Lampiran2c::class;

    public function definition(): array
    {
        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'triwulan' => 1,
            'tahun' => now()->year,
            'nrg' => $this->faker->numerify('############'),
            'nuptk' => $this->faker->numerify('################'),
            'nama_ptk' => $this->faker->name(),
            'kecamatan' => $this->faker->randomElement(array_keys(Lampiran2c::KECAMATAN_OPTIONS)),
            'jenis_kepangkatan' => $this->faker->randomElement(array_keys(Lampiran2c::JENIS_KEPANGKATAN_OPTIONS)),
            'golongan' => $this->faker->randomElement(array_keys(Lampiran2c::GOLONGAN_OPTIONS)),
            'masa_kerja' => $this->faker->numberBetween(1, 20).' Tahun',
            'pangkat_berkala' => $this->faker->randomElement(array_keys(Lampiran2c::PANGKAT_BERKALA_OPTIONS)),
            'tmt' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'gaji_pokok_lama' => $this->faker->numberBetween(3000000, 5000000),
            'gaji_pokok_baru' => $this->faker->numberBetween(3000000, 6000000),
            'keterangan' => $this->faker->sentence(),
        ];
    }
}
