<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Formulir BOS K7b & K7c') }}
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

            @if ($errorExport)
                <div class="p-4 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-sm">
                    {{ $errorExport }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                {{--
                    Tab "Formulir BOS K7b" (Register Penutupan Kas) / "Formulir
                    BOS K7c" (Berita Acara Pemeriksaan Kas) - KEDUANYA membaca &
                    menulis baris FormulirBosK7 yang sama (sekolah+tahun+bulan
                    aktif), lihat App\Livewire\PendataanBosp\FormulirBosK7\Index.

                    Perbaikan 2026-09-24 (round kedua puluh empat, poin 5): setiap
                    tab diberi warna aktif BERBEDA - K7b=sky, K7c=rose.
                --}}
                <div class="border-b border-slate-200 px-4 sm:px-8 pt-4">
                    <nav class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            wire:click="pindahTab('k7b')"
                            @class([
                                'px-4 py-2.5 rounded-t-xl text-sm font-bold tracking-wide transition-all duration-150 border-b-4',
                                'bg-white text-sky-700 border-sky-500 shadow-sm' => $tabAktif === 'k7b',
                                'bg-slate-50 text-slate-500 border-transparent hover:bg-slate-100 hover:text-slate-700' => $tabAktif !== 'k7b',
                            ])
                        >
                            Formulir BOS K7b
                        </button>
                        <button
                            type="button"
                            wire:click="pindahTab('k7c')"
                            @class([
                                'px-4 py-2.5 rounded-t-xl text-sm font-bold tracking-wide transition-all duration-150 border-b-4',
                                'bg-white text-rose-700 border-rose-500 shadow-sm' => $tabAktif === 'k7c',
                                'bg-slate-50 text-slate-500 border-transparent hover:bg-slate-100 hover:text-slate-700' => $tabAktif !== 'k7c',
                            ])
                        >
                            Formulir BOS K7c
                        </button>
                    </nav>
                </div>

                <div class="p-4 sm:p-8">
                    <div class="flex flex-col xl:flex-row xl:flex-wrap xl:items-start xl:justify-between gap-3 mb-6">
                        <div class="flex flex-wrap sm:flex-row sm:items-center gap-2.5">
                            <label for="bulan" class="text-sm text-slate-600">Bulan</label>
                            <select wire:model.live="bulan" id="bulan" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">
                                @foreach ($bulanOptions as $nomorBulan => $labelBulan)
                                    <option value="{{ $nomorBulan }}">{{ $labelBulan }}</option>
                                @endforeach
                            </select>

                            <label for="tahun" class="text-sm text-slate-600">Tahun</label>
                            <select wire:model.live="tahun" id="tahun" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">
                                @foreach ($tahunOptions as $opsiTahun)
                                    <option value="{{ $opsiTahun }}">{{ $opsiTahun }}</option>
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

                        {{-- Perbaikan 2026-09-23 (round ketiga): tombol "Jenis Kertas",
                             "Cetak" (preview PDF), & "Setting Margin" - jawaban
                             AskUserQuestion mengonfirmasi tombol Cetak TERPISAH dari
                             Excel/PDF yang sudah ada, & pengaturan kertas/margin
                             berlaku untuk PDF/Cetak DAN Excel. --}}
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

                            <x-secondary-button wire:click="exportExcel" wire:loading.attr="disabled" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                <x-icon name="download" class="w-3.5 h-3.5 mr-1" />
                                Excel
                            </x-secondary-button>
                            <x-secondary-button wire:click="exportPdf" wire:loading.attr="disabled" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                <x-icon name="download" class="w-3.5 h-3.5 mr-1" />
                                PDF
                            </x-secondary-button>
                        </div>
                    </div>

                    {{-- Panel "Setting Margin" - sengaja ditaruh MENGALIR di bawah
                         toolbar (bukan position:absolute) supaya tidak tertimpa/
                         menimpa tabel formulir di bawahnya (kartu pembungkus
                         memakai overflow-hidden untuk sudut rounded-lg). --}}
                    @if ($tampilSettingMargin)
                        <div class="mb-6 -mt-3 bg-slate-50 border border-slate-200 rounded-lg p-4 text-xs">
                            <p class="font-semibold text-slate-700 mb-3">Margin Halaman (cm) - berlaku untuk Excel, PDF, & Cetak</p>
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
                                Pilih sekolah terlebih dahulu pada filter di atas untuk menampilkan & mengisi Formulir BOS K7b/K7c.
                            @else
                                Akun Anda belum terhubung ke data sekolah manapun.
                            @endif
                        </div>
                    @else
                        @if ($terkunciTriwulanIni)
                            <div class="mb-4 p-3 bg-amber-50 border border-amber-300 text-amber-800 rounded-lg text-xs flex items-start gap-2">
                                <x-icon name="lock-closed" class="w-4 h-4 shrink-0 mt-0.5" />
                                <span>Triwulan bulan ini sudah divalidasi <strong>&quot;Sesuai&quot;</strong> pada halaman Laporan Realisasi BOSP (Form BPK) - formulir bulan ini <strong>terkunci permanen</strong>, tidak bisa diedit lagi. Hubungi Superadmin kalau triwulan ini perlu dibuka kembali.</span>
                            </div>
                        @endif
                        <div class="{{ $terkunciTriwulanIni ? 'pointer-events-none opacity-60 select-none' : '' }}" style="zoom: {{ $zoomPercent }}%;">
                        @php
                            // Kunci wire:key kotak input (x-honor-ptk-tarif-cell) HARUS ikut
                            // berubah saat bulan/tahun/sekolah aktif berganti (bukan cuma
                            // $revisiBaris yang hanya naik saat validasi ditolak) - komponen
                            // itu wire:ignore, jadi tanpa ini kotak akan tetap menampilkan
                            // nilai kosong dari render pertama walau bulan/tahun sudah
                            // diganti & datanya sudah benar tersimpan di database.
                            $revisiKunci = ($sekolah->id ?? 0).'-'.$tahun.'-'.$bulan.'-'.$revisiBaris;
                        @endphp
                        @if ($tabAktif === 'k7b')
                            {{-- ============ TAB FORMULIR BOS K7b (Register Penutupan Kas) ============ --}}
                            <div class="relative border border-slate-300 rounded-lg p-5 sm:p-8 text-sm">
                                <div class="float-right border border-slate-400 rounded px-3 py-1.5 text-xs font-bold">FORMULIR BOS-K7b</div>
                                <div class="clear-both"></div>

                                <h3 class="text-center font-bold text-base tracking-wide mb-6">REGISTER PENUTUPAN KAS</h3>

                                <table class="w-full text-xs mb-5">
                                    <tr>
                                        <td class="py-0.5 w-64">Tanggal Penutupan Kas Bulan ini</td>
                                        <td class="py-0.5 w-3">:</td>
                                        <td class="py-0.5">{{ $tanggalPenutupan->translatedFormat('d F Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-0.5">Nama Penutup KAS (Pemegang KAS)</td>
                                        <td class="py-0.5">:</td>
                                        <td class="py-0.5">{{ $sekolah->nama_bendahara }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-0.5">Tanggal Penutupan KAS Bulan Lalu</td>
                                        <td class="py-0.5">:</td>
                                        <td class="py-0.5">{{ $tanggalPenutupanLalu->translatedFormat('d F Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-0.5">Jumlah Total Penerimaan BKU (D)</td>
                                        <td class="py-0.5">:</td>
                                        <td class="py-1">Rp. <x-honor-ptk-tarif-cell :row-id="'data'" field="jumlah_total_penerimaan_bku" :value="$baris['jumlah_total_penerimaan_bku'] ?? ''" :revisi="$revisiKunci" class="w-32" /></td>
                                    </tr>
                                    <tr>
                                        <td class="py-0.5">Jumlah Total Pengeluaran BKU (K)</td>
                                        <td class="py-0.5">:</td>
                                        <td class="py-1">Rp. <x-honor-ptk-tarif-cell :row-id="'data'" field="jumlah_total_pengeluaran_bku" :value="$baris['jumlah_total_pengeluaran_bku'] ?? ''" :revisi="$revisiKunci" class="w-32" /></td>
                                    </tr>
                                    <tr class="font-bold">
                                        <td class="py-0.5">A.&nbsp;&nbsp;Saldo Buku Kas Umum (A=D-K)</td>
                                        <td class="py-0.5">:</td>
                                        <td class="py-0.5">Rp. {{ number_format($saldoBku, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-0.5">Saldo Kas Tunai</td>
                                        <td class="py-0.5">:</td>
                                        <td class="py-0.5 border-b border-slate-400 inline-block">Rp. {{ number_format($saldoKasTunai, 0, ',', '.') }}</td>
                                    </tr>
                                    {{-- Perbaikan 2026-09-23 (round keenam, permintaan user): field
                                         "Saldo Kas Tunai (manual)" DIHAPUS dari tampilan formulir -
                                         backend (kolom `saldo_kas_tunai_manual` & logika fallback di
                                         App\Models\FormulirBosK7::hitungSaldoKasTunai(), round kedua)
                                         SENGAJA TIDAK disentuh supaya data lama yang sudah tersimpan
                                         tidak hilang & rumus tidak berubah - hanya kotak inputnya yang
                                         dihilangkan dari layar. --}}
                                </table>

                                <div class="font-bold mb-1">1.&nbsp;&nbsp;Lembaran uang kertas</div>
                                <table class="w-full text-xs mb-2">
                                    @foreach (\App\Models\FormulirBosK7::NOMINAL_UANG_KERTAS as $nominal)
                                        @php $field = \App\Models\FormulirBosK7::fieldLembar($nominal); @endphp
                                        <tr>
                                            <td class="py-0.5 w-40">Lembaran uang kertas</td>
                                            <td class="py-0.5 w-24">Rp {{ number_format($nominal, 0, ',', '.') }}</td>
                                            <td class="py-0.5 w-28"><x-honor-ptk-tarif-cell :row-id="'data'" :field="$field" :value="$baris[$field] ?? ''" :revisi="$revisiKunci" class="w-16" /> Lembar</td>
                                            <td class="py-0.5 w-8">Rp.</td>
                                            <td class="py-0.5 text-right">{{ number_format($nominal * (int) ($formulir->{$field} ?? 0), 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="font-bold border-t border-slate-300">
                                        <td colspan="3" class="py-1">Sub Jumlah Lembar uang kertas (1)</td>
                                        <td class="py-1">Rp.</td>
                                        <td class="py-1 text-right">{{ number_format($subJumlahKertas, 0, ',', '.') }}</td>
                                    </tr>
                                </table>

                                <div class="font-bold mb-1 mt-4">2.&nbsp;&nbsp;Keping uang logam</div>
                                <table class="w-full text-xs mb-2">
                                    @foreach (\App\Models\FormulirBosK7::NOMINAL_UANG_LOGAM as $nominal)
                                        @php $field = \App\Models\FormulirBosK7::fieldKeping($nominal); @endphp
                                        <tr>
                                            <td class="py-0.5 w-40">Keping uang logam</td>
                                            <td class="py-0.5 w-24">Rp {{ number_format($nominal, 0, ',', '.') }}</td>
                                            <td class="py-0.5 w-28"><x-honor-ptk-tarif-cell :row-id="'data'" :field="$field" :value="$baris[$field] ?? ''" :revisi="$revisiKunci" class="w-16" /> Keping</td>
                                            <td class="py-0.5 w-8">Rp.</td>
                                            <td class="py-0.5 text-right">{{ number_format($nominal * (int) ($formulir->{$field} ?? 0), 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="font-bold border-t border-slate-300">
                                        <td colspan="3" class="py-1">Sub Jumlah Keping uang logam (2)</td>
                                        <td class="py-1">Rp.</td>
                                        <td class="py-1 text-right">{{ number_format($subJumlahLogam, 0, ',', '.') }}</td>
                                    </tr>
                                </table>

                                <table class="w-full text-xs mb-2 mt-4">
                                    <tr>
                                        <td class="py-0.5 w-72">3.&nbsp;&nbsp;Saldo Rekening Bank &nbsp;&nbsp;Sub Jumlah (3)</td>
                                        <td class="py-1 w-8">Rp.</td>
                                        <td class="py-1"><x-honor-ptk-tarif-cell :row-id="'data'" field="saldo_rekening_bank" :value="$baris['saldo_rekening_bank'] ?? ''" :revisi="$revisiKunci" class="w-32 text-right" /></td>
                                    </tr>
                                    <tr class="font-bold bg-slate-50">
                                        <td class="py-1">B.&nbsp;&nbsp;Jumlah (1+2+3)</td>
                                        <td class="py-1">Rp.</td>
                                        <td class="py-1">{{ number_format($jumlahB, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-1 pt-3">Perbedaan (A-B)</td>
                                        <td class="py-1 pt-3">Rp.</td>
                                        <td class="py-1 pt-3">{{ number_format($perbedaan, 0, ',', '.') }}</td>
                                    </tr>
                                </table>

                                <div class="mt-3">
                                    <label class="block text-xs mb-1" for="penjelasan_perbedaan">Penjelasan Perbedaan</label>
                                    {{-- wire:ignore + Alpine blur->$wire.set (BUKAN wire:model biasa):
                                         pola sama seperti x-honor-ptk-tarif-cell - Livewire morph
                                         TIDAK bisa dipercaya menyegarkan .value input/textarea yang
                                         sudah pernah disentuh JS, walau wire:key sudah diganti;
                                         wire:ignore + wire:key yang ikut berubah saat bulan/tahun/
                                         sekolah berganti (via $revisiKunci) memaksa elemen dibuat
                                         ulang dari nol dengan value HTML yang benar. --}}
                                    <div wire:ignore wire:key="penjelasan-perbedaan-{{ $revisiKunci }}">
                                        <textarea
                                            id="penjelasan_perbedaan"
                                            rows="2"
                                            x-on:blur="$wire.set('baris.data.penjelasan_perbedaan', $event.target.value)"
                                            class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block w-full text-xs"
                                        >{{ $baris['penjelasan_perbedaan'] ?? '' }}</textarea>
                                    </div>
                                    <x-input-error :messages="$errors->get('penjelasan_perbedaan')" class="mt-1" />
                                </div>

                                {{-- Perbaikan 2026-09-23 (round kelima): tanda tangan Bendahara &
                                     Kepala Sekolah SEBELUMNYA rata KIRI di kolomnya masing-masing
                                     (round keempat - supaya sejajar persis dengan baris "Tanggal").
                                     Sekarang diubah jadi rata TENGAH ("posisi di tengah-tengah")
                                     sesuai permintaan baru - class="text-center" ditambahkan ke
                                     SEMUA sel berisi teks di kolom Bendahara (colspan 2) & Kepala
                                     Sekolah (colspan 3), termasuk baris "Tanggal", supaya keduanya
                                     tetap sama-sama di tengah kolomnya (tetap "sejajar" satu sama
                                     lain, cuma titik acuannya sekarang tengah kolom, bukan kiri
                                     kolom). Kolom Kepala Sekolah & Bendahara SENDIRI (colspan/titik
                                     mulai) tidak dipindah dari round ketiga. Ruang tanda tangan
                                     diperbesar lagi dari 3 baris kosong menjadi 4 baris kosong. --}}
                                <table class="w-full text-xs mt-6" style="table-layout:fixed">
                                    <colgroup>
                                        <col style="width:16.6667%"><col style="width:16.6667%"><col style="width:16.6667%">
                                        <col style="width:16.6667%"><col style="width:16.6667%"><col style="width:16.6667%">
                                    </colgroup>
                                    <tr>
                                        <td></td>
                                        <td colspan="2"></td>
                                        <td colspan="3" class="text-center">Tanggal, {{ $tanggalPenutupan->translatedFormat('d F Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td colspan="2" class="text-center">Yang diperiksa,</td>
                                        <td colspan="3" class="text-center">Yang Memeriksa,</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td colspan="2" class="text-center">Bendahara</td>
                                        <td colspan="3" class="text-center">Kepala Sekolah<br>{{ $sekolah->nama_sekolah }}</td>
                                    </tr>
                                    <tr><td class="h-5"></td><td colspan="2"></td><td colspan="3"></td></tr>
                                    <tr><td class="h-5"></td><td colspan="2"></td><td colspan="3"></td></tr>
                                    <tr><td class="h-5"></td><td colspan="2"></td><td colspan="3"></td></tr>
                                    <tr><td class="h-5"></td><td colspan="2"></td><td colspan="3"></td></tr>
                                    <tr class="font-bold underline">
                                        <td></td>
                                        <td colspan="2" class="text-center">{{ $sekolah->nama_bendahara }}</td>
                                        <td colspan="3" class="text-center">{{ $sekolah->nama_kepala_sekolah }}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td colspan="2" class="text-center">NIP. {{ $sekolah->nip_bendahara }}</td>
                                        <td colspan="3" class="text-center">NIP. {{ $sekolah->nip_kepala_sekolah }}</td>
                                    </tr>
                                </table>
                            </div>
                        @else
                            {{-- ============ TAB FORMULIR BOS K7c (Berita Acara Pemeriksaan Kas) ============ --}}
                            <div class="relative border border-slate-300 rounded-lg p-5 sm:p-8 text-sm">
                                <div class="float-right border border-slate-400 rounded px-3 py-1.5 text-xs font-bold">FORMULIR BOS K7c</div>
                                <div class="clear-both"></div>

                                <h3 class="text-center font-bold text-base tracking-wide">BERITA ACARA PEMERIKSAAN KAS</h3>
                                <p class="text-center font-bold text-sm mb-6">PRIODE : {{ $bulanOptions[$bulan] }} {{ $tahun }}</p>

                                <p class="text-justify indent-8 mb-3 text-xs leading-relaxed">
                                    Pada hari ini {{ $narasiTanggalK7c }} yang bertanda tangan di bawah ini, Saya Kepala Sekolah
                                    yang ditunjuk berdasarkan Surat Keputusan Nomor : {{ $noSkKepalaSekolah ?: '_______________' }}
                                    tanggal {{ $tanggalSkKepalaSekolah ? $tanggalSkKepalaSekolah->translatedFormat('d F Y') : '_______________' }}.
                                </p>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4 ml-6 text-xs">
                                    <div>
                                        <label class="block mb-1">Nomor SK Kepala Sekolah</label>
                                        {{-- wire:ignore + Alpine blur->$wire.set (BUKAN wire:model biasa): lihat catatan penjelasan_perbedaan di atas --}}
                                        <div wire:ignore wire:key="no-sk-kepsek-{{ $revisiKunci }}">
                                            <input type="text" value="{{ $baris['no_sk_kepala_sekolah'] ?? '' }}" x-on:blur="$wire.set('baris.data.no_sk_kepala_sekolah', $event.target.value)" placeholder="Nomor Surat Keputusan" class="w-full text-xs rounded px-2 py-1.5 border {{ $errors->has('no_sk_kepala_sekolah') ? 'border-red-400' : 'border-blue-300' }} focus:ring-1 focus:outline-none focus:border-blue-500">
                                        </div>
                                        <x-input-error :messages="$errors->get('no_sk_kepala_sekolah')" class="mt-1" />
                                    </div>
                                    <div>
                                        <label class="block mb-1">Tanggal SK Kepala Sekolah</label>
                                        <div wire:ignore wire:key="tanggal-sk-kepsek-{{ $revisiKunci }}">
                                            <input type="date" value="{{ $baris['tanggal_sk_kepala_sekolah'] ?? '' }}" x-on:blur="$wire.set('baris.data.tanggal_sk_kepala_sekolah', $event.target.value)" class="w-full text-xs rounded px-2 py-1.5 border {{ $errors->has('tanggal_sk_kepala_sekolah') ? 'border-red-400' : 'border-blue-300' }} focus:ring-1 focus:outline-none focus:border-blue-500">
                                        </div>
                                        <x-input-error :messages="$errors->get('tanggal_sk_kepala_sekolah')" class="mt-1" />
                                    </div>
                                </div>

                                <table class="text-xs ml-6 mt-3 mb-3">
                                    <tr><td class="w-16 py-0.5">Nama</td><td class="w-3">:</td><td>{{ $sekolah->nama_kepala_sekolah }}</td></tr>
                                    <tr><td class="py-0.5">Jabatan</td><td>:</td><td>Kepala Sekolah</td></tr>
                                </table>

                                <p class="text-xs ml-6">Melakukan pemeriksaan KAS kepada :</p>

                                <table class="text-xs ml-6 mt-3 mb-3">
                                    <tr><td class="w-16 py-0.5">Nama</td><td class="w-3">:</td><td>{{ $sekolah->nama_bendahara }}</td></tr>
                                    <tr><td class="py-0.5">Jabatan</td><td>:</td><td>Bendahara BOS / Pemegang KAS</td></tr>
                                </table>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3 text-xs">
                                    <div>
                                        <label class="block mb-1">Nomor SK Bendahara</label>
                                        <div wire:ignore wire:key="no-sk-bendahara-{{ $revisiKunci }}">
                                            <input type="text" value="{{ $baris['no_sk_bendahara'] ?? '' }}" x-on:blur="$wire.set('baris.data.no_sk_bendahara', $event.target.value)" placeholder="Nomor Surat Keputusan" class="w-full text-xs rounded px-2 py-1.5 border {{ $errors->has('no_sk_bendahara') ? 'border-red-400' : 'border-blue-300' }} focus:ring-1 focus:outline-none focus:border-blue-500">
                                        </div>
                                        <x-input-error :messages="$errors->get('no_sk_bendahara')" class="mt-1" />
                                    </div>
                                    <div>
                                        <label class="block mb-1">Tanggal SK Bendahara</label>
                                        <div wire:ignore wire:key="tanggal-sk-bendahara-{{ $revisiKunci }}">
                                            <input type="date" value="{{ $baris['tanggal_sk_bendahara'] ?? '' }}" x-on:blur="$wire.set('baris.data.tanggal_sk_bendahara', $event.target.value)" class="w-full text-xs rounded px-2 py-1.5 border {{ $errors->has('tanggal_sk_bendahara') ? 'border-red-400' : 'border-blue-300' }} focus:ring-1 focus:outline-none focus:border-blue-500">
                                        </div>
                                        <x-input-error :messages="$errors->get('tanggal_sk_bendahara')" class="mt-1" />
                                    </div>
                                </div>

                                <p class="text-justify text-xs leading-relaxed mb-4">
                                    Yang berdasarkan Surat Keputusan Nomor : {{ $noSkBendahara ?: '_______________' }}
                                    tanggal {{ $tanggalSkBendahara ? $tanggalSkBendahara->translatedFormat('d F Y') : '_______________' }}
                                    ditugaskan dengan pengurusan uang BOSP. Berdasarkan pemeriksaan kas serta bukti-bukti dalam
                                    pengurusan itu, kami menemui kenyataan sebagai berikut :
                                </p>

                                <p class="text-xs mb-2">Jumlah uang yang dihitung dihadapan Bendahara/ Pemegang Kas adalah :</p>

                                {{-- Perbaikan 2026-09-23 (round keempat): colgroup eksplisit
                                     ditambahkan supaya kolom "Rp" MULAI TEPAT di titik 50% lebar
                                     tabel - persis sama dengan titik mulai kolom Kepala Sekolah di
                                     tabel tanda tangan di bawah (kolom ke-4 dari 6 kolom, lihat
                                     catatan di tabel tanda tangan). Sebelumnya lebar kolom "label"
                                     otomatis mengikuti panjang teks sehingga posisi "Rp" ikut
                                     bergeser (tidak sejajar). table-layout:fixed memaksa lebar
                                     kolom mengikuti colgroup, bukan lagi otomatis. --}}
                                <table class="w-full text-xs mb-3" style="table-layout:fixed">
                                    <colgroup>
                                        <col style="width:4%"><col style="width:42%"><col style="width:4%">
                                        <col style="width:12%"><col style="width:38%">
                                    </colgroup>
                                    <tr>
                                        <td class="py-0.5">a</td>
                                        <td class="py-0.5">Saldo KAS (Uang kertas dan uang logam)</td>
                                        <td class="py-0.5">:</td>
                                        <td class="py-0.5">Rp</td>
                                        <td class="text-right py-0.5">{{ number_format($saldoKasTunai, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="py-0.5">b</td>
                                        <td class="py-0.5">Saldo Bank</td>
                                        <td class="py-0.5">:</td>
                                        <td class="py-0.5">Rp</td>
                                        <td class="text-right py-0.5">{{ number_format((int) ($formulir->saldo_rekening_bank ?? 0), 0, ',', '.') }}</td>
                                    </tr>
                                    {{-- Perbaikan 2026-09-23 (round ketiga): garis pembatas HANYA di
                                         kolom "Rp" dan nilainya, bukan di seluruh baris. --}}
                                    <tr class="font-bold">
                                        <td colspan="2" class="py-1">Jumlah</td>
                                        <td class="py-1">:</td>
                                        <td class="py-1 border-t border-slate-300">Rp</td>
                                        <td class="text-right py-1 border-t border-slate-300">{{ number_format($jumlahB, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="py-1 pt-3">Saldo menurut Buku Kas Umum (BKU)</td>
                                        <td class="py-1 pt-3">:</td>
                                        <td class="py-1 pt-3">Rp</td>
                                        <td class="text-right py-1 pt-3">{{ number_format($saldoBku, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="py-1">Perbedaan Antara Saldo KAS dan Kas Umum</td>
                                        <td class="py-1">:</td>
                                        <td class="py-1">Rp</td>
                                        <td class="text-right py-1">{{ number_format($perbedaan, 0, ',', '.') }}</td>
                                    </tr>
                                </table>

                                {{-- Perbaikan 2026-09-23 (round kelima): tanda tangan Bendahara &
                                     Kepala Sekolah SEBELUMNYA rata KIRI di kolomnya masing-masing
                                     (round keempat - supaya sejajar persis dengan kolom "Rp" di
                                     tabel rincian). Sekarang diubah jadi rata TENGAH ("posisi di
                                     tengah-tengah") sesuai permintaan baru - class="text-center"
                                     ditambahkan ke semua sel berisi teks di kolom Bendahara
                                     (colspan 2) & Kepala Sekolah (colspan 3). Kolom Kepala Sekolah &
                                     Bendahara SENDIRI (colspan/titik mulai) tidak dipindah dari
                                     round ketiga. Ruang tanda tangan diperbesar lagi dari 3 baris
                                     kosong menjadi 4 baris kosong. --}}
                                <table class="w-full text-xs mt-8" style="table-layout:fixed">
                                    <colgroup>
                                        <col style="width:16.6667%"><col style="width:16.6667%"><col style="width:16.6667%">
                                        <col style="width:16.6667%"><col style="width:16.6667%"><col style="width:16.6667%">
                                    </colgroup>
                                    <tr>
                                        <td></td>
                                        <td colspan="2" class="text-center">Bendahara / Pemegang KAS</td>
                                        <td colspan="3" class="text-center">Kepala Sekolah</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td colspan="2"></td>
                                        <td colspan="3" class="text-center">{{ $sekolah->nama_sekolah }}</td>
                                    </tr>
                                    <tr><td class="h-5"></td><td colspan="2"></td><td colspan="3"></td></tr>
                                    <tr><td class="h-5"></td><td colspan="2"></td><td colspan="3"></td></tr>
                                    <tr><td class="h-5"></td><td colspan="2"></td><td colspan="3"></td></tr>
                                    <tr><td class="h-5"></td><td colspan="2"></td><td colspan="3"></td></tr>
                                    <tr class="font-bold underline">
                                        <td></td>
                                        <td colspan="2" class="text-center">{{ $sekolah->nama_bendahara }}</td>
                                        <td colspan="3" class="text-center">{{ $sekolah->nama_kepala_sekolah }}</td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td colspan="2" class="text-center">NIP. {{ $sekolah->nip_bendahara }}</td>
                                        <td colspan="3" class="text-center">NIP. {{ $sekolah->nip_kepala_sekolah }}</td>
                                    </tr>
                                </table>
                            </div>
                        @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
