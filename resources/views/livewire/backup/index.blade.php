<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Backup & Restore') }}
    </h2>
</x-slot>

<div>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm whitespace-pre-line">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('errorBackup'))
                <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm whitespace-pre-line">
                    {{ session('errorBackup') }}
                </div>
            @endif

            {{-- Tab Utama: Backup Data & Aplikasi / Restore Data & Aplikasi
                 (permintaan user 2026-10-14 poin 2: "menu backup di rubah
                 menjadi Backup & Restore ... buat 2 tab"). Pola sama seperti
                 tab Profil Sekolah/Data PTK. --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="border-b border-slate-200 px-4 sm:px-8 pt-4 pb-4">
                    <nav class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            wire:click="pindahTabUtama('backup')"
                            @class([
                                'px-4 py-2.5 rounded-t-xl text-sm font-bold tracking-wide transition-all duration-150 border-b-4',
                                'bg-white text-slate-800 border-indigo-500 shadow-sm' => $tabUtama === 'backup',
                                'bg-slate-50 text-slate-500 border-transparent hover:bg-slate-100 hover:text-slate-700' => $tabUtama !== 'backup',
                            ])
                        >
                            Backup Data &amp; Aplikasi
                        </button>
                        <button
                            type="button"
                            wire:click="pindahTabUtama('restore')"
                            @class([
                                'px-4 py-2.5 rounded-t-xl text-sm font-bold tracking-wide transition-all duration-150 border-b-4',
                                'bg-white text-slate-800 border-indigo-500 shadow-sm' => $tabUtama === 'restore',
                                'bg-slate-50 text-slate-500 border-transparent hover:bg-slate-100 hover:text-slate-700' => $tabUtama !== 'restore',
                            ])
                        >
                            Restore Data &amp; Aplikasi
                        </button>
                    </nav>
                </div>

                @if ($tabUtama === 'backup')
                    {{-- ======================================================
                         TAB 1: BACKUP DATA & APLIKASI - TIDAK ADA PERUBAHAN
                         LOGIKA dari sebelumnya, hanya dipindah ke dalam tab.
                         ====================================================== --}}
                    <div class="p-4 sm:p-8 space-y-6">
                        <div>
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

                        <div>
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

                                    {{--
                                        Tombol "Unduh Semua Sekaligus" (permintaan user
                                        2026-09-26) - memicu unduhan SEMUA backup yang
                                        cocok dengan filter Tahun di atas (bukan cuma
                                        1 halaman pagination), satu per satu otomatis
                                        lewat event unduh-semua-backup (lihat
                                        <script> di bawah) - BUKAN digabung jadi 1
                                        file zip di server (jawaban AskUserQuestion).
                                    --}}
                                    <button
                                        type="button"
                                        wire:click="unduhSemua"
                                        wire:loading.attr="disabled"
                                        wire:target="unduhSemua"
                                        class="inline-flex items-center px-3 py-1.5 bg-white border border-slate-300 rounded-md font-medium text-xs text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                                    >
                                        <span wire:loading.remove wire:target="unduhSemua">Unduh Semua Sekaligus</span>
                                        <span wire:loading wire:target="unduhSemua">Menyiapkan unduhan...</span>
                                    </button>
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
                                                <td class="px-3 py-2 font-medium text-slate-800">
                                                    {{ $item->nama_file }}
                                                    @if ($item->otomatisSebelumRestore())
                                                        <span class="ml-1 inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full bg-amber-100 text-amber-700" title="{{ $item->catatan }}">Otomatis (sebelum Restore)</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-slate-600">{{ $item->tahun }}</td>
                                                <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->ukuranManusiawi() ?: '-' }}</td>
                                                <td class="px-3 py-2 text-slate-600 whitespace-nowrap">
                                                    {{ $item->created_at->keWaktuLokal()->translatedFormat('d M Y H:i') }}
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

                            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex-1">
                                    {{ $backup->links() }}
                                </div>
                                <x-pagination-per-page wire:model.live="perPage" />
                            </div>
                        </div>
                    </div>
                @else
                    {{-- ======================================================
                         TAB 2: RESTORE DATA & APLIKASI (BARU, permintaan user
                         2026-10-14 poin 2) - lihat App\Services\RestoreService
                         untuk keputusan bisnis/teknis lengkap.
                         ====================================================== --}}
                    <div class="p-4 sm:p-8 space-y-6">
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                            <p class="font-bold mb-1">⚠ Restore akan MENGGANTI TOTAL seluruh isi database saat ini.</p>
                            <p>
                                Restore HANYA mengembalikan <strong>database</strong> (bukan kode/file aplikasi) ke kondisi snapshot UTUH saat backup yang
                                dipilih dibuat - SEMUA data yang dimasukkan SETELAH backup itu dibuat akan HILANG dari tampilan (walau tidak benar-benar
                                terhapus permanen, karena sistem otomatis membuat 1 backup pengaman dari kondisi SAAT INI tepat sebelum restore dijalankan).
                                Anda akan otomatis logout & perlu login kembali setelah restore berhasil.
                            </p>
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                                <h3 class="text-lg font-bold text-indigo-900">Pilih Backup untuk Di-Restore (berdasarkan Tahun)</h3>

                                <div class="flex items-center gap-2">
                                    <x-input-label for="restoreTahun" value="Tahun" class="text-xs text-slate-500" />
                                    <select wire:model.live="restoreTahun" id="restoreTahun" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
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
                                            <th class="px-3 py-2">Ukuran</th>
                                            <th class="px-3 py-2">Dibuat</th>
                                            <th class="px-3 py-2 text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse ($backupUntukRestore as $item)
                                            <tr>
                                                <td class="px-3 py-2 font-medium text-slate-800">
                                                    {{ $item->nama_file }}
                                                    @if ($item->otomatisSebelumRestore())
                                                        <span class="ml-1 inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full bg-amber-100 text-amber-700" title="{{ $item->catatan }}">Otomatis (sebelum Restore)</span>
                                                    @endif
                                                </td>
                                                <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->ukuranManusiawi() ?: '-' }}</td>
                                                <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $item->created_at->keWaktuLokal()->translatedFormat('d M Y H:i') }}</td>
                                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                                    <button wire:click="konfirmasiRestore({{ $item->id }})" class="px-3 py-1.5 rounded-md text-xs font-bold bg-red-600 text-white hover:bg-red-700">
                                                        Restore dari File Ini
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="px-3 py-6 text-center text-slate-400">Tidak ada backup pada tahun ini.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-bold text-indigo-900 mb-3">Riwayat Restore Terakhir</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead>
                                        <tr class="text-left text-slate-500">
                                            <th class="px-3 py-2">Waktu</th>
                                            <th class="px-3 py-2">Restore dari File</th>
                                            <th class="px-3 py-2">Backup Pengaman Dibuat</th>
                                            <th class="px-3 py-2">Dilakukan Oleh</th>
                                            <th class="px-3 py-2">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse ($riwayatRestore as $log)
                                            <tr>
                                                <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $log->created_at->keWaktuLokal()->translatedFormat('d M Y H:i') }}</td>
                                                <td class="px-3 py-2 text-slate-600">{{ $log->backup?->nama_file ?? '(file sudah dihapus)' }}</td>
                                                <td class="px-3 py-2 text-slate-600">{{ $log->backupPengaman?->nama_file ?? '-' }}</td>
                                                <td class="px-3 py-2 text-slate-600">{{ $log->dilakukanOleh?->display_name ?? '-' }}</td>
                                                <td class="px-3 py-2">
                                                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full @class(['bg-emerald-100 text-emerald-700' => $log->status === 'berhasil', 'bg-red-100 text-red-700' => $log->status === 'gagal'])">
                                                        {{ $log->status === 'berhasil' ? 'Berhasil' : 'Gagal' }}
                                                    </span>
                                                    @if ($log->status === 'gagal' && $log->pesan_error)
                                                        <span class="block text-xs text-red-500 mt-0.5">{{ $log->pesan_error }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-3 py-6 text-center text-slate-400">Belum pernah ada Restore yang dijalankan.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal konfirmasi Restore (ketik ulang nama file) --}}
    <x-modal name="backup-konfirmasi-restore" :show="$confirmingRestoreId !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-bold text-red-700">Yakin ingin Restore dari file ini?</h2>
            <p class="mt-2 text-sm text-slate-600">
                Database akan DIGANTI TOTAL dengan isi backup
                <span class="font-bold text-slate-800">{{ $backupDikonfirmasiRestore?->nama_file }}</span>.
                Tindakan ini TIDAK BISA dibatalkan setelah berjalan (walau sistem akan membuat 1 backup pengaman otomatis dari kondisi SAAT INI lebih dulu).
                Anda akan otomatis logout setelah proses ini selesai.
            </p>

            <div class="mt-4">
                <x-input-label for="konfirmasiNamaFile" value="Untuk melanjutkan, ketik ulang PERSIS nama file di atas:" />
                <x-text-input wire:model="konfirmasiNamaFile" id="konfirmasiNamaFile" class="block mt-1 w-full" type="text" autocomplete="off" />
                <x-input-error :messages="$errors->get('konfirmasiNamaFile')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalRestore" wire:loading.attr="disabled" wire:target="jalankanRestore">Batal</x-secondary-button>
                <x-primary-button
                    wire:click="jalankanRestore"
                    wire:loading.attr="disabled"
                    wire:target="jalankanRestore"
                    class="!bg-red-600 hover:!bg-red-700"
                >
                    <span wire:loading.remove wire:target="jalankanRestore">Ya, Restore Sekarang</span>
                    <span wire:loading wire:target="jalankanRestore">Sedang me-restore, mohon jangan tutup halaman ini...</span>
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    {{--
        Menangkap event unduh-semua-backup (dispatch dari unduhSemua() di
        Index.php) - memicu unduhan tiap URL SATU PER SATU dengan jeda
        singkat antar file, supaya browser tidak memblokirnya sebagai
        "banyak unduhan sekaligus" tanpa interaksi user.
    --}}
    <script>
        window.addEventListener('unduh-semua-backup', (event) => {
            const urls = event.detail?.urls ?? [];

            urls.forEach((url, index) => {
                setTimeout(() => {
                    const tautan = document.createElement('a');
                    tautan.href = url;
                    document.body.appendChild(tautan);
                    tautan.click();
                    tautan.remove();
                }, index * 700);
            });
        });
    </script>
</div>
