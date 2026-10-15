<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Pengguna') }}
    </h2>
</x-slot>

<div>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                {{-- Tab --}}
                <div class="border-b border-slate-200 px-4 sm:px-8 pt-4">
                    <nav class="flex gap-6 -mb-px">
                        @foreach ($levelOptions as $value => $label)
                            <button
                                wire:click="pindahTab('{{ $value }}')"
                                class="pb-3 text-sm font-medium border-b-2 transition
                                    @if ($tab === $value) border-blue-600 text-blue-600 @else border-transparent text-slate-500 hover:text-slate-700 @endif"
                            >
                                {{ $label }}
                                @if (($jumlahMenunggu[$value] ?? 0) > 0)
                                    <span class="ml-1 inline-flex px-1.5 py-0.5 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">
                                        {{ $jumlahMenunggu[$value] }} menunggu
                                    </span>
                                @endif
                            </button>
                        @endforeach

                        {{--
                            Tab "Riwayat Login" (permintaan user 2026-09-26) -
                            TIDAK ikut $levelOptions di atas (itu khusus 3 tab
                            kelola-akun) supaya badge "menunggu" milik tab
                            kelola-akun tidak ikut tertempel di tab ini.
                        --}}
                        <button
                            wire:click="pindahTab('riwayat_login')"
                            class="pb-3 text-sm font-medium border-b-2 transition
                                @if ($tab === 'riwayat_login') border-blue-600 text-blue-600 @else border-transparent text-slate-500 hover:text-slate-700 @endif"
                        >
                            Riwayat Login
                        </button>

                        {{-- Tab "Authenticator" (permintaan user 2026-09-27) - sama seperti
                            "Riwayat Login", TIDAK ikut $levelOptions karena menampilkan
                            SEMUA level sekaligus dalam satu tabel. --}}
                        <button
                            wire:click="pindahTab('authenticator')"
                            class="pb-3 text-sm font-medium border-b-2 transition
                                @if ($tab === 'authenticator') border-blue-600 text-blue-600 @else border-transparent text-slate-500 hover:text-slate-700 @endif"
                        >
                            Authenticator
                        </button>
                    </nav>
                </div>

                <div class="p-4 sm:p-8">
                    @if ($tab === 'riwayat_login')
                        {{-- Sub-tab Riwayat Login: Superadmin / Admin OPS / Admin BOSP --}}
                        <div class="flex flex-wrap gap-2 mb-4">
                            @foreach ($levelOptions as $value => $label)
                                <button
                                    wire:click="pindahTabRiwayat('{{ $value }}')"
                                    type="button"
                                    class="px-3 py-1.5 text-xs font-medium rounded-full border transition
                                        @if ($subTabRiwayat === $value) bg-blue-600 border-blue-600 text-white @else bg-white border-slate-300 text-slate-600 hover:bg-slate-50 @endif"
                                >
                                    Riwayat Login {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                            <input wire:model.live.debounce.300ms="searchRiwayat" type="text" placeholder="Cari nama sekolah / alamat email..." class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm sm:w-80">

                            <x-zoom-controls :zoom="$zoomPercent" />
                        </div>

                        <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg" style="max-height: 28rem; zoom: {{ $zoomPercent }}%;">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="sticky top-0 bg-slate-50">
                                    <tr class="text-left text-slate-500">
                                        <th class="px-3 py-2">Nama Sekolah</th>
                                        <th class="px-3 py-2">Alamat Email</th>
                                        <th class="px-3 py-2">Hari & Tanggal</th>
                                        <th class="px-3 py-2">Waktu Mulai</th>
                                        <th class="px-3 py-2">Waktu Akhir</th>
                                        <th class="px-3 py-2">Lama Login</th>
                                        <th class="px-3 py-2">IP Address</th>
                                        <th class="px-3 py-2">Titik Koordinat</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($riwayatLogin as $item)
                                        <tr>
                                            <td class="px-3 py-2 text-slate-700 whitespace-nowrap">{{ $item->nama_sekolah ?: '-' }}</td>
                                            <td class="px-3 py-2 text-slate-700 whitespace-nowrap">{{ $item->email ?: '-' }}</td>
                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->login_at?->keWaktuLokal()->translatedFormat('l, d F Y') }}</td>
                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->login_at?->keWaktuLokal()->format('H:i:s') }}</td>
                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">
                                                @if ($item->logout_at)
                                                    {{ $item->logout_at->keWaktuLokal()->format('H:i:s') }}
                                                @else
                                                    <span class="text-emerald-600 font-medium">Masih berlangsung</span>
                                                    {{--
                                                        Permintaan user 2026-10-03 (jaring pengaman fitur kunci 1
                                                        perangkat per akun): Superadmin bisa mengakhiri paksa sesi
                                                        yang "macet" (mis. admin lupa logout di perangkat lama yang
                                                        sudah rusak/hilang, sehingga tidak bisa klik "Paksa Logout
                                                        Perangkat Lain" sendiri dari halaman login).
                                                    --}}
                                                    <button type="button" wire:click="akhiriSesi({{ $item->id }})" wire:confirm="Akhiri sesi login ini sekarang? Akun ini akan bisa login lagi di perangkat lain setelah ini." class="ml-2 text-[11px] font-medium text-rose-600 hover:text-rose-800 hover:underline">
                                                        Akhiri Sesi
                                                    </button>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->durasi ?? '-' }}</td>
                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->ip_address ?: '-' }}</td>
                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">
                                                @if ($item->latitude !== null && $item->longitude !== null)
                                                    <a href="https://www.google.com/maps?q={{ $item->latitude }},{{ $item->longitude }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">
                                                        {{ number_format((float) $item->latitude, 5) }}, {{ number_format((float) $item->longitude, 5) }}
                                                    </a>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-3 py-6 text-center text-slate-400">Belum ada riwayat login untuk tab ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex-1">
                                {{ $riwayatLogin->links() }}
                            </div>
                            <x-pagination-per-page wire:model.live="perPageRiwayat" />
                        </div>
                    @elseif ($tab === 'authenticator')
                        {{-- Tab "Authenticator" (permintaan user 2026-09-27) --}}
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                            <input wire:model.live.debounce.300ms="searchAuthenticator" type="text" placeholder="Cari username / nama sekolah..." class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm sm:w-80">

                            <x-zoom-controls :zoom="$zoomPercent" />
                        </div>

                        @if (count($terpilihUntukSamakan))
                            <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-blue-50 border border-blue-200 rounded-lg px-3 py-2">
                                <p class="text-sm text-blue-800">{{ count($terpilihUntukSamakan) }} akun dipilih untuk disamakan kuncinya (boleh lintas tab).</p>
                                <div class="flex items-center gap-3">
                                    <button wire:click="bersihkanPilihanSamakan" class="text-sm text-slate-500 hover:underline">Batal pilih</button>
                                    <x-primary-button wire:click="bukaSamakanMassal">Samakan Kunci Terpilih</x-primary-button>
                                </div>
                            </div>
                        @endif

                        <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg" style="max-height: 28rem; zoom: {{ $zoomPercent }}%;">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="sticky top-0 bg-slate-50">
                                    <tr class="text-left text-slate-500">
                                        <th class="px-3 py-2 w-8">
                                            <input type="checkbox"
                                                class="rounded border-slate-300"
                                                @checked($penggunaAuthenticator->count() > 0 && count(array_intersect($penggunaAuthenticator->pluck('id')->toArray(), $terpilihUntukSamakan)) === $penggunaAuthenticator->count())
                                                @change="$wire.pilihSemuaDiHalamanIni($event.target.checked, @js($penggunaAuthenticator->pluck('id')))">
                                        </th>
                                        <th class="px-3 py-2">Username</th>
                                        <th class="px-3 py-2">Nama Sekolah</th>
                                        <th class="px-3 py-2">Level Akses</th>
                                        <th class="px-3 py-2">Status Authenticator</th>
                                        <th class="px-3 py-2 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($penggunaAuthenticator as $item)
                                        <tr class="@if ($item->google2fa_reset_diminta_at) bg-amber-50 @endif">
                                            <td class="px-3 py-2">
                                                <input type="checkbox" wire:model.live="terpilihUntukSamakan" value="{{ $item->id }}" class="rounded border-slate-300">
                                            </td>
                                            <td class="px-3 py-2 font-medium text-slate-800 whitespace-nowrap">{{ $item->username }}</td>
                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->nama_sekolah ?: '-' }}</td>
                                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->level_akses_label }}</td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                @if ($item->google2fa_reset_diminta_at)
                                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-700">Menunggu Reset</span>
                                                @elseif ($item->authenticatorAktif())
                                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">Aktif sejak {{ $item->google2fa_aktif_at->keWaktuLokal()->translatedFormat('d M Y') }}</span>
                                                @else
                                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-slate-100 text-slate-600">Belum Aktif</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                                <button wire:click="konfirmasiSamakanAuthenticator({{ $item->id }})" class="text-blue-600 hover:underline">Samakan Kunci</button>
                                                <span class="text-slate-300">|</span>
                                                <button wire:click="konfirmasiResetAuthenticator({{ $item->id }})" class="text-amber-600 hover:underline">Reset & Kirim Barcode Baru</button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-3 py-6 text-center text-slate-400">Belum ada data pengguna.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex-1">
                                {{ $penggunaAuthenticator->links() }}
                            </div>
                            <x-pagination-per-page wire:model.live="perPageAuthenticator" />
                        </div>
                    @else
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari username / nama sekolah..." class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">

                            @if ($tab !== 'superadmin')
                                <select wire:model.live="filterSekolahId" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                    <option value="">Semua Sekolah</option>
                                    @foreach ($sekolahOptions as $sekolah)
                                        <option value="{{ $sekolah->id }}">{{ $sekolah->nama_sekolah }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <x-primary-button wire:click="tambah">+ Tambah {{ $levelOptions[$tab] }}</x-primary-button>
                    </div>

                    @if (count($terpilihUntukSamakan))
                        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-blue-50 border border-blue-200 rounded-lg px-3 py-2">
                            <p class="text-sm text-blue-800">{{ count($terpilihUntukSamakan) }} akun dipilih untuk disamakan kuncinya (boleh lintas tab).</p>
                            <div class="flex items-center gap-3">
                                <button wire:click="bersihkanPilihanSamakan" class="text-sm text-slate-500 hover:underline">Batal pilih</button>
                                <x-primary-button wire:click="bukaSamakanMassal">Samakan Kunci Terpilih</x-primary-button>
                            </div>
                        </div>
                    @endif

                    {{-- 2026-10-15: ceklist "pilih semua" untuk tombol Status Approve
                         registrasi (permintaan user) - pola SAMA PERSIS dengan bar
                         "Samakan Kunci" di atas, HANYA muncul di tab Admin OPS/Admin
                         BOSP (TIDAK di tab Superadmin, krn akun Superadmin yang dibuat
                         lewat menu ini otomatis sudah disetujui). --}}
                    @if ($tab !== 'superadmin' && count($terpilihUntukApprove))
                        <div class="mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-emerald-50 border border-emerald-200 rounded-lg px-3 py-2">
                            <p class="text-sm text-emerald-800">{{ count($terpilihUntukApprove) }} akun dipilih untuk disetujui (boleh lintas tab Admin OPS/Admin BOSP).</p>
                            <div class="flex items-center gap-3">
                                <button wire:click="bersihkanPilihanApprove" class="text-sm text-slate-500 hover:underline">Batal pilih</button>
                                <x-primary-button wire:click="setujuiTerpilih" wire:confirm="Setujui {{ count($terpilihUntukApprove) }} akun terpilih sekaligus?">Setujui Terpilih</x-primary-button>
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end mb-3">
                        <x-zoom-controls :zoom="$zoomPercent" />
                    </div>

                    <div class="overflow-x-auto" style="zoom: {{ $zoomPercent }}%;">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead>
                                <tr class="text-left text-slate-500">
                                    <th class="px-3 py-2 w-8">
                                        <input type="checkbox"
                                            class="rounded border-slate-300"
                                            @checked($pengguna->count() > 0 && count(array_intersect($pengguna->pluck('id')->toArray(), $terpilihUntukSamakan)) === $pengguna->count())
                                            @change="$wire.pilihSemuaDiHalamanIni($event.target.checked, @js($pengguna->pluck('id')))">
                                    </th>
                                    @if ($tab !== 'superadmin')
                                        {{-- 2026-10-15: kolom ceklist "pilih semua" KHUSUS untuk Setujui
                                             Terpilih (permintaan user) - TERPISAH dari kolom checkbox
                                             "Samakan Kunci" di atas. "Pilih semua" di sini HANYA
                                             mencentang baris yang BELUM disetujui pada halaman ini
                                             ($pengguna->where('is_approved', false)), krn akun yang
                                             sudah disetujui tidak relevan untuk aksi ini. --}}
                                        @php($idBelumDisetujuiDiHalamanIni = $pengguna->where('is_approved', false)->pluck('id')->values())
                                        <th class="px-3 py-2 w-8">
                                            <input type="checkbox"
                                                class="rounded border-slate-300"
                                                @checked($idBelumDisetujuiDiHalamanIni->count() > 0 && count(array_intersect($idBelumDisetujuiDiHalamanIni->toArray(), $terpilihUntukApprove)) === $idBelumDisetujuiDiHalamanIni->count())
                                                @change="$wire.pilihSemuaApproveDiHalamanIni($event.target.checked, @js($idBelumDisetujuiDiHalamanIni))">
                                        </th>
                                    @endif
                                    <th class="px-3 py-2">Username</th>
                                    @if ($tab !== 'superadmin')
                                        <th class="px-3 py-2">Nama Sekolah</th>
                                        <th class="px-3 py-2">Jabatan</th>
                                    @endif
                                    <th class="px-3 py-2">Status</th>
                                    <th class="px-3 py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($pengguna as $item)
                                    <tr>
                                        <td class="px-3 py-2">
                                            <input type="checkbox" wire:model.live="terpilihUntukSamakan" value="{{ $item->id }}" class="rounded border-slate-300">
                                        </td>
                                        @if ($tab !== 'superadmin')
                                            <td class="px-3 py-2">
                                                @unless ($item->is_approved)
                                                    <input type="checkbox" wire:model.live="terpilihUntukApprove" value="{{ $item->id }}" class="rounded border-slate-300">
                                                @endunless
                                            </td>
                                        @endif
                                        <td class="px-3 py-2 font-medium text-slate-800">{{ $item->username }}</td>
                                        @if ($tab !== 'superadmin')
                                            <td class="px-3 py-2 text-slate-600">{{ $item->nama_sekolah ?: '-' }}</td>
                                            <td class="px-3 py-2 text-slate-600">{{ $item->jabatan ?: '-' }}</td>
                                        @endif
                                        <td class="px-3 py-2">
                                            @if ($item->is_approved)
                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">Aktif</span>
                                            @else
                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-700">Menunggu Persetujuan</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right space-x-2 whitespace-nowrap">
                                            @unless ($item->is_approved)
                                                <button wire:click="setujui({{ $item->id }})" class="text-emerald-600 hover:underline">Setujui</button>
                                            @endunless
                                            <button wire:click="edit({{ $item->id }})" class="text-blue-600 hover:underline">Edit</button>
                                            @if ($item->level_akses !== \App\Models\User::LEVEL_SUPERADMIN)
                                                <button wire:click="konfirmasiReset({{ $item->id }})" class="text-amber-600 hover:underline">Reset Password</button>
                                            @endif
                                            <button wire:click="konfirmasiSamakanAuthenticator({{ $item->id }})" class="text-blue-600 hover:underline">Samakan Kunci</button>
                                            <button wire:click="konfirmasiResetAuthenticator({{ $item->id }})" class="text-amber-600 hover:underline">Reset Authenticator</button>
                                            @if ($item->level_akses === \App\Models\User::LEVEL_SUPERADMIN)
                                                <button wire:click="konfirmasiBuatUlangKataSandiPemulihan({{ $item->id }})" class="text-amber-600 hover:underline">Buat Ulang Kata Sandi Pemulihan</button>
                                            @endif
                                            @if ($item->id !== auth()->id())
                                                <button wire:click="konfirmasiHapus({{ $item->id }})" class="text-red-600 hover:underline">Hapus</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        {{-- 2026-10-15: colspan disesuaikan supaya akurat dgn jumlah
                                             kolom yang sebenarnya tampil (4 kolom dasar + 3 kolom
                                             tambahan khusus tab Admin OPS/Admin BOSP: Nama Sekolah,
                                             Jabatan, & kolom ceklist Setujui Terpilih yang baru). --}}
                                        <td colspan="{{ $tab !== 'superadmin' ? 7 : 4 }}" class="px-3 py-6 text-center text-slate-400">Belum ada data pengguna di tab ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex-1">
                            {{ $pengguna->links() }}
                        </div>
                        <x-pagination-per-page wire:model.live="perPage" />
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Form Tambah/Edit --}}
    <x-modal name="pengguna-form" :show="$showForm" maxWidth="lg">
        <form wire:submit="simpan" class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-4">
                {{ $editingId ? 'Edit Pengguna' : 'Tambah Pengguna' }}
            </h2>

            <div class="space-y-4">
                <div>
                    <x-input-label for="level_akses" value="Level Akses" />
                    <select wire:model.live="level_akses" id="level_akses" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                        <option value="">-- Pilih Level Akses --</option>
                        @foreach ($levelOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('level_akses')" class="mt-2" />
                </div>

                @if (in_array($level_akses, ['admin_ops', 'admin_bosp']))
                    <div>
                        <x-input-label for="profil_sekolah_id" value="Sekolah" />
                        <select wire:model="profil_sekolah_id" id="profil_sekolah_id" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                            <option value="">-- Pilih Sekolah --</option>
                            @foreach ($sekolahOptions as $sekolah)
                                <option value="{{ $sekolah->id }}">{{ $sekolah->nama_sekolah }}{{ $sekolah->npsn ? ' ('.$sekolah->npsn.')' : '' }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Belum ada sekolahnya? Tambahkan dulu di menu Profil Sekolah.</p>
                        <x-input-error :messages="$errors->get('profil_sekolah_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="jabatan" value="Jabatan" />
                        <x-text-input wire:model="jabatan" id="jabatan" class="block mt-1 w-full bg-slate-100" type="text" readonly />
                    </div>
                @endif

                <div>
                    <x-input-label for="username" :value="in_array($level_akses, ['admin_ops','admin_bosp']) ? 'Email (Username)' : 'Username'" />
                    <x-text-input wire:model="username" id="username" class="block mt-1 w-full" :type="in_array($level_akses, ['admin_ops','admin_bosp']) ? 'email' : 'text'" />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>

                @if ($level_akses === 'superadmin')
                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" />
                        <p class="text-xs text-slate-400 mt-1">Dipakai untuk gerbang Verifikasi Akses sebelum halaman login (bukan untuk login/username).</p>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                @endif

                <div>
                    <x-input-label for="password" :value="$editingId ? 'Password (kosongkan jika tidak diubah)' : 'Password'" />
                    <x-password-input wire:model="password" id="password" class="block mt-1" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="batal">Batal</x-secondary-button>
                <x-primary-button>Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Konfirmasi Hapus --}}
    <x-modal name="pengguna-hapus" :show="$confirmingDeleteId !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Hapus pengguna ini?</h2>
            <p class="mt-1 text-sm text-slate-600">Tindakan ini tidak dapat dibatalkan.</p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalHapus">Batal</x-secondary-button>
                <x-danger-button wire:click="hapus">Hapus</x-danger-button>
            </div>
        </div>
    </x-modal>

    {{-- Modal Konfirmasi Reset Password (khusus Admin OPS/Admin BOSP) --}}
    <x-modal name="pengguna-reset" :show="$confirmingResetId !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Reset password akun ini?</h2>
            <p class="mt-1 text-sm text-slate-600">
                Sistem akan membuat password baru secara acak untuk
                <span class="font-medium text-slate-800">{{ $penggunaDireset?->username }}</span>
                dan mengirimkannya ke email tersebut. Akun ini akan diminta ganti password sendiri begitu berhasil login memakai password baru itu.
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalReset">Batal</x-secondary-button>
                <x-primary-button wire:click="resetPassword" class="!bg-amber-600 hover:!bg-amber-700">Reset Password</x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- Modal Konfirmasi Reset Authenticator (permintaan user 2026-09-27) --}}
    <x-modal name="pengguna-reset-authenticator" :show="$confirmingResetAuthenticatorId !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Reset Authenticator akun ini?</h2>
            <p class="mt-1 text-sm text-slate-600">
                Barcode & kunci lama akun
                <span class="font-medium text-slate-800">{{ $penggunaDiresetAuthenticator?->username }}</span>
                akan dibuang dan diganti dengan yang baru, lalu dikirim ke email terdaftar akun tersebut. Akun ini wajib scan barcode baru & aktivasi ulang sebelum bisa login kembali.
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalResetAuthenticator">Batal</x-secondary-button>
                <x-primary-button wire:click="resetAuthenticator" class="!bg-amber-600 hover:!bg-amber-700">Reset & Kirim Barcode Baru</x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- Audit keamanan 2026-10-14: Modal Konfirmasi "Buat Ulang Kata Sandi
    Pemulihan" (akun Superadmin lain) --}}
    <x-modal name="pengguna-buat-ulang-kata-sandi-pemulihan" :show="$confirmingBuatUlangKataSandiPemulihanId !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Buat ulang kata sandi pemulihan akun ini?</h2>
            <p class="mt-1 text-sm text-slate-600">
                Kata sandi pemulihan LAMA milik
                <span class="font-medium text-slate-800">{{ $penggunaBuatUlangKataSandiPemulihan?->username }}</span>
                (kalau ada) akan langsung tidak berlaku lagi, diganti nilai acak baru. Nilai baru akan ditampilkan SEKALI ke Anda - sampaikan ke pemilik akun lewat jalur yang aman (JANGAN lewat email akun itu sendiri).
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalBuatUlangKataSandiPemulihan">Batal</x-secondary-button>
                <x-primary-button wire:click="buatUlangKataSandiPemulihan" class="!bg-amber-600 hover:!bg-amber-700">Buat Ulang</x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- Audit keamanan 2026-10-14: Modal tampil SEKALI nilai kata sandi
    pemulihan yang baru dibuat --}}
    <x-modal name="pengguna-kata-sandi-pemulihan-baru" :show="$kataSandiPemulihanBaru !== null" maxWidth="md">
        <div class="p-6" x-data="{ disalin: false }">
            <h2 class="text-lg font-medium text-slate-900">Kata Sandi Pemulihan Baru</h2>
            <p class="mt-1 text-sm text-slate-600">
                Nilai ini hanya ditampilkan SEKALI ini saja. Catat/salin sekarang, lalu sampaikan ke pemilik akun lewat jalur yang aman:
            </p>

            <div class="mt-3 flex items-center gap-2">
                <code class="flex-1 select-all break-all rounded bg-slate-50 px-3 py-2 text-sm font-mono text-slate-800 ring-1 ring-slate-200">{{ $kataSandiPemulihanBaru }}</code>
                <button type="button"
                    @click="navigator.clipboard.writeText('{{ $kataSandiPemulihanBaru }}'); disalin = true; setTimeout(() => disalin = false, 2000)"
                    class="rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700 transition">
                    <span x-show="!disalin">Salin</span>
                    <span x-show="disalin" x-cloak>Tersalin!</span>
                </button>
            </div>

            <div class="mt-6 flex justify-end">
                <x-primary-button wire:click="tutupKataSandiPemulihanBaru">Tutup (saya sudah menyimpannya)</x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- Modal Samakan Kunci Authenticator (permintaan user 2026-10-13) -
    supaya 1 kunci yang sama berlaku di aplikasi lokal maupun online,
    Superadmin tempelkan di sini kunci manual yang SUDAH dipakai di aplikasi
    satunya, lalu buktikan dgn kode 6 digit TERBARU dari aplikasi
    Authenticator. --}}
    <x-modal name="pengguna-samakan-authenticator" :show="$confirmingSamakanAuthenticatorId !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Samakan Kunci Authenticator akun ini?</h2>
            <p class="mt-1 text-sm text-slate-600">
                Untuk akun
                <span class="font-medium text-slate-800">{{ $penggunaDisamakanAuthenticator?->username }}</span>
                supaya bisa login memakai kode yang SAMA seperti di aplikasi (lokal/online) satunya - tanpa scan barcode baru lagi.
            </p>
            <p class="mt-2 text-xs text-slate-500">
                Dapatkan kunci manual ini dari aplikasi satunya: kalau akun itu BELUM aktif, kunci manual muncul di halaman aktivasinya ("Tidak bisa scan? Masukkan kunci secara manual"); kalau SUDAH aktif, pakai tombol "Reset & Kirim Barcode Baru" di sana dulu untuk dapat kunci baru (scan juga ke HP dari sana), baru tempelkan kunci yang sama persis di sini.
            </p>

            <form wire:submit="samakanAuthenticator" class="mt-4 space-y-4">
                <div>
                    <x-input-label for="kunciManualSama" value="Kunci Manual (dari aplikasi satunya)" />
                    <x-text-input wire:model="kunciManualSama" id="kunciManualSama" class="block mt-1 w-full font-mono tracking-wider" type="text" autocomplete="off" />
                    <x-input-error :messages="$errors->get('kunciManualSama')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="kodeSamaValid" value="Kode 6 Digit Terbaru dari Aplikasi Authenticator" />
                    <x-text-input wire:model="kodeSamaValid" id="kodeSamaValid" class="block mt-1 w-full text-center tracking-[0.5em] text-lg" type="text" inputmode="numeric" maxlength="6" autocomplete="off" />
                    <x-input-error :messages="$errors->get('kodeSamaValid')" class="mt-2" />
                </div>

                <div class="flex justify-end gap-3">
                    <x-secondary-button type="button" wire:click="batalSamakanAuthenticator">Batal</x-secondary-button>
                    <x-primary-button type="submit">Samakan Kunci</x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>

    {{-- Modal Samakan Kunci Massal (permintaan user 2026-10-13 lanjutan) -
    isi kunci & kode MASING-MASING akun terpilih dalam 1 layar, supaya tidak
    perlu buka-tutup modal satu-satu. Tiap akun tetap pakai kunci sendiri -
    TIDAK ada 1 kunci yang dipakai bersama utk banyak akun. --}}
    <x-modal name="pengguna-samakan-massal" :show="$showModalSamakanMassal" maxWidth="2xl">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Samakan Kunci Authenticator - {{ count($terpilihUntukSamakan) }} Akun Terpilih</h2>
            <p class="mt-1 text-sm text-slate-600">
                Isi kunci manual & kode 6 digit tiap akun di bawah (dapatkan dari aplikasi lokal/online satunya, sama seperti cara "Samakan Kunci" satu-satu - lihat penjelasan di modal itu). Akun yang dikosongkan (tidak diisi sama sekali) akan DILEWATI, tidak diubah.
            </p>

            <form wire:submit="samakanKunciMassal" class="mt-4 space-y-3 max-h-[28rem] overflow-y-auto pr-1">
                @foreach ($penggunaTerpilihMassal as $item)
                    <div class="border border-slate-200 rounded-lg p-3">
                        <p class="text-sm font-medium text-slate-800">
                            {{ $item->username }}
                            <span class="font-normal text-slate-500">({{ $item->level_akses_label }}{{ $item->nama_sekolah ? ' - '.$item->nama_sekolah : '' }})</span>
                        </p>
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <x-input-label :for="'kunciMassal-'.$item->id" value="Kunci Manual" class="text-xs" />
                                <x-text-input wire:model="kunciMassal.{{ $item->id }}" id="kunciMassal-{{ $item->id }}" class="block mt-1 w-full font-mono text-sm" type="text" autocomplete="off" />
                            </div>
                            <div>
                                <x-input-label :for="'kodeMassal-'.$item->id" value="Kode 6 Digit" class="text-xs" />
                                <x-text-input wire:model="kodeMassal.{{ $item->id }}" id="kodeMassal-{{ $item->id }}" class="block mt-1 w-full text-center tracking-widest" type="text" inputmode="numeric" maxlength="6" autocomplete="off" />
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="flex justify-end gap-3 pt-2">
                    <x-secondary-button type="button" wire:click="tutupSamakanMassal">Batal</x-secondary-button>
                    <x-primary-button type="submit">Samakan Semua</x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>
</div>
