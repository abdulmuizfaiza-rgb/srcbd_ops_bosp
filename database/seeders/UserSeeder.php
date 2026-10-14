<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed akun Superadmin default.
     *
     * Audit keamanan 2026-10-14: `must_change_password` SEKARANG ikut
     * diset `true` di sini (sebelumnya tidak, sehingga password default
     * seeder ini bisa saja masih terpakai tanpa paksaan ganti) - akun
     * ini WAJIB mengganti password lewat alur "Ganti Password Wajib"
     * sebelum bisa membuka menu lain sama sekali (lihat
     * App\Http\Middleware\ForcePasswordChange). PENTING: tetap segera
     * login & ganti password default ini SEKARANG kalau belum pernah
     * dilakukan sejak instalasi awal.
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
                'must_change_password' => true,
            ]
        );
    }
}
