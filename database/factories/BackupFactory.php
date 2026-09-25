<?php

namespace Database\Factories;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Backup>
 */
class BackupFactory extends Factory
{
    protected $model = Backup::class;

    public function definition(): array
    {
        return [
            'tahun' => (int) now()->format('Y'),
            'nama_file' => 'backup-'.now()->format('Y').'-'.$this->faker->unique()->numerify('##########').'.zip',
            'path' => 'backup/'.now()->format('Y').'/'.$this->faker->uuid().'.zip',
            'ukuran' => $this->faker->numberBetween(1_000_000, 200_000_000),
            'dibuat_oleh_id' => User::factory(),
        ];
    }
}
