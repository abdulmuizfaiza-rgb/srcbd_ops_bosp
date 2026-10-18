<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Backup & Restore') }}
    </h2>
</x-slot>

<div
    {{--
        Zoom SELURUH halaman & progress bar animasi "Buat Backup Sekarang"
        (permintaan user 2026-10-14: "tambahkan tombol zoom in-zoom out"
        & "progres bar yang bergaya animasi untuk memastikan file backup
        ... sukses terkirim"). zoomPersen dipersist lewat localStorage
        POLOS (bukan plugin Alpine $persist, tidak menambah dependensi
        baru) - CSS `zoom` dipilih krn paling sederhana utk memperbesar/
        memperkecil TEKS & TAMPILAN seluruh halaman sekaligus (jawaban
        AskUserQuestion "Perbesar/perkecil teks & tampilan SELURUH
        halaman"), dibanding transform:scale (butuh penyesuaian layout)
        atau ukuran font rem (tidak ikut memperbesar spacing/gambar).
        Progress bar "Ya, Restore Sekarang" di modal bawah PUNYA x-data
        SENDIRI (lokal per-modal), meniru pola persis Tab Sinkronisasi.
    --}}
    x-data="{
        zoomPersen: 100,
        backupBerjalan: false,
        backupDetik: 0,
        _backupInterval: null,
        init() {
            try {
                const tersimpan = localStorage.getItem('srcbd_backup_zoom');
                if (tersimpan) { this.zoomPersen = parseInt(tersimpan, 10); }
            } catch (e) {}
        },
        perbesarZoom() { this.zoomPersen = Math.min(150, this.zoomPersen + 10); this.simpanZoom(); },
        perkecilZoom() { this.zoomPersen = Math.max(70, this.zoomPersen - 10); this.simpanZoom(); },
        resetZoom() { this.zoomPersen = 100; this.simpanZoom(); },
        simpanZoom() {
            try { localStorage.setItem('srcbd_backup_zoom', this.zoomPersen); } catch (e) {}
        }
    }"
    x-on:backup-proses-selesai.window="backupBerjalan = false; clearInterval(_backupInterval)"
    :style="'zoom: ' + zoomPersen + '%'"
>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="flex flex-wrap items-center justify-end gap-2 text-xs text-slate-500">
                <span class="font-medium">Tampilan:</span>
                <button type="button" x-on:click="perkecilZoom()" class="w-7 h-7 inline-flex items-center justify-center rounded-md border border-slate-300 bg-white hover:bg-slate-50 font-bold" title="Perkecil tampilan">&minus;</button>
                <span class="w-12 text-center" x-text="zoomPersen + '%'"></span>
                <button type="button" x-on:click="perbesarZoom()" class="w-7 h-7 inline-flex items-center justify-center rounded-md border border-slate-300 bg-white hover:bg-slate-50 font-bold" title="Perbesar tampilan">+</button>
                <button type="button" x-on:click="resetZoom()" x-show="zoomPersen !== 100" x-cloak class="ml-1 text-indigo-600 hover:underline">Reset</button>
            </div>

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
                                x-on:click="backupBerjalan = true; backupDetik = 0; clearInterval(_backupInterval); _backupInterval = setInterval(() => backupDetik++, 1000)"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none disabled:opacity-50"
                            >
                                <span wire:loading.remove wire:target="buatBackup">Buat Backup Sekarang</span>
                                <span wire:loading wire:target="buatBackup">Sedang membuat backup...</span>
                            </button>

                            <div x-show="backupBerjalan" x-cloak class="mt-3 max-w-md">
                                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                                    <span>Sedang membuat backup, mohon jangan tutup halaman ini...</span>
                                    <span x-text="backupDetik + ' detik'"></span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden relative">
                                    <div class="absolute inset-y-0 w-1/3 bg-indigo-600 rounded-full animate-backup-progress"></div>
                                </div>
                            </div>
                        </div>

                        {{--
                            Pengaturan Google Drive (BARU, permintaan user
                            2026-10-14) - link folder HANYA utk backup di
                            INSTALASI INI (lihat docblock App\Models\
                            GoogleDriveSetting). Setiap backup baru otomatis
                            ikut terunggah setelah link ini disimpan - tombol
                            "Kirim Sekarang" di bawah HANYA utk uji coba
                            manual memastikan pengaturan sudah benar.
                        --}}
                        <div class="border-t border-slate-200 pt-6">
                            <h3 class="text-lg font-bold text-indigo-900 mb-2">Pengaturan Google Drive</h3>
                            <p class="text-sm text-slate-500 mb-4">
                                Hubungkan akun Google Drive Anda, lalu tempel link folder tujuan (folder boleh folder apa saja milik akun yang terhubung). Setelah itu, SETIAP backup baru yang dibuat (manual maupun otomatis sebelum Restore/Sinkronisasi) langsung ikut terunggah ke folder ini.
                            </p>

                            {{--
                                Status koneksi akun Google (OAuth, GANTI dari
                                Service Account 2026-10-14 - lihat docblock
                                App\Services\GoogleDriveService). Link
                                "Hubungkan" SENGAJA berupa <a href> biasa
                                (BUKAN wire:click) karena harus benar-benar
                                berpindah halaman ke Google.
                            --}}
                            <div class="mb-4 flex flex-wrap items-center gap-3">
                                @if ($pengaturanDrive->sudahTerhubung())
                                    <span class="inline-flex items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-200 rounded-md px-3 py-1.5">
                                        Terhubung sebagai <span class="font-semibold">{{ $pengaturanDrive->email_terhubung ?? '-' }}</span>
                                    </span>
                                    <x-secondary-button
                                        wire:click="putuskanKoneksiDrive"
                                        wire:loading.attr="disabled"
                                        wire:target="putuskanKoneksiDrive"
                                        wire:confirm="Putuskan koneksi Google Drive? Backup baru TIDAK akan terunggah otomatis sampai Anda menghubungkan kembali."
                                    >
                                        Putuskan Koneksi
                                    </x-secondary-button>
                                @else
                                    <a
                                        href="{{ route('google-drive.hubungkan') }}"
                                        class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
                                    >
                                        Hubungkan Google Drive
                                    </a>
                                    <span class="text-xs text-slate-500">Belum terhubung ke akun Google mana pun.</span>
                                @endif
                            </div>

                            <form wire:submit="simpanLinkDrive" class="flex flex-col sm:flex-row gap-3 sm:items-start">
                                <div class="flex-1 w-full">
                                    <x-text-input wire:model="linkDriveBaru" type="text" class="w-full" placeholder="https://drive.google.com/drive/folders/..." />
                                    <x-input-error :messages="$errors->get('linkDriveBaru')" class="mt-1" />
                                </div>
                                <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="simpanLinkDrive">
                                    Simpan
                                </x-primary-button>
                            </form>

                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                <x-secondary-button wire:click="kirimSekarangKeGoogleDrive" wire:loading.attr="disabled" wire:target="kirimSekarangKeGoogleDrive">
                                    <span wire:loading.remove wire:target="kirimSekarangKeGoogleDrive">Kirim Sekarang (uji coba)</span>
                                    <span wire:loading wire:target="kirimSekarangKeGoogleDrive">Mengirim...</span>
                                </x-secondary-button>

                                {{--
                                    Tombol "Urutkan Ulang Nomor File di Drive"
                                    (permintaan EKSPLISIT user 2026-10-14,
                                    jawaban AskUserQuestion "Beri nomor ulang
                                    ke SEMUA file yang sudah ada juga") - lihat
                                    App\Livewire\Backup\Index::urutkanUlangNomorDrive().
                                    Hanya muncul kalau sudah terhubung & folder
                                    terisi (sama seperti "Kirim Sekarang").
                                --}}
                                @if ($pengaturanDrive->sudahDikonfigurasi())
                                    <x-secondary-button
                                        wire:click="urutkanUlangNomorDrive"
                                        wire:loading.attr="disabled"
                                        wire:target="urutkanUlangNomorDrive"
                                        wire:confirm="Urutkan ulang nomor SEMUA file di folder Google Drive ini (file lama maupun baru)? Ini hanya mengganti NAMA file di Drive, isi file tidak berubah."
                                    >
                                        <span wire:loading.remove wire:target="urutkanUlangNomorDrive">Urutkan Ulang Nomor File di Drive</span>
                                        <span wire:loading wire:target="urutkanUlangNomorDrive">Mengurutkan ulang...</span>
                                    </x-secondary-button>
                                @endif

                                @if ($pengaturanDrive->terakhir_kirim_pada)
                                    <span class="text-xs text-slate-500">
                                        Terakhir dikirim {{ $pengaturanDrive->terakhir_kirim_pada->diffForHumans() }} -
                                        <span class="font-semibold {{ $pengaturanDrive->terakhir_kirim_berhasil ? 'text-green-700' : 'text-red-700' }}">
                                            {{ $pengaturanDrive->terakhir_kirim_berhasil ? 'Berhasil' : 'Gagal' }}
                                        </span>
                                        @if (! $pengaturanDrive->terakhir_kirim_berhasil && $pengaturanDrive->pesan_error_terakhir)
                                            ({{ $pengaturanDrive->pesan_error_terakhir }})
                                        @endif
                                    </span>
                                @endif
                            </div>
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
                                        Filter tanggal SPESIFIK (permintaan user
                                        2026-10-14 "pencarian berdasarkan tanggal
                                        backup") - BERLAKU BERSAMA filter Tahun di
                                        atas, lihat App\Livewire\Backup\Index::
                                        $filterTanggalBackup.
                                    --}}
                                    <x-input-label for="filterTanggalBackup" value="Tanggal" class="text-xs text-slate-500" />
                                    <input type="date" wire:model.live="filterTanggalBackup" id="filterTanggalBackup" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm" />
                                    @if ($filterTanggalBackup !== '')
                                        <button type="button" wire:click="$set('filterTanggalBackup', '')" class="text-slate-400 hover:text-red-600" title="Hapus filter tanggal">&times;</button>
                                    @endif

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
                                                    @if ($item->diunggahDariPc())
                                                        <span class="ml-1 inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full bg-blue-100 text-blue-700" title="Diunggah manual oleh Superadmin dari Laptop/PC, bukan dibuat oleh aplikasi ini">Diunggah dari PC</span>
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

                        {{-- "Restore dari File di Laptop/PC" (BARU, permintaan user 2026-10-18)
                             - lihat App\Services\BackupService::simpanUnggahan(). File yang
                             diunggah di sini TIDAK langsung di-restore, cukup disimpan dulu
                             sebagai 1 baris Backup biasa (muncul di tabel "Pilih Backup untuk
                             Di-Restore" di bawah, ditandai badge "Diunggah dari PC") - proses
                             Restore sesungguhnya tetap lewat tombol "Restore dari File Ini" &
                             konfirmasi ketik-ulang-nama-file yang SAMA seperti backup biasa. --}}
                        <div class="p-4 bg-white border border-slate-200 rounded-lg">
                            <h3 class="text-lg font-bold text-indigo-900 mb-1">Atau Unggah File Backup dari Laptop/PC</h3>
                            <p class="text-sm text-slate-500 mb-3">
                                Punya file backup (.zip) hasil unduhan sebelumnya yang tersimpan di komputer Anda (misalnya dari instalasi lain, atau
                                sebelum server/riwayat backup ini pernah hilang)? Unggah di sini - file ini akan ikut tersimpan di Riwayat Backup (ditandai
                                "Diunggah dari PC") & muncul di daftar di bawah untuk di-restore. Ukuran maksimal 500MB, format .zip.
                            </p>

                            <form wire:submit="uploadBackupDariPc" class="flex flex-wrap items-end gap-3">
                                <div>
                                    <input type="file" wire:model="fileBackupUnggahan" accept=".zip" class="block text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                    <div wire:loading wire:target="fileBackupUnggahan" class="text-xs text-slate-400 mt-1">Mengunggah ke server...</div>
                                    <x-input-error :messages="$errors->get('fileBackupUnggahan')" class="mt-1" />
                                </div>
                                <button
                                    type="submit"
                                    wire:loading.attr="disabled"
                                    wire:target="uploadBackupDariPc,fileBackupUnggahan"
                                    @disabled(! $fileBackupUnggahan)
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none disabled:opacity-50"
                                >
                                    <span wire:loading.remove wire:target="uploadBackupDariPc">Unggah File Ini</span>
                                    <span wire:loading wire:target="uploadBackupDariPc">Menyimpan...</span>
                                </button>
                            </form>
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                                <h3 class="text-lg font-bold text-indigo-900">Pilih Backup untuk Di-Restore (berdasarkan Tahun / Tanggal)</h3>

                                <div class="flex items-center gap-2">
                                    <x-input-label for="restoreTahun" value="Tahun" class="text-xs text-slate-500" />
                                    <select wire:model.live="restoreTahun" id="restoreTahun" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                        @foreach ($daftarTahun as $tahun)
                                            <option value="{{ $tahun }}">{{ $tahun }}</option>
                                        @endforeach
                                    </select>

                                    <x-input-label for="restoreTanggalBackup" value="Tanggal" class="text-xs text-slate-500" />
                                    <input type="date" wire:model.live="restoreTanggalBackup" id="restoreTanggalBackup" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm" />
                                    @if ($restoreTanggalBackup !== '')
                                        <button type="button" wire:click="$set('restoreTanggalBackup', '')" class="text-slate-400 hover:text-red-600" title="Hapus filter tanggal">&times;</button>
                                    @endif
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
                                                    @if ($item->diunggahDariPc())
                                                        <span class="ml-1 inline-flex px-2 py-0.5 text-[10px] font-semibold rounded-full bg-blue-100 text-blue-700" title="Diunggah manual oleh Superadmin dari Laptop/PC, bukan dibuat oleh aplikasi ini">Diunggah dari PC</span>
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
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                                <h3 class="text-lg font-bold text-indigo-900">Riwayat Restore Terakhir</h3>

                                <div class="flex items-center gap-2">
                                    <x-input-label for="filterTanggalRestoreLog" value="Tanggal" class="text-xs text-slate-500" />
                                    <input type="date" wire:model.live="filterTanggalRestoreLog" id="filterTanggalRestoreLog" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm" />
                                    @if ($filterTanggalRestoreLog !== '')
                                        <button type="button" wire:click="$set('filterTanggalRestoreLog', '')" class="text-slate-400 hover:text-red-600" title="Hapus filter tanggal">&times;</button>
                                    @endif
                                </div>
                            </div>
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

    {{--
        Modal konfirmasi Restore (ketik ulang nama file) - progress bar
        animasi (permintaan user 2026-10-14) PUNYA x-data SENDIRI (lokal
        di modal ini saja), meniru pola persis modal Sinkronisasi/Tarik
        Data di resources/views/livewire/sinkronisasi/index.blade.php.
    --}}
    <x-modal name="backup-konfirmasi-restore" :show="$confirmingRestoreId !== null" maxWidth="md">
        <div
            class="p-6"
            x-data="{ restoreBerjalan: false, restoreDetik: 0, _restoreInterval: null }"
            x-on:restore-proses-selesai.window="restoreBerjalan = false; clearInterval(_restoreInterval)"
        >
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

            <div x-show="restoreBerjalan" x-cloak class="mt-4">
                <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                    <span>Sedang menjalankan Restore, mohon jangan tutup halaman ini...</span>
                    <span x-text="restoreDetik + ' detik'"></span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden relative">
                    <div class="absolute inset-y-0 w-1/3 bg-red-600 rounded-full animate-backup-progress"></div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalRestore" wire:loading.attr="disabled" wire:target="jalankanRestore">Batal</x-secondary-button>
                <x-primary-button
                    wire:click="jalankanRestore"
                    wire:loading.attr="disabled"
                    wire:target="jalankanRestore"
                    x-on:click="restoreBerjalan = true; restoreDetik = 0; clearInterval(_restoreInterval); _restoreInterval = setInterval(() => restoreDetik++, 1000)"
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

    {{--
        Animasi progress bar "Buat Backup Sekarang" & "Ya, Restore
        Sekarang" (permintaan user 2026-10-14) - pola/nilai animasi SAMA
        dengan Tab Sinkronisasi, tapi dgn nama keyframe/class SENDIRI
        (backup-progress-slide / animate-backup-progress) supaya file
        Blade ini tetap mandiri (tidak bergantung pada <style> di view
        Livewire lain).
    --}}
    <style>
        @keyframes backup-progress-slide {
            0%   { left: -33%; }
            100% { left: 100%; }
        }
        .animate-backup-progress {
            animation: backup-progress-slide 1.1s ease-in-out infinite;
        }
    </style>
</div>
