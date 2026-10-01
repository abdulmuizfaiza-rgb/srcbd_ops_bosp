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
    <div class="animate-fade-in-up flex flex-col lg:flex-row items-stretch gap-4">
        <div class="flex-1 min-w-0">
            @include('livewire.dashboard.partials.selector-triwulan', ['warna' => 'blue'])
        </div>

        {{--
            ============ JAM DIGITAL ANIMASI ============
            Permintaan user 2026-10-01: tambahkan jam bergaya animasi di
            sebelah kanan baris filter Tahun/Triwulan pada landingpage
            (jawaban AskUserQuestion "Jam digital animasi"). Murni
            Alpine.js sisi client (memakai jam PERANGKAT PENGUNJUNG, bukan
            jam server - wajar untuk widget dekoratif seperti ini), TIDAK
            memakai data apapun dari Livewire\Beranda\Index. Animasinya:
            titik dua berkedip (Tailwind animate-pulse) & angka detik
            "berdenyut" tiap kali berganti (scale 100%->125%->100%),
            dibungkus kartu dengan efek blob bergerak (dipakai ulang dari
            animate-blob-a/animate-blob-c yang sudah ada di app.css).
        --}}
        <div class="lg:w-72 relative overflow-hidden rounded-xl shadow-sm border border-white/10 bg-gradient-to-br from-slate-800 via-slate-900 to-black px-5 py-3 flex flex-col items-center justify-center text-center"
             x-data="{
                jamMenit: '00:00',
                detik: '00',
                tanggal: '',
                centang: false,
                perbarui() {
                    const sekarang = new Date();
                    const pad = (n) => String(n).padStart(2, '0');
                    this.jamMenit = pad(sekarang.getHours()) + ':' + pad(sekarang.getMinutes());
                    this.detik = pad(sekarang.getSeconds());
                    const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jum\'at', 'Sabtu'][sekarang.getDay()];
                    const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'][sekarang.getMonth()];
                    this.tanggal = hari + ', ' + sekarang.getDate() + ' ' + bulan + ' ' + sekarang.getFullYear();
                    this.centang = true;
                    setTimeout(() => { this.centang = false; }, 350);
                },
             }"
             x-init="perbarui(); setInterval(() => perbarui(), 1000)">
            <div class="pointer-events-none absolute -top-8 -right-8 w-32 h-32 rounded-full bg-blue-500/20 blur-2xl animate-blob-a"></div>
            <div class="pointer-events-none absolute -bottom-10 -left-10 w-32 h-32 rounded-full bg-sky-400/10 blur-2xl animate-blob-c"></div>

            <p class="relative text-[11px] uppercase tracking-widest text-blue-200/70 font-semibold">Waktu Sekarang</p>
            <div class="relative mt-1 flex items-center justify-center gap-1 font-mono font-bold text-white text-3xl tabular-nums">
                <span x-text="jamMenit"></span>
                <span class="text-sky-400 animate-pulse">:</span>
                <span class="inline-block text-xl text-sky-300 transition-transform duration-300" :class="centang ? 'scale-125' : 'scale-100'" x-text="detik"></span>
            </div>
            <p class="relative mt-1 text-xs text-blue-100/70" x-text="tanggal"></p>
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
