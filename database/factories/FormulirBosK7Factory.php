<?php

namespace Database\Factories;

use App\Models\FormulirBosK7;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormulirBosK7>
 */
class FormulirBosK7Factory extends Factory
{
    protected $model = FormulirBosK7::class;

    public function definition(): array
    {
        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'tahun' => now()->year,
            'bulan' => 1,
            'lembar_100000' => $this->faker->numberBetween(0, 30),
            'lembar_50000' => $this->faker->numberBetween(0, 10),
            'lembar_20000' => $this->faker->numberBetween(0, 10),
            'lembar_10000' => $this->faker->numberBetween(0, 10),
            'lembar_5000' => $this->faker->numberBetween(0, 10),
            'lembar_2000' => $this->faker->numberBetween(0, 10),
            'lembar_1000' => $this->faker->numberBetween(0, 10),
            'keping_1000' => $this->faker->numberBetween(0, 10),
            'keping_500' => $this->faker->numberBetween(0, 10),
            'keping_200' => $this->faker->numberBetween(0, 10),
            'keping_100' => $this->faker->numberBetween(0, 10),
            'saldo_rekening_bank' => $this->faker->numberBetween(1000000, 500000000),
            'jumlah_total_penerimaan_bku' => $this->faker->numberBetween(1000000, 500000000),
            'jumlah_total_pengeluaran_bku' => $this->faker->numberBetween(1000000, 100000000),
            'penjelasan_perbedaan' => null,
            'no_sk_kepala_sekolah' => null,
            'tanggal_sk_kepala_sekolah' => null,
            'no_sk_bendahara' => null,
            'tanggal_sk_bendahara' => null,
        ];
    }
}
