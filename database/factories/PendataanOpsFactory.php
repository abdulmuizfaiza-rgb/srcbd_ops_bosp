<?php

namespace Database\Factories;

use App\Models\PendataanOps;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PendataanOps>
 */
class PendataanOpsFactory extends Factory
{
    protected $model = PendataanOps::class;

    public function definition(): array
    {
        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            // NUPTK 16 digit angka, NIP 18 digit angka (status default PNS
            // di bawah adalah ASN, jadi NIP 18 digit sesuai format yang
            // diwajibkan untuk status ASN - lihat validasi di
            // App\Livewire\PendataanOps\Index, 2026-09-05 lanjutan).
            'nuptk' => $this->faker->numerify('################'),
            'nama' => $this->faker->name(),
            'nip' => $this->faker->numerify('##################'),
            'jk' => $this->faker->randomElement(['L', 'P']),
            'tempat_lahir' => $this->faker->city(),
            'tanggal_lahir' => $this->faker->date(),
            'status_kepegawaian' => 'PNS',
            'pendidikan_terakhir' => 'S1',
            'jurusan' => 'Sistem Informasi',
            'nama_perguruan_tinggi' => 'Universitas Contoh',
            'no_whatsapp' => $this->faker->numerify('08##########'),
        ];
    }
}
