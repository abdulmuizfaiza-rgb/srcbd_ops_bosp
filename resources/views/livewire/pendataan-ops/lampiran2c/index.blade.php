<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Lampiran 2c') }}
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

            @if ($errorImport)
                <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm whitespace-pre-line">
                    {{ $errorImport }}
                </div>
            @endif

            @if ($errorExport)
                <div class="p-4 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-sm">
                    {{ $errorExport }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                {{-- Tab Triwulan --}}
                <div class="border-b border-slate-200 px-4 sm:px-8 pt-4 pb-4">
                    <x-tab-triwulan :options="$triwulanOptions" :active="$triwulan" prefix="Lampiran 2c" />
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
                            <div class="flex items-center gap-1.5 border border-black/10 rounded-md px-1.5 py-1 bg-black/10">
                                <input type="file" wire:model="fileImport" accept=".xlsx,.xls,.csv" class="text-[11px] text-slate-700 w-28 sm:w-36 file:mr-1.5 file:py-0.5 file:px-1.5 file:rounded file:border-0 file:bg-white file:text-slate-700 file:text-[11px] file:shadow-sm hover:file:bg-slate-100">
                                <x-secondary-button wire:click="import" wire:loading.attr="disabled" wire:target="fileImport,import" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="upload" class="w-3.5 h-3.5 mr-1" />
                                    Import Excel
                                </x-secondary-button>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <x-secondary-button wire:click="export" wire:loading.attr="disabled" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="download" class="w-3.5 h-3.5 mr-1" />
                                    Export Excel
                                </x-secondary-button>
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

                    <div wire:loading wire:target="fileImport,import" class="text-xs text-slate-400 -mt-3 mb-3">Memproses import...</div>
                    <x-input-error :messages="$errors->get('fileImport')" class="text-xs -mt-3 mb-3 block" />

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
                                    <th class="px-3 py-2">Tempat Tugas</th>
                                    <th class="px-3 py-2">Kecamatan</th>
                                    <th class="px-3 py-2">Jenis Kepangkatan</th>
                                    <th class="px-3 py-2">Golongan</th>
                                    <th class="px-3 py-2">Masa Kerja</th>
                                    <th class="px-3 py-2">Pangkat/Berkala</th>
                                    <th class="px-3 py-2">TMT</th>
                                    <th class="px-3 py-2">Gaji Pokok Lama</th>
                                    <th class="px-3 py-2">Gaji Pokok Baru</th>
                                    <th class="px-3 py-2">Keterangan</th>
                                    <th class="px-3 py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($daftar as $baris)
                                    <tr wire:key="lampiran-2c-baris-{{ $baris->id }}">
                                        <td class="px-3 py-2">
                                            <input type="checkbox" wire:model="dipilih" value="{{ $baris->id }}" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                        </td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-500">{{ $loop->iteration + $daftar->firstItem() - 1 }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->nrg }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->nuptk }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap font-medium text-slate-800">{{ $baris->nama_ptk }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->profilSekolah->nama_sekolah ?? '-' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->kecamatan }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->jenis_kepangkatan }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->golongan }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->masa_kerja }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $baris->pangkat_berkala }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ optional($baris->tmt)->format('d-m-Y') }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">Rp {{ number_format($baris->gaji_pokok_lama, 0, ',', '.') }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-slate-600">Rp {{ number_format($baris->gaji_pokok_baru, 0, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ $baris->keterangan }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap text-right space-x-2">
                                            <button wire:click="edit({{ $baris->id }})" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline"><x-icon name="pencil" class="w-3 h-3" />Edit</button>
                                            <button wire:click="konfirmasiHapus({{ $baris->id }})" class="inline-flex items-center gap-1 text-xs text-red-600 hover:underline"><x-icon name="trash" class="w-3 h-3" />Hapus</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="16" class="px-3 py-6 text-center text-slate-400">Belum ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $daftar->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Form Tambah/Edit --}}
    <x-modal name="lampiran-2c-form" :show="$showForm" maxWidth="2xl">
        <form wire:submit="simpan" class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-4">
                {{ $editingId ? 'Edit Data Lampiran 2c' : 'Tambah Data Lampiran 2c' }}
                <span class="block text-xs font-normal text-slate-400 mt-1">{{ $triwulanOptions[$triwulan] }}</span>
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="nrg" value="NRG" />
                    <x-text-input wire:model="nrg" id="nrg" class="block mt-1 w-full" type="text" inputmode="numeric" maxlength="12" />
                    <x-input-error :messages="$errors->get('nrg')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="nuptk" value="NUPTK" />
                    <x-text-input wire:model="nuptk" id="nuptk" class="block mt-1 w-full" type="text" inputmode="numeric" maxlength="16" />
                    <x-input-error :messages="$errors->get('nuptk')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="nama_ptk" value="Nama PTK" />
                    <x-text-input wire:model="nama_ptk" id="nama_ptk" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('nama_ptk')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="profil_sekolah_id" value="Tempat Tugas" />
                    <select wire:model="profil_sekolah_id" id="profil_sekolah_id" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full" @disabled(! $bolehKelolaSemua)>
                        <option value="">-- Pilih Sekolah --</option>
                        @foreach ($sekolahOptions as $sekolah)
                            <option value="{{ $sekolah->id }}">{{ $sekolah->nama_sekolah }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('profil_sekolah_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="kecamatan" value="Kecamatan" />
                    <select wire:model="kecamatan" id="kecamatan" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                        <option value="">-- Pilih Kecamatan --</option>
                        @foreach ($kecamatanOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('kecamatan')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="jenis_kepangkatan" value="Jenis Kepangkatan" />
                    <select wire:model="jenis_kepangkatan" id="jenis_kepangkatan" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                        <option value="">-- Pilih Jenis --</option>
                        @foreach ($jenisKepangkatanOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('jenis_kepangkatan')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <p class="text-sm font-medium text-slate-700 mb-2">Pangkat & Masa Kerja yang dimiliki</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="golongan" value="Golongan" />
                            <select wire:model="golongan" id="golongan" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                                <option value="">-- Pilih Golongan --</option>
                                @foreach ($golonganOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('golongan')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="masa_kerja" value="Masa Kerja" />
                            <x-text-input wire:model="masa_kerja" id="masa_kerja" class="block mt-1 w-full" type="text" placeholder="Contoh: 5 Tahun" />
                            <x-input-error :messages="$errors->get('masa_kerja')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="sm:col-span-2">
                    <p class="text-sm font-medium text-slate-700 mb-2">TMT SK Terbaru yang dimiliki</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="pangkat_berkala" value="Pangkat/Berkala" />
                            <select wire:model="pangkat_berkala" id="pangkat_berkala" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                                <option value="">-- Pilih --</option>
                                @foreach ($pangkatBerkalaOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('pangkat_berkala')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="tmt" value="TMT" />
                            <x-text-input wire:model="tmt" id="tmt" class="block mt-1 w-full" type="date" />
                            <x-input-error :messages="$errors->get('tmt')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div>
                    <x-input-label for="gaji_pokok_lama" value="Gaji Pokok Lama" />
                    <x-currency-input name="gaji_pokok_lama" :value="$gaji_pokok_lama" :reset-key="$formInstance" />
                    <x-input-error :messages="$errors->get('gaji_pokok_lama')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="gaji_pokok_baru" value="Gaji Pokok Baru" />
                    <x-currency-input name="gaji_pokok_baru" :value="$gaji_pokok_baru" :reset-key="$formInstance" />
                    <x-input-error :messages="$errors->get('gaji_pokok_baru')" class="mt-2" />
                </div>

                <div class="sm:col-span-2">
                    <x-input-label for="keterangan" value="Keterangan" />
                    <textarea wire:model="keterangan" id="keterangan" rows="3" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full"></textarea>
                    <x-input-error :messages="$errors->get('keterangan')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="batal" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                <x-primary-button class="!px-3 !py-1.5 !text-[10px]"><x-icon name="check" class="w-3.5 h-3.5 mr-1" />Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Konfirmasi Hapus --}}
    <x-modal name="lampiran-2c-hapus" :show="$confirmingDeleteId !== null" maxWidth="md">
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
         2026-09-26, pola sama seperti Lampiran2a/2b. --}}
    <x-modal name="lampiran-2c-hapus-terpilih" :show="$confirmingHapusTerpilih" maxWidth="md">
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
