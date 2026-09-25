<?php

namespace Database\Factories;

use App\Models\PenerimaanHonorPtk;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PenerimaanHonorPtk>
 */
class PenerimaanHonorPtkFactory extends Factory
{
    protected $model = PenerimaanHonorPtk::class;

    public function definition(): array
    {
        $volume = $this->faker->numberBetween(1, 12);
        $tarifHarga = $this->faker->numberBetween(50000, 500000);

        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'tahun' => now()->year,
            'triwulan' => 1,
            'nuptk' => $this->faker->numerify('################'),
            'nama_penerima' => $this->faker->name(),
            'volume' => $volume,
            'satuan' => 'OB',
            'tarif_harga' => $tarifHarga,
            'jumlah_honor' => $volume * $tarifHarga,
            'tanggal_bayar' => now()->toDateString(),
        ];
    }
}
