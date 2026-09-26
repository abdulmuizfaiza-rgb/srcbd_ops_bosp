<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Pengguna') }}
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

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                {{-- Tab --}}
                <div class="border-b border-slate-200 px-4 sm:px-8 pt-4">
                    <nav class="flex gap-6 -mb-px">
                        @foreach ($levelOptions as $value => $label)
                            <button
                                wire:click="pindahTab('{{ $value }}')"
                                class="pb-3 text-sm font-medium border-b-2 transition
                                    @if ($tab === $value) border-blue-600 text-blue-600 @else border-transparent text-slate-500 hover:text-slate-700 @endif"
                            >
                                {{ $label }}
                                @if (($jumlahMenunggu[$value] ?? 0) > 0)
                                    <span class="ml-1 inline-flex px-1.5 py-0.5 text-xs font-semibold rounded-full bg-amber-100 text-amber-700">
                                        {{ $jumlahMenunggu[$value] }} menunggu
                                    </span>
                                @endif
                            </button>
                        @endforeach
                    </nav>
                </div>

                <div class="p-4 sm:p-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
                        <div class="flex flex-col sm:flex-row gap-3">
                            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari username / nama sekolah..." class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">

                            @if ($tab !== 'superadmin')
                                <select wire:model.live="filterSekolahId" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm text-sm">
                                    <option value="">Semua Sekolah</option>
                                    @foreach ($sekolahOptions as $sekolah)
                                        <option value="{{ $sekolah->id }}">{{ $sekolah->nama_sekolah }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <x-primary-button wire:click="tambah">+ Tambah {{ $levelOptions[$tab] }}</x-primary-button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead>
                                <tr class="text-left text-slate-500">
                                    <th class="px-3 py-2">Username</th>
                                    @if ($tab !== 'superadmin')
                                        <th class="px-3 py-2">Nama Sekolah</th>
                                        <th class="px-3 py-2">Jabatan</th>
                                    @endif
                                    <th class="px-3 py-2">Status</th>
                                    <th class="px-3 py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($pengguna as $item)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-slate-800">{{ $item->username }}</td>
                                        @if ($tab !== 'superadmin')
                                            <td class="px-3 py-2 text-slate-600">{{ $item->nama_sekolah ?: '-' }}</td>
                                            <td class="px-3 py-2 text-slate-600">{{ $item->jabatan ?: '-' }}</td>
                                        @endif
                                        <td class="px-3 py-2">
                                            @if ($item->is_approved)
                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700">Aktif</span>
                                            @else
                                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-700">Menunggu Persetujuan</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-right space-x-2">
                                            @unless ($item->is_approved)
                                                <button wire:click="setujui({{ $item->id }})" class="text-emerald-600 hover:underline">Setujui</button>
                                            @endunless
                                            <button wire:click="edit({{ $item->id }})" class="text-blue-600 hover:underline">Edit</button>
                                            @if ($item->level_akses !== \App\Models\User::LEVEL_SUPERADMIN)
                                                <button wire:click="konfirmasiReset({{ $item->id }})" class="text-amber-600 hover:underline">Reset Password</button>
                                            @endif
                                            @if ($item->id !== auth()->id())
                                                <button wire:click="konfirmasiHapus({{ $item->id }})" class="text-red-600 hover:underline">Hapus</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-6 text-center text-slate-400">Belum ada data pengguna di tab ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $pengguna->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Form Tambah/Edit --}}
    <x-modal name="pengguna-form" :show="$showForm" maxWidth="lg">
        <form wire:submit="simpan" class="p-6">
            <h2 class="text-lg font-medium text-slate-900 mb-4">
                {{ $editingId ? 'Edit Pengguna' : 'Tambah Pengguna' }}
            </h2>

            <div class="space-y-4">
                <div>
                    <x-input-label for="level_akses" value="Level Akses" />
                    <select wire:model.live="level_akses" id="level_akses" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                        <option value="">-- Pilih Level Akses --</option>
                        @foreach ($levelOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('level_akses')" class="mt-2" />
                </div>

                @if (in_array($level_akses, ['admin_ops', 'admin_bosp']))
                    <div>
                        <x-input-label for="profil_sekolah_id" value="Sekolah" />
                        <select wire:model="profil_sekolah_id" id="profil_sekolah_id" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                            <option value="">-- Pilih Sekolah --</option>
                            @foreach ($sekolahOptions as $sekolah)
                                <option value="{{ $sekolah->id }}">{{ $sekolah->nama_sekolah }}{{ $sekolah->npsn ? ' ('.$sekolah->npsn.')' : '' }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-400 mt-1">Belum ada sekolahnya? Tambahkan dulu di menu Profil Sekolah.</p>
                        <x-input-error :messages="$errors->get('profil_sekolah_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="jabatan" value="Jabatan" />
                        <x-text-input wire:model="jabatan" id="jabatan" class="block mt-1 w-full bg-slate-100" type="text" readonly />
                    </div>
                @endif

                <div>
                    <x-input-label for="username" :value="in_array($level_akses, ['admin_ops','admin_bosp']) ? 'Email (Username)' : 'Username'" />
                    <x-text-input wire:model="username" id="username" class="block mt-1 w-full" :type="in_array($level_akses, ['admin_ops','admin_bosp']) ? 'email' : 'text'" />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>

                @if ($level_akses === 'superadmin')
                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" />
                        <p class="text-xs text-slate-400 mt-1">Dipakai untuk gerbang Verifikasi Akses sebelum halaman login (bukan untuk login/username).</p>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                @endif

                <div>
                    <x-input-label for="password" :value="$editingId ? 'Password (kosongkan jika tidak diubah)' : 'Password'" />
                    <x-password-input wire:model="password" id="password" class="block mt-1" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" wire:click="batal">Batal</x-secondary-button>
                <x-primary-button>Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Modal Konfirmasi Hapus --}}
    <x-modal name="pengguna-hapus" :show="$confirmingDeleteId !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Hapus pengguna ini?</h2>
            <p class="mt-1 text-sm text-slate-600">Tindakan ini tidak dapat dibatalkan.</p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalHapus">Batal</x-secondary-button>
                <x-danger-button wire:click="hapus">Hapus</x-danger-button>
            </div>
        </div>
    </x-modal>

    {{-- Modal Konfirmasi Reset Password (khusus Admin OPS/Admin BOSP) --}}
    <x-modal name="pengguna-reset" :show="$confirmingResetId !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-slate-900">Reset password akun ini?</h2>
            <p class="mt-1 text-sm text-slate-600">
                Sistem akan membuat password baru secara acak untuk
                <span class="font-medium text-slate-800">{{ $penggunaDireset?->username }}</span>
                dan mengirimkannya ke email tersebut. Akun ini akan diminta ganti password sendiri begitu berhasil login memakai password baru itu.
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="batalReset">Batal</x-secondary-button>
                <x-primary-button wire:click="resetPassword" class="!bg-amber-600 hover:!bg-amber-700">Reset Password</x-primary-button>
            </div>
        </div>
    </x-modal>
</div>
