<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Panduan Aplikasi') }}
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

            @if (session('errorPanduan'))
                <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                    {{ session('errorPanduan') }}
                </div>
            @endif

            {{--
                Kartu "Filter Panduan" - 3 field filter (Book Manual/judul,
                Deskripsi, Tanggal Upload) sesuai gambar contoh yang
                diupload user.
            --}}
            <div class="bg-white shadow sm:rounded-lg p-4 sm:p-8">
                <h3 class="flex items-center gap-2 text-lg font-bold text-indigo-900 mb-4">
                    <x-icon name="search" class="w-5 h-5 text-indigo-500" />
                    Filter Panduan
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="search" value="Book Manual" class="text-xs text-slate-500" />
                        <x-text-input wire:model.live.debounce.300ms="search" id="search" type="text" placeholder="Cari judul panduan..." class="block mt-1 w-full text-sm" />
                    </div>
                    <div>
                        <x-input-label for="searchDeskripsi" value="Deskripsi" class="text-xs text-slate-500" />
                        <x-text-input wire:model.live.debounce.300ms="searchDeskripsi" id="searchDeskripsi" type="text" placeholder="Cari isi deskripsi..." class="block mt-1 w-full text-sm" />
                    </div>
                    <div>
                        <x-input-label for="filterTanggal" value="Tanggal Upload" class="text-xs text-slate-500" />
                        <x-text-input wire:model.live="filterTanggal" id="filterTanggal" type="date" class="block mt-1 w-full text-sm" />
                    </div>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="p-4 sm:p-8">
                    <div class="flex flex-wrap items-center justify-between gap-2.5 mb-4">
                        <x-zoom-controls :zoom="$zoomPercent" />

                        <x-primary-button wire:click="tambah">+ Tambah Panduan</x-primary-button>
                    </div>

                    {{--
                        Round 26, poin 2: tabel riwayat panduan dibungkus
                        scroll area bergaya sama dgn pola "scrollbar-modern"
                        yang sudah dipakai di menu lain (mis. Lampiran 2a/2b/
                        2c, Pajak BOSP Reguler) - supaya tabel tidak
                        memanjang ke bawah tanpa batas kalau datanya banyak.
                        Transisi halus pada scrollbar-nya ditambahkan di
                        resources/css/app.css.
                    --}}
                    <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg" style="max-height: 32rem; zoom: {{ $zoomPercent }}%;">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="sticky top-0 bg-white">
                                <tr class="text-left text-slate-500">
                                    <th class="px-3 py-2">Book Manual</th>
                                    <th class="px-3 py-2">Deskripsi</th>
                                    <th class="px-3 py-2">Ukuran</th>
                                    <th class="px-3 py-2">Tanggal Upload</th>
                                    <th class="px-3 py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($panduan as $item)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-slate-800 max-w-xs align-top">{{ $item->judul }}</td>
                                        <td class="px-3 py-2 text-slate-600 max-w-sm align-top">{{ \Illuminate\Support\Str::limit($item->deskripsi, 80) ?: '-' }}</td>
                                        <td class="px-3 py-2 text-slate-600 whitespace-nowrap align-top">{{ $item->ukuranTotalManusiawi() ?: '-' }}</td>
                                        <td class="px-3 py-2 text-slate-600 whitespace-nowrap align-top">{{ $item->created_at->translatedFormat('d M Y') }}</td>
                                        <td class="px-3 py-2 text-right align-top">
                                            {{--
                                                Round 25, poin 1: satu Judul boleh punya banyak file & link -
                                                masing-masing tampil sebagai baris sendiri, bisa diunduh/dibuka
                                                & dihapus satu per satu.

                                                Round 26, poin 1: kalau daftarnya panjang (lebih dari
                                                $batasTampil), hanya beberapa yang tampil DULU - sisanya
                                                disembunyikan di belakang tombol "Baca Selengkapnya" (Alpine.js
                                                x-show, TANPA round-trip Livewire - murni tampilan) supaya
                                                baris tabel tidak terlalu panjang ke bawah kalau file/link-nya
                                                banyak.
                                            --}}
                                            @php
                                                $entriesFile = $item->files->map(fn ($f) => ['type' => 'file', 'model' => $f]);
                                                $entriesLink = $item->links->map(fn ($l) => ['type' => 'link', 'model' => $l]);
                                                $entries = $entriesFile->concat($entriesLink)->values();
                                                $batasTampil = 3;
                                            @endphp
                                            <div class="flex flex-col items-end gap-1.5" @if ($entries->count() > $batasTampil) x-data="{ expanded: false }" @endif>
                                                @foreach ($entries as $i => $entry)
                                                    <div @if ($i >= $batasTampil) x-show="expanded" x-cloak @endif class="flex items-center gap-2 text-xs">
                                                        @if ($entry['type'] === 'file')
                                                            <a href="{{ route('panduan-aplikasi.file.unduh', $entry['model']) }}" class="text-emerald-600 hover:underline truncate max-w-[10rem]" title="{{ $entry['model']->file_nama_asli }}">
                                                                Unduh: {{ \Illuminate\Support\Str::limit($entry['model']->file_nama_asli, 20) }}
                                                            </a>
                                                            <button wire:click="hapusFile({{ $entry['model']->id }})" wire:confirm="Hapus file ini?" class="text-red-500 hover:underline">Hapus</button>
                                                        @else
                                                            <a href="{{ $entry['model']->link_drive }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline">Buka Link</a>
                                                            <button wire:click="hapusLink({{ $entry['model']->id }})" wire:confirm="Hapus link ini?" class="text-red-500 hover:underline">Hapus</button>
                                                        @endif
                                                    </div>
                                                @endforeach

                                                @if ($entries->count() > $batasTampil)
                                                    <button type="button" x-on:click="expanded = !expanded" class="text-slate-500 hover:underline text-xs" x-text="expanded ? 'Sembunyikan' : 'Baca Selengkapnya ({{ $entries->count() - $batasTampil }} lagi)'"></button>
                                                @endif

                                                {{-- Round 26, poin 3: unduh semua file sekaligus (zip) - hanya ditampilkan kalau Judul ini punya 2 file atau lebih (kalau cuma 1 file, link "Unduh" per-file di atas sudah cukup). --}}
                                                @if ($item->files->count() >= 2)
                                                    <a href="{{ route('panduan-aplikasi.unduh-semua', $item) }}" class="text-indigo-600 hover:underline text-xs">Unduh Semua ({{ $item->files->count() }} file)</a>
                                                @endif

                                                <div class="flex items-center gap-2 pt-1">
                                                    <button wire:click="edit({{ $item->id }})" class="text-indigo-600 hover:underline text-xs">Edit</button>
                                                    <button wire:click="konfirmasiHapus({{ $item->id }})" class="text-red-600 hover:underline text-xs">Hapus Judul</button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-6 text-center text-slate-400">Belum ada panduan yang diupload.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $panduan->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Form Tambah/Edit --}}
    <x-modal name="panduan-form" :show="$showForm" maxWidth="lg">
        <form wire:submit="simpan" class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-4">
                {{ $editingId ? 'Edit Panduan' : 'Tambah Panduan' }}
            </h2>

            <div class="space-y-4">
                <div>
                    <x-input-label for="judul" value="Judul Book Manual" />
                    <x-text-input wire:model="judul" id="judul" class="block mt-1 w-full" type="text" />
                    <x-input-error :messages="$errors->get('judul')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="deskripsi" value="Deskripsi Book Manual" />
                    <textarea wire:model="deskripsi" id="deskripsi" rows="3" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full text-sm"></textarea>
                    <x-input-error :messages="$errors->get('deskripsi')" class="mt-2" />
                </div>

                {{--
                    Round 25, poin 1: file/link yang SUDAH tersimpan (mode
                    Edit) - masing-masing bisa dihapus satu per satu tanpa
                    mempengaruhi yang lain.

                    Round 26, poin 1: sama seperti daftar di tabel - kalau
                    lebih dari $batasTampil, sisanya disembunyikan di
                    belakang "Baca Selengkapnya".
                --}}
                @if ($editingId)
                    @if ($panduanDiedit && ($panduanDiedit->files->isNotEmpty() || $panduanDiedit->links->isNotEmpty()))
                        @php
                            $entriesEditFile = $panduanDiedit->files->map(fn ($f) => ['type' => 'file', 'model' => $f]);
                            $entriesEditLink = $panduanDiedit->links->map(fn ($l) => ['type' => 'link', 'model' => $l]);
                            $entriesEdit = $entriesEditFile->concat($entriesEditLink)->values();
                            $batasTampilEdit = 3;
                        @endphp
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-md space-y-1.5" @if ($entriesEdit->count() > $batasTampilEdit) x-data="{ expanded: false }" @endif>
                            <p class="text-xs font-semibold text-slate-500">File & link yang sudah tersimpan:</p>
                            @foreach ($entriesEdit as $i => $entry)
                                <div @if ($i >= $batasTampilEdit) x-show="expanded" x-cloak @endif class="flex items-center justify-between gap-3 text-xs">
                                    @if ($entry['type'] === 'file')
                                        <span class="text-slate-700 truncate">{{ $entry['model']->file_nama_asli }} ({{ $entry['model']->ukuranManusiawi() }})</span>
                                        <button type="button" wire:click="hapusFile({{ $entry['model']->id }})" wire:confirm="Hapus file ini?" class="text-red-600 hover:underline whitespace-nowrap">Hapus</button>
                                    @else
                                        <span class="text-slate-700 truncate">{{ $entry['model']->link_drive }}</span>
                                        <button type="button" wire:click="hapusLink({{ $entry['model']->id }})" wire:confirm="Hapus link ini?" class="text-red-600 hover:underline whitespace-nowrap">Hapus</button>
                                    @endif
                                </div>
                            @endforeach

                            @if ($entriesEdit->count() > $batasTampilEdit)
                                <button type="button" x-on:click="expanded = !expanded" class="text-slate-500 hover:underline text-xs" x-text="expanded ? 'Sembunyikan' : 'Baca Selengkapnya ({{ $entriesEdit->count() - $batasTampilEdit }} lagi)'"></button>
                            @endif
                        </div>
                    @endif
                @endif

                <div>
                    <x-input-label value="Tambah Link Google Drive" />
                    <div class="space-y-2 mt-1">
                        @foreach ($linkBaruList as $index => $link)
                            <div class="flex items-center gap-2">
                                <x-text-input wire:model="linkBaruList.{{ $index }}" class="block w-full" type="text" placeholder="https://drive.google.com/..." />
                                @if (count($linkBaruList) > 1)
                                    <button type="button" wire:click="hapusLinkBaruBaris({{ $index }})" class="text-red-500 hover:underline text-xs whitespace-nowrap">Hapus</button>
                                @endif
                            </div>
                            <x-input-error :messages="$errors->get('linkBaruList.'.$index)" class="mt-1" />
                        @endforeach
                    </div>
                    <button type="button" wire:click="tambahLinkBaru" class="text-indigo-600 hover:underline text-xs mt-1">+ Tambah link lagi</button>
                </div>

                <div>
                    <x-input-label for="fileBaruList" value="Tambah Upload File (boleh pilih lebih dari satu sekaligus)" />
                    <input type="file" wire:model="fileBaruList" id="fileBaruList" multiple class="block mt-2 w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                    <p class="text-xs text-slate-400 mt-1">Format: PDF, ZIP, RAR, DOC/DOCX, XLS/XLSX, PNG, JPG, HTML, MD - maksimal 20 MB per file.</p>
                    <div wire:loading wire:target="fileBaruList" class="text-xs text-slate-400 mt-1">Mengunggah file...</div>
                    <x-input-error :messages="collect($errors->get('fileBaruList.*'))->flatten()->all()" class="mt-2" />
                </div>

                <p class="text-xs text-slate-400">Isi minimal salah satu: Link Google Drive atau Upload File (boleh lebih dari satu, atau kombinasi keduanya).</p>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="batal">Batal</x-secondary-button>
                <x-primary-button>Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Konfirmasi Hapus Judul --}}
    <x-modal name="panduan-hapus" :show="$confirmingDeleteId !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Hapus panduan ini?</h2>
            <p class="mt-1 text-sm text-slate-600">Tindakan ini tidak dapat dibatalkan - SELURUH file & link yang sudah diupload di bawah Judul ini juga akan ikut terhapus.</p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalHapus">Batal</x-secondary-button>
                <x-danger-button wire:click="hapus">Hapus</x-danger-button>
            </div>
        </div>
    </x-modal>
</div>
