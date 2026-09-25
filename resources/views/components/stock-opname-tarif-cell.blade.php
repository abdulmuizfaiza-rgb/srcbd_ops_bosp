@props(['rowId', 'field', 'value' => null, 'revisi' => 0])

{{--
    Kotak input Rupiah (Harga & 4 kolom "Jumlah (Rp)") LANGSUNG di tabel
    tab "Stock Opname" - terikat ke property Livewire
    "barisOpname.{rowId}.{field}". Pola SAMA PERSIS seperti
    <x-honor-ptk-tarif-cell> (format Rupiah live sambil mengetik,
    wire:ignore + Alpine, auto-save saat blur), hanya properti Livewire
    tujuannya "barisOpname" (bukan "baris") supaya tidak bentrok dengan
    tab Rincian Belanja Barang Habis Pakai (2 tabel BEDA, ID baris bisa
    kebetulan sama).
--}}
@php
    $namaField = 'barisOpname.'.$rowId.'.'.$field;
    $nilaiAwal = $value !== null && $value !== '' ? number_format((int) $value, 0, ',', '.') : '';
@endphp

<div wire:ignore wire:key="stock-opname-tarif-cell-{{ $rowId }}-{{ $field }}-{{ $revisi }}">
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
