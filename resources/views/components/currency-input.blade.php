@props(['name', 'value' => null, 'resetKey' => 'default', 'borderClass' => 'border-slate-300'])

{{--
    Input angka Rupiah (Rp 1.000.000) - property Livewire tetap menyimpan
    angka mentah (tanpa titik pemisah ribuan), pemisah ribuan hanya untuk
    tampilan. wire:ignore dipakai supaya tampilan yang sedang diketik tidak
    ditimpa ulang oleh Livewire setiap kali ada render lain; wire:key diberi
    komponen "resetKey" supaya kotaknya di-refresh (menampilkan nilai baru)
    setiap kali form dibuka untuk Tambah/Edit data yang berbeda.
--}}
<div wire:ignore wire:key="currency-{{ $name }}-{{ $resetKey }}" class="relative mt-1">
    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 text-sm pointer-events-none">Rp</span>
    <input
        type="text"
        inputmode="numeric"
        id="{{ $name }}"
        value="{{ $value !== null && $value !== '' ? number_format((int) $value, 0, ',', '.') : '' }}"
        x-on:input="
            const angka = $event.target.value.replace(/\D/g, '');
            $event.target.value = angka ? new Intl.NumberFormat('id-ID').format(angka) : '';
            $wire.set('{{ $name }}', angka, false);
        "
        {{ $attributes->merge(['class' => 'pl-9 '.$borderClass.' focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block w-full']) }}
    >
</div>
