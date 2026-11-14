<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Upload Rekening Koran') }}
    </h2>
</x-slot>

@php
    $labelTw = [1 => 'Triwulan 1', 2 => 'Triwulan 2', 3 => 'Triwulan 3', 4 => 'Triwulan 4'];

    // Palet warna berbeda per kartu Triwulan (permintaan user 2026-11-14:
    // "dibuat bergaya animasi dengan warna yang modern dan menarik") -
    // SENGAJA ditulis sebagai string class Tailwind LENGKAP (bukan
    // disambung/interpolasi seperti "bg-{$x}-500"), supaya tetap terdeteksi
    // oleh Tailwind JIT scanner (yang mencari string literal, bukan hasil
    // PHP runtime).
    $warnaTw = [
        1 => [
            'bar' => 'bg-gradient-to-r from-indigo-500 to-blue-500',
            'iconBg' => 'bg-gradient-to-br from-indigo-500 to-blue-500',
            'border' => 'border-indigo-200',
            'borderHover' => 'hover:border-indigo-400',
            'text' => 'text-indigo-600',
            'hoverBg' => 'hover:bg-gradient-to-br hover:from-indigo-50 hover:to-blue-50',
        ],
        2 => [
            'bar' => 'bg-gradient-to-r from-violet-500 to-fuchsia-500',
            'iconBg' => 'bg-gradient-to-br from-violet-500 to-fuchsia-500',
            'border' => 'border-violet-200',
            'borderHover' => 'hover:border-violet-400',
            'text' => 'text-violet-600',
            'hoverBg' => 'hover:bg-gradient-to-br hover:from-violet-50 hover:to-fuchsia-50',
        ],
        3 => [
            'bar' => 'bg-gradient-to-r from-sky-500 to-cyan-500',
            'iconBg' => 'bg-gradient-to-br from-sky-500 to-cyan-500',
            'border' => 'border-sky-200',
            'borderHover' => 'hover:border-sky-400',
            'text' => 'text-sky-600',
            'hoverBg' => 'hover:bg-gradient-to-br hover:from-sky-50 hover:to-cyan-50',
        ],
        4 => [
            'bar' => 'bg-gradient-to-r from-emerald-500 to-teal-500',
            'iconBg' => 'bg-gradient-to-br from-emerald-500 to-teal-500',
            'border' => 'border-emerald-200',
            'borderHover' => 'hover:border-emerald-400',
            'text' => 'text-emerald-600',
            'hoverBg' => 'hover:bg-gradient-to-br hover:from-emerald-50 hover:to-teal-50',
        ],
    ];
@endphp

