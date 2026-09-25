@props(['active' => false])

@php
// Warna huruf menu (non-aktif) bisa diatur Superadmin lewat menu Tampilan
// (--warna-huruf-menu, lihat layouts/app.blade.php) - dipasang lewat CSS
// variable class (BUKAN inline style) supaya class "hover:text-white" di
// bawah tetap menang saat kursor hover (spesifisitas class+pseudo lebih
// tinggi dari class warna dasar ini). Link yang sedang aktif (bg biru)
// SENGAJA tidak ikut diubah warnanya - dianggap gaya "badge/tombol aktif",
// bukan teks label biasa.
$classes = ($active ?? false)
            ? 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold bg-blue-600 text-white shadow-sm shadow-blue-900/30'
            : 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-[color:var(--warna-huruf-menu)] hover:bg-slate-800/80 hover:text-white transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
