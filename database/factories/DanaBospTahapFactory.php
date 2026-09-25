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

        $data = [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'tahun' => now()->year,
            'saldo_bosp_tahun_sebelumnya' => $this->faker->numberBetween(0, 50000000),
            'jumlah_siswa' => $jumlahSiswa,
            'jumlah_dana_bosp_per_tahun' => $danaPerTahun,
            'total_penerimaan_setahun' => $total,
            'penerimaan_tahap_1' => $tahap1,
            'penerimaan_tahap_2' => $tahap2,
            'saldo_bosp_tw4_tahun_sebelumnya' => $this->faker->numberBetween(0, 20000000),
        ];

        // Tarik Tunai TW 1-4 + Saldo Kas Bank/Tunai/Saldo TW 1-4 (rumus
        // Saldo TW = Saldo Kas Bank + Saldo Kas Tunai, permintaan user
        // 2026-09-16, Part 31).
        foreach ([1, 2, 3, 4] as $tw) {
            $kasBank = $this->faker->numberBetween(500000, 20000000);
            $kasTunai = $this->faker->numberBetween(100000, 5000000);

            $data["tarik_tunai_tw{$tw}"] = $this->faker->numberBetween(1000000, 40000000);
            $data["saldo_kas_bank_tw{$tw}"] = $kasBank;
            $data["saldo_kas_tunai_tw{$tw}"] = $kasTunai;
            $data["saldo_tw{$tw}"] = DanaBospTahap::hitungSaldoTw($kasBank, $kasTunai);
        }

        return $data;
    }
}
