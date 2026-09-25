<?php

namespace Database\Factories;

use App\Models\BiayaPendaftaranLomba;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BiayaPendaftaranLomba>
 */
class BiayaPendaftaranLombaFactory extends Factory
{
    protected $model = BiayaPendaftaranLomba::class;

    public function definition(): array
    {
        $volume = $this->faker->numberBetween(1, 10);
        $tarifHarga = $this->faker->numberBetween(50000, 500000);

        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'tahun' => now()->year,
            'triwulan' => 1,
            'uraian' => $this->faker->randomElement(['Pendaftaran Lomba OSN', 'Pendaftaran Bimtek Kurikulum', 'Pendaftaran Workshop Digitalisasi']),
            'volume' => $volume,
            'satuan' => 'Orang',
            'tarif_harga' => $tarifHarga,
            'jumlah' => $volume * $tarifHarga,
            'tanggal' => now()->toDateString(),
        ];
    }
}
