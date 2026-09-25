<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Format Surat Rekomendasi & Pembatalan TPG') }}
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

            @if ($errors->has('umum'))
                <div class="p-4 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-sm">
                    {{ $errors->first('umum') }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                {{--
                    Tab "Surat Rekomendasi TPG" / "Surat Penghentian TPG" / "Surat
                    Pernyataan" (tab 3 BARU round kedua puluh satu) - lihat
                    App\Livewire\PendataanOps\SuratTpg\Index.

                    Perbaikan 2026-09-24 (round kedua puluh empat, poin 2): setiap
                    tab diberi warna aktif BERBEDA (sebelumnya sama-sama
                    indigo) - Rekomendasi=indigo, Penghentian=amber,
                    Pernyataan=emerald - supaya user bisa membedakan tab mana
                    yang sedang aktif lebih cepat secara visual.
                --}}
                <div class="border-b border-slate-200 px-4 sm:px-8 pt-4">
                    <nav class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            wire:click="pindahTab('rekomendasi')"
                            @class([
                                'px-4 py-2.5 rounded-t-xl text-sm font-bold tracking-wide transition-all duration-150 border-b-4',
                                'bg-white text-indigo-700 border-indigo-500 shadow-sm' => $tabAktif === 'rekomendasi',
                                'bg-slate-50 text-slate-500 border-transparent hover:bg-slate-100 hover:text-slate-700' => $tabAktif !== 'rekomendasi',
                            ])
                        >
                            Surat Rekomendasi TPG
                        </button>
                        <button
                            type="button"
                            wire:click="pindahTab('penghentian')"
                            @class([
                                'px-4 py-2.5 rounded-t-xl text-sm font-bold tracking-wide transition-all duration-150 border-b-4',
                                'bg-white text-amber-700 border-amber-500 shadow-sm' => $tabAktif === 'penghentian',
                                'bg-slate-50 text-slate-500 border-transparent hover:bg-slate-100 hover:text-slate-700' => $tabAktif !== 'penghentian',
                            ])
                        >
                            Surat Penghentian TPG
                        </button>
                        <button
                            type="button"
                            wire:click="pindahTab('pernyataan')"
                            @class([
                                'px-4 py-2.5 rounded-t-xl text-sm font-bold tracking-wide transition-all duration-150 border-b-4',
                                'bg-white text-emerald-700 border-emerald-500 shadow-sm' => $tabAktif === 'pernyataan',
                                'bg-slate-50 text-slate-500 border-transparent hover:bg-slate-100 hover:text-slate-700' => $tabAktif !== 'pernyataan',
                            ])
                        >
                            Surat Pernyataan
                        </button>
                    </nav>
                </div>

                <div class="p-4 sm:p-8">
                    <div class="flex flex-col xl:flex-row xl:flex-wrap xl:items-start xl:justify-between gap-3 mb-6">
                        <div class="flex flex-wrap sm:flex-row sm:items-center gap-2.5">
                            <label for="tahun" class="text-sm text-slate-600">Tahun</label>
                            <select wire:model.live="tahun" id="tahun" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">
                                @foreach ($tahunOptions as $opsiTahun)
                                    <option value="{{ $opsiTahun }}">{{ $opsiTahun }}</option>
                                @endforeach
                            </select>

                            <label for="triwulan" class="text-sm text-slate-600">Triwulan</label>
                            <select wire:model.live="triwulan" id="triwulan" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">
                                @foreach ($triwulanOptions as $nomorTriwulan => $labelTriwulanOpsi)
                                    <option value="{{ $nomorTriwulan }}">{{ $labelTriwulanOpsi }}</option>
                                @endforeach
                            </select>

                            @if ($bolehKelolaSemua)
                                <select wire:model.live="profil_sekolah_id" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">
                                    <option value="">-- Pilih Sekolah --</option>
                                    @foreach ($sekolahOptions as $opsiSekolah)
                                        <option value="{{ $opsiSekolah->id }}">{{ $opsiSekolah->nama_sekolah }}</option>
                                    @endforeach
                                </select>
                            @endif

                            <x-zoom-controls :zoom="$zoomPercent" />
                        </div>

                        <div class="flex flex-wrap sm:flex-row sm:items-center gap-2.5">
                            <label for="jenis_kertas" class="text-sm text-slate-600">Jenis Kertas</label>
                            <select wire:model.live="jenisKertas" id="jenis_kertas" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">
                                @foreach ($kertasOptions as $nilaiKertas => $labelKertas)
                                    <option value="{{ $nilaiKertas }}">{{ $labelKertas }}</option>
                                @endforeach
                            </select>

                            <x-secondary-button wire:click="toggleSettingMargin" wire:loading.attr="disabled" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                <x-icon name="cog" class="w-3.5 h-3.5 mr-1" />
                                Setting Margin
                            </x-secondary-button>

                            @if ($urlCetak)
                                <a href="{{ $urlCetak }}" target="_blank" rel="noopener" class="inline-flex items-center px-2.5 py-1.5 bg-white border border-slate-300 rounded-md font-semibold text-[10px] text-slate-700 uppercase tracking-widest shadow-sm hover:bg-slate-50 whitespace-nowrap">
                                    <x-icon name="printer" class="w-3.5 h-3.5 mr-1" />
                                    Cetak
                                </a>
                            @endif

                            <x-secondary-button wire:click="exportPdf" wire:loading.attr="disabled" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                <x-icon name="download" class="w-3.5 h-3.5 mr-1" />
                                Unduh PDF
                            </x-secondary-button>
                            <x-secondary-button wire:click="exportWord" wire:loading.attr="disabled" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                <x-icon name="download" class="w-3.5 h-3.5 mr-1" />
                                Unduh Word
                            </x-secondary-button>
                        </div>
                    </div>

                    {{--
                        Toolbar "Cetak Semua"/"Unduh PDF Semua"/"Unduh Word
                        Semua" (round kedua puluh satu) DIPINDAHKAN ke menu
                        Unduhan round kedua puluh dua (permintaan user poin 3:
                        "untuk cetak Gabungan ketiga surat (irit kertas) di
                        pindah ke menu Unduhan berdasarkan triwulan dan
                        tahun") - lihat
                        resources/views/livewire/pendataan-ops/unduhan/index.blade.php
                        & App\Livewire\PendataanOps\Unduhan\Index. Tombol
                        Cetak/Unduh PDF/Unduh Word PER-TAB di atas TIDAK
                        berubah.
                    --}}

                    @if ($tampilSettingMargin)
                        <div class="mb-6 -mt-3 bg-slate-50 border border-slate-200 rounded-lg p-4 text-xs">
                            <p class="font-semibold text-slate-700 mb-3">Margin Halaman (cm) - berlaku untuk PDF, Word, & Cetak</p>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 max-w-xl">
                                <div>
                                    <label for="margin_kiri" class="block mb-1 text-slate-500">Left</label>
                                    <input type="number" step="0.1" min="0.5" max="5" wire:model.live="marginKiri" id="margin_kiri" class="w-full text-xs rounded px-2 py-1.5 border border-slate-300 focus:ring-1 focus:outline-none focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="margin_kanan" class="block mb-1 text-slate-500">Right</label>
                                    <input type="number" step="0.1" min="0.5" max="5" wire:model.live="marginKanan" id="margin_kanan" class="w-full text-xs rounded px-2 py-1.5 border border-slate-300 focus:ring-1 focus:outline-none focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="margin_atas" class="block mb-1 text-slate-500">Top</label>
                                    <input type="number" step="0.1" min="0.5" max="5" wire:model.live="marginAtas" id="margin_atas" class="w-full text-xs rounded px-2 py-1.5 border border-slate-300 focus:ring-1 focus:outline-none focus:border-blue-500">
                                </div>
                                <div>
                                    <label for="margin_bawah" class="block mb-1 text-slate-500">Bottom</label>
                                    <input type="number" step="0.1" min="0.5" max="5" wire:model.live="marginBawah" id="margin_bawah" class="w-full text-xs rounded px-2 py-1.5 border border-slate-300 focus:ring-1 focus:outline-none focus:border-blue-500">
                                </div>
                            </div>
                            <x-secondary-button wire:click="toggleSettingMargin" class="mt-3 !text-[10px]">Tutup</x-secondary-button>
                        </div>
                    @endif

                    @if (! $sekolah)
                        <div class="p-6 text-center text-slate-400 border border-dashed border-slate-300 rounded-lg">
                            @if ($bolehKelolaSemua)
                                Pilih sekolah terlebih dahulu pada filter di atas untuk menampilkan & mengisi surat.
                            @else
                                Akun Anda belum terhubung ke data sekolah manapun.
                            @endif
                        </div>
                    @else
                        <x-input-error :messages="$errors->get('tanggalSurat')" class="mb-2" />

                        <div class="relative border border-slate-300 rounded-lg p-5 sm:p-8 text-sm bg-white" style="zoom: {{ $zoomPercent }}%;">
                            @if ($tabAktif === 'penghentian')
                                @include('pdf.partials.surat-tpg-kop')
                                @include('pdf.partials.surat-tpg-penghentian-isi', ['editable' => true])
                            @elseif ($tabAktif === 'pernyataan')
                                {{-- Kop Surat SENGAJA tidak disertakan pada tab ini
                                     (round kedua puluh dua, permintaan user "hapus
                                     kop surat nya"). --}}
                                @include('pdf.partials.surat-tpg-pernyataan-isi', ['editable' => true])
                            @else
                                @include('pdf.partials.surat-tpg-kop')
                                @include('pdf.partials.surat-tpg-rekomendasi-isi', ['editable' => true])
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
