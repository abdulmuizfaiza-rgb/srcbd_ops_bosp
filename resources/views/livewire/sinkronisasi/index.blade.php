<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Sinkronisasi dan Tarik Data') }}
    </h2>
</x-slot>

<div>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('sinkronisasi_success'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm whitespace-pre-line">
                    {{ session('sinkronisasi_success') }}
                </div>
            @endif

            @if (session('sinkronisasi_error'))
                <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm whitespace-pre-line">
                    {{ session('sinkronisasi_error') }}
                </div>
            @endif

            {{-- Permintaan user 2026-10-14 poin 3: menu ini tampil di LOGIN
                 SUPERADMIN baik di Lokal maupun Online, tapi tombol aksi
                 hanya aktif di Lokal (lihat App\Livewire\Sinkronisasi\Index
                 ::isLokal(), dari config('sync.peran')). --}}
            <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-4 text-sm">
                Instalasi ini berperan sebagai:
                <span class="font-bold">{{ $this->isLokal() ? 'LOKAL (bisa mengirim & menarik data)' : 'ONLINE (hanya melayani & menampilkan riwayat)' }}</span>.
                @unless ($this->isLokal())
                    Tombol Sinkronisasi &amp; Tarik Data HANYA bisa dijalankan dari Aplikasi Lokal - sisi Online ini tidak bisa memulai koneksi balik ke Lokal.
                @endunless
            </div>

            @if ($this->isLokal())
                {{-- ================= SISI LOKAL ================= --}}
                <div class="bg-white shadow sm:rounded-lg p-4 sm:p-8 space-y-4">
                    <h3 class="text-base font-bold text-slate-800">Pengaturan Koneksi ke Online</h3>
                    <p class="text-sm text-slate-600">
                        Isi alamat (URL) Aplikasi Online dan Token API yang dibuat lewat menu ini di sisi Online, lalu disalin ke sini.
                    </p>

                    <div>
                        <x-input-label for="urlOnline" value="URL Aplikasi Online" />
                        <x-text-input wire:model="urlOnline" id="urlOnline" class="block mt-1 w-full" type="url" placeholder="https://srcibadak.my.id" autocomplete="off" />
                        <x-input-error :messages="$errors->get('urlOnline')" class="mt-2" />
                    </div>
                    <x-secondary-button wire:click="simpanUrlOnline" wire:loading.attr="disabled">Simpan URL</x-secondary-button>

                    <div class="pt-4 border-t border-slate-200">
                        <x-input-label for="tokenOnlineBaru" value="Token API Online (ganti/isi baru)" />
                        <x-text-input wire:model="tokenOnlineBaru" id="tokenOnlineBaru" class="block mt-1 w-full" type="password" autocomplete="off" placeholder="Tempel Token API dari menu Sinkronisasi sisi Online" />
                        <x-input-error :messages="$errors->get('tokenOnlineBaru')" class="mt-2" />
                        <p class="mt-1 text-xs text-slate-500">
                            Token saat ini: <span class="font-mono">{{ $pengaturan->token_online_samar ?? '(belum diisi)' }}</span>
                        </p>
                    </div>
                    <x-secondary-button wire:click="simpanTokenOnline" wire:loading.attr="disabled">Simpan Token</x-secondary-button>

                    <div class="pt-4 border-t border-slate-200 flex items-center gap-4">
                        <x-secondary-button wire:click="tesKoneksi" wire:loading.attr="disabled" wire:target="tesKoneksi">
                            <span wire:loading.remove wire:target="tesKoneksi">Tes Koneksi</span>
                            <span wire:loading wire:target="tesKoneksi">Menguji koneksi...</span>
                        </x-secondary-button>
                        @if ($pengaturan->terakhir_tes_koneksi_pada)
                            <span class="text-xs text-slate-500">
                                Tes terakhir {{ $pengaturan->terakhir_tes_koneksi_pada->diffForHumans() }}:
                                <span class="font-semibold {{ $pengaturan->terakhir_tes_koneksi_berhasil ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $pengaturan->terakhir_tes_koneksi_berhasil ? 'Berhasil' : 'Gagal' }}
                                </span>
                            </span>
                        @endif
                    </div>
                </div>

                <div class="bg-white shadow sm:rounded-lg p-4 sm:p-8 space-y-4">
                    <h3 class="text-base font-bold text-slate-800">Jalankan Sinkronisasi / Tarik Data</h3>
                    <p class="text-sm text-slate-600">
                        <span class="font-semibold">Sinkronisasi</span> = kirim data dari Lokal ini ke Online.
                        <span class="font-semibold">Tarik Data</span> = ambil data dari Online ke Lokal ini.
                        Kedua proses membandingkan waktu update tiap baris data (data lebih baru menang) dan membuat 1 backup pengaman otomatis dulu di sisi penerima sebelum menerapkan apa pun.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <x-primary-button wire:click="konfirmasiSinkronisasi">Sinkronisasi (Kirim ke Online)</x-primary-button>
                        <x-primary-button wire:click="konfirmasiTarikData">Tarik Data (Ambil dari Online)</x-primary-button>
                    </div>
                </div>

                {{--
                    "Tarik Data Pengguna" (BARU 2026-10-16, permintaan user)
                    - TERPISAH dari "Tarik Data" di atas (dikonfirmasi
                    AskUserQuestion): akun Pengguna lebih sensitif (kata
                    sandi, level akses) daripada data sekolah, jadi
                    aksi/konfirmasinya sengaja dipisah supaya Superadmin
                    bisa menjalankan salah satunya saja.
                --}}
                <div class="bg-white shadow sm:rounded-lg p-4 sm:p-8 space-y-4">
                    <h3 class="text-base font-bold text-slate-800">Tarik Data Pengguna</h3>
                    <p class="text-sm text-slate-600">
                        Menarik SELURUH akun Pengguna (Superadmin, Admin OPS, Admin BOSP) dari Online ke Lokal ini - username, email, kata sandi, level akses, jabatan, dan sekolah yang terhubung (dicocokkan lewat NPSN). Data lebih baru menang, 1 backup pengaman dibuat otomatis dulu.
                        Google Authenticator (2FA) TIDAK ikut ditarik - tiap instalasi mengatur 2FA-nya sendiri, akun baru dari Online akan diminta aktivasi 2FA dari awal saat pertama login di Lokal.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <x-primary-button wire:click="konfirmasiTarikDataPengguna">Tarik Data Pengguna (Ambil dari Online)</x-primary-button>
                    </div>
                </div>
            @else
                {{-- ================= SISI ONLINE ================= --}}
                <div class="bg-white shadow sm:rounded-lg p-4 sm:p-8 space-y-4">
                    <h3 class="text-base font-bold text-slate-800">Token API untuk Sinkronisasi</h3>
                    <p class="text-sm text-slate-600">
                        Buat/ganti Token API di sini, lalu salin ke pengaturan menu Sinkronisasi di sisi Lokal.
                        Membuat token baru akan MENCABUT token lama (hanya 1 token yang aktif pada satu waktu).
                    </p>

                    @if ($tokenBaruDitampilkan)
                        <div class="p-4 bg-amber-50 border border-amber-300 rounded-lg">
                            <p class="text-sm font-bold text-amber-800">Catat token ini SEKARANG - tidak akan ditampilkan lagi setelah halaman ini dimuat ulang:</p>
                            <p class="mt-2 font-mono text-sm break-all bg-white border border-amber-200 rounded p-2">{{ $tokenBaruDitampilkan }}</p>
                        </div>
                    @endif

                    <p class="text-sm text-slate-500">
                        Status token saat ini:
                        <span class="font-semibold {{ $tokenAktif ? 'text-green-700' : 'text-red-700' }}">
                            {{ $tokenAktif ? 'Ada token aktif (dibuat '.$tokenAktif->created_at->diffForHumans().')' : 'Belum ada token aktif' }}
                        </span>
                    </p>

                    <x-secondary-button wire:click="buatTokenBaru" wire:loading.attr="disabled">
                        {{ $tokenAktif ? 'Ganti Token (cabut yang lama)' : 'Buat Token Baru' }}
                    </x-secondary-button>
                </div>

                <div class="bg-white shadow sm:rounded-lg p-4 sm:p-8 space-y-3 opacity-60">
                    <h3 class="text-base font-bold text-slate-800">Jalankan Sinkronisasi / Tarik Data</h3>
                    <div class="flex flex-wrap gap-3">
                        <x-primary-button disabled>Sinkronisasi (Kirim ke Online)</x-primary-button>
                        <x-primary-button disabled>Tarik Data (Ambil dari Online)</x-primary-button>
                        <x-primary-button disabled>Tarik Data Pengguna (Ambil dari Online)</x-primary-button>
                    </div>
                    <p class="text-xs text-slate-500">Nonaktif di sisi Online - jalankan dari Aplikasi Lokal.</p>
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 sm:px-8 py-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <h3 class="text-base font-bold text-slate-800">Riwayat Sinkronisasi Terakhir</h3>

                    {{--
                        Filter tanggal, tombol Hapus & zoom (permintaan user
                        2026-10-14) - pola SAMA dengan tab Percobaan Login
                        Gagal di App\Livewire\CekDatabaseAplikasi\Index.
                    --}}
                    <div class="flex flex-wrap items-center gap-2">
                        <x-input-label for="filterTanggalRiwayat" value="Tanggal" class="text-xs text-slate-500 !mb-0" />
                        <input type="date" wire:model.live="filterTanggalRiwayat" id="filterTanggalRiwayat" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                        @if ($filterTanggalRiwayat !== '')
                            <button type="button" wire:click="$set('filterTanggalRiwayat', '')" class="text-slate-400 hover:text-red-600" title="Hapus filter tanggal">&times;</button>
                        @endif

                        <x-danger-button type="button" wire:click="konfirmasiHapusTerpilihRiwayat" wire:loading.attr="disabled" :disabled="count($dipilihRiwayat) === 0" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                            <x-icon name="trash" class="w-3.5 h-3.5 mr-1" />
                            Hapus Terpilih ({{ count($dipilihRiwayat) }})
                        </x-danger-button>
                        <x-secondary-button type="button" wire:click="konfirmasiHapusSemuaRiwayat" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                            <x-icon name="trash" class="w-3.5 h-3.5 mr-1" />
                            Hapus Semua
                        </x-secondary-button>
                        <x-zoom-controls :zoom="$zoomPercent" />
                    </div>
                </div>
                <div class="overflow-x-auto" style="zoom: {{ $zoomPercent }}%;">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2">
                                    <input type="checkbox" wire:click="toggleSemuaRiwayat" @checked($semuaTerpilihRiwayat) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" title="Pilih/batal pilih semua baris yang tampil">
                                </th>
                                <th class="px-4 py-2 text-left font-semibold text-slate-600">Waktu</th>
                                <th class="px-4 py-2 text-left font-semibold text-slate-600">Arah</th>
                                <th class="px-4 py-2 text-left font-semibold text-slate-600">Status</th>
                                <th class="px-4 py-2 text-left font-semibold text-slate-600">Dilakukan Oleh</th>
                                <th class="px-4 py-2 text-left font-semibold text-slate-600">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($riwayat as $log)
                                <tr>
                                    <td class="px-4 py-2">
                                        <input type="checkbox" wire:model="dipilihRiwayat" value="{{ $log->id }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    </td>
                                    {{-- 2026-10-15: ->keWaktuLokal() ditambahkan (permintaan user "jam nya
                                         masih belum sesuai dengan jam yang ada di laptop") - menu ini
                                         terlewat saat macro keWaktuLokal() (konversi UTC tersimpan ->
                                         Asia/Jakarta/WIB untuk TAMPILAN saja, lihat
                                         AppServiceProvider::boot()) diterapkan ke seluruh aplikasi pada
                                         round 2026-09-27. --}}
                                    <td class="px-4 py-2 whitespace-nowrap text-slate-600">{{ $log->created_at->keWaktuLokal()->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-slate-800">{{ \App\Models\SyncLog::labelArah($log->arah) }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <span class="font-semibold {{ $log->status === 'berhasil' ? 'text-green-700' : 'text-red-700' }}">
                                            {{ ucfirst($log->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-slate-600">{{ $log->dilakukanOleh?->display_name ?? '-' }}</td>
                                    <td class="px-4 py-2 text-slate-500 text-xs">
                                        @if ($log->status === 'gagal')
                                            {{ $log->pesan_error }}
                                        @elseif ($log->ringkasan)
                                            {{ collect($log->ringkasan)->map(fn ($v, $k) => is_array($v) ? $k.': +'.($v['ditambah'] ?? 0).' ~'.($v['diupdate'] ?? 0) : null)->filter()->implode(', ') }}
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-slate-400">Belum ada riwayat sinkronisasi.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Modal konfirmasi Hapus Terpilih & Hapus Semua (permintaan user 2026-10-14) --}}
            <x-modal name="sinkronisasi-riwayat-hapus-terpilih" :show="$confirmingHapusTerpilihRiwayat" maxWidth="md">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-slate-900">Hapus {{ count($dipilihRiwayat) }} baris riwayat terpilih?</h2>
                    <p class="mt-1 text-sm text-slate-600">Semua baris riwayat sinkronisasi yang dicentang akan dihapus sekaligus. Tindakan ini tidak dapat dibatalkan.</p>
                    <div class="mt-6 flex justify-end gap-3">
                        <x-secondary-button wire:click="batalHapusTerpilihRiwayat" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                        <x-danger-button wire:click="hapusTerpilihRiwayat" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Hapus</x-danger-button>
                    </div>
                </div>
            </x-modal>

            <x-modal name="sinkronisasi-riwayat-hapus-semua" :show="$confirmingHapusSemuaRiwayat" maxWidth="md">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-slate-900">Hapus SEMUA riwayat sinkronisasi?</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        @if ($filterTanggalRiwayat !== '')
                            Ini akan menghapus SEMUA baris pada tanggal {{ \Illuminate\Support\Carbon::parse($filterTanggalRiwayat)->translatedFormat('d F Y') }} (bukan hanya yang tampil di atas). Tindakan ini tidak dapat dibatalkan.
                        @else
                            Tidak ada filter tanggal aktif - ini akan menghapus SEMUA riwayat sinkronisasi di database. Tindakan ini tidak dapat dibatalkan.
                        @endif
                    </p>
                    <div class="mt-6 flex justify-end gap-3">
                        <x-secondary-button wire:click="batalHapusSemuaRiwayat" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                        <x-danger-button wire:click="hapusSemuaRiwayat" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Ya, Hapus Semua</x-danger-button>
                    </div>
                </div>
            </x-modal>

        </div>
    </div>

    <x-modal name="sinkronisasi-konfirmasi-kirim" :show="$confirmingSinkronisasi" maxWidth="md">
        <div
            class="p-6"
            x-data="{ sedangBerjalan: false, detik: 0, _interval: null }"
            x-on:sinkronisasi-proses-selesai.window="sedangBerjalan = false; clearInterval(_interval)"
        >
            <h2 class="text-lg font-bold text-red-700">Yakin ingin menjalankan Sinkronisasi?</h2>
            <p class="mt-2 text-sm text-slate-600">
                Seluruh data Pendataan OPS/BOSP, Data PTK, dan Profil Sekolah dari Lokal ini akan dikirim ke Online dan DIGABUNGKAN (data yang lebih baru menang). Online akan membuat 1 backup pengaman otomatis dulu sebelum menerapkannya.
            </p>

            <div x-show="sedangBerjalan" x-cloak class="mt-4">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                    <span>Sedang mengirim data ke Online, mohon jangan tutup halaman ini...</span>
                    <span x-text="detik + ' detik'"></span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden relative">
                    <div class="absolute inset-y-0 w-1/3 bg-indigo-600 rounded-full animate-sinkron-progress"></div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalSinkronisasi" wire:loading.attr="disabled" wire:target="jalankanSinkronisasi">Batal</x-secondary-button>
                <x-primary-button
                    wire:click="jalankanSinkronisasi"
                    wire:loading.attr="disabled"
                    wire:target="jalankanSinkronisasi"
                    x-on:click="sedangBerjalan = true; detik = 0; clearInterval(_interval); _interval = setInterval(() => detik++, 1000)"
                    class="!bg-red-600 hover:!bg-red-700"
                >
                    <span wire:loading.remove wire:target="jalankanSinkronisasi">Ya, Sinkronisasi Sekarang</span>
                    <span wire:loading wire:target="jalankanSinkronisasi">Sedang mengirim...</span>
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="sinkronisasi-konfirmasi-tarik" :show="$confirmingTarikData" maxWidth="md">
        <div
            class="p-6"
            x-data="{ sedangBerjalan: false, detik: 0, _interval: null }"
            x-on:tarik-data-proses-selesai.window="sedangBerjalan = false; clearInterval(_interval)"
        >
            <h2 class="text-lg font-bold text-red-700">Yakin ingin menjalankan Tarik Data?</h2>
            <p class="mt-2 text-sm text-slate-600">
                Seluruh data dari Online akan diambil dan DIGABUNGKAN ke database Lokal ini (data yang lebih baru menang). Lokal akan membuat 1 backup pengaman otomatis dulu sebelum menerapkannya.
            </p>

            <div x-show="sedangBerjalan" x-cloak class="mt-4">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                    <span>Sedang menarik data dari Online, mohon jangan tutup halaman ini...</span>
                    <span x-text="detik + ' detik'"></span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden relative">
                    <div class="absolute inset-y-0 w-1/3 bg-indigo-600 rounded-full animate-sinkron-progress"></div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalTarikData" wire:loading.attr="disabled" wire:target="jalankanTarikData">Batal</x-secondary-button>
                <x-primary-button
                    wire:click="jalankanTarikData"
                    wire:loading.attr="disabled"
                    wire:target="jalankanTarikData"
                    x-on:click="sedangBerjalan = true; detik = 0; clearInterval(_interval); _interval = setInterval(() => detik++, 1000)"
                    class="!bg-red-600 hover:!bg-red-700"
                >
                    <span wire:loading.remove wire:target="jalankanTarikData">Ya, Tarik Data Sekarang</span>
                    <span wire:loading wire:target="jalankanTarikData">Sedang menarik...</span>
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- "Tarik Data Pengguna" (BARU 2026-10-16) - pola modal SAMA PERSIS dgn "sinkronisasi-konfirmasi-tarik" di atas. --}}
    <x-modal name="sinkronisasi-konfirmasi-tarik-pengguna" :show="$confirmingTarikDataPengguna" maxWidth="md">
        <div
            class="p-6"
            x-data="{ sedangBerjalan: false, detik: 0, _interval: null }"
            x-on:tarik-data-pengguna-proses-selesai.window="sedangBerjalan = false; clearInterval(_interval)"
        >
            <h2 class="text-lg font-bold text-red-700">Yakin ingin menjalankan Tarik Data Pengguna?</h2>
            <p class="mt-2 text-sm text-slate-600">
                Seluruh akun Pengguna (Superadmin, Admin OPS, Admin BOSP) dari Online akan diambil dan DIGABUNGKAN ke akun Lokal ini (data yang lebih baru menang, termasuk kata sandi). Lokal akan membuat 1 backup pengaman otomatis dulu sebelum menerapkannya. Google Authenticator (2FA) TIDAK ikut ditarik.
            </p>

            <div x-show="sedangBerjalan" x-cloak class="mt-4">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                    <span>Sedang menarik data Pengguna dari Online, mohon jangan tutup halaman ini...</span>
                    <span x-text="detik + ' detik'"></span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden relative">
                    <div class="absolute inset-y-0 w-1/3 bg-indigo-600 rounded-full animate-sinkron-progress"></div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalTarikDataPengguna" wire:loading.attr="disabled" wire:target="jalankanTarikDataPengguna">Batal</x-secondary-button>
                <x-primary-button
                    wire:click="jalankanTarikDataPengguna"
                    wire:loading.attr="disabled"
                    wire:target="jalankanTarikDataPengguna"
                    x-on:click="sedangBerjalan = true; detik = 0; clearInterval(_interval); _interval = setInterval(() => detik++, 1000)"
                    class="!bg-red-600 hover:!bg-red-700"
                >
                    <span wire:loading.remove wire:target="jalankanTarikDataPengguna">Ya, Tarik Data Pengguna Sekarang</span>
                    <span wire:loading wire:target="jalankanTarikDataPengguna">Sedang menarik...</span>
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    <style>
        @keyframes sinkron-progress-slide {
            0%   { left: -33%; }
            100% { left: 100%; }
        }
        .animate-sinkron-progress {
            animation: sinkron-progress-slide 1.1s ease-in-out infinite;
        }
    </style>
</div>
