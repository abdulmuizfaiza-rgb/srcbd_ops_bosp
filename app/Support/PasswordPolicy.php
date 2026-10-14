<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Aturan kekuatan password TUNGGAL untuk SEMUA form yang melibatkan
 * password akun di aplikasi ini: minimal 8 karakter, wajib kombinasi
 * huruf besar, huruf kecil, angka, dan simbol.
 *
 * Audit keamanan 2026-10-14: sebelumnya aturan ini (minimal 6 karakter)
 * HANYA dipakai di 2 form ganti password milik sendiri ("Update
 * Password" & "Ganti Password Wajib"), sedangkan form Registrasi Admin
 * OPS/BOSP dan form Kelola Pengguna (Superadmin membuat/reset password
 * akun lain) masih memakai `Password::defaults()` bawaan Laravel (hanya
 * minimal 8 karakter, tanpa wajib kombinasi) - sekarang DISAMAKAN, aturan
 * ini dipakai di SEMUA form tsb supaya tidak ada jalur dengan kekuatan
 * password yang lebih lemah.
 */
class PasswordPolicy
{
    public static function rule(): Password
    {
        return Password::min(8)->mixedCase()->numbers()->symbols();
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
            "{$field}.*" => 'Password minimal 8 karakter dan wajib kombinasi huruf besar, huruf kecil, angka, dan simbol. Silakan ketik ulang.',
        ];
    }
}
