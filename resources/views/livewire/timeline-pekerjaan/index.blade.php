<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Timeline Pekerjaan') }}
    </h2>
</x-slot>

<div class="py-12">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                {{ session('status') }}
            </div>
        @endif

        <div class="bg-white shadow sm:rounded-lg p-4 sm:p-8">
            <p class="text-sm text-slate-500 mb-2">
                @if ($bolehKelola)
                    Atur batas waktu (deadline) pengerjaan tiap tahap kerja Pendataan OPS &amp; Pendataan BOSP
                    untuk tahun &amp; triwulan yang dipilih. Deadline berlaku untuk SEMUA sekolah sekaligus (bukan
                    per sekolah). Kosongkan tanggal untuk membatalkan/mereset deadline suatu tahap kerja.
                @else
                    Halaman ini menampilkan batas waktu (deadline) pengerjaan tiap tahap kerja Pendataan OPS
                    &amp; Pendataan BOSP untuk tahun &amp; triwulan yang dipilih, beserta status terlambat/tidaknya.
                    Hanya Superadmin yang bisa mengatur tanggal deadline.
                @endif
            </p>

            <div class="flex flex-wrap items-center gap-4 mb-6">
                <div class="flex items-center gap-2.5">
                    <label for="tahun" class="text-sm text-slate-600">Tahun</label>
                    <select wire:model.live="tahun" id="tahun" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                        @foreach ($tahunOptions as $opsiTahun)
                            <option value="{{ $opsiTahun }}">{{ $opsiTahun }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-1.5">
                    <label class="text-sm text-slate-600 mr-1">Triwulan</label>
                    @foreach ([1, 2, 3, 4] as $opsiTriwulan)
                        <button
                            type="button"
                            wire:click="$set('triwulan', {{ $opsiTriwulan }})"
                            class="px-3 py-1.5 rounded-md text-sm font-medium border {{ $triwulan === $opsiTriwulan ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-slate-300 text-slate-600 hover:bg-slate-50' }}"
                        >
                            TW-{{ $opsiTriwulan }}
                        </button>
                    @endforeach
                </div>
            </div>

            @php
                $kategoriLabel = ['ops' => 'Pendataan OPS', 'bosp' => 'Pendataan BOSP'];
                $badgeStatus = [
                    'belum_diatur' => ['label' => 'Belum diatur', 'class' => 'bg-slate-100 text-slate-500'],
                    'berjalan' => ['label' => 'Berjalan', 'class' => 'bg-blue-50 text-blue-700'],
                    'terlambat' => ['label' => 'Terlambat', 'class' => 'bg-red-50 text-red-700'],
                ];
            @endphp

            @foreach ($kategoriLabel as $kategori => $labelKategori)
                <div class="mb-8 last:mb-0">
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-3">{{ $labelKategori }}</h3>

                    @if ($bolehKelola)
                        <div class="flex flex-wrap items-end gap-2.5 mb-4 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                            <div>
                                <label for="tanggalTerapkanSemua-{{ $kategori }}" class="block text-xs text-slate-600 mb-1">
                                    Terapkan 1 tanggal ke SEMUA tahap kerja {{ $labelKategori }} (Triwulan {{ $triwulan }} saja)
                                </label>
                                <input
                                    type="date"
                                    wire:model="tanggalTerapkanSemua.{{ $kategori }}"
                                    id="tanggalTerapkanSemua-{{ $kategori }}"
                                    class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm"
                                />
                                @error("tanggalTerapkanSemua.{$kategori}")
                                    <span class="block text-xs text-red-600 mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                            <button
                                type="button"
                                wire:click="terapkanSemua('{{ $kategori }}')"
                                wire:confirm="Terapkan tanggal ini ke SEMUA tahap kerja {{ $labelKategori }} untuk Triwulan {{ $triwulan }}? Tanggal deadline yang sudah diatur sebelumnya (untuk triwulan ini) akan ditimpa."
                                class="px-3 py-1.5 rounded-md text-sm font-medium bg-slate-800 text-white hover:bg-slate-700"
                            >
                                Terapkan ke Semua
                            </button>
                            <button
                                type="button"
                                wire:click="resetSemua('{{ $kategori }}')"
                                wire:confirm="Kosongkan/reset SEMUA deadline {{ $labelKategori }} untuk Triwulan {{ $triwulan }}? Tahap kerja yang sebelumnya sudah terlambat akan langsung terbuka lagi."
                                class="px-3 py-1.5 rounded-md text-sm font-medium bg-white border border-red-300 text-red-600 hover:bg-red-50"
                            >
                                Reset Semua
                            </button>
                        </div>
                    @endif

                    <div class="overflow-x-auto border border-slate-200 rounded-lg">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-semibold text-slate-600">Tahap Kerja (Menu)</th>
                                    <th class="px-4 py-2.5 text-left font-semibold text-slate-600 w-52">Tanggal Deadline</th>
                                    <th class="px-4 py-2.5 text-left font-semibold text-slate-600 w-36">Status</th>
                                    @if ($bolehKelola)
                                        <th class="px-4 py-2.5 text-left font-semibold text-slate-600 w-24">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($tahapKerja as $kunciMenu => $info)
                                    @continue($info['kategori'] !== $kategori)
                                    @php $kunciAman = \App\Livewire\TimelinePekerjaan\Index::kunciAman($kunciMenu); @endphp
                                    <tr wire:key="tahap-{{ $kunciMenu }}">
                                        <td class="px-4 py-2.5 text-slate-700">
                                            <a href="{{ route($kunciMenu) }}" wire:navigate class="hover:underline hover:text-blue-600">
                                                {{ $info['label'] }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-2.5">
                                            @if ($bolehKelola)
                                                <input
                                                    type="date"
                                                    wire:model="tanggal.{{ $kunciAman }}"
                                                    wire:change="simpanDeadline('{{ $kunciMenu }}')"
                                                    class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm w-full"
                                                />
                                                @error("tanggal.{$kunciAman}")
                                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                                @enderror
                                            @else
                                                <span class="text-slate-600">
                                                    {{ $tanggal[$kunciAman] ? \Illuminate\Support\Carbon::parse($tanggal[$kunciAman])->translatedFormat('d F Y') : '-' }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeStatus[$status[$kunciMenu]]['class'] }}">
                                                {{ $badgeStatus[$status[$kunciMenu]]['label'] }}
                                            </span>
                                        </td>
                                        @if ($bolehKelola)
                                            <td class="px-4 py-2.5">
                                                @if ($tanggal[$kunciAman])
                                                    <button
                                                        type="button"
                                                        wire:click="hapusDeadline('{{ $kunciMenu }}')"
                                                        wire:confirm="Kosongkan/reset deadline '{{ $info['label'] }}' untuk Triwulan {{ $triwulan }} tahun {{ $tahun }}? Menu ini akan langsung terbuka lagi kalau sebelumnya sudah terlambat."
                                                        class="text-xs text-red-600 hover:text-red-800 font-medium"
                                                    >
                                                        Reset
                                                    </button>
                                                @endif
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
