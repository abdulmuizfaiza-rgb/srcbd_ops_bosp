{{--
    Pembungkus field password + indikator kekuatan password bergaya "3D"
    (progress bar bertingkat warna + daftar kriteria) yang diperbarui
    LANGSUNG di browser (Alpine, tanpa request ke server) begitu user
    mengetik - dipakai di form "Update Password" (Profil) dan "Ganti
    Password Wajib" supaya kedua tempat user mengganti password sendiri
    konsisten.

    Cara pakai: bungkus <x-text-input> milik field password (BUKAN field
    konfirmasi) dengan komponen ini lewat slot - komponen ini mendengarkan
    event "input" bawaan browser yang menggelembung (bubble) dari field
    di dalam slot, jadi tidak perlu mengubah wire:model field itu sendiri
    (tetap deferred, tidak ada request tambahan tiap ketik).
--}}
<div
    x-data="{
        kekuatan: 0,
        kriteria: { panjang: false, besar: false, kecil: false, angka: false, simbol: false },
        cek(nilai) {
            this.kriteria.panjang = nilai.length >= 6;
            this.kriteria.besar = /[A-Z]/.test(nilai);
            this.kriteria.kecil = /[a-z]/.test(nilai);
            this.kriteria.angka = /[0-9]/.test(nilai);
            this.kriteria.simbol = /[^A-Za-z0-9]/.test(nilai);
            this.kekuatan = Object.values(this.kriteria).filter(Boolean).length;
            this.diisi = nilai.length > 0;
        },
        diisi: false,
        warnaGradasi() {
            if (this.kekuatan <= 2) return 'from-rose-400 to-rose-600 border-rose-800/40';
            if (this.kekuatan <= 4) return 'from-amber-400 to-amber-600 border-amber-800/40';
            return 'from-emerald-400 to-emerald-600 border-emerald-800/40';
        },
        warnaTeks() {
            if (this.kekuatan <= 2) return 'text-rose-600';
            if (this.kekuatan <= 4) return 'text-amber-600';
            return 'text-emerald-600';
        },
        label() {
            if (this.kekuatan <= 2) return 'Lemah';
            if (this.kekuatan <= 4) return 'Sedang';
            return 'Kuat';
        },
    }"
    x-on:input="cek($event.target.value)"
>
    {{ $slot }}

    <p class="mt-1.5 text-xs text-slate-500">
        Gunakan minimal 6 karakter dengan kombinasi huruf besar, huruf kecil, angka, dan simbol.
    </p>

    <div class="mt-2" x-show="diisi" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        <div class="flex items-center gap-2">
            <div class="flex-1 flex gap-1 rounded-full bg-slate-300/70 p-1 shadow-[inset_0_2px_3px_rgba(0,0,0,0.25)]">
                <template x-for="i in 5" :key="i">
                    <div
                        class="h-3 flex-1 rounded-full transition-all duration-300 ease-out"
                        :class="i <= kekuatan
                            ? `bg-gradient-to-b ${warnaGradasi()} shadow-[0_1px_2px_rgba(0,0,0,0.3),inset_0_1px_1px_rgba(255,255,255,0.5)] border-b-2 scale-100`
                            : 'bg-slate-100/80 scale-y-75'"
                    ></div>
                </template>
            </div>
            <span class="text-xs font-bold tracking-wide w-12 text-right drop-shadow-sm" :class="warnaTeks()" x-text="label()"></span>
        </div>

        <ul class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1">
            <li class="flex items-center gap-1.5 text-xs" :class="kriteria.panjang ? 'text-emerald-600' : 'text-slate-400'">
                <span class="flex items-center justify-center w-3.5 h-3.5 rounded-full shrink-0" :class="kriteria.panjang ? 'bg-emerald-500 text-white' : 'bg-slate-200'">
                    <x-icon name="check" class="w-2 h-2" />
                </span>
                Minimal 6 karakter
            </li>
            <li class="flex items-center gap-1.5 text-xs" :class="kriteria.besar ? 'text-emerald-600' : 'text-slate-400'">
                <span class="flex items-center justify-center w-3.5 h-3.5 rounded-full shrink-0" :class="kriteria.besar ? 'bg-emerald-500 text-white' : 'bg-slate-200'">
                    <x-icon name="check" class="w-2 h-2" />
                </span>
                Huruf besar (A-Z)
            </li>
            <li class="flex items-center gap-1.5 text-xs" :class="kriteria.kecil ? 'text-emerald-600' : 'text-slate-400'">
                <span class="flex items-center justify-center w-3.5 h-3.5 rounded-full shrink-0" :class="kriteria.kecil ? 'bg-emerald-500 text-white' : 'bg-slate-200'">
                    <x-icon name="check" class="w-2 h-2" />
                </span>
                Huruf kecil (a-z)
            </li>
            <li class="flex items-center gap-1.5 text-xs" :class="kriteria.angka ? 'text-emerald-600' : 'text-slate-400'">
                <span class="flex items-center justify-center w-3.5 h-3.5 rounded-full shrink-0" :class="kriteria.angka ? 'bg-emerald-500 text-white' : 'bg-slate-200'">
                    <x-icon name="check" class="w-2 h-2" />
                </span>
                Angka (0-9)
            </li>
            <li class="flex items-center gap-1.5 text-xs" :class="kriteria.simbol ? 'text-emerald-600' : 'text-slate-400'">
                <span class="flex items-center justify-center w-3.5 h-3.5 rounded-full shrink-0" :class="kriteria.simbol ? 'bg-emerald-500 text-white' : 'bg-slate-200'">
                    <x-icon name="check" class="w-2 h-2" />
                </span>
                Simbol (!@#$%...)
            </li>
        </ul>
    </div>
</div>
