<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="text-lg font-medium">
                        Selamat datang, {{ auth()->user()->display_name }}
                    </p>
                    <p class="text-sm text-gray-500 mt-1">
                        Anda masuk sebagai <span class="font-medium">{{ auth()->user()->level_akses_label }}</span>
                        pada Aplikasi OPS_BOSP SR CBD.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @can('akses-profil-sekolah')
                    <a href="{{ route('profil-sekolah.index') }}" wire:navigate class="block bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                        <p class="text-sm text-gray-500">Kelola</p>
                        <p class="text-lg font-semibold text-gray-800">Profil Sekolah</p>
                    </a>
                @endcan

                @can('akses-pendataan-ops')
                    <a href="{{ route('pendataan-ops.index') }}" wire:navigate class="block bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                        <p class="text-sm text-gray-500">Kelola</p>
                        <p class="text-lg font-semibold text-gray-800">Pendataan OPS</p>
                    </a>
                @endcan

                @can('akses-pendataan-bosp')
                    <a href="{{ route('pendataan-bosp.index') }}" wire:navigate class="block bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                        <p class="text-sm text-gray-500">Kelola</p>
                        <p class="text-lg font-semibold text-gray-800">Pendataan BOSP</p>
                    </a>
                @endcan

                @can('akses-pengguna')
                    <a href="{{ route('pengguna.index') }}" wire:navigate class="block bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                        <p class="text-sm text-gray-500">Kelola</p>
                        <p class="text-lg font-semibold text-gray-800">Pengguna</p>
                    </a>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
