<?php

namespace Database\Factories;

use App\Models\LanggananDayaJasa;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LanggananDayaJasa>
 */
class LanggananDayaJasaFactory extends Factory
{
    protected $model = LanggananDayaJasa::class;

    public function definition(): array
    {
        $volume = $this->faker->numberBetween(1, 12);
        $tarifHarga = $this->faker->numberBetween(50000, 500000);

        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'tahun' => now()->year,
            'triwulan' => 1,
            'uraian_pembayaran' => $this->faker->randomElement(['Listrik', 'Air PDAM', 'Internet', 'Telepon']),
            'volume' => $volume,
            'satuan' => 'Bulan',
            'tarif_harga' => $tarifHarga,
            'jumlah' => $volume * $tarifHarga,
            'tanggal_bayar' => now()->toDateString(),
        ];
    }
}
