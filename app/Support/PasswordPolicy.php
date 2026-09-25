<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Aturan kekuatan password untuk form-form GANTI PASSWORD milik akun
 * sendiri ("Update Password" di halaman Profil & "Ganti Password Wajib"
 * setelah Pemulihan Akun): minimal 6 karakter, wajib kombinasi huruf
 * besar, huruf kecil, angka, dan simbol.
 *
 * Sengaja dibuat terpisah dari `Password::defaults()` bawaan Laravel
 * (yang TIDAK diubah di sini, dan masih dipakai apa adanya oleh form
 * Registrasi Admin OPS/BOSP serta form Kelola Pengguna Superadmin) -
 * supaya aturan baru ini HANYA berlaku di 2 form ganti password yang
 * diminta, tidak ikut mengubah perilaku form lain yang tidak diminta.
 */
class PasswordPolicy
{
    public static function rule(): Password
    {
        return Password::min(6)->mixedCase()->numbers()->symbols();
    }

    /**
     * Pesan validasi Bahasa Indonesia untuk tiap kemungkinan pelanggaran
     * aturan di atas - dipakai lewat parameter ke-2 `$this->validate()`
     * di form yang memakai `PasswordPolicy::rule()`.
     *
     * @return array<string, string>
     */
    public static function messages(string $field = 'password'): array
    {
        // Wildcard "field.*" menimpa SEMUA kemungkinan pesan bawaan Laravel
        // untuk rule Password (min, mixed, numbers, symbols, dst) sekaligus
        // dengan 1 pesan yang sama, jelas, dan konsisten dalam Bahasa
        // Indonesia - daripada menampilkan beberapa baris error terpisah
        // berbahasa Inggris untuk tiap kriteria yang belum terpenuhi.
        return [
            "{$field}.*" => 'Password minimal 6 karakter dan wajib kombinasi huruf besar, huruf kecil, angka, dan simbol. Silakan ketik ulang.',
        ];
    }
}
