<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Profil Sekolah') }}
    </h2>
</x-slot>

<div>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                @if (! $editing)
                    {{-- Mode tampilan --}}
                    <div class="flex justify-between items-start mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Data Sekolah</h3>
                        <x-primary-button wire:click="edit">Edit Profil</x-primary-button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="flex items-center gap-4">
                            @if ($profil?->logo_sekolah)
                                <img src="{{ Storage::url($profil->logo_sekolah) }}" alt="Logo Sekolah" class="h-16 w-16 object-contain border rounded">
                            @endif
                            <div>
                                <p class="text-xs text-gray-500">Logo Sekolah</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            @if ($profil?->logo_pemda)
                                <img src="{{ Storage::url($profil->logo_pemda) }}" alt="Logo Pemda" class="h-16 w-16 object-contain border rounded">
                            @endif
                            <div>
                                <p class="text-xs text-gray-500">Logo Pemda</p>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500">NPSN</p>
                            <p class="font-medium text-gray-800">{{ $profil?->npsn ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Nama Sekolah</p>
                            <p class="font-medium text-gray-800">{{ $profil?->nama_sekolah ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Nama Kepala Sekolah</p>
                            <p class="font-medium text-gray-800">{{ $profil?->nama_kepala_sekolah ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">NIP Kepala Sekolah</p>
                            <p class="font-medium text-gray-800">{{ $profil?->nip_kepala_sekolah ?: '-' }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-xs text-gray-500">Alamat Sekolah</p>
                            <p class="font-medium text-gray-800">{{ $profil?->alamat_sekolah ?: '-' }}</p>
                        </div>
                    </div>
                @else
                    {{-- Mode edit --}}
                    <h3 class="text-lg font-medium text-gray-900 mb-6">
                        {{ $profil ? 'Edit Profil Sekolah' : 'Lengkapi Profil Sekolah' }}
                    </h3>

                    <form wire:submit="simpan" class="space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="npsn" value="NPSN" />
                                <x-text-input wire:model="npsn" id="npsn" class="block mt-1 w-full" type="text" />
                                <x-input-error :messages="$errors->get('npsn')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="nama_sekolah" value="Nama Sekolah" />
                                <x-text-input wire:model="nama_sekolah" id="nama_sekolah" class="block mt-1 w-full" type="text" required />
                                <x-input-error :messages="$errors->get('nama_sekolah')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="nama_kepala_sekolah" value="Nama Kepala Sekolah" />
                                <x-text-input wire:model="nama_kepala_sekolah" id="nama_kepala_sekolah" class="block mt-1 w-full" type="text" />
                                <x-input-error :messages="$errors->get('nama_kepala_sekolah')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="nip_kepala_sekolah" value="NIP Kepala Sekolah" />
                                <x-text-input wire:model="nip_kepala_sekolah" id="nip_kepala_sekolah" class="block mt-1 w-full" type="text" />
                                <x-input-error :messages="$errors->get('nip_kepala_sekolah')" class="mt-2" />
                            </div>

                            <div class="sm:col-span-2">
                                <x-input-label for="alamat_sekolah" value="Alamat Sekolah" />
                                <textarea wire:model="alamat_sekolah" id="alamat_sekolah" rows="3" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full"></textarea>
                                <x-input-error :messages="$errors->get('alamat_sekolah')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="logo_sekolah_baru" value="Logo Sekolah" />
                                <input wire:model="logo_sekolah_baru" id="logo_sekolah_baru" type="file" accept="image/*" class="block mt-1 w-full text-sm text-gray-600">
                                <div wire:loading wire:target="logo_sekolah_baru" class="text-xs text-gray-400 mt-1">Mengunggah...</div>
                                @if ($logo_sekolah_baru)
                                    <img src="{{ $logo_sekolah_baru->temporaryUrl() }}" class="h-16 mt-2 object-contain">
                                @endif
                                <x-input-error :messages="$errors->get('logo_sekolah_baru')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="logo_pemda_baru" value="Logo Pemda" />
                                <input wire:model="logo_pemda_baru" id="logo_pemda_baru" type="file" accept="image/*" class="block mt-1 w-full text-sm text-gray-600">
                                <div wire:loading wire:target="logo_pemda_baru" class="text-xs text-gray-400 mt-1">Mengunggah...</div>
                                @if ($logo_pemda_baru)
                                    <img src="{{ $logo_pemda_baru->temporaryUrl() }}" class="h-16 mt-2 object-contain">
                                @endif
                                <x-input-error :messages="$errors->get('logo_pemda_baru')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <x-primary-button>Simpan</x-primary-button>
                            @if ($profil)
                                <x-secondary-button type="button" wire:click="batal">Batal</x-secondary-button>
                            @endif
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
