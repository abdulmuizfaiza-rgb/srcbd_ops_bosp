<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Unduhan') }}
    </h2>
</x-slot>

<div>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                {{-- Tab Triwulan --}}
                <div class="border-b border-slate-200 px-4 sm:px-8 pt-4 pb-4">
                    <x-tab-triwulan :options="$triwulanOptions" :active="$triwulan" prefix="Data" />
                </div>

                <div class="p-4 sm:p-8 space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-slate-900">Unduhan Lampiran 2a, 2b &amp; 2c</h3>
                        <p class="text-sm text-slate-500 mt-1">
                            Pilih Triwulan (tab di atas) &amp; Tahun, lalu unduh gabungan data Lampiran 2a, 2b, dan
                            2c untuk pilihan tersebut - siap pakai dalam format Excel (3 sheet) atau PDF.
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                        <div>
                            <x-input-label for="tahun" value="Tahun" />
                            <select wire:model.live="tahun" id="tahun" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full sm:w-40">
                                @foreach ($tahunOptions as $th)
                                    <option value="{{ $th }}">{{ $th }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="text-sm text-slate-500">
                            Data untuk: <span class="font-medium text-slate-700">{{ $namaSekolahTampil }}</span>
                            @if ($bolehKelolaSemua)
                                <span class="block text-xs text-slate-400 mt-0.5">
                                    Superadmin selalu mengunduh gabungan data seluruh sekolah yang terdaftar
                                    (diurutkan Status &rarr; Kecamatan &rarr; Nama Sekolah).
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="rounded-lg border border-slate-200 p-3 text-center">
                            <div class="text-xs text-slate-400">Lampiran 2a</div>
                            <div class="text-xl font-semibold text-slate-800">{{ $jumlah2a }}</div>
                            <div class="text-xs text-slate-400">baris data</div>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-3 text-center">
                            <div class="text-xs text-slate-400">Lampiran 2b</div>
                            <div class="text-xl font-semibold text-slate-800">{{ $jumlah2b }}</div>
                            <div class="text-xs text-slate-400">baris data</div>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-3 text-center">
                            <div class="text-xs text-slate-400">Lampiran 2c</div>
                            <div class="text-xl font-semibold text-slate-800">{{ $jumlah2c }}</div>
                            <div class="text-xs text-slate-400">baris data</div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-slate-100">
                        <x-primary-button wire:click="unduhExcel" wire:loading.attr="disabled" wire:target="unduhExcel" class="whitespace-nowrap">
                            <x-icon name="download" class="w-4 h-4 mr-1.5" />
                            Unduh Excel
                        </x-primary-button>

                        <x-secondary-button wire:click="unduhPdf" wire:loading.attr="disabled" wire:target="unduhPdf" class="whitespace-nowrap">
                            <x-icon name="download" class="w-4 h-4 mr-1.5" />
                            Unduh PDF
                        </x-secondary-button>

                        <span wire:loading wire:target="unduhExcel,unduhPdf" class="text-xs text-slate-400">Menyiapkan file...</span>
                    </div>
                </div>
            </div>

            {{--
                "Cetak Surat Rekomendasi, Surat Penghentian TPG dan Surat
                Pernyataan" (round kedua puluh dua) - DIPINDAHKAN dari menu
                "Format Surat Rekomendasi & Pembatalan TPG" (permintaan
                user poin 3 & 4). Memakai Triwulan (tab) & Tahun yang SAMA
                dengan bagian Lampiran 2a/2b/2c di atas. Superadmin WAJIB
                pilih sekolah dulu lewat dropdown khusus di bawah (surat
                TPG itu satu surat = satu sekolah tertentu, TIDAK bisa
                digabung semua sekolah seperti rekap Lampiran) - lihat
                docblock App\Livewire\PendataanOps\Unduhan\Index.

                Round kedua puluh tiga: ditambah kontrol Jenis Kertas/
                Setting Margin sendiri (meniru pola & default menu asal
                persis) supaya user bisa menyamakan hasil cetak/unduh di
                sini dgn menu "Format Surat Rekomendasi & Pembatalan TPG".
            --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="p-4 sm:p-8 space-y-4">
                    <div>
                        <h3 class="text-lg font-medium text-slate-900">Cetak Surat Rekomendasi, Surat Penghentian TPG dan Surat Pernyataan</h3>
                        <p class="text-sm text-slate-500 mt-1">
                            Menggunakan Triwulan &amp; Tahun yang sama seperti pilihan di atas. Ketiga surat digabung
                            dalam 1 dokumen/1 kali cetak (masing-masing tetap 1 halaman penuh, dipisah oleh page-break).
                        </p>
                    </div>

                    @if ($errors->has('suratTpgUmum'))
                        <div class="p-3 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-xs">
                            {{ $errors->first('suratTpgUmum') }}
                        </div>
                    @endif

                    @if ($bolehKelolaSemua)
                        <div>
                            <x-input-label for="surat_tpg_sekolah" value="Pilih Sekolah" />
                            <select wire:change="pilihSekolahSuratTpg($event.target.value ? $event.target.value : null)" id="surat_tpg_sekolah" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full sm:w-80">
                                <option value="" @selected(! $suratTpgProfilSekolahId)>-- Pilih Sekolah --</option>
                                @foreach ($sekolahOptionsSuratTpg as $opsiSekolah)
                                    <option value="{{ $opsiSekolah->id }}" @selected($suratTpgProfilSekolahId === $opsiSekolah->id)>{{ $opsiSekolah->nama_sekolah }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-2.5 pt-2 border-t border-slate-100">
                        <label for="surat_tpg_jenis_kertas" class="text-sm text-slate-600">Jenis Kertas</label>
                        <select wire:model.live="suratTpgJenisKertas" id="surat_tpg_jenis_kertas" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">
                            @foreach ($kertasOptionsSuratTpg as $nilaiKertas => $labelKertas)
                                <option value="{{ $nilaiKertas }}">{{ $labelKertas }}</option>
                            @endforeach
                        </select>

                        <x-secondary-button wire:click="toggleSuratTpgSettingMargin" wire:loading.attr="disabled" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                            <x-icon name="cog" class="w-3.5 h-3.5 mr-1" />
                            Setting Margin
                        </x-secondary-button>
                    </div>

                    @if ($suratTpgTampilSettingMargin)
                        <div class="-mt-1 bg-slate-50 border border-slate-200 rounded-lg p-4 text-xs">
                            <p class="font-semibold text-slate-700 mb-3">Margin Halaman (cm) - berlaku untuk PDF, Word, &amp; Cetak</p>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 max-w-xl">
                                <div>
                                    <label for="surat_tpg_margin_kiri" class="block mb-1 text-slate-500">Left</label>
                                    <input type="number" step="0.1" min="0.5" max="5" wire:model.live="suratTpgMarginKiri" id="surat_tpg_margin_kiri" class="w-full text-xs rounded px-2 py-1.5 border border-slate-300 focus:ring-1 focus:outline-none focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="surat_tpg_margin_kanan" class="block mb-1 text-slate-500">Right</label>
                                    <input type="number" step="0.1" min="0.5" max="5" wire:model.live="suratTpgMarginKanan" id="surat_tpg_margin_kanan" class="w-full text-xs rounded px-2 py-1.5 border border-slate-300 focus:ring-1 focus:outline-none focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="surat_tpg_margin_atas" class="block mb-1 text-slate-500">Top</label>
                                    <input type="number" step="0.1" min="0.5" max="5" wire:model.live="suratTpgMarginAtas" id="surat_tpg_margin_atas" class="w-full text-xs rounded px-2 py-1.5 border border-slate-300 focus:ring-1 focus:outline-none focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="surat_tpg_margin_bawah" class="block mb-1 text-slate-500">Bottom</label>
                                    <input type="number" step="0.1" min="0.5" max="5" wire:model.live="suratTpgMarginBawah" id="surat_tpg_margin_bawah" class="w-full text-xs rounded px-2 py-1.5 border border-slate-300 focus:ring-1 focus:outline-none focus:border-blue-500">
                                </div>
                            </div>
                            <x-secondary-button wire:click="toggleSuratTpgSettingMargin" class="mt-3 !text-[10px]">Tutup</x-secondary-button>
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-slate-100">
                        @if ($urlCetakSuratTpgGabungan)
                            <a href="{{ $urlCetakSuratTpgGabungan }}" target="_blank" rel="noopener" class="inline-flex items-center px-4 py-2 bg-white border border-slate-300 rounded-md font-semibold text-xs text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50 whitespace-nowrap">
                                <x-icon name="printer" class="w-4 h-4 mr-1.5" />
                                Cetak
                            </a>
                        @endif

                        <x-secondary-button wire:click="unduhSuratTpgGabunganPdf" wire:loading.attr="disabled" wire:target="unduhSuratTpgGabunganPdf" class="whitespace-nowrap">
                            <x-icon name="download" class="w-4 h-4 mr-1.5" />
                            Unduh PDF
                        </x-secondary-button>

                        <x-secondary-button wire:click="unduhSuratTpgGabunganWord" wire:loading.attr="disabled" wire:target="unduhSuratTpgGabunganWord" class="whitespace-nowrap">
                            <x-icon name="download" class="w-4 h-4 mr-1.5" />
                            Unduh Word
                        </x-secondary-button>

                        <span wire:loading wire:target="unduhSuratTpgGabunganPdf,unduhSuratTpgGabunganWord" class="text-xs text-slate-400">Menyiapkan file...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
