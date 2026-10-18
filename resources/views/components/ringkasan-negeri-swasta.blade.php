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

        {{-- Rincian Status Sertifikasi (permintaan user 2026-10-18) - khusus
             Data PTK, key ini TIDAK ADA pada ringkasan() Lampiran 2a/2b/2c
             (tidak punya field status_sertifikasi) sehingga bagian ini
             otomatis tidak tampil di menu-menu itu. --}}
        @if (isset($ringkasan['sudahSertifikasi']))
            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 font-medium text-emerald-700">Sudah Sertifikasi: {{ $ringkasan['sudahSertifikasi'] }}</span>
            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 font-medium text-amber-700">Belum Sertifikasi: {{ $ringkasan['belumSertifikasi'] }}</span>
        @endif
    </div>

    {{-- Daftar "data belum lengkap" per sekolah (permintaan user
         2026-10-18) - khusus Data PTK, hanya sekolah yang punya data
         belum lengkap > 0 yang ditampilkan (jawaban AskUserQuestion
         "Daftar ringkas, hanya sekolah yang punya data belum lengkap").
         Dihitung dari 7 field: NUPTK, Tempat Lahir, Tanggal Lahir, TMT
         Sekolah Induk, Jurusan/Prodi, Tahun Lulus Ijazah, NPWP - lihat
         DataPtk::FIELD_BELUM_LENGKAP. --}}
    @if (isset($ringkasan['belumLengkap']) && $ringkasan['belumLengkap']->isNotEmpty())
        <div class="mt-2 flex flex-wrap items-start gap-2 text-xs text-slate-500">
            <span class="mt-1">Data PTK belum lengkap isiannya:</span>
            <div class="flex flex-wrap gap-2">
                @foreach ($ringkasan['belumLengkap'] as $baris)
                    <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 font-medium text-rose-700">{{ $baris->nama_sekolah }}: {{ $baris->jumlah }}</span>
                @endforeach
            </div>
        </div>
    @endif
@endif
