<?php

namespace Database\Factories;

use App\Models\ProfilSekolah;
use App\Models\RincianBelanjaBarangHabisPakai;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RincianBelanjaBarangHabisPakai>
 */
class RincianBelanjaBarangHabisPakaiFactory extends Factory
{
    protected $model = RincianBelanjaBarangHabisPakai::class;

    public function definition(): array
    {
        $volume = $this->faker->numberBetween(1, 50);
        $hargaSatuan = $this->faker->numberBetween(5000, 200000);

        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'tahun' => now()->year,
            'triwulan' => 1,
            'kode_upb' => $this->faker->bothify('UPB-####'),
            'nama_barang' => $this->faker->randomElement(['Kertas HVS', 'Tinta Printer', 'Spidol', 'Alat Tulis Kantor']),
            'nama_merk_barang' => $this->faker->company(),
            'volume' => $volume,
            'satuan' => 'Pak',
            'harga_satuan' => $hargaSatuan,
            'total_harga' => $volume * $hargaSatuan,
            'asal_usul' => 'Toko ATK Lokal',
            'tanggal' => now()->toDateString(),
            'keterangan' => null,
        ];
    }
}
