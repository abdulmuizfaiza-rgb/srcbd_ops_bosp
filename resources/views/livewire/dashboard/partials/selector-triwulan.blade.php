{{--
    Partial selector Tahun & Triwulan, dipakai bersama oleh dashboard
    Admin OPS dan Admin BOSP (round ketiga belas).

    Variabel yang dipakai (disediakan oleh Livewire\Dashboard\Index):
    - $tahun, $tahunOptions, $triwulanAktif
    Parameter include:
    - $warna: 'violet' (OPS) atau 'blue' (BOSP) - menentukan warna aktif tombol triwulan.
--}}
@php
    $warnaAktif = $warna === 'blue' ? 'bg-blue-600 border-blue-600 text-white' : 'bg-violet-600 border-violet-600 text-white';
    $warnaFokus = $warna === 'blue' ? 'focus:border-blue-500 focus:ring-blue-500' : 'focus:border-violet-500 focus:ring-violet-500';
@endphp
<div class="flex flex-wrap items-center gap-4 bg-white p-4 rounded-xl shadow-sm border border-slate-200/70">
    <div class="flex items-center gap-2.5">
        <label for="dashboardTahun{{ ucfirst($warna) }}" class="text-sm text-slate-600">Tahun</label>
        <select wire:model.live="tahun" id="dashboardTahun{{ ucfirst($warna) }}"
                class="border-slate-300 {{ $warnaFokus }} rounded-md shadow-sm text-sm">
            @foreach ($tahunOptions as $opsiTahun)
                <option value="{{ $opsiTahun }}">{{ $opsiTahun }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex items-center gap-1.5">
        <label class="text-sm text-slate-600 mr-1">Triwulan</label>
        @foreach ([1, 2, 3, 4] as $opsiTriwulan)
            <button type="button" wire:click="$set('triwulan', {{ $opsiTriwulan }})"
                class="px-3 py-1.5 rounded-md text-sm font-medium border transition
                    {{ $triwulanAktif === $opsiTriwulan ? $warnaAktif : 'bg-white border-slate-300 text-slate-600 hover:bg-slate-50' }}">
                TW-{{ $opsiTriwulan }}
            </button>
        @endforeach
    </div>
</div>
