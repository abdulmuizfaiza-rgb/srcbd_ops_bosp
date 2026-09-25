<x-slot name="header">
    <h2 class="font-semibold text-xl text-slate-800 leading-tight">
        {{ __('Tampilan') }}
    </h2>
</x-slot>

<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

        @if (session('status'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                {{ session('status') }}
            </div>
        @endif

        <div class="bg-white shadow sm:rounded-lg p-4 sm:p-8">
            <p class="text-sm text-slate-500 mb-6">
                Pengaturan di halaman ini berlaku untuk seluruh pengguna aplikasi (Superadmin, Admin OPS,
                dan Admin BOSP): warna &amp; huruf berlaku pada tampilan setelah login (halaman dan menu),
                sedangkan gambar latar berlaku pada halaman pemilihan akses dan form login.
            </p>

            <form wire:submit="simpan" class="space-y-8">
                {{-- Gambar latar --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <x-input-label value="Background Landing Page (pemilihan akses)" />
                        <p class="text-xs text-slate-400 mt-0.5 mb-2">Otomatis di-crop jika ukuran gambar terlalu besar.</p>

                        <div class="rounded-lg border border-slate-200 overflow-hidden bg-slate-50 aspect-video flex items-center justify-center">
                            @if ($backgroundLandingBaru)
                                <img src="{{ $backgroundLandingBaru->temporaryUrl() }}" class="h-full w-full object-cover" alt="Pratinjau background landing">
                            @elseif ($tampilan->background_landing_url)
                                <img src="{{ $tampilan->background_landing_url }}" class="h-full w-full object-cover" alt="Background landing saat ini">
                            @else
                                <span class="text-xs text-slate-400">Belum ada gambar</span>
                            @endif
                        </div>

                        <input type="file" wire:model="backgroundLandingBaru" accept="image/*" class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                        <div wire:loading wire:target="backgroundLandingBaru" class="text-xs text-slate-400 mt-1">Mengunggah...</div>
                        <x-input-error :messages="$errors->get('backgroundLandingBaru')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label value="Background Form Login" />
                        <p class="text-xs text-slate-400 mt-0.5 mb-2">Otomatis di-crop jika ukuran gambar terlalu besar.</p>

                        <div class="rounded-lg border border-slate-200 overflow-hidden bg-slate-50 aspect-video flex items-center justify-center">
                            @if ($backgroundLoginBaru)
                                <img src="{{ $backgroundLoginBaru->temporaryUrl() }}" class="h-full w-full object-cover" alt="Pratinjau background login">
                            @elseif ($tampilan->background_login_url)
                                <img src="{{ $tampilan->background_login_url }}" class="h-full w-full object-cover" alt="Background login saat ini">
                            @else
                                <span class="text-xs text-slate-400">Belum ada gambar</span>
                            @endif
                        </div>

                        <input type="file" wire:model="backgroundLoginBaru" accept="image/*" class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                        <div wire:loading wire:target="backgroundLoginBaru" class="text-xs text-slate-400 mt-1">Mengunggah...</div>
                        <x-input-error :messages="$errors->get('backgroundLoginBaru')" class="mt-2" />
                    </div>
                </div>

                <hr class="border-slate-200">

                {{-- Warna --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <x-input-label for="warnaHalaman" value="Warna Halaman" />
                        <div class="mt-1 flex items-center gap-3">
                            <input type="color" wire:model="warnaHalaman" id="warnaHalaman" class="h-10 w-14 rounded border border-slate-300 p-1">
                            <x-text-input wire:model="warnaHalaman" type="text" class="w-32" />
                        </div>
                        <x-input-error :messages="$errors->get('warnaHalaman')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="warnaMenu" value="Warna Menu" />
                        <div class="mt-1 flex items-center gap-3">
                            <input type="color" wire:model="warnaMenu" id="warnaMenu" class="h-10 w-14 rounded border border-slate-300 p-1">
                            <x-text-input wire:model="warnaMenu" type="text" class="w-32" />
                        </div>
                        <x-input-error :messages="$errors->get('warnaMenu')" class="mt-2" />
                    </div>
                </div>

                <hr class="border-slate-200">

                {{-- Warna Huruf (teks) - terpisah dari warna latar di atas. Hanya
                     diterapkan ke teks judul/label/keterangan (bukan teks di
                     dalam tombol/badge berwarna, supaya kontras tombol tetap
                     aman apapun warna yang dipilih Superadmin). --}}
                <div>
                    <p class="text-sm font-medium text-slate-700 mb-3">Warna Huruf (Teks)</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <x-input-label for="warnaHurufMenu" value="Warna Huruf Menu" />
                            <div class="mt-1 flex items-center gap-3">
                                <input type="color" wire:model="warnaHurufMenu" id="warnaHurufMenu" class="h-10 w-14 rounded border border-slate-300 p-1">
                                <x-text-input wire:model="warnaHurufMenu" type="text" class="w-32" />
                            </div>
                            <x-input-error :messages="$errors->get('warnaHurufMenu')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="warnaHurufLanding" value="Warna Huruf Landing Page" />
                            <div class="mt-1 flex items-center gap-3">
                                <input type="color" wire:model="warnaHurufLanding" id="warnaHurufLanding" class="h-10 w-14 rounded border border-slate-300 p-1">
                                <x-text-input wire:model="warnaHurufLanding" type="text" class="w-32" />
                            </div>
                            <x-input-error :messages="$errors->get('warnaHurufLanding')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="warnaHurufLogin" value="Warna Huruf Form Login" />
                            <div class="mt-1 flex items-center gap-3">
                                <input type="color" wire:model="warnaHurufLogin" id="warnaHurufLogin" class="h-10 w-14 rounded border border-slate-300 p-1">
                                <x-text-input wire:model="warnaHurufLogin" type="text" class="w-32" />
                            </div>
                            <x-input-error :messages="$errors->get('warnaHurufLogin')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="warnaHurufRegistrasi" value="Warna Huruf Form Registrasi" />
                            <div class="mt-1 flex items-center gap-3">
                                <input type="color" wire:model="warnaHurufRegistrasi" id="warnaHurufRegistrasi" class="h-10 w-14 rounded border border-slate-300 p-1">
                                <x-text-input wire:model="warnaHurufRegistrasi" type="text" class="w-32" />
                            </div>
                            <x-input-error :messages="$errors->get('warnaHurufRegistrasi')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <hr class="border-slate-200">

                {{-- Huruf --}}
                <div class="space-y-6">
                    <div>
                        <p class="text-sm font-medium text-slate-700 mb-3">Ukuran Huruf</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div>
                                <x-input-label for="ukuranHurufHalaman" value="Ukuran Huruf Halaman" />
                                <select wire:model="ukuranHurufHalaman" id="ukuranHurufHalaman" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                                    @foreach ($ukuranOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="ukuranHurufMenu" value="Ukuran Huruf Menu" />
                                <select wire:model="ukuranHurufMenu" id="ukuranHurufMenu" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                                    @foreach ($ukuranOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="ukuranHurufRegistrasi" value="Ukuran Huruf Form Registrasi" />
                                <select wire:model="ukuranHurufRegistrasi" id="ukuranHurufRegistrasi" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                                    @foreach ($ukuranOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="text-sm font-medium text-slate-700 mb-3">Jenis Huruf</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div>
                                <x-input-label for="jenisHurufHalaman" value="Jenis Huruf Halaman" />
                                <select wire:model="jenisHurufHalaman" id="jenisHurufHalaman" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                                    @foreach ($jenisHurufOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="jenisHurufMenu" value="Jenis Huruf Menu" />
                                <select wire:model="jenisHurufMenu" id="jenisHurufMenu" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                                    @foreach ($jenisHurufOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <x-input-label for="jenisHurufRegistrasi" value="Jenis Huruf Form Registrasi" />
                                <select wire:model="jenisHurufRegistrasi" id="jenisHurufRegistrasi" class="border-slate-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm block mt-1 w-full">
                                    @foreach ($jenisHurufOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Simpan Tampilan</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
