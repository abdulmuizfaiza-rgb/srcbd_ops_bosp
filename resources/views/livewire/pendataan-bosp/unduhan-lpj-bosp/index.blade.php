<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Unduhan LPJ BOSP') }}
    </h2>
</x-slot>

<div>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                {{-- Tab Triwulan --}}
                <div class="border-b border-slate-200 px-4 sm:px-8 pt-4 pb-4">
                    <x-tab-triwulan :options="$triwulanOptions" :active="$triwulan" prefix="LPJ" />
                </div>

                <div class="p-4 sm:p-8 space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-slate-900">Unduhan LPJ BOSP (Gabungan 1 File Excel)</h3>
                        <p class="text-sm text-slate-500 mt-1">
                            Pilih Triwulan (tab di atas) &amp; Tahun, lalu unduh 1 file Excel berisi seluruh laporan
                            LPJ BOSP untuk Triwulan &amp; Tahun tersebut (Penerimaan Honor PTK, Langganan Daya &amp;
                            Jasa, Rincian Pemeliharaan Bangunan/PC, Biaya Pendaftaran Lomba, Belanja Honor Kegiatan,
                            Rincian Belanja Modal, BMD, Rincian Belanja Barang Habis Pakai, Stock Opname, Pajak BOSP
                            Reguler, Laporan Realisasi BOSP, dan Formulir BOS K7b/K7c).
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                        <div>
                            <x-input-label for="tahun" value="Tahun Anggaran" />
                            {{-- Dropdown HANYA tampil utk Superadmin - Admin BOSP dikunci total ke tahun sesi login mereka (pola sama seperti menu lain Pendataan BOSP). --}}
                            @if ($bolehKelolaSemua)
                                <select wire:model.live="tahun" id="tahun" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full sm:w-40">
                                    @foreach ($tahunOptions as $th)
                                        <option value="{{ $th }}">{{ $th }}</option>
                                    @endforeach
                                </select>
                            @else
                                <span id="tahun" class="mt-1 inline-flex items-center rounded-md bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-700">{{ $tahun }}</span>
                            @endif
                        </div>

                        @if ($bolehKelolaSemua)
                            <div>
                                <x-input-label for="filter_sekolah_lpj" value="Sekolah" />
                                <select wire:model.live="filterSekolahLpj" id="filter_sekolah_lpj" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full sm:w-64">
                                    <option value="">Semua Sekolah</option>
                                    @foreach ($sekolahOptions as $opsiSekolah)
                                        <option value="{{ $opsiSekolah->id }}">{{ $opsiSekolah->nama_sekolah }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="text-sm text-slate-500">
                            File untuk: <span class="font-medium text-slate-700">{{ $namaSekolahTampil }}</span>
                        </div>
                    </div>

                    @if ($bolehKelolaSemua && ! $filterSekolahLpj)
                        <div class="rounded-md bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-800">
                            Mode <span class="font-semibold">Semua Sekolah</span> dipilih: file akan berisi tabel
                            gabungan seluruh sekolah untuk setiap sheet. Sheet <span class="font-semibold">Formulir
                            BOS K7b</span> &amp; <span class="font-semibold">Formulir BOS K7c</span> otomatis
                            dilewati pada mode ini (formulir tersebut hanya berlaku untuk 1 sekolah tertentu) - pilih
                            1 sekolah pada dropdown di atas untuk menyertakan kedua sheet itu.
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-3 pt-2 border-t border-slate-100">
                        <x-primary-button wire:click="unduhExcel" wire:loading.attr="disabled" wire:target="unduhExcel" class="whitespace-nowrap">
                            <x-icon name="download" class="w-4 h-4 mr-1.5" />
                            Unduh Excel LPJ BOSP
                        </x-primary-button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
