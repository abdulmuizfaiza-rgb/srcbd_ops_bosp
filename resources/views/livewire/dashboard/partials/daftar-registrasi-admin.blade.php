{{--
    Partial daftar sekolah + status Registrasi Admin OPS & Admin BOSP
    sekaligus (2 badge per baris) - dashboard Superadmin, round keempat
    belas (2026-09-24). Beda dari partials/daftar-sekolah.blade.php:
    partial itu memisah "sudah"/"belum" jadi 2 KOLOM (dgn badge
    Negeri/Swasta di tiap baris); partial ini SATU list utuh (semua
    sekolah), status "sudah"/"belum" ditandai lewat WARNA badge
    (biru/merah) - sesuai permintaan user eksplisit.

    ROUND KELIMA BELAS (2026-09-24): daftar diganti dari Collection
    biasa (di-scroll dalam kotak) jadi LengthAwarePaginator (Livewire
    WithPagination, pageName 'halamanRegistrasi') - permintaan user
    "supaya tidak terlalu panjang ke bawah". Kotak scroll
    (max-h-96/overflow-y-auto) DIHAPUS krn sudah tidak perlu (1 halaman
    cuma 10 baris).

    ROUND KEENAM BELAS (2026-09-24): `scrollTo => false` ditambahkan ke
    `->links()` - bawaan Livewire men-scroll ke `<body>` (efektifnya ke
    PALING ATAS halaman) setiap kali tombol halaman diklik, bikin
    posisi scroll user "meloncat" ke atas alih-alih tetap di widget ini
    - keluhan user eksplisit ("langsung kursor ke atas").

    Variabel:
    - $halamanRegistrasi: LengthAwarePaginator<{sekolah: ProfilSekolah, opsSudah: bool, bospSudah: bool}>
--}}
<div class="bg-white rounded-xl shadow-sm border border-slate-200/70 overflow-hidden">
    <div class="p-5 pb-3">
        <p class="text-sm font-semibold text-slate-800">Registrasi Admin OPS & Admin BOSP per Sekolah</p>
        <p class="text-xs text-slate-500">Biru = sudah registrasi (akun disetujui Superadmin) - Merah = belum registrasi.</p>
    </div>
    <div class="divide-y divide-slate-100">
        @forelse ($halamanRegistrasi as $baris)
            <div class="px-5 py-2.5 flex items-center justify-between gap-3 text-sm">
                <span class="text-slate-700 truncate">{{ $baris['sekolah']->nama_sekolah }}</span>
                <span class="shrink-0 flex items-center gap-1.5">
                    <span class="text-[11px] px-2 py-0.5 rounded-full {{ $baris['opsSudah'] ? 'bg-blue-50 text-blue-700' : 'bg-red-50 text-red-700' }}">
                        OPS: {{ $baris['opsSudah'] ? 'Sudah' : 'Belum' }}
                    </span>
                    <span class="text-[11px] px-2 py-0.5 rounded-full {{ $baris['bospSudah'] ? 'bg-blue-50 text-blue-700' : 'bg-red-50 text-red-700' }}">
                        BOSP: {{ $baris['bospSudah'] ? 'Sudah' : 'Belum' }}
                    </span>
                </span>
            </div>
        @empty
            <p class="text-sm text-slate-400 text-center py-8">Belum ada data sekolah.</p>
        @endforelse
    </div>
    @if ($halamanRegistrasi->hasPages())
        <div class="px-5 py-3 border-t border-slate-100">
            {{ $halamanRegistrasi->links(data: ['scrollTo' => false]) }}
        </div>
    @endif
</div>
