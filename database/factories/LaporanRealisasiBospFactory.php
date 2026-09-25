<?php

namespace Database\Factories;

use App\Models\LaporanRealisasiBosp;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LaporanRealisasiBosp>
 */
class LaporanRealisasiBospFactory extends Factory
{
    protected $model = LaporanRealisasiBosp::class;

    public function definition(): array
    {
        $sisaDanaBos = $this->faker->numberBetween(0, 5000000);
        $saldoRekeningKasBank = $this->faker->numberBetween(0, 3000000);
        $saldoKasTunai = $sisaDanaBos - $saldoRekeningKasBank;

        [$verifikasiJumlah, $verifikasiSaldo] = LaporanRealisasiBosp::hitungVerifikasiSaldo(
            $sisaDanaBos, $saldoRekeningKasBank, $saldoKasTunai
        );

        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'tahun' => now()->year,
            'triwulan' => 1,
            'saldo_awal_dana_bosp' => $this->faker->numberBetween(0, 5000000),
            'penerimaan_dana_bos' => $this->faker->numberBetween(1000000, 20000000),
            'total_penerimaan' => $this->faker->numberBetween(1000000, 25000000),
            'belanja_barang_pakai_habis_persediaan' => $this->faker->numberBetween(0, 2000000),
            'jasa_tenaga_pendidik_dan_kependidikan' => $this->faker->numberBetween(0, 2000000),
            'daya_dan_jasa' => $this->faker->numberBetween(0, 2000000),
            'pemeliharaan' => $this->faker->numberBetween(0, 2000000),
            'upah_pemeliharaan' => $this->faker->numberBetween(0, 2000000),
            'biaya_pendaftaran_lomba_bimtek_workshop' => $this->faker->numberBetween(0, 2000000),
            'honor_kegiatan' => $this->faker->numberBetween(0, 2000000),
            'makan_dan_minum_kegiatan' => $this->faker->numberBetween(0, 2000000),
            'perjalanan_dinas' => $this->faker->numberBetween(0, 2000000),
            'total_belanja_barang_dan_jasa' => $this->faker->numberBetween(0, 10000000),
            'peralatan_dan_mesin_kib_b' => $this->faker->numberBetween(0, 2000000),
            'aset_tetap_lainnya_kib_e' => $this->faker->numberBetween(0, 2000000),
            'total_belanja_modal' => $this->faker->numberBetween(0, 3000000),
            'total_realisasi_dana_bos' => $this->faker->numberBetween(1000000, 20000000),
            'sisa_dana_bos' => $sisaDanaBos,
            'saldo_rekening_kas_bank' => $saldoRekeningKasBank,
            'saldo_kas_tunai' => $saldoKasTunai,
            'verifikasi_jumlah' => $verifikasiJumlah,
            'verifikasi_saldo' => $verifikasiSaldo,
        ];
    }
}
