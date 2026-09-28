@props(['options' => [10, 25, 50, 100]])

{{--
    Dropdown "N / page" (permintaan user 2026-09-28, disertai contoh
    gambar) - komponen Blade dipakai bersama SEMUA menu yang punya
    pagination, ditaruh berdampingan dengan {{ $paginator->links() }}.
    `wire:model.live="..."` (atau nama properti manapun) dioper lewat
    $attributes->merge() di bawah, jadi cukup panggil:

        <x-pagination-per-page wire:model.live="perPage" />

    Livewire otomatis mengisi nilai <select> sesuai properti yang
    di-bind & memicu ulang render() (termasuk paginate() dgn jumlah baru)
    setiap kali dipilih - TIDAK perlu logika tambahan di sini.
--}}
<div class="inline-flex items-center gap-1.5 self-start rounded-full bg-white px-3 py-1.5 text-sm text-slate-600 shadow-sm ring-1 ring-slate-200 sm:self-auto">
    <select {{ $attributes->merge(['class' => 'border-0 bg-transparent p-0 pr-6 text-sm font-medium text-slate-700 focus:ring-0 cursor-pointer']) }}>
        @foreach ($options as $opsi)
            <option value="{{ $opsi }}">{{ $opsi }}</option>
        @endforeach
    </select>
    <span class="whitespace-nowrap">/ page</span>
</div>
