<?php

namespace Database\Factories;

use App\Models\PendataanBosp;
use App\Models\ProfilSekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PendataanBosp>
 */
class PendataanBospFactory extends Factory
{
    protected $model = PendataanBosp::class;

    public function definition(): array
    {
        return [
            'profil_sekolah_id' => ProfilSekolah::factory(),
            'nuptk' => $this->faker->numerify('##################'),
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
