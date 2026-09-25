<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Pajak BOSP Reguler') }}
    </h2>
</x-slot>

@php
    $labelPajak = \App\Models\PajakBospReguler::LABEL_PAJAK;
@endphp

<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errorExport)
                <div class="p-4 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-sm">
                    {{ $errorExport }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                {{--
                    Tab 1 "Pajak Per Sekolah" / Tab 2 "Rekapitulasi Pajak Seluruh
                    Sekolah" (Tab 2 HANYA Superadmin, sesuai jawaban
                    AskUserQuestion 2026-09-11 "1 baris per sekolah, total
                    setahun").

                    Perbaikan 2026-09-24 (round kedua puluh empat, poin 3): setiap
                    tab diberi warna aktif BERBEDA - Pajak BOSP
                    Reguler=indigo, Rekapitulasi=violet.
                --}}
                <div class="border-b border-slate-200 px-4 sm:px-8 pt-4">
                    <nav class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            wire:click="pindahTab('per_sekolah')"
                            @class([
                                'px-4 py-2.5 rounded-t-xl text-sm font-bold tracking-wide transition-all duration-150 border-b-4',
                                'bg-white text-indigo-700 border-indigo-500 shadow-sm' => $tab === 'per_sekolah',
                                'bg-slate-50 text-slate-500 border-transparent hover:bg-slate-100 hover:text-slate-700' => $tab !== 'per_sekolah',
                            ])
                        >
                            Pajak BOSP Reguler
                        </button>
                        @if ($bolehKelolaSemua)
                            <button
                                type="button"
                                wire:click="pindahTab('rekap')"
                                @class([
                                    'px-4 py-2.5 rounded-t-xl text-sm font-bold tracking-wide transition-all duration-150 border-b-4',
                                    'bg-white text-violet-700 border-violet-500 shadow-sm' => $tab === 'rekap',
                                    'bg-slate-50 text-slate-500 border-transparent hover:bg-slate-100 hover:text-slate-700' => $tab !== 'rekap',
                                ])
                            >
                                Rekapitulasi Pajak Seluruh Sekolah
                            </button>
                        @endif
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

                            @if ($tab === 'per_sekolah' && $bolehKelolaSemua)
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
                            @if ($tab === 'per_sekolah')
                                <x-secondary-button wire:click="exportExcel" wire:loading.attr="disabled" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="download" class="w-3.5 h-3.5 mr-1" />
                                    Excel
                                </x-secondary-button>
                                <x-secondary-button wire:click="exportPdf" wire:loading.attr="disabled" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="download" class="w-3.5 h-3.5 mr-1" />
                                    PDF
                                </x-secondary-button>
                            @else
                                <x-secondary-button wire:click="exportExcelRekap" wire:loading.attr="disabled" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="download" class="w-3.5 h-3.5 mr-1" />
                                    Excel
                                </x-secondary-button>
                                <x-secondary-button wire:click="exportPdfRekap" wire:loading.attr="disabled" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="download" class="w-3.5 h-3.5 mr-1" />
                                    PDF
                                </x-secondary-button>
                            @endif
                        </div>
                    </div>

                    @if ($tab === 'per_sekolah')
                        {{-- ============ TAB 1: PAJAK PER SEKOLAH ============ --}}
                        @if (! $sekolah)
                            <div class="p-6 text-center text-slate-400 border border-dashed border-slate-300 rounded-lg">
                                @if ($bolehKelolaSemua)
                                    Pilih sekolah terlebih dahulu pada filter di atas untuk menampilkan & mengisi data Pajak BOSP Reguler.
                                @else
                                    Akun Anda belum terhubung ke data sekolah manapun.
                                @endif
                            </div>
                        @else
                            <div class="text-center mb-4">
                                <h3 class="font-bold text-slate-800 text-sm tracking-wide">REKAPITULASI PAJAK REGULER DAN PAJAK DAERAH</h3>
                                <p class="font-semibold text-slate-700 text-xs">DANA BANTUAN OPERASIONAL SEKOLAH (BOS)</p>
                                <p class="text-slate-500 text-xs">{{ $sekolah->nama_sekolah }} - PERIODE JANUARI-DESEMBER TAHUN ANGGARAN {{ $tahun }}</p>
                            </div>

                            {{-- Tabel 1: 12 baris TETAP (1 baris = 1 bulan), input
                                 langsung di kotak (CRUD: isi kotak = Create/Update,
                                 tombol Hapus per baris = Delete). Jumlah & Saldo
                                 SELALU read-only hasil rumus (lihat
                                 PajakBospReguler::hitungJumlahDebit()/hitungJumlahKredit()/
                                 hitungSaldoBerjalan()). --}}
                            <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg mb-8" style="max-height: 36rem; zoom: {{ $zoomPercent }}%;">
                                <table class="min-w-full divide-y divide-slate-200 text-xs">
                                    <thead class="sticky top-0 z-10 bg-slate-50">
                                        <tr class="text-center text-slate-500">
                                            <th rowspan="2" class="px-2 py-2 border-r border-slate-200">No</th>
                                            <th rowspan="2" class="px-2 py-2 border-r border-slate-200">Bulan</th>
                                            <th colspan="6" class="px-2 py-2 border-r border-slate-200 bg-sky-50">PENERIMAAN / DEBIT</th>
                                            <th colspan="6" class="px-2 py-2 border-r border-slate-200 bg-rose-50">PENGELUARAN / KREDIT</th>
                                            <th rowspan="2" class="px-2 py-2">Saldo</th>
                                            <th rowspan="2" class="px-2 py-2">Aksi</th>
                                        </tr>
                                        <tr class="text-center text-slate-500">
                                            @foreach (['ppn_debit', 'pph21_debit', 'pph23_debit', 'pph4_debit', 'sspd_debit'] as $f)
                                                <th class="px-2 py-2 bg-sky-50/60 whitespace-nowrap">{{ $labelPajak[str_replace('_debit', '', $f)] }}</th>
                                            @endforeach
                                            <th class="px-2 py-2 border-r border-slate-200 bg-sky-50/60 font-bold">Jumlah</th>
                                            @foreach (['ppn_kredit', 'pph21_kredit', 'pph23_kredit', 'pph4_kredit', 'sspd_kredit'] as $f)
                                                <th class="px-2 py-2 bg-rose-50/60 whitespace-nowrap">{{ $labelPajak[str_replace('_kredit', '', $f)] }}</th>
                                            @endforeach
                                            <th class="px-2 py-2 border-r border-slate-200 bg-rose-50/60 font-bold">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($bulanOptions as $bulan => $labelBulan)
                                            @php $revisi = $revisiBaris[$bulan] ?? 0; $terkunciBulanIni = $terkunciPerBulan[$bulan] ?? false; @endphp
                                            <tr wire:key="pajak-bosp-reguler-bulan-{{ $bulan }}" class="{{ $terkunciBulanIni ? 'pointer-events-none opacity-60 select-none' : '' }}">
                                                <td class="px-2 py-2 text-center text-slate-400">{{ $bulan }}</td>
                                                <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-700">
                                                    {{ $labelBulan }}
                                                    @if ($terkunciBulanIni)
                                                        <x-icon name="lock-closed" class="inline w-3 h-3 text-amber-600 ml-1" />
                                                    @endif
                                                </td>
                                                @foreach (['ppn_debit', 'pph21_debit', 'pph23_debit', 'pph4_debit', 'sspd_debit'] as $f)
                                                    <td class="px-1 py-1.5">
                                                        <x-honor-ptk-tarif-cell :row-id="$bulan" :field="$f" :value="$baris[$bulan][$f] ?? ''" :revisi="$revisi" :disabled="$terkunciBulanIni" class="w-20" />
                                                    </td>
                                                @endforeach
                                                <td class="px-2 py-2 border-r border-slate-200 text-right font-bold text-sky-700 bg-sky-50/30">
                                                    Rp {{ number_format($jumlahPerBulan[$bulan]['debit'], 0, ',', '.') }}
                                                </td>
                                                @foreach (['ppn_kredit', 'pph21_kredit', 'pph23_kredit', 'pph4_kredit', 'sspd_kredit'] as $f)
                                                    <td class="px-1 py-1.5">
                                                        <x-honor-ptk-tarif-cell :row-id="$bulan" :field="$f" :value="$baris[$bulan][$f] ?? ''" :revisi="$revisi" :disabled="$terkunciBulanIni" class="w-20" />
                                                    </td>
                                                @endforeach
                                                <td class="px-2 py-2 border-r border-slate-200 text-right font-bold text-rose-700 bg-rose-50/30">
                                                    Rp {{ number_format($jumlahPerBulan[$bulan]['kredit'], 0, ',', '.') }}
                                                </td>
                                                <td class="px-2 py-2 text-right font-bold text-slate-700 bg-slate-50">
                                                    Rp {{ number_format($saldoPerBulan[$bulan], 0, ',', '.') }}
                                                </td>
                                                <td class="px-2 py-2 text-center">
                                                    <button type="button" wire:click="konfirmasiHapusBulan({{ $bulan }})" @disabled($terkunciBulanIni) class="inline-flex items-center gap-1 text-[10px] text-red-600 hover:underline">
                                                        <x-icon name="trash" class="w-3 h-3" />Hapus
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach

                                        {{-- Baris "Jumlah" total setahun - pola sama seperti
                                             baris Jumlah pada Rekap RKAS/Penerimaan Honor PTK. --}}
                                        <tr class="bg-slate-200 font-bold border-t-2 border-slate-400">
                                            <td colspan="2" class="px-2 py-2 text-center">Jumlah</td>
                                            @foreach (['ppn_debit', 'pph21_debit', 'pph23_debit', 'pph4_debit', 'sspd_debit'] as $f)
                                                <td class="px-2 py-2 text-right">Rp {{ number_format($totalRaw[$f], 0, ',', '.') }}</td>
                                            @endforeach
                                            <td class="px-2 py-2 border-r border-slate-400 text-right text-sky-800">Rp {{ number_format($totalJumlahDebit, 0, ',', '.') }}</td>
                                            @foreach (['ppn_kredit', 'pph21_kredit', 'pph23_kredit', 'pph4_kredit', 'sspd_kredit'] as $f)
                                                <td class="px-2 py-2 text-right">Rp {{ number_format($totalRaw[$f], 0, ',', '.') }}</td>
                                            @endforeach
                                            <td class="px-2 py-2 border-r border-slate-400 text-right text-rose-800">Rp {{ number_format($totalJumlahKredit, 0, ',', '.') }}</td>
                                            <td class="px-2 py-2 text-right">Rp {{ number_format($saldoAkhir, 0, ',', '.') }}</td>
                                            <td class="px-2 py-2"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Tabel 2: Triwulan - SELURUHNYA read-only, otomatis
                                 diagregasi dari tabel bulanan (jawaban AskUserQuestion
                                 "Otomatis dari data bulanan"). --}}
                            <h4 class="text-sm font-bold text-slate-700 mb-2">Rekapitulasi per Triwulan</h4>
                            <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg" style="zoom: {{ $zoomPercent }}%;">
                                <table class="min-w-full divide-y divide-slate-200 text-xs">
                                    <thead class="bg-slate-50">
                                        <tr class="text-center text-slate-500">
                                            <th rowspan="2" class="px-2 py-2 border-r border-slate-200">No</th>
                                            <th rowspan="2" class="px-2 py-2 border-r border-slate-200">Triwulan</th>
                                            <th colspan="6" class="px-2 py-2 border-r border-slate-200 bg-sky-50">Penerimaan (Debit)</th>
                                            <th colspan="6" class="px-2 py-2 border-r border-slate-200 bg-rose-50">Pengeluaran (Kredit)</th>
                                            <th rowspan="2" class="px-2 py-2">Saldo</th>
                                        </tr>
                                        <tr class="text-center text-slate-500">
                                            @foreach (['ppn_debit', 'pph21_debit', 'pph23_debit', 'pph4_debit', 'sspd_debit'] as $f)
                                                <th class="px-2 py-2 bg-sky-50/60 whitespace-nowrap">{{ $labelPajak[str_replace('_debit', '', $f)] }}</th>
                                            @endforeach
                                            <th class="px-2 py-2 border-r border-slate-200 bg-sky-50/60 font-bold">Jumlah</th>
                                            @foreach (['ppn_kredit', 'pph21_kredit', 'pph23_kredit', 'pph4_kredit', 'sspd_kredit'] as $f)
                                                <th class="px-2 py-2 bg-rose-50/60 whitespace-nowrap">{{ $labelPajak[str_replace('_kredit', '', $f)] }}</th>
                                            @endforeach
                                            <th class="px-2 py-2 border-r border-slate-200 bg-rose-50/60 font-bold">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($triwulanOptions as $tw => $labelTw)
                                            <tr wire:key="pajak-bosp-reguler-tw-{{ $tw }}">
                                                <td class="px-2 py-2 text-center text-slate-400">{{ $tw }}</td>
                                                <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-700">{{ $labelTw }}</td>
                                                @foreach (['ppn_debit', 'pph21_debit', 'pph23_debit', 'pph4_debit', 'sspd_debit'] as $f)
                                                    <td class="px-2 py-2 text-right text-slate-600">Rp {{ number_format($triwulanData[$tw]['rincian'][$f] ?? 0, 0, ',', '.') }}</td>
                                                @endforeach
                                                <td class="px-2 py-2 border-r border-slate-200 text-right font-bold text-sky-700 bg-sky-50/30">Rp {{ number_format($triwulanData[$tw]['debit'], 0, ',', '.') }}</td>
                                                @foreach (['ppn_kredit', 'pph21_kredit', 'pph23_kredit', 'pph4_kredit', 'sspd_kredit'] as $f)
                                                    <td class="px-2 py-2 text-right text-slate-600">Rp {{ number_format($triwulanData[$tw]['rincian'][$f] ?? 0, 0, ',', '.') }}</td>
                                                @endforeach
                                                <td class="px-2 py-2 border-r border-slate-200 text-right font-bold text-rose-700 bg-rose-50/30">Rp {{ number_format($triwulanData[$tw]['kredit'], 0, ',', '.') }}</td>
                                                <td class="px-2 py-2 text-right font-bold text-slate-700 bg-slate-50">Rp {{ number_format($triwulanData[$tw]['saldo'], 0, ',', '.') }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-slate-200 font-bold border-t-2 border-slate-400">
                                            <td colspan="2" class="px-2 py-2 text-center">Jumlah</td>
                                            @foreach (['ppn_debit', 'pph21_debit', 'pph23_debit', 'pph4_debit', 'sspd_debit'] as $f)
                                                <td class="px-2 py-2 text-right">Rp {{ number_format($totalRaw[$f], 0, ',', '.') }}</td>
                                            @endforeach
                                            <td class="px-2 py-2 border-r border-slate-400 text-right text-sky-800">Rp {{ number_format($totalJumlahDebit, 0, ',', '.') }}</td>
                                            @foreach (['ppn_kredit', 'pph21_kredit', 'pph23_kredit', 'pph4_kredit', 'sspd_kredit'] as $f)
                                                <td class="px-2 py-2 text-right">Rp {{ number_format($totalRaw[$f], 0, ',', '.') }}</td>
                                            @endforeach
                                            <td class="px-2 py-2 border-r border-slate-400 text-right text-rose-800">Rp {{ number_format($totalJumlahKredit, 0, ',', '.') }}</td>
                                            <td class="px-2 py-2 text-right">Rp {{ number_format($saldoAkhir, 0, ',', '.') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @else
                        {{-- ============ TAB 2: REKAPITULASI SELURUH SEKOLAH (Superadmin) ============ --}}
                        <div class="text-center mb-4">
                            <h3 class="font-bold text-slate-800 text-sm tracking-wide">REKAPITULASI PAJAK BOSP REGULER SELURUH SEKOLAH</h3>
                            <p class="text-slate-500 text-xs">TAHUN ANGGARAN {{ $tahun }}</p>
                        </div>

                        <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg" style="max-height: 36rem; zoom: {{ $zoomPercent }}%;">
                            <table class="min-w-full divide-y divide-slate-200 text-xs">
                                <thead class="sticky top-0 z-10 bg-slate-50">
                                    <tr class="text-center text-slate-500">
                                        <th rowspan="2" class="px-2 py-2.5 border-r border-slate-200">No</th>
                                        <th rowspan="2" class="px-2 py-2.5 border-r border-slate-200">NPSN</th>
                                        <th rowspan="2" class="px-2 py-2.5 border-r border-slate-200">Nama Sekolah</th>
                                        <th colspan="6" class="px-2 py-2.5 border-r border-slate-200 bg-sky-50">Total Debit Setahun</th>
                                        <th colspan="6" class="px-2 py-2.5 border-r border-slate-200 bg-rose-50">Total Kredit Setahun</th>
                                        <th rowspan="2" class="px-2 py-2.5">Saldo Akhir</th>
                                    </tr>
                                    <tr class="text-center text-slate-500">
                                        @foreach (['ppn_debit', 'pph21_debit', 'pph23_debit', 'pph4_debit', 'sspd_debit'] as $f)
                                            <th class="px-2 py-2 bg-sky-50/60 whitespace-nowrap">{{ $labelPajak[str_replace('_debit', '', $f)] }}</th>
                                        @endforeach
                                        <th class="px-2 py-2 border-r border-slate-200 bg-sky-50/60 font-bold">Jumlah</th>
                                        @foreach (['ppn_kredit', 'pph21_kredit', 'pph23_kredit', 'pph4_kredit', 'sspd_kredit'] as $f)
                                            <th class="px-2 py-2 bg-rose-50/60 whitespace-nowrap">{{ $labelPajak[str_replace('_kredit', '', $f)] }}</th>
                                        @endforeach
                                        <th class="px-2 py-2 border-r border-slate-200 bg-rose-50/60 font-bold">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse ($rekapSekolah as $i => $rekap)
                                        <tr wire:key="pajak-bosp-reguler-rekap-{{ $rekap['sekolah']->id }}">
                                            <td class="px-2 py-2 text-center text-slate-400">{{ $i + 1 }}</td>
                                            <td class="px-2 py-2 whitespace-nowrap text-slate-600">{{ $rekap['sekolah']->npsn ?? '-' }}</td>
                                            <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800">{{ $rekap['sekolah']->nama_sekolah ?? '-' }}</td>
                                            @foreach (['ppn_debit', 'pph21_debit', 'pph23_debit', 'pph4_debit', 'sspd_debit'] as $f)
                                                <td class="px-2 py-2 text-right text-slate-600">Rp {{ number_format($rekap['rincian'][$f] ?? 0, 0, ',', '.') }}</td>
                                            @endforeach
                                            <td class="px-2 py-2 border-r border-slate-200 text-right font-semibold text-sky-700">Rp {{ number_format($rekap['total_debit'], 0, ',', '.') }}</td>
                                            @foreach (['ppn_kredit', 'pph21_kredit', 'pph23_kredit', 'pph4_kredit', 'sspd_kredit'] as $f)
                                                <td class="px-2 py-2 text-right text-slate-600">Rp {{ number_format($rekap['rincian'][$f] ?? 0, 0, ',', '.') }}</td>
                                            @endforeach
                                            <td class="px-2 py-2 border-r border-slate-200 text-right font-semibold text-rose-700">Rp {{ number_format($rekap['total_kredit'], 0, ',', '.') }}</td>
                                            <td class="px-2 py-2 text-right font-bold text-slate-700">Rp {{ number_format($rekap['saldo_akhir'], 0, ',', '.') }}</td>
                                        </tr>
                                        @if ($loop->last)
                                            {{-- Baris "Jumlah" total seluruh sekolah setelah baris
                                                 data terakhir - kolom No/NPSN/Nama Sekolah digabung
                                                 (colspan) jadi satu sel, tiap kolom pajak/Jumlah/
                                                 Saldo Akhir dijumlahkan dari seluruh baris sekolah
                                                 di atasnya - permintaan user 2026-09-15 (Part 25). --}}
                                            <tr class="bg-slate-200 font-bold border-t-2 border-slate-400">
                                                <td colspan="3" class="px-2 py-2 text-center">Jumlah</td>
                                                @foreach (['ppn_debit', 'pph21_debit', 'pph23_debit', 'pph4_debit', 'sspd_debit'] as $f)
                                                    <td class="px-2 py-2 text-right">Rp {{ number_format($rekapTotal['rincian'][$f], 0, ',', '.') }}</td>
                                                @endforeach
                                                <td class="px-2 py-2 border-r border-slate-400 text-right text-sky-800">Rp {{ number_format($rekapTotal['total_debit'], 0, ',', '.') }}</td>
                                                @foreach (['ppn_kredit', 'pph21_kredit', 'pph23_kredit', 'pph4_kredit', 'sspd_kredit'] as $f)
                                                    <td class="px-2 py-2 text-right">Rp {{ number_format($rekapTotal['rincian'][$f], 0, ',', '.') }}</td>
                                                @endforeach
                                                <td class="px-2 py-2 border-r border-slate-400 text-right text-rose-800">Rp {{ number_format($rekapTotal['total_kredit'], 0, ',', '.') }}</td>
                                                <td class="px-2 py-2 text-right">Rp {{ number_format($rekapTotal['saldo_akhir'], 0, ',', '.') }}</td>
                                            </tr>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="16" class="px-3 py-6 text-center text-slate-400">Tidak ada sekolah yang bisa ditampilkan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Konfirmasi Hapus (per bulan) --}}
    <x-modal name="pajak-bosp-reguler-hapus" :show="$confirmingHapusBulan !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">
                Hapus seluruh data pajak bulan {{ $confirmingHapusBulan ? (\App\Models\PajakBospReguler::BULAN_OPTIONS[$confirmingHapusBulan] ?? '') : '' }}?
            </h2>
            <p class="mt-1 text-sm text-slate-600">Tindakan ini tidak dapat dibatalkan - seluruh 10 kolom pajak bulan ini akan dikosongkan kembali.</p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalHapusBulan" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                <x-danger-button wire:click="hapusBulan" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Hapus</x-danger-button>
            </div>
        </div>
    </x-modal>
</div>
