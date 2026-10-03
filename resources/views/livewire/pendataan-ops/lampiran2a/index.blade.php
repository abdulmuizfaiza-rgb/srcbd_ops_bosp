<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Lampiran 2a') }}
    </h2>
</x-slot>

<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                {{-- Tab Triwulan --}}
                <div class="border-b border-slate-200 px-4 sm:px-8 pt-4 pb-4">
                    <x-tab-triwulan :options="$triwulanOptions" :active="$triwulan" prefix="Lampiran 2a" />
                </div>

                <div class="p-4 sm:p-8">
                    <div class="flex flex-col xl:flex-row xl:flex-wrap xl:items-start xl:justify-between gap-3 mb-6">
                        <div class="flex flex-wrap sm:flex-row sm:items-center gap-2.5">
                            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama PTK / NRG / NUPTK..." class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">

                            @if ($bolehKelolaSemua)
                                <select wire:model.live="filterSekolahId" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">
                                    <option value="">Semua Sekolah</option>
                                    @foreach ($sekolahOptions as $sekolah)
                                        <option value="{{ $sekolah->id }}">{{ $sekolah->nama_sekolah }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <div class="flex flex-wrap sm:flex-row sm:items-center gap-2.5">
                            <div class="flex items-center gap-1.5">
                                <x-primary-button wire:click="tambah" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="plus" class="w-3.5 h-3.5 mr-1" />
                                    Tambah Data
                                </x-primary-button>
                                <x-danger-button type="button" wire:click="konfirmasiHapusTerpilih" wire:loading.attr="disabled" :disabled="count($dipilih) === 0" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="trash" class="w-3.5 h-3.5 mr-1" />
                                    Hapus Terpilih ({{ count($dipilih) }})
                                </x-danger-button>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg" style="max-height: 30rem;">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="sticky top-0 z-10 bg-slate-50">
                                <tr class="text-left text-slate-500">
                                    <th class="px-3 py-2">
                                        <input type="checkbox" wire:click="toggleSemua" @checked($semuaTerpilih) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" title="Pilih/batal pilih semua baris di halaman ini">
                                    </th>
                                    <th class="px-3 py-2">No</th>
                                    <th class="px-3 py-2">NRG</th>
                                    <th class="px-3 py-2">NUPTK</th>
                                    <th class="px-3 py-2">Nama PTK</th>
                                    <th class="px-3 py-2">Status Kepegawaian</th>
                                    <th class="px-3 py-2">Nama Sekolah</th>
                                    <th class="px-3 py-2">Gaji Pokok Januari {{ $tahunSekarang }}</th>
                                    <th class="px-3 py-2">NPWP</th>
                                    <th class="px-3 py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($daftar as $baris)
                                    <tr wire:key="lampiran-2a-baris-{{ $baris->id }}">
                                        <td class="px-3 py-2">
                                            <input type="checkbox" wire:model="dipilih" value="{{ $baris->id }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-500">{{ $loop->iteration + $daftar->firstItem() - 1 }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->nrg }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->nuptk }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->nama_ptk }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->status_kepegawaian }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap font-medium text-slate-800">{{ $baris->profilSekolah->nama_sekolah ?? '-' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">Rp {{ number_format($baris->gaji_pokok_januari, 0, ',', '.') }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->npwp }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-right space-x-2">
                                            <button wire:click="edit({{ $baris->id }})" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline"><x-icon name="pencil" class="w-3 h-3" />Edit</button>
                                            <button wire:click="konfirmasiHapus({{ $baris->id }})" class="inline-flex items-center gap-1 text-xs text-red-600 hover:underline"><x-icon name="trash" class="w-3 h-3" />Hapus</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="px-3 py-6 text-center text-slate-400">Belum ada data pada triwulan ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex-1">
                            {{ $daftar->links() }}
                        </div>
                        <x-pagination-per-page wire:model.live="perPage" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Form Tambah/Edit --}}
    <x-modal name="lampiran-2a-form" :show="$showForm" maxWidth="lg">
        <form wire:submit="simpan" class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-4">
                {{ $editingId ? 'Edit Data Lampiran 2a' : 'Tambah Data Lampiran 2a' }}
                <span class="block text-xs font-normal text-slate-400 mt-1">{{ $triwulanOptions[$triwulan] }}</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <x-input-label for="profil_sekolah_id" value="Nama Sekolah" />
                    <select wire:model.live="profil_sekolah_id" id="profil_sekolah_id" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full" @disabled(! $bolehKelolaSemua)>
                        <option value="">-- Pilih Sekolah --</option>
                        @foreach ($sekolahOptions as $sekolah)
                            <option value="{{ $sekolah->id }}">{{ $sekolah->nama_sekolah }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('profil_sekolah_id')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="dataPtkId" value="Pilih PTK (dari Data PTK)" />
                    <select wire:model.live="dataPtkId" id="dataPtkId" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full" @disabled(! $profil_sekolah_id)>
                        <option value="">-- Ketik Manual / Belum Ada di Data PTK --</option>
                        @foreach ($daftarPtkOptions as $ptk)
                            <option value="{{ $ptk->id }}">{{ $ptk->nama_ptk }} ({{ $ptk->jabatan }})</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-400 mt-1">
                        Hanya menampilkan PTK pada sekolah ini dengan Status Sertifikasi "Sudah" &amp; Status Keaktifan "Aktif".
                        Kalau PTK yang dicari tidak ada di daftar, biarkan "Ketik Manual" lalu isi Nama PTK/NRG/NUPTK di bawah sendiri.
                    </p>
                </div>

                <div>
                    <x-input-label for="nrg" value="NRG" />
                    <x-text-input wire:model="nrg" id="nrg" class="block mt-1 w-full" type="text" inputmode="numeric" maxlength="12" @disabled($dataPtkId) />
                    <p class="text-xs text-slate-400 mt-1" @if (! $dataPtkId) style="display:none" @endif>Otomatis dari Nomor Registrasi Guru pada Data PTK.</p>
                    <x-input-error :messages="$errors->get('nrg')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="nuptk" value="NUPTK" />
                    <x-text-input wire:model="nuptk" id="nuptk" class="block mt-1 w-full" type="text" inputmode="numeric" maxlength="16" @disabled($dataPtkId) />
                    <p class="text-xs text-slate-400 mt-1" @if (! $dataPtkId) style="display:none" @endif>Otomatis dari Data PTK.</p>
                    <x-input-error :messages="$errors->get('nuptk')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="nama_ptk" value="Nama PTK" />
                    <x-text-input wire:model="nama_ptk" id="nama_ptk" class="block mt-1 w-full" type="text" @disabled($dataPtkId) />
                    <p class="text-xs text-slate-400 mt-1" @if (! $dataPtkId) style="display:none" @endif>Otomatis dari Data PTK.</p>
                    <x-input-error :messages="$errors->get('nama_ptk')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="status_kepegawaian" value="Status Kepegawaian" />
                    <select wire:model="status_kepegawaian" id="status_kepegawaian" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                        <option value="">-- Pilih Status Kepegawaian --</option>
                        @foreach ($statusKepegawaianOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('status_kepegawaian')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="gaji_pokok_januari" value="Gaji Pokok Bulan Januari {{ $tahunSekarang }}" />
                    <x-currency-input name="gaji_pokok_januari" :value="$gaji_pokok_januari" :reset-key="$formInstance" />
                    <x-input-error :messages="$errors->get('gaji_pokok_januari')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="npwp" value="NPWP" />
                    <x-text-input wire:model="npwp" id="npwp" class="block mt-1 w-full" type="text" inputmode="numeric" maxlength="16" />
                    <p class="text-xs text-slate-400 mt-1">15 digit (format lama) atau 16 digit (format NIK).</p>
                    <x-input-error :messages="$errors->get('npwp')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="batal" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                <x-primary-button class="!px-3 !py-1.5 !text-[10px]"><x-icon name="check" class="w-3.5 h-3.5 mr-1" />Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Konfirmasi Hapus --}}
    <x-modal name="lampiran-2a-hapus" :show="$confirmingDeleteId !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Hapus data ini?</h2>
            <p class="mt-1 text-sm text-slate-600">Tindakan ini tidak dapat dibatalkan.</p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalHapus" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                <x-danger-button wire:click="hapus" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Hapus</x-danger-button>
            </div>
        </div>
    </x-modal>

    {{-- Modal Konfirmasi Hapus Terpilih (hapus massal) - permintaan user
         2026-09-26, pola sama seperti Penerimaan Honor PTK - HANYA
         menghapus baris yang dicentang di halaman yang sedang tampil. --}}
    <x-modal name="lampiran-2a-hapus-terpilih" :show="$confirmingHapusTerpilih" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Hapus {{ count($dipilih) }} data terpilih?</h2>
            <p class="mt-1 text-sm text-slate-600">Semua baris yang dicentang akan dihapus sekaligus. Tindakan ini tidak dapat dibatalkan.</p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalHapusTerpilih" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                <x-danger-button wire:click="hapusTerpilih" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Hapus Semua Terpilih</x-danger-button>
            </div>
        </div>
    </x-modal>
</div>
