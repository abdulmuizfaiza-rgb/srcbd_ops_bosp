<?php

namespace Database\Seeders;

use App\Models\ProfilSekolah;
use Illuminate\Database\Seeder;

class ProfilSekolahSeeder extends Seeder
{
    /**
     * Seed data awal Profil Sekolah untuk SR Cibadak.
     * Detail (NPSN, kepala sekolah, alamat, logo) dilengkapi oleh
     * Superadmin melalui menu Profil Sekolah.
     */
    public function run(): void
    {
        ProfilSekolah::firstOrCreate(
            ['id' => 1],
            [
                'npsn' => null,
                'nama_sekolah' => 'SR Cibadak',
                'nama_kepala_sekolah' => null,
                'nip_kepala_sekolah' => null,
                'alamat_sekolah' => null,
            ]
        );
    }
}
