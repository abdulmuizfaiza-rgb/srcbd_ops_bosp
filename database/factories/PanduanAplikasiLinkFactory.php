<?php

namespace Database\Factories;

use App\Models\PanduanAplikasi;
use App\Models\PanduanAplikasiLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PanduanAplikasiLink>
 */
class PanduanAplikasiLinkFactory extends Factory
{
    protected $model = PanduanAplikasiLink::class;

    public function definition(): array
    {
        return [
            'panduan_aplikasi_id' => PanduanAplikasi::factory(),
            'link_drive' => 'https://drive.google.com/file/d/'.$this->faker->uuid(),
        ];
    }
}
