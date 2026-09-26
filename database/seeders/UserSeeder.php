<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed akun Superadmin default.
     * PENTING: segera ganti password default ini setelah login pertama.
     *
     * Akun Admin OPS & Admin BOSP tidak di-seed otomatis karena harus
     * dibuat oleh Superadmin melalui menu Pengguna (username memakai NPSN
     * sekolah yang diinput lewat menu Profil Sekolah).
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['username' => 'superadmin'],
            [
                'email' => 'abdulmuizfaiza@gmail.com',
                'password' => Hash::make('password'),
                'level_akses' => 'superadmin',
                'nama_sekolah' => null,
                'jabatan' => null,
            ]
        );
    }
}
