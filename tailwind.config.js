import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // Warna latar kolom kategori tabel Rekap RKAS Awal-Perubahan
    // (App\Models\RekapRkas::KATEGORI / WARNA_JUMLAH) - class-nya cuma ada
    // sebagai string di file PHP (app/Models/RekapRkas.php), BUKAN di file
    // .blade.php manapun yang di-scan lewat "content" di atas, jadi Tailwind
    // JIT tidak akan pernah menemukannya sendiri kalau tidak di-safelist di
    // sini (kalau lupa, kotak/judul kolom akan tampil TANPA warna latar sama
    // sekali walau class-nya sudah benar di HTML - dicek & ketahuan saat
    // verifikasi visual 2026-09-09 lanjutan ke-2).
    safelist: [
        'bg-yellow-100',
        'bg-blue-100',
        'bg-green-100',
        'bg-orange-100',
        'bg-purple-100',
        'bg-yellow-300',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
