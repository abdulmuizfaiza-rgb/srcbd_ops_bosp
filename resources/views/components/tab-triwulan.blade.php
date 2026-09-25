@props(['options', 'active', 'prefix'])

<nav class="flex flex-wrap gap-3">
    @foreach ($options as $value => $label)
        @php
            $skema = match ($value) {
                1 => ['aktif' => 'from-sky-400 to-blue-600', 'nonaktif' => 'from-sky-50 to-sky-100 text-sky-700 ring-sky-200'],
                2 => ['aktif' => 'from-emerald-400 to-green-600', 'nonaktif' => 'from-emerald-50 to-emerald-100 text-emerald-700 ring-emerald-200'],
                3 => ['aktif' => 'from-amber-400 to-orange-600', 'nonaktif' => 'from-amber-50 to-amber-100 text-amber-700 ring-amber-200'],
                4 => ['aktif' => 'from-fuchsia-400 to-purple-600', 'nonaktif' => 'from-fuchsia-50 to-fuchsia-100 text-fuchsia-700 ring-fuchsia-200'],
                default => ['aktif' => 'from-slate-400 to-slate-600', 'nonaktif' => 'from-slate-50 to-slate-100 text-slate-700 ring-slate-200'],
            };
        @endphp
        <button
            type="button"
            wire:click="pindahTab({{ $value }})"
            @class([
                'px-4 py-2 rounded-xl text-sm font-bold tracking-wide transition-all duration-150 ring-1 bg-gradient-to-b',
                $skema['aktif'].' text-white shadow-lg ring-black/10 border-b-4 border-black/20 scale-105' => $active === $value,
                $skema['nonaktif'].' shadow-sm hover:shadow-md hover:scale-[1.02] border-b-2 border-black/5' => $active !== $value,
            ])
        >
            {{ $prefix }} {{ $label }}
        </button>
    @endforeach
</nav>
