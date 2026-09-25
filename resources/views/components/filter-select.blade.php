@props(['wireModel', 'label', 'options', 'allLabel' => null, 'color' => 'indigo'])

@php
    $skema = match ($color) {
        'sky' => 'from-sky-400 to-sky-700 border-sky-950/30 focus-within:ring-sky-300',
        'emerald' => 'from-emerald-400 to-emerald-700 border-emerald-950/30 focus-within:ring-emerald-300',
        'amber' => 'from-amber-400 to-amber-600 border-amber-950/30 focus-within:ring-amber-300',
        'fuchsia' => 'from-fuchsia-400 to-fuchsia-700 border-fuchsia-950/30 focus-within:ring-fuchsia-300',
        default => 'from-indigo-400 to-indigo-700 border-indigo-950/30 focus-within:ring-indigo-300',
    };
@endphp

{{--
    Catatan teknis: gaya gradient "3D" SENGAJA dipasang pada <label> pembungkus
    ini, BUKAN langsung pada elemen <select>. Plugin @tailwindcss/forms
    memaksa `background-color: #fff` pada semua <select> lewat reset di
    layer base, dan pada elemen <select> (berbeda dari <div> biasa) warna
    solid itu tetap "menang" secara visual dibanding `background-image`
    (gradient) yang dipasang lewat utility class langsung di elemen yang
    sama. Solusinya: <select>-nya sendiri dibuat transparan sepenuhnya
    (bg-transparent, tanpa border/shadow sendiri) dan hanya menampilkan
    teks + dropdown arrow bawaan disembunyikan (appearance-none) - seluruh
    tampilan gradient/shadow/border-b "3D" datang dari <label> ini.
--}}
<label
    {{ $attributes->merge([
        'class' => "relative inline-flex items-center rounded-lg bg-gradient-to-b $skema shadow-md border-b-[3px] hover:shadow-lg hover:brightness-110 hover:-translate-y-px active:translate-y-0 active:border-b-[1px] transition-all duration-100 focus-within:ring-2 focus-within:ring-offset-1 cursor-pointer"
    ]) }}
>
    <span class="sr-only">{{ $label }}</span>
    <x-icon name="filter" class="pointer-events-none absolute left-2.5 w-3 h-3 text-white/85" />
    <select
        wire:model.live="{{ $wireModel }}"
        class="appearance-none cursor-pointer bg-transparent border-0 shadow-none pl-7 pr-7 py-1.5 rounded-lg text-[11px] font-bold tracking-wide text-white focus:outline-none focus:ring-0"
    >
        @if ($allLabel)
            <option value="" class="text-slate-700">{{ $allLabel }}</option>
        @endif
        @foreach ($options as $value => $optLabel)
            <option value="{{ $value }}" class="text-slate-700">{{ $optLabel }}</option>
        @endforeach
    </select>
    <svg class="pointer-events-none absolute right-2 w-3 h-3 text-white/85" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
    </svg>
</label>
