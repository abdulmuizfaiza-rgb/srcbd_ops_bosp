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

        {{-- BARU 2026-10-20 (permintaan user poin 3 & 4): berapa SEKOLAH
             yang sudah input vs belum (bukan jumlah baris PTK/Lampiran),
             dihitung dari SEMUA sekolah (tidak ikut filter pencarian yang
             sedang aktif, dikonfirmasi lewat AskUserQuestion). Key beda
             nama antar menu ('sekolahSudahInput'/'sekolahBelumInput' utk
             Lampiran 2a/2b/2c, 'sekolahSudahInputPtk'/'sekolahBelumInputPtk'
             utk Data PTK) supaya tidak ambigu field "input" yang mana. --}}
        @if (isset($ringkasan['sekolahSudahInput']))
            <span class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-1 font-medium text-sky-700">Sekolah Sudah Input: {{ $ringkasan['sekolahSudahInput'] }}</span>
            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 font-medium text-rose-700">Sekolah Belum Input: {{ $ringkasan['sekolahBelumInput'] }}</span>
        @endif
        @if (isset($ringkasan['sekolahSudahInputPtk']))
            <span class="inline-flex items-center rounded-full bg-sky-100 px-2.5 py-1 font-medium text-sky-700">Sekolah Sudah Input: {{ $ringkasan['sekolahSudahInputPtk'] }}</span>
            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 font-medium text-rose-700">Sekolah Belum Input: {{ $ringkasan['sekolahBelumInputPtk'] }}</span>
        @endif
    </div>

    {{-- Daftar "data belum lengkap" per sekolah (permintaan user
         2026-10-18) - khusus Data PTK, hanya sekolah yang punya data
         belum lengkap > 0 yang ditampilkan (jawaban AskUserQuestion
         "Daftar ringkas, hanya sekolah yang punya data belum lengkap").
         Dihitung dari 7 field: NUPTK, Tempat Lahir, Tanggal Lahir, TMT
         Sekolah Induk, Jurusan/Prodi, Tahun Lulus Ijazah, NPWP - lihat
         DataPtk::FIELD_BELUM_LENGKAP.

         SEJAK 2026-10-20 (permintaan user poin 1): badge nama sekolah
         ini sekarang bisa DIKLIK untuk membuka modal detail (daftar PTK
         + field yang belum lengkap per PTK), lewat
         wire:click="lihatDetailBelumLengkapPtk(...)" - method ini HANYA
         ada pada App\Livewire\ProfilSekolah\Index (komponen yang
         mengisi key 'belumLengkap'), bukan pada Lampiran 2a/2b/2c yang
         juga memakai komponen Blade ini, jadi aman: tombol ini TIDAK
         PERNAH ter-render pada komponen lain (key 'belumLengkap' selalu
         kosong/tidak ada di sana). --}}
    @if (isset($ringkasan['belumLengkap']) && $ringkasan['belumLengkap']->isNotEmpty())
        <div class="mt-2 flex flex-wrap items-start gap-2 text-xs text-slate-500">
            <span class="mt-1">Data PTK belum lengkap isiannya:</span>
            <div class="flex flex-wrap gap-2">
                @foreach ($ringkasan['belumLengkap'] as $baris)
                    <button type="button" wire:click="lihatDetailBelumLengkapPtk('{{ $baris->nama_sekolah }}')" class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 font-medium text-rose-700 hover:bg-rose-200 transition">{{ $baris->nama_sekolah }}: {{ $baris->jumlah }}</button>
                @endforeach
            </div>
        </div>
    @endif
@endif
