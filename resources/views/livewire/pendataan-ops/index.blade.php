<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Identitas OPS') }}
    </h2>
</x-slot>

<div>
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="flex flex-col xl:flex-row xl:flex-wrap xl:items-start xl:justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-lg font-medium text-slate-900">Identitas OPS</h3>
                        <p class="text-sm text-slate-500">Data identitas Operator Sekolah (OPS) per sekolah.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <x-zoom-controls :zoom="$zoomPercent" />

                        @if ($bolehKelolaSemua)
                            <x-filter-select wireModel="filterNamaSekolah" label="Nama Sekolah" allLabel="Semua Sekolah" :options="$filterNamaSekolahOptions" color="indigo" />
                            <x-filter-select wireModel="filterNamaOps" label="Nama OPS" allLabel="Semua Nama OPS" :options="$filterNamaOpsOptions" color="sky" />
                        @endif
                    </div>
                </div>

                <div class="overflow-auto scrollbar-modern border border-slate-200 rounded-lg" style="max-height: 30rem; zoom: {{ $zoomPercent }}%;">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="sticky top-0 z-10 bg-slate-50">
                            <tr class="text-left text-slate-500">
                                <th class="px-3 py-2">No</th>
                                <th class="px-3 py-2">Photo</th>
                                <th class="px-3 py-2">Nama Sekolah</th>
                                <th class="px-3 py-2">Nama OPS</th>
                                <th class="px-3 py-2">NUPTK</th>
                                <th class="px-3 py-2">JK</th>
                                <th class="px-3 py-2">Status Kepegawaian</th>
                                <th class="px-3 py-2">No Whatsapp</th>
                                <th class="px-3 py-2">SK OPS</th>
                                <th class="px-3 py-2">Status Data</th>
                                <th class="px-3 py-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($daftarSekolah as $sekolah)
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-500">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @if ($sekolah->identitasOps?->foto_ops_url)
                                            <img src="{{ $sekolah->identitasOps->foto_ops_url }}" alt="Photo OPS" class="w-8 h-12 object-cover rounded border border-slate-200">
                                        @else
                                            <span class="inline-flex w-8 h-12 items-center justify-center rounded border border-dashed border-slate-300 text-slate-300 text-[9px]">2x3</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap font-medium text-slate-800">{{ $sekolah->nama_sekolah }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->identitasOps->nama ?? '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->identitasOps->nuptk ?? '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->identitasOps->jk_label ?? '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->identitasOps->status_kepegawaian ?? '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ $sekolah->identitasOps->no_whatsapp ?? '-' }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @if ($sekolah->identitasOps?->sk_ops_url)
                                            <a href="{{ $sekolah->identitasOps->sk_ops_url }}" target="_blank" class="inline-flex items-center gap-1 text-blue-600 hover:underline text-xs">
                                                <x-icon name="download" class="w-3.5 h-3.5" />Lihat PDF
                                            </a>
                                        @else
                                            <span class="text-slate-300 text-xs">-</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        @if ($sekolah->identitasOps)
                                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">Sudah diisi</span>
                                        @else
                                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-700">Belum diisi</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap text-right">
                                        <button wire:click="isi({{ $sekolah->id }})" class="inline-flex items-center gap-1 text-blue-600 hover:underline">
                                            <x-icon name="pencil" class="w-3.5 h-3.5" />
                                            {{ $sekolah->identitasOps ? 'Edit' : 'Isi' }}
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-3 py-6 text-center text-slate-400">Belum ada data sekolah.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Form Identitas OPS --}}
    <x-modal name="identitas-ops-form" :show="$showForm" maxWidth="2xl">
        <form wire:submit="simpan" class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-4">Identitas OPS</h2>

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="nama" value="Nama OPS" />
                        <x-text-input wire:model="nama" id="nama" class="block mt-1 w-full" type="text" required />
                        <x-input-error :messages="$errors->get('nama')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="nuptk" value="NUPTK" />
                        <x-text-input wire:model="nuptk" id="nuptk" class="block mt-1 w-full" type="text" inputmode="numeric" maxlength="16" placeholder="16 digit angka" />
                        <x-input-error :messages="$errors->get('nuptk')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="nip" value="NIP OPS" />
                        <x-text-input wire:model="nip" id="nip" class="block mt-1 w-full" type="text"
                            placeholder="{{ in_array($status_kepegawaian, ['Honorer', 'PTT Yayasan'], true) ? 'Ketik tanda -' : '18 digit angka' }}" />
                        <x-input-error :messages="$errors->get('nip')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="jk" value="Jenis Kelamin" />
                        <select wire:model="jk" id="jk" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                            <option value="">-- Pilih JK --</option>
                            @foreach ($jkOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('jk')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="tempat_lahir" value="Tempat Lahir" />
                        <x-text-input wire:model="tempat_lahir" id="tempat_lahir" class="block mt-1 w-full" type="text" />
                        <x-input-error :messages="$errors->get('tempat_lahir')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="tanggal_lahir" value="Tanggal Lahir" />
                        <x-text-input wire:model="tanggal_lahir" id="tanggal_lahir" class="block mt-1 w-full" type="date" />
                        <x-input-error :messages="$errors->get('tanggal_lahir')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="status_kepegawaian" value="Status Kepegawaian" />
                        <select wire:model.live="status_kepegawaian" id="status_kepegawaian" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                            <option value="">-- Pilih Status Kepegawaian --</option>
                            @foreach ($statusKepegawaianOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status_kepegawaian')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="pendidikan_terakhir" value="Pendidikan Terakhir" />
                        <select wire:model.live="pendidikan_terakhir" id="pendidikan_terakhir" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                            <option value="">-- Pilih Pendidikan Terakhir --</option>
                            @foreach ($pendidikanOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('pendidikan_terakhir')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="jurusan" value="Jurusan" />
                        <x-text-input wire:model="jurusan" id="jurusan" class="block mt-1 w-full" type="text" :disabled="! $butuhJurusan" />
                        <p class="text-xs text-slate-400 mt-1">Hanya aktif untuk Pendidikan Terakhir S1/S2/S3.</p>
                        <x-input-error :messages="$errors->get('jurusan')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="nama_perguruan_tinggi" value="Nama Perguruan Tinggi" />
                        <x-text-input wire:model="nama_perguruan_tinggi" id="nama_perguruan_tinggi" class="block mt-1 w-full" type="text" :disabled="! $butuhJurusan" />
                        <p class="text-xs text-slate-400 mt-1">Hanya aktif untuk Pendidikan Terakhir S1/S2/S3.</p>
                        <x-input-error :messages="$errors->get('nama_perguruan_tinggi')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="no_whatsapp" value="No Whatsapp Admin OPS" />
                        <x-text-input wire:model="no_whatsapp" id="no_whatsapp" class="block mt-1 w-full" type="text" inputmode="numeric" maxlength="12" />
                        <x-input-error :messages="$errors->get('no_whatsapp')" class="mt-2" />
                    </div>
                </div>

                <hr class="border-slate-200">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="fotoOpsBaru" value="Photo OPS (ukuran 2x3)" />
                        <div class="flex items-start gap-3 mt-1">
                            <div class="shrink-0">
                                @if ($fotoOpsBaru)
                                    <img src="{{ $fotoOpsBaru->temporaryUrl() }}" class="w-16 h-24 object-cover rounded border border-slate-200">
                                @elseif ($foto_ops)
                                    <img src="{{ route('pendataan-ops.file', ['jenis' => 'foto', 'pendataanOps' => $editingId]) }}" class="w-16 h-24 object-cover rounded border border-slate-200">
                                @else
                                    <span class="inline-flex w-16 h-24 items-center justify-center rounded border border-dashed border-slate-300 text-slate-300 text-xs">2x3</span>
                                @endif
                            </div>
                            <div class="flex-1">
                                <input wire:model="fotoOpsBaru" id="fotoOpsBaru" type="file" accept="image/*" class="block w-full text-sm text-slate-600">
                                <div wire:loading wire:target="fotoOpsBaru" class="text-xs text-slate-400 mt-1">Mengunggah...</div>
                                <p class="text-xs text-slate-400 mt-1">Foto otomatis dipotong (crop) &amp; disesuaikan sistem ke ukuran standar 2x3, berapapun ukuran/rasio foto asli yang diupload.</p>
                                <x-input-error :messages="$errors->get('fotoOpsBaru')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <x-input-label for="skOpsBaru" value="Upload SK OPS Terbaru (PDF, maks 1 MB)" />
                        <input wire:model="skOpsBaru" id="skOpsBaru" type="file" accept="application/pdf" class="block mt-1 w-full text-sm text-slate-600">
                        <div wire:loading wire:target="skOpsBaru" class="text-xs text-slate-400 mt-1">Mengunggah...</div>
                        @if ($sk_ops && ! $skOpsBaru)
                            <a href="{{ route('pendataan-ops.file', ['jenis' => 'sk', 'pendataanOps' => $editingId]) }}" target="_blank" class="inline-flex items-center gap-1 text-blue-600 hover:underline text-xs mt-2">
                                <x-icon name="download" class="w-3.5 h-3.5" />Lihat file SK OPS yang sudah tersimpan
                            </a>
                        @endif
                        <p class="text-xs text-slate-400 mt-1">Format PDF, ukuran file maksimal 1 MB. File yang melebihi 1 MB akan ditolak - silakan upload ulang dengan ukuran lebih kecil.</p>
                        <x-input-error :messages="$errors->get('skOpsBaru')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="batal"><x-icon name="x-mark" class="w-4 h-4 mr-1.5" />Batal</x-secondary-button>
                <x-primary-button><x-icon name="check" class="w-4 h-4 mr-1.5" />Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Popup sukses (sekali saja): Profil Sekolah baru saja lengkap --}}
    <x-modal name="identitas-ops-sukses" :show="$tampilkanSuksesProfilLengkap" maxWidth="md">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-emerald-100 text-emerald-600">
                    <x-icon name="check" class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-lg font-medium text-slate-900">Profil Sekolah Lengkap</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Profil Sekolah Anda sudah lengkap. Silakan lanjutkan dengan mengisi Identitas OPS di
                        bawah ini supaya menu-menu lain pada aplikasi ini dapat digunakan.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-primary-button wire:click="tutupSuksesProfilLengkap">Mengerti</x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- Popup wajib: Identitas OPS milik akun ini belum lengkap --}}
    <x-modal-wajib name="identitas-ops-belum-lengkap" :show="false" maxWidth="md">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div class="shrink-0 flex items-center justify-center w-10 h-10 rounded-full bg-amber-100 text-amber-600">
                    <x-icon name="alert-circle" class="w-6 h-6" />
                </div>
                <div>
                    <h2 class="text-lg font-medium text-slate-900">Identitas OPS Belum Lengkap</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Data Identitas OPS Anda belum diisi. Mohon segera lengkapi data di bawah ini supaya
                        menu-menu lain (mis. Lampiran 2a/2b/2c) pada aplikasi ini dapat digunakan.
                    </p>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <x-primary-button wire:click="tutupPeringatanIdentitasBelumLengkap">Mengerti, Isi Sekarang</x-primary-button>
            </div>
        </div>
    </x-modal-wajib>
</div>
