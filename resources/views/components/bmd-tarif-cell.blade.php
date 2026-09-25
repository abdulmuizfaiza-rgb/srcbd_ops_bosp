@props(['rowId', 'field', 'value' => null, 'revisi' => 0])

{{--
    Kotak input Harga Satuan LANGSUNG di tabel tab "BMD" (menu Rincian
    Belanja Modal, permintaan user 2026-09-22) - pola SAMA PERSIS seperti
    <x-honor-ptk-tarif-cell>, HANYA nama property Livewire yang diikat
    diganti "barisBmd.{rowId}.{field}" (bukan "baris.{rowId}.{field}")
    supaya TIDAK bentrok dengan kotak Harga Satuan pada tab "jenis" yang
    sudah berjalan (2 tab KIB memakai <x-honor-ptk-tarif-cell> yang
    ASLI, TIDAK disentuh sama sekali oleh komponen baru ini).
--}}
@php
    $namaField = 'barisBmd.'.$rowId.'.'.$field;
    $nilaiAwal = $value !== null && $value !== '' ? number_format((int) $value, 0, ',', '.') : '';
@endphp

<div wire:ignore wire:key="bmd-tarif-cell-{{ $rowId }}-{{ $field }}-{{ $revisi }}">
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
