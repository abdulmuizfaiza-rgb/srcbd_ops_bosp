<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Tahun Anggaran') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Kartu "Tahun Anggaran Aktif" - tampil utk SEMUA role, tapi HANYA Superadmin yang bisa mengubahnya. --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-slate-900">Tahun Anggaran Aktif</h3>
                <p class="text-sm text-slate-500 mt-1">Tahun ini otomatis tertuju untuk SEMUA Admin OPS/Admin BOSP yang baru login, tanpa perlu izin apa pun.</p>

                @if ($bolehKelolaSemua)
                    <form wire:submit="simpanTahunAktif" class="mt-4 flex flex-wrap items-end gap-3">
                        <div>
                            <x-input-label for="tahunAktifBaru" value="Tahun Anggaran Aktif" />
                            <x-text-input wire:model="tahunAktifBaru" id="tahunAktifBaru" type="number" class="mt-1 w-40" />
                            <x-input-error :messages="$errors->get('tahunAktifBaru')" class="mt-2" />
                        </div>
                        <x-primary-button>Simpan</x-primary-button>
                    </form>
                @else
                    <div class="mt-4 flex flex-wrap items-center gap-6">
                        <div>
                            <div class="text-xs text-slate-500">Tahun Anggaran Aktif</div>
                            <div class="text-2xl font-semibold text-slate-900">{{ $tahunAktif }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500">Tahun yang Anda akses sekarang (sejak login)</div>
                            <div class="text-2xl font-semibold text-blue-700">{{ $tahunSesiSaya ?? '-' }}</div>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">Tahun yang sedang Anda akses mengikuti pilihan saat login dan tidak bisa diganti di sini - logout lalu login ulang untuk berganti tahun.</p>
                @endif
            </div>

            @if ($bolehKelolaSemua)
                {{-- Superadmin: daftar permintaan akses tahun sebelumnya. --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-medium text-slate-900">Permintaan Akses Tahun Sebelumnya</h3>
                        <select wire:model.live="filterStatus" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                            <option value="menunggu">Menunggu</option>
                            <option value="disetujui">Disetujui</option>
                            <option value="ditolak">Ditolak</option>
                            <option value="semua">Semua</option>
                        </select>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead>
                                <tr class="text-left text-slate-500">
                                    <th class="py-2 pr-4">Nama Sekolah</th>
                                    <th class="py-2 pr-4">Level</th>
                                    <th class="py-2 pr-4">Tahun Diminta</th>
                                    <th class="py-2 pr-4">Status</th>
                                    <th class="py-2 pr-4">Tanggal Diminta</th>
                                    <th class="py-2 pr-4">Diproses Oleh</th>
                                    <th class="py-2 pr-4">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($daftarPermintaan as $permintaan)
                                    <tr>
                                        <td class="py-2 pr-4">{{ $permintaan->user->profilSekolah?->nama_sekolah ?? $permintaan->user->nama_sekolah ?? $permintaan->user->username }}</td>
                                        <td class="py-2 pr-4">{{ $permintaan->user->level_akses_label }}</td>
                                        <td class="py-2 pr-4 font-semibold">{{ $permintaan->tahun }}</td>
                                        <td class="py-2 pr-4">
                                            @if ($permintaan->status === 'disetujui')
                                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Disetujui</span>
                                            @elseif ($permintaan->status === 'ditolak')
                                                <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-medium text-rose-700">Ditolak</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Menunggu</span>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-4">{{ $permintaan->diminta_pada?->format('d-m-Y H:i') }}</td>
                                        <td class="py-2 pr-4">{{ $permintaan->diprosesOleh?->username ?? '-' }}</td>
                                        <td class="py-2 pr-4">
                                            <div class="flex gap-2">
                                                @if ($permintaan->status !== 'disetujui')
                                                    <button type="button" wire:click="setujui({{ $permintaan->id }})" class="text-xs font-semibold text-emerald-700 hover:text-emerald-900">Setujui</button>
                                                @endif
                                                @if ($permintaan->status === 'disetujui')
                                                    <button type="button" wire:click="tolakAtauCabut({{ $permintaan->id }})" wire:confirm="Yakin ingin mencabut izin tahun {{ $permintaan->tahun }} untuk akun ini? Admin tersebut tidak akan bisa membuka tahun ini lagi sampai diizinkan ulang." class="text-xs font-semibold text-rose-700 hover:text-rose-900">Cabut Izin</button>
                                                @elseif ($permintaan->status === 'menunggu')
                                                    <button type="button" wire:click="tolakAtauCabut({{ $permintaan->id }})" class="text-xs font-semibold text-rose-700 hover:text-rose-900">Tolak</button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="py-4 text-center text-slate-500">Belum ada permintaan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                {{-- Admin OPS/Admin BOSP: ajukan permintaan + riwayat sendiri. --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-slate-900">Ajukan Permintaan Akses Tahun Sebelumnya</h3>
                    <p class="text-sm text-slate-500 mt-1">Permintaan akan diteruskan ke Superadmin untuk disetujui. Setelah disetujui, tahun ini akan muncul sebagai pilihan di form login - berlaku terus sampai dicabut Superadmin.</p>

                    @if (count($tahunOpsiAjukan) > 0)
                        <form wire:submit="ajukanPermintaan" class="mt-4 flex flex-wrap items-end gap-3">
                            <div>
                                <x-input-label for="tahunDiajukan" value="Tahun" />
                                <select wire:model="tahunDiajukan" id="tahunDiajukan" class="mt-1 border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                    <option value="">- Pilih Tahun -</option>
                                    @foreach ($tahunOpsiAjukan as $opsiTahun)
                                        <option value="{{ $opsiTahun }}">{{ $opsiTahun }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('tahunDiajukan')" class="mt-2" />
                            </div>
                            <x-primary-button>Ajukan</x-primary-button>
                        </form>
                    @else
                        <p class="mt-4 text-sm text-slate-500">Tidak ada tahun lain yang bisa diajukan saat ini.</p>
                    @endif
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-slate-900">Riwayat Permintaan Saya</h3>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead>
                                <tr class="text-left text-slate-500">
                                    <th class="py-2 pr-4">Tahun</th>
                                    <th class="py-2 pr-4">Status</th>
                                    <th class="py-2 pr-4">Tanggal Diminta</th>
                                    <th class="py-2 pr-4">Tanggal Diproses</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($riwayatSaya as $permintaan)
                                    <tr>
                                        <td class="py-2 pr-4 font-semibold">{{ $permintaan->tahun }}</td>
                                        <td class="py-2 pr-4">
                                            @if ($permintaan->status === 'disetujui')
                                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Disetujui</span>
                                            @elseif ($permintaan->status === 'ditolak')
                                                <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-medium text-rose-700">Ditolak</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Menunggu</span>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-4">{{ $permintaan->diminta_pada?->format('d-m-Y H:i') }}</td>
                                        <td class="py-2 pr-4">{{ $permintaan->diproses_pada?->format('d-m-Y H:i') ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-4 text-center text-slate-500">Belum pernah mengajukan permintaan.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
