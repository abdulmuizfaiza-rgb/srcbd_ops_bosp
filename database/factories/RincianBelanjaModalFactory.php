<?php

namespace Database\Factories;

use App\Models\ProfilSekolah;
use App\Models\RincianBelanjaModal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RincianBelanjaModal>
 */
class RincianBelanjaModalFactory extends Factory
{
    protected $model = RincianBelanjaModal::class;

    public function definition(): array
    {
        $volume = $this->faker->numberBetween(1, 20);
        $hargaSatuan = $this->faker->numberBetween(100000, 5000000);

        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'jenis' => RincianBelanjaModal::JENIS_PERALATAN_MESIN,
            'tahun' => now()->year,
            'triwulan' => 1,
            'kode_upb' => $this->faker->bothify('UPB-####'),
            'nama_barang' => $this->faker->randomElement(['Laptop', 'Printer', 'AC', 'Meja Kursi']),
            'nama_merk_barang' => $this->faker->company(),
            'volume' => $volume,
            'satuan' => 'Unit',
            'harga_satuan' => $hargaSatuan,
            'total_harga' => $volume * $hargaSatuan,
            'asal_usul' => 'Dana BOSP',
            'tanggal' => now()->toDateString(),
            'keterangan' => null,
        ];
    }

    public function asetTetapLainnya(): static
    {
        return $this->state(fn () => [
            'jenis' => RincianBelanjaModal::JENIS_ASET_TETAP_LAINNYA,
            'nama_barang' => $this->faker->randomElement(['Buku Perpustakaan', 'Alat Peraga', 'Alat Musik']),
        ]);
    }
}
