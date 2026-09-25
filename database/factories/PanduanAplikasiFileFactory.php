<?php

namespace Database\Factories;

use App\Models\PanduanAplikasi;
use App\Models\PanduanAplikasiFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PanduanAplikasiFile>
 */
class PanduanAplikasiFileFactory extends Factory
{
    protected $model = PanduanAplikasiFile::class;

    public function definition(): array
    {
        return [
            'panduan_aplikasi_id' => PanduanAplikasi::factory(),
            'file_path' => 'panduan-aplikasi/'.$this->faker->uuid().'.pdf',
            'file_nama_asli' => $this->faker->words(2, true).'.pdf',
            'file_ukuran' => $this->faker->numberBetween(1000, 5_000_000),
            'file_mime' => 'application/pdf',
        ];
    }
}
