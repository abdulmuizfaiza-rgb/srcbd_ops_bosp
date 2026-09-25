{{--
    Partial daftar sekolah "sudah" vs "belum" mengerjakan, dipakai bersama
    oleh dashboard Admin OPS dan Admin BOSP (round ketiga belas).

    Parameter include:
    - $label: teks aksi, misal 'mengerjakan pendataan OPS' / 'mengerjakan pendataan BOSP'
    - $sekolahSudah, $sekolahBelum: Collection<ProfilSekolah>
    - $triwulanAktif: int
    - $catatanSudah: penjelasan singkat definisi "sudah" utk kategori ini
--}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200/70 overflow-hidden">
        <div class="p-5 pb-3 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-800">Sudah {{ ucfirst($label) }}</p>
                <p class="text-xs text-slate-500">Triwulan {{ $triwulanAktif }} - {{ $catatanSudah }}</p>
            </div>
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-emerald-50 text-emerald-600 text-sm font-bold">
                {{ $sekolahSudah->count() }}
            </span>
        </div>
        <div class="max-h-80 overflow-y-auto scrollbar-modern divide-y divide-slate-100">
            @forelse ($sekolahSudah as $sekolahBaris)
                <div class="px-5 py-2.5 flex items-center justify-between text-sm">
                    <span class="text-slate-700">{{ $sekolahBaris->nama_sekolah }}</span>
                    <span class="text-[11px] px-2 py-0.5 rounded-full {{ $sekolahBaris->status === 'negeri' ? 'bg-sky-50 text-sky-700' : 'bg-amber-50 text-amber-700' }}">
                        {{ ucfirst($sekolahBaris->status) }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-slate-400 text-center py-8">Belum ada sekolah yang selesai.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200/70 overflow-hidden">
        <div class="p-5 pb-3 flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-800">Belum {{ ucfirst($label) }}</p>
                <p class="text-xs text-slate-500">Triwulan {{ $triwulanAktif }}</p>
            </div>
            <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-rose-50 text-rose-600 text-sm font-bold">
                {{ $sekolahBelum->count() }}
            </span>
        </div>
        <div class="max-h-80 overflow-y-auto scrollbar-modern divide-y divide-slate-100">
            @forelse ($sekolahBelum as $sekolahBaris)
                <div class="px-5 py-2.5 flex items-center justify-between text-sm">
                    <span class="text-slate-700">{{ $sekolahBaris->nama_sekolah }}</span>
                    <span class="text-[11px] px-2 py-0.5 rounded-full {{ $sekolahBaris->status === 'negeri' ? 'bg-sky-50 text-sky-700' : 'bg-amber-50 text-amber-700' }}">
                        {{ ucfirst($sekolahBaris->status) }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-slate-400 text-center py-8">Semua sekolah sudah selesai.</p>
            @endforelse
        </div>
    </div>
</div>
