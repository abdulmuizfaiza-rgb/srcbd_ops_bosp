@props(['zoom'])

<div class="inline-flex items-center gap-0.5 rounded-lg bg-gradient-to-b from-slate-600 to-slate-800 shadow-md border-b-[3px] border-slate-950/40 px-1 py-1">
    <button type="button" wire:click="zoomOut" title="Perkecil tampilan halaman" class="p-1.5 rounded-md text-white hover:bg-white/10 active:scale-90 transition">
        <x-icon name="zoom-out" class="w-3.5 h-3.5" />
    </button>
    <button type="button" wire:click="zoomReset" title="Kembalikan ke ukuran normal (100%)" class="px-1.5 text-[11px] font-bold text-white/90 hover:text-white min-w-[2.75rem] text-center">
        {{ $zoom }}%
    </button>
    <button type="button" wire:click="zoomIn" title="Perbesar tampilan halaman" class="p-1.5 rounded-md text-white hover:bg-white/10 active:scale-90 transition">
        <x-icon name="zoom-in" class="w-3.5 h-3.5" />
    </button>
</div>
