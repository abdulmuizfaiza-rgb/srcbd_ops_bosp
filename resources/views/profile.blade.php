<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <p class="text-sm text-slate-600">
                        <span class="font-medium text-slate-800">Username:</span> {{ auth()->user()->username }}<br>
                        <span class="font-medium text-slate-800">Level Akses:</span> {{ auth()->user()->level_akses_label }}
                        @if(auth()->user()->nama_sekolah)
                            <br><span class="font-medium text-slate-800">Nama Sekolah:</span> {{ auth()->user()->nama_sekolah }}
                        @endif
                        @if(auth()->user()->jabatan)
                            <br><span class="font-medium text-slate-800">Jabatan:</span> {{ auth()->user()->jabatan }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
