<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Backup') }}
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

            @if (session('errorBackup'))
                <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                    {{ session('errorBackup') }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-4 sm:p-8">
                <h3 class="text-lg font-bold text-indigo-900 mb-2">Buat Backup Baru</h3>
                <p class="text-sm text-slate-500 mb-4">
                    Backup berisi seluruh kode aplikasi & data database terbaru, digabung dalam satu file .zip.
                    Proses ini bisa memakan waktu beberapa saat, mohon jangan tutup halaman ini sampai selesai.
                </p>

                <button
                    wire:click="buatBackup"
                    wire:loading.attr="disabled"
                    wire:target="buatBackup"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="buatBackup">Buat Backup Sekarang</span>
                    <span wire:loading wire:target="buatBackup">Sedang membuat backup...</span>
                </button>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="p-4 sm:p-8">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                        <h3 class="text-lg font-bold text-indigo-900">Riwayat Backup</h3>

                        <div class="flex items-center gap-2">
                            <x-input-label for="filterTahun" value="Tahun" class="text-xs text-slate-500" />
                            <select wire:model.live="filterTahun" id="filterTahun" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                <option value="">Semua Tahun</option>
                                @foreach ($daftarTahun as $tahun)
                                    <option value="{{ $tahun }}">{{ $tahun }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead>
                                <tr class="text-left text-slate-500">
                                    <th class="px-3 py-2">Nama File</th>
                                    <th class="px-3 py-2">Tahun</th>
                                    <th class="px-3 py-2">Ukuran</th>
                                    <th class="px-3 py-2">Dibuat</th>
                                    <th class="px-3 py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($backup as $item)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-slate-800">{{ $item->nama_file }}</td>
                                        <td class="px-3 py-2 text-slate-600">{{ $item->tahun }}</td>
                                        <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->ukuranManusiawi() ?: '-' }}</td>
                                        <td class="px-3 py-2 text-slate-600 whitespace-nowrap">
                                            {{ $item->created_at->translatedFormat('d M Y H:i') }}
                                            @if ($item->dibuatOleh)
                                                <span class="text-slate-400">oleh {{ $item->dibuatOleh->display_name }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right whitespace-nowrap space-x-2">
                                            <a href="{{ route('backup.unduh', $item) }}" class="text-emerald-600 hover:underline">Unduh</a>
                                            <button wire:click="hapus({{ $item->id }})" wire:confirm="Hapus backup ini? Tindakan ini tidak dapat dibatalkan." class="text-red-600 hover:underline">Hapus</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-6 text-center text-slate-400">Belum ada backup yang dibuat.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $backup->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
