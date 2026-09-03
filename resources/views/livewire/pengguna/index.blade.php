<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
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

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari username / nama sekolah..." class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">

                        <select wire:model.live="filterLevel" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">Semua Level Akses</option>
                            @foreach ($levelOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <x-primary-button wire:click="tambah">+ Tambah Pengguna</x-primary-button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="px-3 py-2">Username</th>
                                <th class="px-3 py-2">Nama Sekolah</th>
                                <th class="px-3 py-2">Jabatan</th>
                                <th class="px-3 py-2">Level Akses</th>
                                <th class="px-3 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($pengguna as $item)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-gray-800">{{ $item->username }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $item->nama_sekolah ?: '-' }}</td>
                                    <td class="px-3 py-2 text-gray-600">{{ $item->jabatan ?: '-' }}</td>
                                    <td class="px-3 py-2">
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                            @class([
                                                'bg-indigo-100 text-indigo-700' => $item->level_akses === 'superadmin',
                                                'bg-emerald-100 text-emerald-700' => $item->level_akses === 'admin_ops',
                                                'bg-amber-100 text-amber-700' => $item->level_akses === 'admin_bosp',
                                            ])">
                                            {{ $item->level_akses_label }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-right space-x-2">
                                        <button wire:click="edit({{ $item->id }})" class="text-indigo-600 hover:underline">Edit</button>
                                        @if ($item->id !== auth()->id())
                                            <button wire:click="konfirmasiHapus({{ $item->id }})" class="text-red-600 hover:underline">Hapus</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-gray-400">Belum ada data pengguna.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $pengguna->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Form Tambah/Edit --}}
    <x-modal name="pengguna-form" :show="$showForm" maxWidth="lg">
        <form wire:submit="simpan" class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">
                {{ $editingId ? 'Edit Pengguna' : 'Tambah Pengguna' }}
            </h2>

            <div class="space-y-4">
                <div>
                    <x-input-label for="level_akses" value="Level Akses" />
                    <select wire:model.live="level_akses" id="level_akses" class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm block mt-1 w-full">
                        <option value="">-- Pilih Level Akses --</option>
                        @foreach ($levelOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('level_akses')" class="mt-2" />
                </div>

                @if (in_array($level_akses, ['admin_ops', 'admin_bosp']))
                    <div>
                        <x-input-label for="nama_sekolah" value="Nama Sekolah" />
                        <x-text-input wire:model="nama_sekolah" id="nama_sekolah" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('nama_sekolah')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="jabatan" value="Jabatan" />
                        <x-text-input wire:model="jabatan" id="jabatan" class="block mt-1 w-full bg-gray-100" type="text" readonly />
                    </div>
                @endif

                <div>
                    <x-input-label for="username" :value="in_array($level_akses, ['admin_ops','admin_bosp']) ? 'Username (NPSN)' : 'Username'" />
                    <x-text-input wire:model="username" id="username" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="password" :value="$editingId ? 'Password (kosongkan jika tidak diubah)' : 'Password'" />
                    <x-text-input wire:model="password" id="password" class="block mt-1 w-full" type="password" />
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
            <h2 class="text-lg font-medium text-gray-900">Hapus pengguna ini?</h2>
            <p class="mt-1 text-sm text-gray-600">Tindakan ini tidak dapat dibatalkan.</p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalHapus">Batal</x-secondary-button>
                <x-danger-button wire:click="hapus">Hapus</x-danger-button>
            </div>
        </div>
    </x-modal>
</div>
