<?php

namespace Database\Factories;

use App\Models\ProfilSekolah;
use App\Models\RekapRkas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RekapRkas>
 */
class RekapRkasFactory extends Factory
{
    protected $model = RekapRkas::class;

    public function definition(): array
    {
        $angka = fn () => $this->faker->numberBetween(1000000, 50000000);

        $data = [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'tahun' => now()->year,
            'anggaran_bosp' => $this->faker->numberBetween(100000000, 900000000),
        ];

        foreach (array_keys(RekapRkas::KATEGORI) as $kategori) {
            $data[$kategori.'_sebelum'] = $angka();
            $data[$kategori.'_realisasi_tahap1'] = $angka();
            $data[$kategori.'_perubahan_tahap2'] = $angka();
            $data[$kategori.'_jml_sesudah'] = $angka();
            $data[$kategori.'_selisih'] = $angka();
        }

        $data['jumlah_sebelum'] = $angka();
        $data['jumlah_sesudah'] = $angka();
        $data['jumlah_selisih'] = $angka();

        return $data;
    }
}
