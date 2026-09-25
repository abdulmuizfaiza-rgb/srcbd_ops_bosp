<?php

namespace Database\Factories;

use App\Models\PanduanAplikasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PanduanAplikasi>
 */
class PanduanAplikasiFactory extends Factory
{
    protected $model = PanduanAplikasi::class;

    public function definition(): array
    {
        return [
            'judul' => 'Panduan '.$this->faker->words(3, true),
            'deskripsi' => $this->faker->sentence(),
        ];
    }
}
