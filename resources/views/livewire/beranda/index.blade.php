{{--
    Landing page publik (belum login) - route "/", nama route "beranda".
    Lihat docblock App\Livewire\Beranda\Index utk detail keputusan
    bisnis (jawaban AskUserQuestion 2026-09-24) & alasan teknis.
--}}
<div class="space-y-8" wire:poll.30s.visible>
    {{-- ============ HERO / SAPAAN ============ --}}
    <div class="text-center pt-6 sm:pt-10 pb-2 animate-fade-in-up">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md ring-1 ring-white/20 text-blue-100 text-xs font-medium">
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
            </span>
            Informasi Terkini - Tahun {{ $tahun }}, Triwulan {{ $triwulan }}
        </span>

        <h1 class="mt-4 text-2xl sm:text-4xl font-bold text-white">
            Progres Pendataan OPS &amp; BOSP
        </h1>
        <p class="mt-2 text-sm sm:text-base text-blue-100/90 max-w-2xl mx-auto">
            Rekap sekolah yang sudah dan belum melakukan Pendataan OPS maupun Pendataan BOSP untuk Triwulan {{ $triwulan }}
            Tahun {{ $tahun }} - dari total {{ $totalSekolah }} sekolah terdaftar.
        </p>
    </div>

    {{--
        ============ SELECTOR TAHUN & TRIWULAN ============
        Round ketujuh belas (2026-09-24, permintaan user "tambahkan
        tombol Tahun dan pilihan triwulan pada halaman landingpage
        awal") - memakai ulang APA ADANYA partial yang sudah ada &
        teruji di dashboard Admin OPS/BOSP (lihat docblock
        App\Livewire\Beranda\Index).
    --}}
    {{--
        ============ JAM DIGITAL ANIMASI (dirapikan 2026-10-03) ============
        Permintaan user 2026-10-01, DIRAPIKAN 2026-10-03 atas permintaan
        user: posisi di sebelah kanan, SEJAJAR (1 baris, items-center)
        dengan baris filter Tahun/Triwulan, dan ukuran LEBIH KECIL -
        kartu jam sekarang dibuat kompak (setinggi baris filter itu
        sendiri) & dipakaikan gaya visual yang SAMA PERSIS dengan kartu
        filter (bg-white, rounded-xl, shadow-sm, border-slate-200/70)
        supaya terlihat sejajar/serasi sebagai 1 baris, bukan 2 kartu
        yang berbeda tinggi seperti sebelumnya. Partial selector-triwulan
        TETAP TIDAK diubah (dipakai bersama Dashboard Admin OPS/BOSP) -
        jam ini tetap elemen terpisah di sampingnya, hanya gaya &
        ukurannya yang disamakan supaya "menyatu" secara visual.

        Animasinya: titik dua berkedip (Tailwind animate-pulse) & angka
        detik "berdenyut" tiap kali berganti (scale 100%->125%->100%),
        plus ikon jam kecil dengan cincin denyut (pola sama seperti badge
        "Informasi Terkini" di bagian hero atas - animate-ping).
    --}}
    <div class="animate-fade-in-up flex flex-row flex-wrap items-center gap-4">
        <div class="flex-1 min-w-0">
            @include('livewire.dashboard.partials.selector-triwulan', ['warna' => 'blue'])
        </div>

        <div class="flex items-center gap-2.5 bg-white rounded-xl shadow-sm border border-slate-200/70 px-4 py-3"
             x-data="{
                jamMenit: '00:00',
                detik: '00',
                tanggalSingkat: '',
                centang: false,
                perbarui() {
                    const sekarang = new Date();
                    const pad = (n) => String(n).padStart(2, '0');
                    this.jamMenit = pad(sekarang.getHours()) + ':' + pad(sekarang.getMinutes());
                    this.detik = pad(sekarang.getSeconds());
                    const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jum\'at', 'Sabtu'][sekarang.getDay()];
                    const bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][sekarang.getMonth()];
                    this.tanggalSingkat = hari + ', ' + sekarang.getDate() + ' ' + bulan + ' ' + sekarang.getFullYear();
                    this.centang = true;
                    setTimeout(() => { this.centang = false; }, 350);
                },
             }"
             x-init="perbarui(); setInterval(() => perbarui(), 1000)">
            <span class="relative flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-50">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-20"></span>
                <svg class="relative h-4 w-4 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9" />
                    <path d="M12 7v5l3 3" />
                </svg>
            </span>
            <div class="text-left leading-tight">
                <p class="flex items-baseline gap-0.5 font-mono font-bold text-slate-700 text-base tabular-nums">
                    <span x-text="jamMenit"></span>
                    <span class="text-blue-500 animate-pulse">:</span>
                    <span class="inline-block text-sm text-blue-500 transition-transform duration-300" :class="centang ? 'scale-125' : 'scale-100'" x-text="detik"></span>
                </p>
                <p class="text-[11px] text-slate-400" x-text="tanggalSingkat"></p>
            </div>
        </div>
    </div>

    {{-- ============ 2 KARTU RINGKASAN (HERO GRADIENT) ============ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        {{-- Ringkasan Pendataan OPS --}}
        <div class="relative overflow-hidden rounded-2xl shadow-xl bg-gradient-to-br from-violet-600 via-purple-600 to-fuchsia-600 animate-fade-in-up">
            <div class="pointer-events-none absolute -top-10 -right-10 w-56 h-56 rounded-full bg-white/10 blur-2xl animate-blob-a"></div>
            <div class="pointer-events-none absolute -bottom-16 -left-10 w-64 h-64 rounded-full bg-fuchsia-300/20 blur-2xl animate-blob-b"></div>

            <div class="relative p-6 sm:p-7 text-white">
                <p class="text-xs font-semibold uppercase tracking-wider text-violet-100">Pendataan OPS</p>
                <div class="mt-3 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-4xl font-bold leading-none">{{ $opsPersen }}%</p>
                        <p class="text-xs text-violet-100 mt-1">Sekolah sudah pendataan</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm"><span class="font-bold">{{ $opsSudah }}</span> Sudah</p>
                        <p class="text-sm text-violet-100"><span class="font-bold">{{ $opsBelum }}</span> Belum</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ringkasan Pendataan BOSP --}}
        <div class="relative overflow-hidden rounded-2xl shadow-xl bg-gradient-to-br from-sky-600 via-blue-600 to-indigo-600 animate-fade-in-up" style="animation-delay:.15s">
            <div class="pointer-events-none absolute -top-10 -right-10 w-56 h-56 rounded-full bg-white/10 blur-2xl animate-blob-a"></div>
            <div class="pointer-events-none absolute -bottom-16 -left-10 w-64 h-64 rounded-full bg-sky-300/20 blur-2xl animate-blob-c"></div>

            <div class="relative p-6 sm:p-7 text-white">
                <p class="text-xs font-semibold uppercase tracking-wider text-sky-100">Pendataan BOSP</p>
                <div class="mt-3 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-4xl font-bold leading-none">{{ $bospPersen }}%</p>
                        <p class="text-xs text-sky-100 mt-1">Sekolah sudah pendataan</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm"><span class="font-bold">{{ $bospSudah }}</span> Sudah</p>
                        <p class="text-sm text-sky-100"><span class="font-bold">{{ $bospBelum }}</span> Belum</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============ 2 DAFTAR SEKOLAH (PAGINASI) ============ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 pb-4">
        @include('livewire.dashboard.partials.daftar-status-sekolah', [
            'judul' => 'Daftar Sekolah - Pendataan OPS',
            'keterangan' => 'Lampiran 2a, 2b, dan 2c lengkap - Triwulan '.$triwulan.', Tahun '.$tahun.'.',
            'daftarStatus' => $halamanOps,
            'labelSudah' => 'Sudah Pendataan',
            'labelBelum' => 'Belum Pendataan',
            'jumlahSudah' => $opsSudah,
            'jumlahBelum' => $opsBelum,
        ])

        @include('livewire.dashboard.partials.daftar-status-sekolah', [
            'judul' => 'Daftar Sekolah - Pendataan BOSP',
            'keterangan' => 'Ada data di salah satu menu BOSP - Triwulan '.$triwulan.', Tahun '.$tahun.'.',
            'daftarStatus' => $halamanBosp,
            'labelSudah' => 'Sudah Pendataan',
            'labelBelum' => 'Belum Pendataan',
            'jumlahSudah' => $bospSudah,
            'jumlahBelum' => $bospBelum,
        ])
    </div>
</div>
