<?php

namespace Database\Factories;

use App\Models\DanaBospTahap;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DanaBospTahap>
 */
class DanaBospTahapFactory extends Factory
{
    protected $model = DanaBospTahap::class;

    public function definition(): array
    {
        $jumlahSiswa = $this->faker->numberBetween(50, 500);
        $danaPerTahun = $this->faker->numberBetween(700000, 1200000);

        [$total, $tahap1, $tahap2] = DanaBospTahap::hitungRumusTab1($jumlahSiswa, $danaPerTahun);

        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'tahun' => now()->year,
            'saldo_bosp_tahun_sebelumnya' => $this->faker->numberBetween(0, 50000000),
            'jumlah_siswa' => $jumlahSiswa,
            'jumlah_dana_bosp_per_tahun' => $danaPerTahun,
            'total_penerimaan_setahun' => $total,
            'penerimaan_tahap_1' => $tahap1,
            'penerimaan_tahap_2' => $tahap2,
            'saldo_bosp_tw4_tahun_sebelumnya' => $this->faker->numberBetween(0, 20000000),
            'tarik_tunai_tw1' => $this->faker->numberBetween(1000000, 40000000),
            'tarik_tunai_tw2' => $this->faker->numberBetween(1000000, 40000000),
            'tarik_tunai_tw3' => $this->faker->numberBetween(1000000, 40000000),
            'tarik_tunai_tw4' => $this->faker->numberBetween(1000000, 40000000),
        ];
    }
}
