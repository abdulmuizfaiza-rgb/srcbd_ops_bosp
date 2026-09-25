@props(['rowId', 'field', 'value' => null, 'revisi' => 0])

{{--
    Kotak input Tarif Harga LANGSUNG di tabel Penerimaan Honor PTK (di
    luar form modal Tambah/Edit) - terikat ke property Livewire
    "baris.{rowId}.{field}". Pola sama persis seperti
    <x-rekap-rkas-cell> (format Rupiah live sambil mengetik, wire:ignore
    + Alpine, auto-save saat blur), hanya kuncinya diganti "rowId"
    (1 baris = 1 PTK) bukan "sekolahId" (Rekap RKAS 1 baris = 1 sekolah).
--}}
@php
    $namaField = 'baris.'.$rowId.'.'.$field;
    $nilaiAwal = $value !== null && $value !== '' ? number_format((int) $value, 0, ',', '.') : '';
@endphp

<div wire:ignore wire:key="honor-ptk-tarif-cell-{{ $rowId }}-{{ $field }}-{{ $revisi }}">
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
        {{ $attributes->merge(['class' => 'w-28 text-right text-xs rounded px-1.5 py-1.5 focus:ring-1 focus:outline-none border '.($errors->has($namaField) ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400')]) }}
    >
</div>
@error($namaField)
    <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
@enderror
