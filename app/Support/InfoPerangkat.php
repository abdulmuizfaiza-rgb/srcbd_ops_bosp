<?php

namespace App\Support;

/**
 * Menguraikan header User-Agent browser menjadi info Sistem Operasi,
 * Browser, dan Jenis Perangkat yang mudah dibaca manusia - permintaan
 * user 2026-10-03 (menu "Cek Database dan Aplikasi" > tab "Log Akses
 * Data" & "Percobaan Login Gagal") sebagai bagian dari kontrol
 * keamanan tambahan.
 *
 * PENTING - keterbatasan teknis yang sudah disepakati lewat
 * AskUserQuestion SEBELUM fitur ini dibuat: browser web TIDAK BISA
 * (dan tidak akan pernah bisa, demi keamanan & privasi semua pengguna
 * internet) memberi tahu nama asli laptop/komputer (hostname) atau
 * alamat MAC perangkat ke aplikasi web manapun - bukan hanya aplikasi
 * ini. Info yang BISA didapat otomatis dari setiap request browser
 * (tanpa izin apapun dari Admin OPS/BOSP) hanyalah: jenis Sistem
 * Operasi, jenis Browser, dan jenis Perangkat (Komputer/HP/Tablet) -
 * diuraikan dari header "User-Agent" yang selalu dikirim browser ke
 * server. Kelas ini HANYA mengurai teks User-Agent yang SUDAH tersimpan
 * (kolom user_agent) - tidak melakukan pengambilan data baru apapun.
 *
 * Deteksi memakai pencarian teks sederhana (bukan paket Composer
 * tambahan) - sengaja supaya tidak ada dependency/paket baru yang perlu
 * di-install user lewat composer. Cukup akurat untuk browser & OS besar
 * yang umum dipakai, tapi BUKAN daftar lengkap semua kemungkinan
 * browser/OS di dunia - User-Agent asli tetap disimpan apa adanya di
 * database (tidak diganti), kelas ini hanya dipakai untuk TAMPILAN yang
 * lebih mudah dibaca.
 */
class InfoPerangkat
{
    /**
     * Mengurai 1 teks User-Agent menjadi label singkat "OS - Browser -
     * Jenis Perangkat", mis. "Windows - Chrome - Komputer" atau
     * "Android - Chrome - HP". Mengembalikan "Tidak diketahui" kalau
     * User-Agent kosong/null.
     */
    public static function label(?string $userAgent): string
    {
        if (! $userAgent) {
            return 'Tidak diketahui';
        }

        return self::os($userAgent).' - '.self::browser($userAgent).' - '.self::jenisPerangkat($userAgent);
    }

    private static function os(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad'), str_contains($ua, 'iPod') => 'iOS',
            str_contains($ua, 'Macintosh'), str_contains($ua, 'Mac OS X') => 'Mac',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Lainnya',
        };
    }

    private static function browser(string $ua): string
    {
        // Urutan pengecekan SENGAJA penting - User-Agent Edge/Opera/Samsung
        // juga mengandung kata "Chrome" & "Safari" di dalamnya, jadi harus
        // dicek LEBIH DULU sebelum Chrome/Safari generik.
        return match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/'), str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'SamsungBrowser') => 'Samsung Internet',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/'), str_contains($ua, 'CriOS/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'Lainnya',
        };
    }

    private static function jenisPerangkat(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'iPad'), (str_contains($ua, 'Android') && ! str_contains($ua, 'Mobile')) => 'Tablet',
            str_contains($ua, 'Mobile'), str_contains($ua, 'iPhone'), str_contains($ua, 'Android') => 'HP',
            default => 'Komputer',
        };
    }
}
