{{--
    Partial daftar SEMUA sekolah dgn 1 badge status per baris (biru =
    sudah, merah = belum) - dipakai dashboard Superadmin utk widget
    "Validasi Pendataan BOSP" & "Validasi Pendataan OPS" (round keempat
    belas, 2026-09-24, jawaban AskUserQuestion "Gabung/Pisah List": 2
    WIDGET terpisah, tapi masing-masing 1 list utuh dibedakan lewat
    warna, BUKAN dipecah jadi kolom sudah/belum).

    ROUND KELIMA BELAS (2026-09-24): daftar diganti dari Collection
    biasa (di-scroll dalam kotak) jadi LengthAwarePaginator (Livewire
    WithPagination) - permintaan user "supaya tidak terlalu panjang ke
    bawah". Kotak scroll (max-h-96/overflow-y-auto) DIHAPUS krn sudah
    tidak perlu (1 halaman cuma 10 baris). $daftarStatus sekarang berupa
    paginator BUKAN Collection biasa - pageName-nya BEDA utk widget
    Validasi BOSP ('halamanValidasiBosp') vs Validasi OPS
    ('halamanValidasiOps') supaya bisa dipaginasi INDEPENDEN walau
    partial-nya sama & tampil bersamaan di 1 halaman.

    ROUND KEENAM BELAS (2026-09-24): `scrollTo => false` ditambahkan ke
    `->links()` - bawaan Livewire men-scroll ke `<body>` (efektifnya ke
    PALING ATAS halaman) setiap kali tombol halaman diklik, bikin
    posisi scroll user "meloncat" ke atas alih-alih tetap di widget ini
    - keluhan user eksplisit ("langsung kursor ke atas").

    Variabel:
    - $judul, $keterangan: judul & sub-judul widget
    - $daftarStatus: LengthAwarePaginator<{sekolah: ProfilSekolah, sudah: bool}>
    - $labelSudah, $labelBelum: teks pada badge (misal "Sudah Validasi"/"Belum Validasi")
    - $jumlahSudah, $jumlahBelum: angka ringkasan di atas list
--}}
<div class="bg-white rounded-xl shadow-sm border border-slate-200/70 overflow-hidden">
    <div class="p-5 pb-3 flex items-center justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-slate-800">{{ $judul }}</p>
            <p class="text-xs text-slate-500">{{ $keterangan }}</p>
        </div>
        <div class="shrink-0 flex items-center gap-2 text-xs">
            <span class="px-2 py-1 rounded-lg bg-blue-50 text-blue-700 font-semibold">{{ $jumlahSudah }} Sudah</span>
            <span class="px-2 py-1 rounded-lg bg-red-50 text-red-700 font-semibold">{{ $jumlahBelum }} Belum</span>
        </div>
    </div>
    <div class="divide-y divide-slate-100">
        @forelse ($daftarStatus as $baris)
            <div class="px-5 py-2.5 flex items-center justify-between gap-3 text-sm">
                <span class="text-slate-700 truncate">{{ $baris['sekolah']->nama_sekolah }}</span>
                <span class="shrink-0 text-[11px] px-2 py-0.5 rounded-full {{ $baris['sudah'] ? 'bg-blue-50 text-blue-700' : 'bg-red-50 text-red-700' }}">
                    {{ $baris['sudah'] ? $labelSudah : $labelBelum }}
                </span>
            </div>
        @empty
            <p class="text-sm text-slate-400 text-center py-8">Belum ada data sekolah.</p>
        @endforelse
    </div>
    @if ($daftarStatus->hasPages())
        <div class="px-5 py-3 border-t border-slate-100">
            {{ $daftarStatus->links(data: ['scrollTo' => false]) }}
        </div>
    @endif
</div>
