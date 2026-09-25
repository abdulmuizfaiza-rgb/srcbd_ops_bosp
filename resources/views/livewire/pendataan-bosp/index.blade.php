<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Identitas Admin BOSP') }}
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

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="flex flex-col xl:flex-row xl:flex-wrap xl:items-start xl:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-lg font-medium text-slate-900">Identitas Admin BOSP</h3>
                        <p class="text-sm text-slate-500">Data identitas Admin BOSP per sekolah.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <x-zoom-controls :zoom="$zoomPercent" />
                    </div>
                </div>

                <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg" style="max-height: 30rem; zoom: {{ $zoomPercent }}%;">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-50">
                            <tr class="text-left text-slate-500">
                                <th class="px-3 py-2">No</th>
                                <th class="px-3 py-2">Nama Sekolah</th>
                                <th class="px-3 py-2">Nama Admin BOSP</th>
                                <th class="px-3 py-2">NUPTK</th>
                                <th class="px-3 py-2">JK</th>
                                <th class="px-3 py-2">Status Kepegawaian</th>
                                <th class="px-3 py-2">No Whatsapp</th>
                                <th class="px-3 py-2">Status Data</th>
                                <th class="px-3 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($daftarSekolah as $sekolah)
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap font-medium text-slate-800">{{ $sekolah->nama_sekolah }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->identitasBosp->nama ?? '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->identitasBosp->nuptk ?? '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->identitasBosp->jk_label ?? '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->identitasBosp->status_kepegawaian ?? '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->identitasBosp->no_whatsapp ?? '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @if ($sekolah->identitasBosp)
                                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">Sudah diisi</span>
                                        @else
                                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-700">Belum diisi</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-right">
                                        <button wire:click="isi({{ $sekolah->id }})" class="text-blue-600 hover:underline">
                                            {{ $sekolah->identitasBosp ? 'Edit' : 'Isi' }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-3 py-6 text-center text-slate-400">Belum ada data sekolah.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Form Identitas Admin BOSP --}}
    <x-modal name="identitas-bosp-form" :show="$showForm" maxWidth="2xl">
        <form wire:submit="simpan" class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-4">Identitas Admin BOSP</h2>

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="nama" value="Nama Admin BOSP" />
                        <x-text-input wire:model="nama" id="nama" class="block mt-1 w-full" type="text" required />
                        <x-input-error :messages="$errors->get('nama')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="nuptk" value="NUPTK" />
                        <x-text-input wire:model="nuptk" id="nuptk" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('nuptk')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="nip" value="NIP Admin BOSP" />
                        <x-text-input wire:model="nip" id="nip" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('nip')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="jk" value="Jenis Kelamin" />
                        <select wire:model="jk" id="jk" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                            <option value="">-- Pilih JK --</option>
                            @foreach ($jkOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('jk')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="tempat_lahir" value="Tempat Lahir" />
                        <x-text-input wire:model="tempat_lahir" id="tempat_lahir" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('tempat_lahir')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="tanggal_lahir" value="Tanggal Lahir" />
                        <x-text-input wire:model="tanggal_lahir" id="tanggal_lahir" class="block mt-1 w-full" type="date" />
                        <x-input-error :messages="$errors->get('tanggal_lahir')" class="mt-2" />
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
                        <x-input-label for="pendidikan_terakhir" value="Pendidikan Terakhir" />
                        <select wire:model.live="pendidikan_terakhir" id="pendidikan_terakhir" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                            <option value="">-- Pilih Pendidikan Terakhir --</option>
                            @foreach ($pendidikanOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('pendidikan_terakhir')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="jurusan" value="Jurusan" />
                        <x-text-input wire:model="jurusan" id="jurusan" class="block mt-1 w-full" type="text" :disabled="! $butuhJurusan" />
                        <p class="text-xs text-slate-400 mt-1">Hanya aktif untuk Pendidikan Terakhir S1/S2/S3.</p>
                        <x-input-error :messages="$errors->get('jurusan')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="nama_perguruan_tinggi" value="Nama Perguruan Tinggi" />
                        <x-text-input wire:model="nama_perguruan_tinggi" id="nama_perguruan_tinggi" class="block mt-1 w-full" type="text" :disabled="! $butuhJurusan" />
                        <p class="text-xs text-slate-400 mt-1">Hanya aktif untuk Pendidikan Terakhir S1/S2/S3.</p>
                        <x-input-error :messages="$errors->get('nama_perguruan_tinggi')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="no_whatsapp" value="No Whatsapp Admin BOSP" />
                        <x-text-input wire:model="no_whatsapp" id="no_whatsapp" class="block mt-1 w-full" type="text" inputmode="numeric" maxlength="12" />
                        <x-input-error :messages="$errors->get('no_whatsapp')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="batal">Batal</x-secondary-button>
                <x-primary-button>Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Popup sukses (sekali saja): Profil Sekolah baru saja lengkap --}}
    <x-modal name="identitas-bosp-sukses" :show="$tampilkanSuksesProfilLengkap" maxWidth="md">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-emerald-100 text-emerald-600">
                    <x-icon name="check" class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-lg font-medium text-slate-900">Profil Sekolah Lengkap</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Profil Sekolah Anda sudah lengkap. Silakan lanjutkan dengan mengisi Identitas Admin
                        BOSP di bawah ini supaya menu-menu lain pada aplikasi ini dapat digunakan.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-primary-button wire:click="tutupSuksesProfilLengkap">Mengerti</x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- Popup wajib: Identitas Admin BOSP milik akun ini belum lengkap --}}
    <x-modal-wajib name="identitas-bosp-belum-lengkap" :show="false" maxWidth="md">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-amber-100 text-amber-600">
                    <x-icon name="alert-circle" class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-lg font-medium text-slate-900">Identitas Admin BOSP Belum Lengkap</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Data Identitas Admin BOSP Anda belum diisi. Mohon segera lengkapi data di bawah ini
                        supaya menu-menu lain pada aplikasi ini dapat digunakan.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-primary-button wire:click="tutupPeringatanIdentitasBelumLengkap">Mengerti, Isi Sekarang</x-primary-button>
            </div>
        </div>
    </x-modal-wajib>
</div>
