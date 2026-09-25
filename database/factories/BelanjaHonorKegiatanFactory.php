<?php

namespace Database\Factories;

use App\Models\BelanjaHonorKegiatan;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BelanjaHonorKegiatan>
 */
class BelanjaHonorKegiatanFactory extends Factory
{
    protected $model = BelanjaHonorKegiatan::class;

    public function definition(): array
    {
        $volume = $this->faker->numberBetween(1, 10);
        $tarifHarga = $this->faker->numberBetween(50000, 500000);

        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'jenis' => BelanjaHonorKegiatan::JENIS_HONOR_KEGIATAN,
            'tahun' => now()->year,
            'triwulan' => 1,
            'uraian' => $this->faker->randomElement(['Honor Panitia Kegiatan', 'Honor Narasumber', 'Honor Pembina Kegiatan']),
            'volume' => $volume,
            'satuan' => 'Orang',
            'tarif_harga' => $tarifHarga,
            'jumlah' => $volume * $tarifHarga,
            'tanggal' => now()->toDateString(),
        ];
    }
}
