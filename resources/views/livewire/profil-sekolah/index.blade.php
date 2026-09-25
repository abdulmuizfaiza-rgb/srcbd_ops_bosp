<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Profil Sekolah') }}
    </h2>
</x-slot>

<div>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm whitespace-pre-line">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errorImport)
                <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm whitespace-pre-line">
                    {{ $errorImport }}
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="flex flex-col xl:flex-row xl:flex-wrap xl:items-start xl:justify-between gap-3 mb-6">
                    <div>
                        <h3 class="text-lg font-medium text-slate-900">Data Sekolah</h3>
                        <p class="text-sm text-slate-500">Diurutkan berdasarkan status (Negeri &rarr; Swasta), lalu kecamatan.</p>
                    </div>

                    @if ($bolehKelolaSemua)
                        <div class="flex flex-wrap sm:flex-row sm:items-center gap-2.5">
                            <div class="flex items-center gap-1.5 border border-black/10 rounded-md px-1.5 py-1 bg-black/10">
                                <input type="file" wire:model="fileImport" accept=".xlsx,.xls,.csv" class="text-[11px] text-slate-700 w-28 sm:w-36 file:mr-1.5 file:py-0.5 file:px-1.5 file:rounded file:border-0 file:bg-white file:text-slate-700 file:text-[11px] file:shadow-sm hover:file:bg-slate-100">
                                <x-secondary-button wire:click="import" wire:loading.attr="disabled" wire:target="fileImport,import" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="upload" class="w-3.5 h-3.5 mr-1" />
                                    Import Excel
                                </x-secondary-button>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <x-secondary-button wire:click="export" wire:loading.attr="disabled" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="download" class="w-3.5 h-3.5 mr-1" />
                                    Export Excel
                                </x-secondary-button>
                                <x-primary-button wire:click="tambah" class="whitespace-nowrap !px-2.5 !py-1.5 !text-[10px]">
                                    <x-icon name="plus" class="w-3.5 h-3.5 mr-1" />
                                    Tambah Sekolah
                                </x-primary-button>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2.5 mb-4">
                    <x-zoom-controls :zoom="$zoomPercent" />

                    @if ($bolehKelolaSemua)
                        <div class="flex flex-wrap items-center gap-2">
                            <x-filter-select wireModel="filterNamaSekolah" label="Nama Sekolah" allLabel="Semua Sekolah" :options="$filterNamaSekolahOptions" color="indigo" />
                            <x-filter-select wireModel="filterStatus" label="Status" allLabel="Semua Status" :options="$statusOptions" color="sky" />
                            <x-filter-select wireModel="filterKecamatan" label="Kecamatan" allLabel="Semua Kecamatan" :options="$filterKecamatanOptions" color="emerald" />
                        </div>
                    @endif
                </div>

                @if ($bolehKelolaSemua)
                    <div wire:loading wire:target="fileImport,import" class="text-xs text-slate-400 -mt-3 mb-3">Memproses import...</div>
                    <x-input-error :messages="$errors->get('fileImport')" class="text-xs -mt-3 mb-3 block" />
                @endif

                <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg" style="max-height: 30rem; zoom: {{ $zoomPercent }}%;">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-50">
                            <tr class="text-left text-slate-500">
                                <th class="px-3 py-2">No</th>
                                <th class="px-3 py-2">NPSN</th>
                                <th class="px-3 py-2">Kode UPB</th>
                                <th class="px-3 py-2">Nama Sekolah</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Kecamatan</th>
                                <th class="px-3 py-2">Subrayon</th>
                                <th class="px-3 py-2">Kepala Sekolah</th>
                                <th class="px-3 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($daftarSekolah as $sekolah)
                                <tr class="@if ($sekolah->id === $sekolahSayaId) bg-blue-50/60 @endif">
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->npsn ?: '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->kode_upb ?: '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap font-medium text-slate-800">
                                        {{ $sekolah->nama_sekolah }}
                                        @if ($sekolah->id === $sekolahSayaId)
                                            <span class="ml-1 inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Sekolah Anda</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                            @class([
                                                'bg-emerald-100 text-emerald-700' => $sekolah->status === 'negeri',
                                                'bg-amber-100 text-amber-700' => $sekolah->status === 'swasta',
                                                'bg-slate-100 text-slate-500' => ! $sekolah->status,
                                            ])">
                                            {{ $sekolah->status_label }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->kecamatan ?: '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->subrayon ?: '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->nama_kepala_sekolah ?: '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-right space-x-2">
                                        @if ($bolehKelolaSemua || $sekolah->id === $sekolahSayaId)
                                            <button wire:click="edit({{ $sekolah->id }})" class="text-blue-600 hover:underline">Edit</button>
                                        @endif
                                        @if ($bolehKelolaSemua)
                                            <button wire:click="konfirmasiHapus({{ $sekolah->id }})" class="text-red-600 hover:underline">Hapus</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-3 py-6 text-center text-slate-400">Belum ada data sekolah.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Form Tambah/Edit Sekolah --}}
    <x-modal name="sekolah-form" :show="$showForm" maxWidth="lg">
        <form wire:submit="simpan" class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-4">
                {{ $editingId ? 'Edit Data Sekolah' : 'Tambah Sekolah' }}
            </h2>

            <div class="space-y-4">
                @if ($bolehKelolaSemua)
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="npsn" value="NPSN" />
                            <x-text-input wire:model="npsn" id="npsn" class="block mt-1 w-full" type="text" required />
                            <x-input-error :messages="$errors->get('npsn')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="kode_upb" value="Kode UPB" />
                            <x-text-input wire:model="kode_upb" id="kode_upb" class="block mt-1 w-full" type="text" />
                            <x-input-error :messages="$errors->get('kode_upb')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="nama_sekolah" value="Nama Sekolah" />
                            <x-text-input wire:model="nama_sekolah" id="nama_sekolah" class="block mt-1 w-full" type="text" required />
                            <x-input-error :messages="$errors->get('nama_sekolah')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="status" value="Status" />
                            <select wire:model="status" id="status" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                                <option value="">-- Pilih Status --</option>
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('status')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="kecamatan" value="Kecamatan" />
                            <x-text-input wire:model="kecamatan" id="kecamatan" class="block mt-1 w-full" type="text" required />
                            <x-input-error :messages="$errors->get('kecamatan')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="subrayon" value="Subrayon" />
                            <x-text-input wire:model="subrayon" id="subrayon" class="block mt-1 w-full" type="text" />
                            <x-input-error :messages="$errors->get('subrayon')" class="mt-2" />
                        </div>
                    </div>
                @elseif ($editingId)
                    <div class="grid grid-cols-2 gap-4 p-3 bg-slate-50 rounded-lg text-sm">
                        <div>
                            <p class="text-xs text-slate-500">NPSN</p>
                            <p class="font-medium text-slate-700">{{ $npsn ?: '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Nama Sekolah</p>
                            <p class="font-medium text-slate-700">{{ $nama_sekolah ?: '-' }}</p>
                        </div>
                    </div>
                    <p class="text-xs text-slate-400">NPSN, Kode UPB, nama sekolah, status, kecamatan, dan subrayon hanya bisa diubah oleh Superadmin.</p>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="nama_kepala_sekolah" value="Nama Kepala Sekolah" />
                        <x-text-input wire:model="nama_kepala_sekolah" id="nama_kepala_sekolah" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('nama_kepala_sekolah')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="nip_kepala_sekolah" value="NIP Kepala Sekolah" />
                        <x-text-input wire:model="nip_kepala_sekolah" id="nip_kepala_sekolah" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('nip_kepala_sekolah')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="no_whatsapp_kepala_sekolah" value="No Whatsapp Kepala Sekolah" />
                        <x-text-input wire:model="no_whatsapp_kepala_sekolah" id="no_whatsapp_kepala_sekolah" class="block mt-1 w-full" type="text" inputmode="numeric" maxlength="13" placeholder="Contoh: 081234567890" />
                        <x-input-error :messages="$errors->get('no_whatsapp_kepala_sekolah')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="status_kepegawaian_kepsek" value="Status Kepegawaian Kepala Sekolah" />
                        <select wire:model="status_kepegawaian_kepsek" id="status_kepegawaian_kepsek" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                            <option value="">-- Pilih Status Kepegawaian --</option>
                            @foreach ($statusKepegawaianOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status_kepegawaian_kepsek')" class="mt-2" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="nama_pengawas" value="Nama Pengawas" />
                        <x-text-input wire:model="nama_pengawas" id="nama_pengawas" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('nama_pengawas')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="nip_pengawas" value="NIP Pengawas" />
                        <x-text-input wire:model="nip_pengawas" id="nip_pengawas" class="block mt-1 w-full" type="text" />
                        <p class="text-xs text-slate-400 mt-1">Nama & NIP Pengawas otomatis muncul pada baris tanda tangan hasil export Excel Lampiran 2a, 2b, dan 2c.</p>
                        <x-input-error :messages="$errors->get('nip_pengawas')" class="mt-2" />
                    </div>
                </div>

                <hr class="border-slate-200">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="nama_bendahara" value="Nama Bendahara" />
                        <x-text-input wire:model="nama_bendahara" id="nama_bendahara" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('nama_bendahara')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="status_kepegawaian_bendahara" value="Status Kepegawaian Bendahara" />
                        <select wire:model.live="status_kepegawaian_bendahara" id="status_kepegawaian_bendahara" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                            <option value="">-- Pilih Status Kepegawaian --</option>
                            @foreach ($statusKepegawaianOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status_kepegawaian_bendahara')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="nip_bendahara" value="NIP Bendahara" />
                        <x-text-input wire:model="nip_bendahara" id="nip_bendahara" class="block mt-1 w-full" type="text"
                            placeholder="{{ $status_kepegawaian_bendahara === 'Honorer' ? 'Ketik tanda -' : '18 digit angka' }}" />
                        <p class="text-xs text-slate-400 mt-1">Wajib 18 digit angka untuk ASN (PNS/ASN PPPK/ASN PPPK-PW), atau ketik tanda "-" untuk Non-ASN (Honorer).</p>
                        <x-input-error :messages="$errors->get('nip_bendahara')" class="mt-2" />
                    </div>
                </div>

                <hr class="border-slate-200">

                <div>
                    <x-input-label for="alamat_sekolah" value="Alamat Sekolah" />
                    <textarea wire:model="alamat_sekolah" id="alamat_sekolah" rows="3" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full"></textarea>
                    <x-input-error :messages="$errors->get('alamat_sekolah')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="batal">Batal</x-secondary-button>
                <x-primary-button>Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Konfirmasi Hapus --}}
    <x-modal name="sekolah-hapus" :show="$confirmingDeleteId !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Hapus sekolah ini?</h2>
            <p class="mt-1 text-sm text-slate-600">Tindakan ini tidak dapat dibatalkan.</p>

            @if ($errorHapus)
                <div class="mt-3 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                    {{ $errorHapus }}
                </div>
            @endif

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalHapus">Batal</x-secondary-button>
                <x-danger-button wire:click="hapus">Hapus</x-danger-button>
            </div>
        </div>
    </x-modal>

    {{-- Popup wajib: Profil Sekolah milik akun ini belum lengkap --}}
    <x-modal-wajib name="profil-belum-lengkap" :show="$tampilkanPeringatanBelumLengkap" maxWidth="md">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-amber-100 text-amber-600">
                    <x-icon name="alert-circle" class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-lg font-medium text-slate-900">Profil Sekolah Belum Lengkap</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Data Profil Sekolah Anda belum lengkap. Mohon segera lengkapi/update data di bawah ini
                        (Kepala Sekolah, Pengawas, Bendahara, dan Alamat Sekolah) supaya menu-menu lain pada
                        aplikasi ini dapat digunakan.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-primary-button wire:click="tutupPeringatanBelumLengkap">Mengerti, Isi Sekarang</x-primary-button>
            </div>
        </div>
    </x-modal-wajib>
</div>
