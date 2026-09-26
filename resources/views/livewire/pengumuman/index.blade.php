<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Pengumuman') }}
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
                <div class="p-4 sm:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                        <p class="text-sm text-slate-500 max-w-xl">
                            Pengumuman di bawah ini otomatis tampil sebagai teks berjalan di landing page (beranda) selama tanggal hari ini berada di antara Tanggal Aktif & Tanggal Non Aktifnya.
                        </p>

                        <x-primary-button wire:click="tambah" class="shrink-0">+ Tambah Pengumuman</x-primary-button>
                    </div>

                    <div class="flex justify-end mb-3">
                        <x-zoom-controls :zoom="$zoomPercent" />
                    </div>

                    <div class="overflow-x-auto" style="zoom: {{ $zoomPercent }}%;">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead>
                                <tr class="text-left text-slate-500">
                                    <th class="px-3 py-2">Judul</th>
                                    <th class="px-3 py-2">Tanggal Aktif</th>
                                    <th class="px-3 py-2">Tanggal Non Aktif</th>
                                    <th class="px-3 py-2">Status</th>
                                    <th class="px-3 py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($pengumuman as $item)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-slate-800 max-w-xs truncate">{{ $item->judul }}</td>
                                        <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->tanggal_aktif->translatedFormat('d F Y') }}</td>
                                        <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->tanggal_nonaktif->translatedFormat('d F Y') }}</td>
                                        <td class="px-3 py-2">
                                            @if ($item->status === 'Aktif')
                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">Aktif</span>
                                            @elseif ($item->status === 'Akan Datang')
                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-700">Akan Datang</span>
                                            @else
                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-slate-100 text-slate-500">Berakhir</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right space-x-2">
                                            <button wire:click="edit({{ $item->id }})" class="text-blue-600 hover:underline">Edit</button>
                                            <button wire:click="konfirmasiHapus({{ $item->id }})" class="text-red-600 hover:underline">Hapus</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-6 text-center text-slate-400">Belum ada pengumuman.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $pengumuman->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Form Tambah/Edit --}}
    <x-modal name="pengumuman-form" :show="$showForm" maxWidth="lg">
        <form wire:submit="simpan" class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-4">
                {{ $editingId ? 'Edit Pengumuman' : 'Tambah Pengumuman' }}
            </h2>

            <div class="space-y-4">
                <div>
                    <x-input-label for="judul" value="Judul Pengumuman" />
                    <x-text-input wire:model="judul" id="judul" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('judul')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="isi" value="Isi Pengumuman" />
                    <textarea wire:model="isi" id="isi" rows="5" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full text-sm"></textarea>
                    <x-input-error :messages="$errors->get('isi')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="tanggal_aktif" value="Tanggal Pengumuman Aktif" />
                        <x-text-input wire:model="tanggal_aktif" id="tanggal_aktif" class="block mt-1 w-full" type="date" />
                        <x-input-error :messages="$errors->get('tanggal_aktif')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="tanggal_nonaktif" value="Tanggal Pengumuman Non Aktif" />
                        <x-text-input wire:model="tanggal_nonaktif" id="tanggal_nonaktif" class="block mt-1 w-full" type="date" />
                        <x-input-error :messages="$errors->get('tanggal_nonaktif')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="batal">Batal</x-secondary-button>
                <x-primary-button>Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Konfirmasi Hapus --}}
    <x-modal name="pengumuman-hapus" :show="$confirmingDeleteId !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Hapus pengumuman ini?</h2>
            <p class="mt-1 text-sm text-slate-600">Tindakan ini tidak dapat dibatalkan. Pengumuman ini akan langsung hilang dari landing page kalau sedang aktif.</p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalHapus">Batal</x-secondary-button>
                <x-danger-button wire:click="hapus">Hapus</x-danger-button>
            </div>
        </div>
    </x-modal>
</div>
