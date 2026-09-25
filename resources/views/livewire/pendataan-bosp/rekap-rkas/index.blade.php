<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Rekap RKAS Awal-Perubahan') }}
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
                        <h3 class="text-lg font-medium text-slate-900">Rekap RKAS Awal-Perubahan</h3>
                        <p class="text-sm text-slate-500">Rekap RKAS Awal-Perubahan per sekolah, per tahun.</p>
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

                <p class="text-xs text-slate-500 mb-2">Anda bisa mengetik langsung di tiap kotak pada tabel (tersimpan otomatis begitu pindah kotak), atau memakai tombol "Isi/Edit" di kolom Aksi untuk mengisi lewat form.</p>

                <div class="overflow-auto scrollbar-modern border border-blue-200 rounded-lg" style="max-height: 34rem; zoom: {{ $zoomPercent }}%;">
                    <table class="min-w-full divide-y divide-blue-100 text-xs">
                        <thead class="sticky top-0 z-10 bg-blue-50">
                            <tr class="text-center text-slate-700">
                                <th rowspan="4" class="px-2 py-2.5 align-middle border-2 border-blue-300">No</th>
                                <th rowspan="4" class="px-2 py-2.5 align-middle border-2 border-blue-300">Nama Sekolah</th>
                                <th rowspan="4" class="px-2 py-2.5 align-middle border-2 border-blue-300">Anggaran BOSP {{ $tahun }}</th>
                                <th colspan="25" class="px-2 py-2.5 border-2 border-blue-300">BELANJA</th>
                                <th colspan="3" rowspan="2" class="px-2 py-2.5 align-middle border-2 border-blue-300 {{ \App\Models\RekapRkas::WARNA_JUMLAH }} font-bold">JUMLAH</th>
                                <th rowspan="4" class="px-2 py-2.5 align-middle border-2 border-blue-300">Aksi</th>
                            </tr>
                            <tr class="text-center text-slate-700">
                                @foreach ($kategori as $key => $info)
                                    <th colspan="5" class="px-2 py-2.5 border-2 border-blue-300 {{ $info['warna'] }} font-bold">{{ $info['label'] }}</th>
                                @endforeach
                            </tr>
                            <tr class="text-center text-slate-700">
                                @foreach ($kategori as $key => $info)
                                    <th rowspan="2" class="px-2 py-2.5 align-middle border-2 border-blue-300 {{ $info['warna'] }} font-bold">Sebelum {{ $info['sebelum'] }}</th>
                                    <th colspan="3" class="px-2 py-2.5 border-2 border-blue-300 {{ $info['warna'] }} font-bold">SESUDAH</th>
                                    <th rowspan="2" class="px-2 py-2.5 align-middle border-2 border-blue-300 {{ $info['warna'] }} font-bold">Selisih {{ $info['singkatan'] }}</th>
                                @endforeach
                                <th rowspan="2" class="px-2 py-2.5 align-middle border-2 border-blue-300 {{ \App\Models\RekapRkas::WARNA_JUMLAH }} font-bold">Sebelum (Jumlah)</th>
                                <th rowspan="2" class="px-2 py-2.5 align-middle border-2 border-blue-300 {{ \App\Models\RekapRkas::WARNA_JUMLAH }} font-bold">Sesudah</th>
                                <th rowspan="2" class="px-2 py-2.5 align-middle border-2 border-blue-300 {{ \App\Models\RekapRkas::WARNA_JUMLAH }} font-bold">Selisih Belanja</th>
                            </tr>
                            <tr class="text-center text-slate-700">
                                @foreach ($kategori as $key => $info)
                                    <th class="px-2 py-2.5 border-2 border-blue-300 {{ $info['warna'] }} font-bold">Realisasi Tahap 1</th>
                                    <th class="px-2 py-2.5 border-2 border-blue-300 {{ $info['warna'] }} font-bold">Perubahan Tahap 2</th>
                                    <th class="px-2 py-2.5 border-2 border-blue-300 {{ $info['warna'] }} font-bold">Jml Sesudah</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-blue-100">
                            @forelse ($daftarSekolah as $sekolah)
                                @php $rekap = $sekolah->rekapRkasTahunIni; @endphp
                                @if (! $sekolah->danaBospTahapTerisi)
                                    {{--
                                        Gate (permintaan user 2026-09-23): sekolah ini belum mengisi
                                        Dana BOSP Tahap 1 & 2 - Penerimaan BOSP untuk tahun yang
                                        aktif, jadi baris Rekap RKAS-nya terkunci - seluruh kolom
                                        data (Anggaran BOSP s.d. Jumlah, 29 kolom = 1 + 25 + 3,
                                        SAMA dengan colspan="32" pada baris "Belum ada data sekolah"
                                        di bawah dikurangi No & Nama Sekolah & Aksi) digabung jadi 1
                                        pesan terkunci, bukan kotak input.
                                    --}}
                                    <tr wire:key="rekap-rkas-baris-{{ $sekolah->id }}" class="bg-amber-50/60">
                                        <td class="px-2 py-2 whitespace-nowrap text-center text-slate-500 border border-blue-100">{{ $loop->iteration }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800 border border-blue-100">{{ $sekolah->nama_sekolah }}</td>
                                        <td colspan="29" class="px-3 py-2 text-center text-amber-700 border border-blue-100 text-xs">
                                            Sekolah ini belum mengisi <strong>Dana BOSP Tahap 1 &amp; 2 - Penerimaan BOSP</strong> untuk tahun {{ $tahun }}. Rekap RKAS baru bisa diisi setelah data itu dilengkapi.
                                        </td>
                                        <td class="px-2 py-2 whitespace-nowrap text-center border border-blue-100">
                                            @if ($bolehKelolaSemua || auth()->user()->profil_sekolah_id === $sekolah->id)
                                                <a href="{{ route('pendataan-bosp.dana-bosp-tahap') }}" class="text-amber-700 font-semibold hover:underline whitespace-nowrap">Isi Dana BOSP Tahap &rarr;</a>
                                            @else
                                                <span class="text-slate-300">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @continue
                                @endif
                                <tr wire:key="rekap-rkas-baris-{{ $sekolah->id }}">
                                    <td class="px-2 py-2 whitespace-nowrap text-center text-slate-500 border border-blue-100">{{ $loop->iteration }}</td>
                                    <td class="px-2 py-2 whitespace-nowrap font-medium text-slate-800 border border-blue-100">{{ $sekolah->nama_sekolah }}</td>
                                    @php $revisi = $revisiBaris[$sekolah->id] ?? 0; @endphp
                                    {{--
                                        Anggaran BOSP {tahun} OTOMATIS (permintaan user 2026-09-23) -
                                        BUKAN <x-rekap-rkas-cell> (kotak input) lagi, murni tampilan
                                        read-only kuning (mengikuti pola field otomatis lain di
                                        aplikasi ini), diambil dari Dana BOSP Tahap - Penerimaan BOSP.
                                    --}}
                                    <td class="px-2 py-2 whitespace-nowrap text-right text-slate-600 bg-yellow-50 border border-blue-100">
                                        Rp {{ number_format((int) ($baris[$sekolah->id]['anggaran_bosp'] ?? 0), 0, ',', '.') }}
                                    </td>

                                    @foreach ($kategori as $key => $info)
                                        <td class="px-1 py-1.5 whitespace-nowrap border border-blue-100 {{ $info['warna'] }}">
                                            <x-rekap-rkas-cell :sekolah-id="$sekolah->id" :field="$key.'_sebelum'" :value="$baris[$sekolah->id][$key.'_sebelum'] ?? ''" :revisi="$revisi" class="{{ $info['warna'] }} font-bold" />
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap border border-blue-100 {{ $info['warna'] }}">
                                            <x-rekap-rkas-cell :sekolah-id="$sekolah->id" :field="$key.'_realisasi_tahap1'" :value="$baris[$sekolah->id][$key.'_realisasi_tahap1'] ?? ''" :revisi="$revisi" class="{{ $info['warna'] }} font-bold" />
                                        </td>
                                        <td class="px-1 py-1.5 whitespace-nowrap border border-blue-100 {{ $info['warna'] }}">
                                            <x-rekap-rkas-cell :sekolah-id="$sekolah->id" :field="$key.'_perubahan_tahap2'" :value="$baris[$sekolah->id][$key.'_perubahan_tahap2'] ?? ''" :revisi="$revisi" class="{{ $info['warna'] }} font-bold" />
                                        </td>
                                        @if (in_array($key, \App\Models\RekapRkas::KATEGORI_RUMUS, true))
                                            {{-- Rumus sudah ditentukan (2026-09-09 lanjutan ke-4) - kolom ini
                                                 BUKAN kotak input lagi, murni tampilan hasil hitung otomatis
                                                 (lihat Index::updated()/hitungUlangRumus() & simpan()). --}}
                                            <td class="px-1 py-1.5 whitespace-nowrap text-right border border-blue-100 {{ $info['warna'] }} font-bold">Rp {{ number_format((int) ($baris[$sekolah->id][$key.'_jml_sesudah'] ?? 0), 0, ',', '.') }}</td>
                                            <td class="px-1 py-1.5 whitespace-nowrap text-right border border-blue-100 {{ $info['warna'] }} font-bold">Rp {{ number_format((int) ($baris[$sekolah->id][$key.'_selisih'] ?? 0), 0, ',', '.') }}</td>
                                        @else
                                            <td class="px-1 py-1.5 whitespace-nowrap border border-blue-100 {{ $info['warna'] }}">
                                                <x-rekap-rkas-cell :sekolah-id="$sekolah->id" :field="$key.'_jml_sesudah'" :value="$baris[$sekolah->id][$key.'_jml_sesudah'] ?? ''" :revisi="$revisi" class="{{ $info['warna'] }} font-bold" />
                                            </td>
                                            <td class="px-1 py-1.5 whitespace-nowrap border border-blue-100 {{ $info['warna'] }}">
                                                <x-rekap-rkas-cell :sekolah-id="$sekolah->id" :field="$key.'_selisih'" :value="$baris[$sekolah->id][$key.'_selisih'] ?? ''" :revisi="$revisi" class="{{ $info['warna'] }} font-bold" />
                                            </td>
                                        @endif
                                    @endforeach

                                    {{-- Rumus sudah ditentukan (2026-09-09 lanjutan ke-5) - kolom
                                         Sebelum/Sesudah/Selisih baris JUMLAH ini BUKAN kotak input
                                         lagi, murni tampilan hasil hitung otomatis (lihat
                                         Index::updated()/hitungUlangJumlahBaris() & simpan()). --}}
                                    <td class="px-1 py-1.5 whitespace-nowrap text-right border border-blue-100 {{ \App\Models\RekapRkas::WARNA_JUMLAH }} font-bold">Rp {{ number_format((int) ($baris[$sekolah->id]['jumlah_sebelum'] ?? 0), 0, ',', '.') }}</td>
                                    <td class="px-1 py-1.5 whitespace-nowrap text-right border border-blue-100 {{ \App\Models\RekapRkas::WARNA_JUMLAH }} font-bold">Rp {{ number_format((int) ($baris[$sekolah->id]['jumlah_sesudah'] ?? 0), 0, ',', '.') }}</td>
                                    <td class="px-1 py-1.5 whitespace-nowrap text-right border border-blue-100 {{ \App\Models\RekapRkas::WARNA_JUMLAH }} font-bold">Rp {{ number_format((int) ($baris[$sekolah->id]['jumlah_selisih'] ?? 0), 0, ',', '.') }}</td>

                                    <td class="px-2 py-2 whitespace-nowrap text-center border border-blue-100">
                                        @if ($bolehKelolaSemua || auth()->user()->profil_sekolah_id === $sekolah->id)
                                            <button wire:click="isi({{ $sekolah->id }})" class="text-blue-600 hover:underline">
                                                {{ $rekap ? 'Edit' : 'Isi' }}
                                            </button>
                                        @else
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="32" class="px-3 py-6 text-center text-slate-400">Belum ada data sekolah.</td>
                                </tr>
                            @endforelse

                            @if ($daftarSekolah->isNotEmpty())
                                {{-- Baris Jumlah (total) - dihitung otomatis sebagai penjumlahan seluruh
                                     baris sekolah di atas (sesuai jawaban AskUserQuestion 2026-09-09
                                     lanjutan ke-3: "Otomatis dihitung sistem") - baris ini SELALU hasil
                                     hitung, bukan kotak yang bisa diketik/diedit manual. Label "Jumlah"
                                     ditaruh di kolom Nama Sekolah (kolom B), rata tengah, sesuai posisi
                                     yang diminta. --}}
                                <tr wire:key="rekap-rkas-baris-jumlah" class="bg-slate-100 font-bold">
                                    <td class="px-2 py-2 whitespace-nowrap border border-blue-100"></td>
                                    <td class="px-2 py-2 whitespace-nowrap text-center text-slate-800 border border-blue-100">Jumlah</td>
                                    <td class="px-1 py-1.5 whitespace-nowrap text-right border border-blue-100">Rp {{ number_format($totalBaris['anggaran_bosp'], 0, ',', '.') }}</td>

                                    @foreach ($kategori as $key => $info)
                                        <td class="px-1 py-1.5 whitespace-nowrap text-right border border-blue-100 {{ $info['warna'] }}">Rp {{ number_format($totalBaris[$key.'_sebelum'], 0, ',', '.') }}</td>
                                        <td class="px-1 py-1.5 whitespace-nowrap text-right border border-blue-100 {{ $info['warna'] }}">Rp {{ number_format($totalBaris[$key.'_realisasi_tahap1'], 0, ',', '.') }}</td>
                                        <td class="px-1 py-1.5 whitespace-nowrap text-right border border-blue-100 {{ $info['warna'] }}">Rp {{ number_format($totalBaris[$key.'_perubahan_tahap2'], 0, ',', '.') }}</td>
                                        <td class="px-1 py-1.5 whitespace-nowrap text-right border border-blue-100 {{ $info['warna'] }}">Rp {{ number_format($totalBaris[$key.'_jml_sesudah'], 0, ',', '.') }}</td>
                                        <td class="px-1 py-1.5 whitespace-nowrap text-right border border-blue-100 {{ $info['warna'] }}">Rp {{ number_format($totalBaris[$key.'_selisih'], 0, ',', '.') }}</td>
                                    @endforeach

                                    <td class="px-1 py-1.5 whitespace-nowrap text-right border border-blue-100 {{ \App\Models\RekapRkas::WARNA_JUMLAH }}">Rp {{ number_format($totalBaris['jumlah_sebelum'], 0, ',', '.') }}</td>
                                    <td class="px-1 py-1.5 whitespace-nowrap text-right border border-blue-100 {{ \App\Models\RekapRkas::WARNA_JUMLAH }}">Rp {{ number_format($totalBaris['jumlah_sesudah'], 0, ',', '.') }}</td>
                                    <td class="px-1 py-1.5 whitespace-nowrap text-right border border-blue-100 {{ \App\Models\RekapRkas::WARNA_JUMLAH }}">Rp {{ number_format($totalBaris['jumlah_selisih'], 0, ',', '.') }}</td>

                                    <td class="px-2 py-2 whitespace-nowrap border border-blue-100"></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Form Rekap RKAS Awal-Perubahan --}}
    <x-modal name="rekap-rkas-form" :show="$showForm" maxWidth="4xl">
        <form wire:submit="simpan" class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-1">Rekap RKAS Awal-Perubahan</h2>
            <p class="text-sm text-slate-500 mb-4">Tahun {{ $tahun }}</p>

            <div class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label value="Anggaran BOSP {{ $tahun }}" />
                        <div class="mt-1 block w-full rounded-md border border-amber-200 bg-amber-50 text-slate-700 text-sm px-3 py-2 text-right">
                            Rp {{ number_format((int) ($anggaran_bosp ?: 0), 0, ',', '.') }}
                        </div>
                        <p class="text-xs text-slate-400 mt-1">Otomatis mengikuti Total Penerimaan BOSP Setahun pada Dana BOSP Tahap 1 & 2 - Penerimaan BOSP.</p>
                    </div>
                </div>

                @foreach ($kategori as $key => $info)
                    @php
                        $fSebelum = $key.'_sebelum';
                        $fRealisasi = $key.'_realisasi_tahap1';
                        $fPerubahan = $key.'_perubahan_tahap2';
                        $fJmlSesudah = $key.'_jml_sesudah';
                        $fSelisih = $key.'_selisih';
                        $adaRumus = in_array($key, \App\Models\RekapRkas::KATEGORI_RUMUS, true);
                    @endphp
                    <div class="border-t border-slate-200 pt-4">
                        <h3 class="text-sm font-semibold text-slate-700 mb-3">{{ $info['label'] }}</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                            <div>
                                <x-input-label :for="$fSebelum" value="Sebelum" />
                                <x-currency-input :name="$fSebelum" :value="${$fSebelum}" :reset-key="$formInstance" border-class="border-blue-300" />
                                <x-input-error :messages="$errors->get($fSebelum)" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label :for="$fRealisasi" value="Realisasi Tahap 1" />
                                <x-currency-input :name="$fRealisasi" :value="${$fRealisasi}" :reset-key="$formInstance" border-class="border-blue-300" />
                                <x-input-error :messages="$errors->get($fRealisasi)" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label :for="$fPerubahan" value="Perubahan Tahap 2" />
                                <x-currency-input :name="$fPerubahan" :value="${$fPerubahan}" :reset-key="$formInstance" border-class="border-blue-300" />
                                <x-input-error :messages="$errors->get($fPerubahan)" class="mt-2" />
                            </div>
                            @if ($adaRumus)
                                {{-- Rumus sudah ditentukan (2026-09-09 lanjutan ke-4): Jml Sesudah &
                                     Selisih BUKAN kotak input lagi, murni tampilan hasil hitung
                                     terakhir - nilai barunya baru terlihat di sini setelah form
                                     ini disimpan (lihat Index::simpan()). --}}
                                <div>
                                    <x-input-label :for="$fJmlSesudah" value="Jml Sesudah" />
                                    <div class="mt-1 block w-full rounded-md border border-blue-200 bg-blue-50 text-slate-700 text-sm px-3 py-2 text-right">
                                        Rp {{ number_format((int) (${$fJmlSesudah} ?: 0), 0, ',', '.') }}
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1">Otomatis: Realisasi Tahap 1 + Perubahan Tahap 2 (dihitung ulang saat disimpan).</p>
                                </div>
                                <div>
                                    <x-input-label :for="$fSelisih" value="Selisih {{ $info['singkatan'] }}" />
                                    <div class="mt-1 block w-full rounded-md border border-blue-200 bg-blue-50 text-slate-700 text-sm px-3 py-2 text-right">
                                        Rp {{ number_format((int) (${$fSelisih} ?: 0), 0, ',', '.') }}
                                    </div>
                                    <p class="text-xs text-slate-400 mt-1">Otomatis: Sebelum - Jml Sesudah (dihitung ulang saat disimpan).</p>
                                </div>
                            @else
                                <div>
                                    <x-input-label :for="$fJmlSesudah" value="Jml Sesudah" />
                                    <x-currency-input :name="$fJmlSesudah" :value="${$fJmlSesudah}" :reset-key="$formInstance" border-class="border-blue-300" />
                                    <p class="text-xs text-slate-400 mt-1">Sementara diisi manual.</p>
                                    <x-input-error :messages="$errors->get($fJmlSesudah)" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label :for="$fSelisih" value="Selisih {{ $info['singkatan'] }}" />
                                    <x-currency-input :name="$fSelisih" :value="${$fSelisih}" :reset-key="$formInstance" border-class="border-blue-300" />
                                    <p class="text-xs text-slate-400 mt-1">Sementara diisi manual.</p>
                                    <x-input-error :messages="$errors->get($fSelisih)" class="mt-2" />
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div class="border-t border-slate-200 pt-4">
                    <h3 class="text-sm font-semibold text-slate-700 mb-3">JUMLAH</h3>
                    {{-- Rumus sudah ditentukan (2026-09-09 lanjutan ke-5): Sebelum/Sesudah/
                         Selisih baris JUMLAH BUKAN kotak input lagi, murni tampilan hasil
                         hitung terakhir - nilai barunya baru terlihat di sini setelah form
                         ini disimpan (lihat Index::simpan()). --}}
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label for="jumlah_sebelum" value="Sebelum" />
                            <div class="mt-1 block w-full rounded-md border border-blue-200 bg-blue-50 text-slate-700 text-sm px-3 py-2 text-right">
                                Rp {{ number_format((int) ($jumlah_sebelum ?: 0), 0, ',', '.') }}
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Otomatis: total Sebelum seluruh kategori Belanja (dihitung ulang saat disimpan).</p>
                        </div>
                        <div>
                            <x-input-label for="jumlah_sesudah" value="Sesudah" />
                            <div class="mt-1 block w-full rounded-md border border-blue-200 bg-blue-50 text-slate-700 text-sm px-3 py-2 text-right">
                                Rp {{ number_format((int) ($jumlah_sesudah ?: 0), 0, ',', '.') }}
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Otomatis: total Jml Sesudah seluruh kategori Belanja (dihitung ulang saat disimpan).</p>
                        </div>
                        <div>
                            <x-input-label for="jumlah_selisih" value="Selisih Belanja" />
                            <div class="mt-1 block w-full rounded-md border border-blue-200 bg-blue-50 text-slate-700 text-sm px-3 py-2 text-right">
                                Rp {{ number_format((int) ($jumlah_selisih ?: 0), 0, ',', '.') }}
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Otomatis: Sebelum - Sesudah (dihitung ulang saat disimpan).</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="batal">Batal</x-secondary-button>
                <x-primary-button>Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
