@props(['ringkasan'])

{{--
    Ringkasan jumlah data tersimpan dirinci per status sekolah
    (permintaan user 2026-10-16) - ditaruh di bawah tabel, di atas
    pagination. Komponen Blade dipakai bersama di menu Data PTK &
    Lampiran 2a/2b/2c (pola sama dgn <x-pagination-per-page>) supaya
    tampilannya konsisten di ke-4 menu. Prop `ringkasan` null artinya
    sedang TIDAK dalam tampilan "semua sekolah" (sedang difilter ke 1
    sekolah tertentu, atau bukan Superadmin) - komponen tidak
    menampilkan apa pun.
--}}
@if ($ringkasan)
    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
        <span>Jumlah data tersimpan:</span>
        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-700">Sekolah Negeri: {{ $ringkasan['negeri'] }}</span>
        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-700">Sekolah Swasta: {{ $ringkasan['swasta'] }}</span>
        <span class="inline-flex items-center rounded-full bg-slate-200 px-2.5 py-1 font-semibold text-slate-800">Total: {{ $ringkasan['total'] }}</span>
    </div>
@endif