<div>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($pesanGabung)
                <div class="p-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-lg text-sm">
                    {{ $pesanGabung }}
                </div>
            @endif

            @if ($errorUnduh)
                <div class="p-4 bg-amber-50 border border-amber-200 text-amber-700 rounded-lg text-sm">
                    {{ $errorUnduh }}
                </div>
            @endif

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                @if ($bolehKelolaSemua)
                    <div class="border-b border-slate-200 px-4 sm:px-8 pt-4">
                        <nav class="flex flex-wrap gap-3">
                            <button
                                type="button"
                                wire:click="pindahTab('input')"
                                @class([
                                    'px-4 py-2.5 rounded-t-xl text-sm font-bold tracking-wide transition-all duration-150 border-b-4',
                                    'bg-white text-indigo-700 border-indigo-500 shadow-sm' => $tab === 'input',
                                    'bg-slate-50 text-slate-500 border-transparent hover:bg-slate-100 hover:text-slate-700' => $tab !== 'input',
                                ])
                            >
                                Upload Per Sekolah
                            </button>
                            <button
                                type="button"
                                wire:click="pindahTab('semua')"
                                @class([
                                    'px-4 py-2.5 rounded-t-xl text-sm font-bold tracking-wide transition-all duration-150 border-b-4',
                                    'bg-white text-violet-700 border-violet-500 shadow-sm' => $tab === 'semua',
                                    'bg-slate-50 text-slate-500 border-transparent hover:bg-slate-100 hover:text-slate-700' => $tab !== 'semua',
                                ])
                            >
                                Semua Sekolah (Gabungkan & Unduh)
                            </button>
                        </nav>
                    </div>
                @endif

                <div class="p-4 sm:p-8">
                    <div class="flex flex-wrap items-center gap-2.5 mb-6">
                        <label for="tahun" class="text-sm text-slate-600">Tahun</label>
                        @if ($bolehKelolaSemua)
                            <select wire:model.live="tahun" id="tahun" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">
                                @foreach ($tahunOptions as $opsiTahun)
                                    <option value="{{ $opsiTahun }}">{{ $opsiTahun }}</option>
                                @endforeach
                            </select>
                        @else
                            <span id="tahun" class="inline-flex items-center rounded-md bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-700">{{ $tahun }}</span>
                        @endif

                        @if ($tab === 'input' && $bolehKelolaSemua)
                            <select wire:model.live="profil_sekolah_id" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-xs">
                                <option value="">-- Pilih Sekolah --</option>
                                @foreach ($sekolahOptions as $opsiSekolah)
                                    <option value="{{ $opsiSekolah->id }}">{{ $opsiSekolah->nama_sekolah }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    @if ($tab === 'input')
                        @if (! $sekolahAktif)
                            <div class="text-sm text-slate-400 border border-dashed border-slate-300 rounded-lg py-10 text-center">
                                Pilih salah satu sekolah pada filter di atas terlebih dahulu.
                            </div>
                        @else
                            <p class="text-xs text-slate-400 mb-4">Pilih salah satu Triwulan untuk mengupload file PDF-nya (maksimal 1 MB). Selama satu Triwulan sedang diupload, Triwulan lain tidak bisa dipilih - setelah selesai, Anda bebas memilih Triwulan lain kapan saja.</p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                @foreach ([1, 2, 3, 4] as $tw)
                                    @php
                                        $kolom = \App\Models\RekeningKoran::kolomTw($tw);
                                        $sudahAda = $rekeningKoranAktif && $rekeningKoranAktif->{$kolom};
                                        $warna = $warnaTw[$tw];
                                    @endphp
                                    <div class="group relative overflow-hidden rounded-2xl border {{ $warna['border'] }} {{ $warna['borderHover'] }} bg-white shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
                                        <div class="absolute inset-x-0 top-0 h-1.5 {{ $warna['bar'] }}"></div>

                                        <div class="p-5 pt-6">
                                            <div class="flex items-center gap-2.5 mb-3.5">
                                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $warna['iconBg'] }} text-white shadow-sm">
                                                    <x-icon name="document-text" class="w-5 h-5" />
                                                </span>
                                                <x-input-label :value="'Rekening Koran '.$labelTw[$tw]" class="!mb-0 !font-semibold" />
                                            </div>

                                            @if ($triwulanDipilih === $tw)
                                                <div wire:loading.remove wire:target="fileBaru">
                                                    <label class="flex flex-col items-center justify-center gap-2 border-2 border-dashed {{ $warna['border'] }} rounded-xl py-6 {{ $warna['text'] }} {{ $warna['hoverBg'] }} cursor-pointer text-xs transition-all duration-200">
                                                        <span class="flex h-10 w-10 items-center justify-center rounded-full {{ $warna['iconBg'] }} text-white animate-bounce">
                                                            <x-icon name="upload" class="w-5 h-5" />
                                                        </span>
                                                        <span class="font-medium">Pilih file PDF untuk diupload</span>
                                                        <input type="file" wire:model="fileBaru" accept="application/pdf" class="hidden">
                                                    </label>
                                                </div>
                                                <div wire:loading wire:target="fileBaru" class="relative overflow-hidden rounded-xl border {{ $warna['border'] }} py-6 text-center text-xs {{ $warna['text'] }}">
                                                    <div class="relative z-10 flex flex-col items-center gap-2">
                                                        <svg class="h-9 w-9 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                        </svg>
                                                        <span class="font-medium">Mengunggah...</span>
                                                    </div>
                                                    <span class="absolute inset-y-0 left-0 w-1/3 {{ $warna['bar'] }} opacity-25 animate-rk-progress"></span>
                                                </div>
                                                <button type="button" wire:click="batalPilihTriwulanUpload" wire:loading.attr="disabled" wire:target="fileBaru" class="mt-2.5 text-xs text-slate-400 hover:text-slate-600 transition-colors">
                                                    Batalkan
                                                </button>
                                                <x-input-error :messages="$errors->get('fileBaru')" class="mt-2" />
                                            @elseif ($sudahAda)
                                                <div class="animate-rk-pop rounded-xl bg-emerald-50 border border-emerald-200 p-3.5">
                                                    <div class="flex items-center gap-2.5">
                                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 text-white shadow-sm">
                                                            <x-icon name="check" class="w-5 h-5" />
                                                        </span>
                                                        <a href="{{ route('pendataan-bosp.rekening-koran.file', [$tw, $rekeningKoranAktif]) }}" target="_blank" rel="noopener" class="text-xs font-medium text-emerald-700 hover:underline">
                                                            Lihat file
                                                        </a>
                                                    </div>
                                                    <div class="flex items-center gap-2 mt-3 text-xs">
                                                        <button type="button" wire:click="pilihTriwulanUpload({{ $tw }})" @disabled($triwulanDipilih !== null) class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-slate-500 hover:text-slate-700 hover:bg-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">
                                                            <x-icon name="upload" class="w-3.5 h-3.5" />
                                                            Ganti
                                                        </button>
                                                        <button type="button" wire:click="konfirmasiHapusTw({{ $tw }})" @disabled($triwulanDipilih !== null) class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-red-500 hover:text-red-700 hover:bg-white transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer">
                                                            <x-icon name="trash" class="w-3.5 h-3.5" />
                                                            Hapus
                                                        </button>
                                                    </div>
                                                </div>
                                            @else
                                                <button
                                                    type="button"
                                                    wire:click="pilihTriwulanUpload({{ $tw }})"
                                                    @disabled($triwulanDipilih !== null)
                                                    class="relative w-full flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed {{ $warna['border'] }} py-6 {{ $warna['text'] }} {{ $warna['hoverBg'] }} transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-none cursor-pointer"
                                                >
                                                    <span class="relative flex h-10 w-10 items-center justify-center">
                                                        <span class="absolute inset-0 rounded-full {{ $warna['iconBg'] }} opacity-25 animate-ping"></span>
                                                        <span class="relative flex h-10 w-10 items-center justify-center rounded-full {{ $warna['iconBg'] }} text-white shadow-sm transition-transform duration-200 group-hover:scale-110 group-hover:rotate-6">
                                                            <x-icon name="upload" class="w-5 h-5" />
                                                        </span>
                                                    </span>
                                                    <span class="text-xs font-medium">Belum diupload - klik untuk upload</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        {{-- Tab "Semua Sekolah" - khusus Superadmin --}}
                        <div class="flex flex-wrap gap-3 mb-5">
                            <x-secondary-button wire:click="konfirmasiGabungkanSemua" wire:loading.attr="disabled">
                                <x-icon name="document-duplicate" class="w-4 h-4 mr-1.5" />
                                Gabungkan TW1-4 Semua Sekolah
                            </x-secondary-button>
                            <x-primary-button wire:click="unduhZipSemua" wire:loading.attr="disabled" wire:target="unduhZipSemua">
                                <x-icon name="download" class="w-4 h-4 mr-1.5" />
                                Unduh ZIP Semua Sekolah
                            </x-primary-button>
                            <span wire:loading wire:target="unduhZipSemua,gabungkanSemua" class="text-xs text-slate-400 self-center">Memproses, mohon tunggu...</span>
                        </div>

                        <div class="overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Nama Sekolah</th>
                                        <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500">TW1</th>
                                        <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500">TW2</th>
                                        <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500">TW3</th>
                                        <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500">TW4</th>
                                        <th class="px-3 py-2 text-center text-xs font-semibold text-slate-500">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse ($statusSemuaSekolah as $item)
                                        <tr>
                                            <td class="px-3 py-2 whitespace-nowrap">{{ $item['sekolah']->nama_sekolah }}</td>
                                            @foreach ([1, 2, 3, 4] as $tw)
                                                @php
                                                    $kolom = \App\Models\RekeningKoran::kolomTw($tw);
                                                    $ada = $item['baris'] && $item['baris']->{$kolom};
                                                @endphp
                                                <td class="px-3 py-2 text-center">
                                                    @if ($ada)
                                                        <x-icon name="check-circle" class="w-4 h-4 text-emerald-500 inline" />
                                                    @else
                                                        <x-icon name="x-circle" class="w-4 h-4 text-slate-300 inline" />
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="px-3 py-2 text-center">
                                                @if ($item['lengkap'])
                                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Lengkap</span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700">Belum Lengkap</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-3 py-6 text-center text-slate-400">Belum ada data sekolah.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-slate-400 mt-3">Sekolah berstatus "Belum Lengkap" otomatis dilewati saat tombol Gabungkan/Unduh ZIP dijalankan.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <x-modal name="rekening-koran-hapus" :show="$confirmingHapusTw !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">
                Hapus Rekening Koran {{ $confirmingHapusTw ? ($labelTw[$confirmingHapusTw] ?? '') : '' }}?
            </h2>
            <p class="mt-1 text-sm text-slate-600">Setelah dihapus, file akan kosong sampai diupload lagi.</p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalHapusTw" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                <x-danger-button wire:click="hapusTw" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="trash" class="w-3.5 h-3.5 mr-1" />Hapus</x-danger-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="rekening-koran-gabungkan" :show="$confirmingGabungkan" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">
                Gabungkan Rekening Koran TW1-4 seluruh sekolah tahun {{ $tahun }}?
            </h2>
            <p class="mt-1 text-sm text-slate-600">Hanya sekolah yang sudah lengkap 4 Triwulan yang akan digabungkan - sekolah lain otomatis dilewati. Proses ini bisa dijalankan ulang kapan saja (hasil sebelumnya akan ditimpa dengan data terbaru).</p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalGabungkanSemua" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="x-mark" class="w-3.5 h-3.5 mr-1" />Batal</x-secondary-button>
                <x-primary-button wire:click="gabungkanSemua" class="!px-3 !py-1.5 !text-[10px]"><x-icon name="document-duplicate" class="w-3.5 h-3.5 mr-1" />Ya, Gabungkan</x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- Animasi kartu upload per Triwulan (permintaan user 2026-11-14) -
         pola sama seperti <style>/@keyframes di
         resources/views/livewire/sinkronisasi/index.blade.php. --}}
    <style>
        @keyframes rk-progress-slide {
            0%   { left: -33%; }
            100% { left: 100%; }
        }
        .animate-rk-progress {
            animation: rk-progress-slide 1.1s ease-in-out infinite;
        }

        @keyframes rk-pop {
            0%   { transform: scale(0.92); opacity: 0; }
            60%  { transform: scale(1.03); opacity: 1; }
            100% { transform: scale(1); opacity: 1; }
        }
        .animate-rk-pop {
            animation: rk-pop 0.35s ease-out;
        }
    </style>
</div>
