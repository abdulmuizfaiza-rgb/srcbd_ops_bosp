<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Dana BOSP Tahap 1 & 2') }}
    </h2>
</x-slot>

<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="flex flex-col xl:flex-row xl:flex-wrap xl:items-start xl:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-lg font-medium text-slate-900">Dana BOSP Tahap 1 & 2</h3>
                        <p class="text-sm text-slate-500">Penerimaan BOSP & Tarik Tunai BOSP per sekolah, per tahun.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <label for="tahun" class="text-sm text-slate-600">Tahun</label>
                        <select wire:model.live="tahun" id="tahun" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                            @foreach ($tahunOptions as $opsiTahun)
                                <option value="{{ $opsiTahun }}">{{ $opsiTahun }}</option>
                            @endforeach
                        </select>

                        <x-zoom-controls :zoom="$zoomPercent" />
                    </div>
                </div>

                {{-- Tab (2 tab, keduanya berasal dari 1 record per sekolah - lihat
                     App\Livewire\PendataanBosp\DanaBospTahap\Index). --}}
                <div class="border-b border-slate-200 mb-4">
                    <nav class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            wire:click="pindahTab('penerimaan')"
                            class="px-3 py-2 text-sm font-medium border-b-2 -mb-px {{ $tabAktif === 'penerimaan' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
                        >
                            Penerimaan BOSP
                        </button>
                        <button
                            type="button"
                            wire:click="pindahTab('tarik_tunai')"
                            class="px-3 py-2 text-sm font-medium border-b-2 -mb-px {{ $tabAktif === 'tarik_tunai' ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-700' }}"
                        >
                            Tarik Tunai BOSP
                        </button>
                    </nav>
                </div>

                @if ($tabAktif === 'penerimaan')
                    {{-- ============ TAB 1: PENERIMAAN BOSP (tabel biasa, semua role) ============ --}}
                    <p class="text-xs text-slate-500 mb-2">Anda bisa mengetik langsung di tiap kotak pada tabel (tersimpan otomatis begitu pindah kotak). Kolom Total Penerimaan Setahun, Tahap 1, dan Tahap 2 dihitung otomatis oleh sistem.</p>

                    <div class="overflow-auto scrollbar-modern border border-blue-200 rounded-lg" style="max-height: 34rem; zoom: {{ $zoomPercent }}%;">
                        <table class="min-w-full divide-y divide-blue-100 text-xs">
                            <thead class="sticky top-0 z-10 bg-blue-50">
                                <tr class="text-center text-slate-700">
                                    <th class="px-2 py-2.5 border-2 border-blue-300">No</th>
                                    <th class="px-2 py-2.5 border-2 border-blue-300">Nama Sekolah</th>
                                    <th class="px-2 py-2.5 border-2 border-blue-300">Saldo BOSP Tahun Sebelumnya</th>
                                    <th class="px-2 py-2.5 border-2 border-blue-300">Jumlah Siswa</th>
                                    <th class="px-2 py-2.5 border-2 border-blue-300">Jumlah Dana BOSP Per Tahun</th>
                                    <th class="px-2 py-2.5 border-2 border-blue-300 bg-yellow-100 font-bold">Total Penerimaan BOSP Setahun</th>
                                    <th class="px-2 py-2.5 border-2 border-blue-300 bg-yellow-100 font-bold">Penerimaan BOSP Tahap 1</th>
                                    <th class="px-2 py-2.5 border-2 border-blue-300 bg-yellow-100 font-bold">Penerimaan BOSP Tahap 2</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-blue-100">
                                @forelse ($daftarSekolah as $sekolah)
                                    @php $revisi = $revisiBaris[$sekolah->id] ?? 0; @endphp
                                    <tr wire:key="dana-bosp-tahap-penerimaan-{{ $sekolah->id }}">
                                        <td class="px-2 py-2 whitespace-nowrap text-center text-slate-500 border border-blue-100">{{ $loop->iteration }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800 border border-blue-100">{{ $sekolah->nama_sekolah }}</td>
                                        <td class="px-1 py-1.5 whitespace-nowrap border border-blue-100">
                                            <x-honor-ptk-tarif-cell :row-id="$sekolah->id" field="saldo_bosp_tahun_sebelumnya" :value="$baris[$sekolah->id]['saldo_bosp_tahun_sebelumnya'] ?? ''" :revisi="$revisi" />
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap border border-blue-100">
                                            <input
                                                type="text"
                                                inputmode="numeric"
                                                wire:model.blur="baris.{{ $sekolah->id }}.jumlah_siswa"
                                                class="w-20 text-right text-xs rounded px-1.5 py-1.5 border {{ $errors->has('baris.'.$sekolah->id.'.jumlah_siswa') ? 'border-red-400 focus:border-red-500 focus:ring-red-400' : 'border-blue-300 focus:border-blue-500 focus:ring-blue-400' }} focus:ring-1 focus:outline-none"
                                            >
                                            @error('baris.'.$sekolah->id.'.jumlah_siswa')
                                                <span class="block text-[10px] text-red-500 mt-0.5 whitespace-normal">{{ $message }}</span>
                                            @enderror
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap border border-blue-100">
                                            <x-honor-ptk-tarif-cell :row-id="$sekolah->id" field="jumlah_dana_bosp_per_tahun" :value="$baris[$sekolah->id]['jumlah_dana_bosp_per_tahun'] ?? ''" :revisi="$revisi" />
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-right font-bold text-slate-700 border border-blue-100 bg-yellow-50">
                                            Rp {{ number_format((int) ($baris[$sekolah->id]['total_penerimaan_setahun'] ?? 0), 0, ',', '.') }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-right font-bold text-slate-700 border border-blue-100 bg-yellow-50">
                                            Rp {{ number_format((int) ($baris[$sekolah->id]['penerimaan_tahap_1'] ?? 0), 0, ',', '.') }}
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-right font-bold text-slate-700 border border-blue-100 bg-yellow-50">
                                            Rp {{ number_format((int) ($baris[$sekolah->id]['penerimaan_tahap_2'] ?? 0), 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-2 py-4 text-center text-slate-400">Belum ada data sekolah.</td>
                                    </tr>
                                @endforelse

                                {{-- Baris "Jumlah" (total seluruh sekolah yang sedang tampil) -
                                     kolom "Jumlah Dana BOSP Per Tahun" SENGAJA dikosongkan (dash)
                                     karena itu angka per-siswa/tahun, bukan kuantitas yang bisa
                                     dijumlahkan antar sekolah. --}}
                                @if ($daftarSekolah->isNotEmpty())
                                    <tr class="bg-yellow-100 font-bold text-slate-800">
                                        <td colspan="2" class="px-2 py-2 text-center border border-blue-100">JUMLAH</td>
                                        <td class="px-2 py-2 text-right border border-blue-100">Rp {{ number_format($totalTab1['saldo_bosp_tahun_sebelumnya'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="px-2 py-2 text-right border border-blue-100">{{ number_format($totalTab1['jumlah_siswa'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="px-2 py-2 text-center border border-blue-100 text-slate-400">-</td>
                                        <td class="px-2 py-2 text-right border border-blue-100">Rp {{ number_format($totalTab1['total_penerimaan_setahun'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="px-2 py-2 text-right border border-blue-100">Rp {{ number_format($totalTab1['penerimaan_tahap_1'] ?? 0, 0, ',', '.') }}</td>
                                        <td class="px-2 py-2 text-right border border-blue-100">Rp {{ number_format($totalTab1['penerimaan_tahap_2'] ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                @else
                    {{-- ============ TAB 2: TARIK TUNAI BOSP (layout beda per role,
                         jawaban AskUserQuestion 2026-09-16) ============ --}}
                    @if ($bolehKelolaSemua)
                        {{-- Superadmin: tabel multi-sekolah, tanda +/- di kolom No,
                             expand menampilkan 5 field berbaris ke bawah (pola "tabel
                             diringkas per sekolah" sama seperti RincianBelanjaModal). --}}
                        <p class="text-xs text-slate-500 mb-2">Klik baris sekolah untuk membuka/menutup rincian Tarik Tunai BOSP-nya. Kotak input tersimpan otomatis begitu pindah kotak.</p>

                        <div class="overflow-auto scrollbar-modern border border-blue-200 rounded-lg" style="max-height: 34rem; zoom: {{ $zoomPercent }}%;">
                            <table class="min-w-full divide-y divide-blue-100 text-xs">
                                <thead class="sticky top-0 z-10 bg-blue-50">
                                    <tr class="text-center text-slate-700">
                                        <th class="px-2 py-2.5 border-2 border-blue-300">No</th>
                                        <th class="px-2 py-2.5 border-2 border-blue-300">Nama Sekolah</th>
                                        <th class="px-2 py-2.5 border-2 border-blue-300">Rincian</th>
                                    </tr>
                                </thead>
                                {{-- Pola tbody-per-sekolah dengan simbol +/- di kolom No, sama
                                     persis seperti RincianBelanjaModal/RincianPemeliharaan -
                                     setiap sekolah = 1 elemen <tbody> tersendiri, BUKAN dibungkus
                                     <tbody> luar (HTML memperbolehkan banyak <tbody> langsung di
                                     bawah <table>). --}}
                                @forelse ($daftarSekolah as $sekolah)
                                    @php $revisi = $revisiBaris[$sekolah->id] ?? 0; @endphp
                                    <tbody wire:key="dana-bosp-tahap-tarik-tunai-grup-{{ $sekolah->id }}" x-data="{ terbuka: false }" class="divide-y divide-slate-100 border-b-2 border-slate-200">
                                        <tr class="bg-slate-50/70 hover:bg-slate-100 cursor-pointer select-none" x-on:click="terbuka = ! terbuka">
                                            <td class="px-2 py-2 whitespace-nowrap text-center text-slate-600 font-semibold border border-blue-100">
                                                <span class="inline-flex items-center gap-1.5 justify-center">
                                                    <span class="inline-flex items-center justify-center w-4 h-4 rounded border border-slate-300 bg-white text-slate-500 shrink-0">
                                                        <x-icon name="plus" class="w-2.5 h-2.5" x-show="! terbuka" x-cloak />
                                                        <x-icon name="minus" class="w-2.5 h-2.5" x-show="terbuka" x-cloak />
                                                    </span>
                                                    {{ $loop->iteration }}
                                                </span>
                                            </td>
                                            <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800 border border-blue-100">{{ $sekolah->nama_sekolah }}</td>
                                            <td class="px-2 py-2 whitespace-nowrap text-slate-400 italic border border-blue-100">
                                                Klik untuk <span x-text="terbuka ? 'menutup' : 'melihat'"></span> rincian Tarik Tunai BOSP
                                            </td>
                                        </tr>
                                        <tr x-show="terbuka" x-cloak>
                                            <td colspan="3" class="px-4 py-3 bg-white border border-blue-100">
                                                <div class="max-w-sm space-y-2">
                                                    @foreach ($labelTab2 as $field => $label)
                                                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2 last:border-b-0 last:pb-0">
                                                            <span class="text-xs font-medium text-slate-600">{{ $label }}</span>
                                                            <x-honor-ptk-tarif-cell :row-id="$sekolah->id" :field="$field" :value="$baris[$sekolah->id][$field] ?? ''" :revisi="$revisi" class="w-32" />
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                @empty
                                    <tbody>
                                        <tr>
                                            <td colspan="3" class="px-2 py-4 text-center text-slate-400">Belum ada data sekolah.</td>
                                        </tr>
                                    </tbody>
                                @endforelse
                            </table>
                        </div>

                        {{-- Jumlah seluruh sekolah (Tab 2) - selalu tampil, tidak ikut
                             collapse per-sekolah, mengikuti pola baris Jumlah menu lain. --}}
                        @if ($daftarSekolah->isNotEmpty())
                            <div class="mt-4 border-t-2 border-blue-200 pt-4">
                                <h4 class="text-sm font-semibold text-slate-700 mb-2">JUMLAH Tarik Tunai BOSP Seluruh Sekolah</h4>
                                <div class="max-w-sm space-y-2">
                                    @foreach ($labelTab2 as $field => $label)
                                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2 last:border-b-0 last:pb-0">
                                            <span class="text-xs font-semibold text-slate-700">{{ $label }}</span>
                                            <span class="text-xs font-bold text-slate-800">Rp {{ number_format($totalTab2[$field] ?? 0, 0, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @else
                        {{-- Admin BOSP: form vertikal untuk sekolahnya sendiri saja,
                             field berbaris ke bawah sejajar kiri (BUKAN tabel), sesuai
                             jawaban AskUserQuestion 2026-09-16. --}}
                        @php $sekolahSaya = $daftarSekolah->first(); @endphp
                        @if ($sekolahSaya)
                            @php $revisi = $revisiBaris[$sekolahSaya->id] ?? 0; @endphp
                            <div class="max-w-sm">
                                <h4 class="text-sm font-semibold text-slate-700 mb-3">{{ $sekolahSaya->nama_sekolah }}</h4>
                                <div class="space-y-3">
                                    @foreach ($labelTab2 as $field => $label)
                                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-2 last:border-b-0 last:pb-0">
                                            <span class="text-xs font-medium text-slate-600">{{ $label }}</span>
                                            <x-honor-ptk-tarif-cell :row-id="$sekolahSaya->id" :field="$field" :value="$baris[$sekolahSaya->id][$field] ?? ''" :revisi="$revisi" class="w-32" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-slate-400">Data sekolah Anda belum ditemukan.</p>
                        @endif
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
