<?php

namespace Database\Factories;

use App\Models\ProfilSekolah;
use App\Models\RincianPemeliharaan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RincianPemeliharaan>
 */
class RincianPemeliharaanFactory extends Factory
{
    protected $model = RincianPemeliharaan::class;

    public function definition(): array
    {
        $volume = $this->faker->numberBetween(1, 20);
        $hargaSatuan = $this->faker->numberBetween(10000, 500000);

        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'jenis' => RincianPemeliharaan::JENIS_BARANG,
            'tahun' => now()->year,
            'triwulan' => 1,
            'kode_upb' => $this->faker->bothify('UPB-####'),
            'nama_barang' => $this->faker->randomElement(['Semen', 'Cat Tembok', 'Pasir', 'Keramik']),
            'nama_merk_barang' => $this->faker->company(),
            'volume' => $volume,
            'satuan' => 'Unit',
            'harga_satuan' => $hargaSatuan,
            'total_harga' => $volume * $hargaSatuan,
            'asal_usul' => 'Toko Bangunan Lokal',
            'tanggal' => now()->toDateString(),
            'keterangan' => null,
        ];
    }

    public function jasa(): static
    {
        return $this->state(fn () => [
            'jenis' => RincianPemeliharaan::JENIS_JASA,
            'nama_barang' => $this->faker->randomElement(['Jasa Tukang', 'Jasa Cat', 'Jasa Instalasi Listrik']),
        ]);
    }
}
