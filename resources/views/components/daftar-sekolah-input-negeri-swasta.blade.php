@props(['daftar', 'status'])

{{--
    Konten modal detail "Sekolah Sudah/Belum Input" (permintaan user
    2026-10-25: "muncul total jumlah berdasarkan Sekolah negeri dan
    Sekolah Swasta. dan per sekolah") - dipakai bersama di ke-11 menu
    Pendataan BOSP (pola sama dgn <x-ringkasan-negeri-swasta>) supaya
    tampilannya konsisten. BEDA dari modal sejenis pada Lampiran 2a/2b/2c
    (yang hanya daftar nama rata, tanpa pengelompokan) - di sini daftar
    sekolah DIKELOMPOKKAN Negeri/Swasta sesuai permintaan.

    Prop `daftar`: Collection<ProfilSekolah> (minimal kolom nama_sekolah
    & status) hasil {Menu}\Index::daftarSekolahInput().
    Prop `status`: 'sudah' atau 'belum'.
--}}
@php
    $negeri = $daftar->where('status', \App\Models\ProfilSekolah::STATUS_NEGERI);
    $swasta = $daftar->where('status', \App\Models\ProfilSekolah::STATUS_SWASTA);
@endphp

<h2 class="text-lg font-medium text-slate-900">{{ $status === 'sudah' ? 'Sekolah Sudah Input' : 'Sekolah Belum Input' }} ({{ $daftar->count() }})</h2>
<p class="mt-1 text-xs text-slate-500">Sekolah Negeri: {{ $negeri->count() }} &middot; Sekolah Swasta: {{ $swasta->count() }}</p>

<div class="mt-4 max-h-96 overflow-y-auto space-y-4">
    <div>
        <h3 class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">Sekolah Negeri ({{ $negeri->count() }})</h3>
        @forelse ($negeri as $sekolah)
            <div class="text-sm text-slate-700 py-0.5 border-b border-slate-100 last:border-0">{{ $sekolah->nama_sekolah }}</div>
        @empty
            <p class="text-xs text-slate-400 italic">Tidak ada.</p>
        @endforelse
    </div>
    <div>
        <h3 class="text-xs font-semibold text-slate-600 uppercase tracking-wide mb-1.5">Sekolah Swasta ({{ $swasta->count() }})</h3>
        @forelse ($swasta as $sekolah)
            <div class="text-sm text-slate-700 py-0.5 border-b border-slate-100 last:border-0">{{ $sekolah->nama_sekolah }}</div>
        @empty
            <p class="text-xs text-slate-400 italic">Tidak ada.</p>
        @endforelse
    </div>
</div>
