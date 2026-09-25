@props(['sekolahId', 'field', 'value' => null, 'revisi' => 0])

{{--
    Kotak input LANGSUNG di tabel Rekap RKAS Awal-Perubahan (di luar form
    modal "Isi/Edit" yang sudah ada) - terikat ke property Livewire
    "baris.{sekolahId}.{field}".

    Sejak permintaan format Rupiah pada kolom tabel (2026-09-09 lanjutan
    ke-2), kotak ini TIDAK lagi memakai wire:model.blur langsung (beda
    dari round sebelumnya) - sekarang wire:ignore + tampilan diformat
    pemisah ribuan saat mengetik (pola sama persis seperti
    <x-currency-input>, lihat komponen itu), lalu nilainya dikirim ke
    server SENDIRI lewat $wire.set(...) begitu kotak kehilangan fokus
    (blur) - method Livewire\PendataanBosp\RekapRkas\Index::updated()
    tetap yang memvalidasi & menyimpan seperti sebelumnya, TIDAK berubah.

    Karena wire:ignore, kotak ini TIDAK otomatis di-refresh Livewire kalau
    inputnya ditolak (bukan angka) - wire:key disisipi properti "revisi"
    (dari $revisiBaris[sekolahId] pada komponen Livewire, dinaikkan
    server setiap kali validasi kotak baris itu gagal) supaya Livewire
    membuat ulang kotak-kotak baris itu dari nilai database yang
    sebenarnya - perilaku "kembali ke nilai semula kalau input tidak
    valid" dari round sebelumnya tetap berjalan walau sekarang wire:ignore.

    Warna latar & tebal huruf kolom (kalau ada) dikirim lewat atribut
    class dari pemanggil (resources/views/livewire/pendataan-bosp/
    rekap-rkas/index.blade.php) - class bg-* WAJIB dipasang di sini
    (bukan cuma di <td> pembungkusnya) karena plugin @tailwindcss/forms
    memaksa background putih pada elemen <input>, jadi warna di <td> saja
    akan tertutup putih (bug class yang sama seperti pernah ditemukan di
    komponen filter-select sebelumnya).
--}}
@php
    $namaField = 'baris.'.$sekolahId.'.'.$field;
    $nilaiAwal = $value !== null && $value !== '' ? number_format((int) $value, 0, ',', '.') : '';
@endphp

<div wire:ignore wire:key="rekap-rkas-cell-{{ $sekolahId }}-{{ $field }}-{{ $revisi }}">
    <input
        type="text"
        inputmode="numeric"
        value="{{ $nilaiAwal }}"
        x-on:input="
            const angka = $event.target.value.replace(/\D/g, '');
            $event.target.value = angka ? new Intl.NumberFormat('id-ID').format(angka) : '';
        "
        x-on:blur="
            const angka = $event.target.value.replace(/\D/g, '');
            $wire.set('{{ $namaField }}', angka);
        "
        {{ $attributes->merge(['class' => 'w-24 text-right text-xs rounded px-1.5 py-1.5 focus:ring-1 focus:outline-none border '.($errors->has($namaField) ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400')]) }}
    >
</div>
@error($namaField)
    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
@enderror
