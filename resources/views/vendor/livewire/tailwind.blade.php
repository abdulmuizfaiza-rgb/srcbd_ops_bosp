@php
/**
 * INI FILE YANG SEBENARNYA DIPAKAI (ditemukan 2026-09-28, setelah
 * tampilan pil pagination tidak juga muncul walau CSS sudah di-build
 * ulang & cache sudah dibersihkan).
 *
 * AKAR MASALAH: percobaan pertama (App\Providers\AppServiceProvider
 * memanggil Paginator::defaultView('vendor.pagination.custom')) TIDAK
 * PERNAH BEKERJA, karena Livewire sendiri (lewat
 * Livewire\Features\SupportPagination\SupportPagination::boot(), yang
 * berjalan setiap kali komponen Livewire ber-pagination di-render)
 * SELALU menimpa balik Paginator::defaultView() ke tampilan bawaan
 * Livewire sendiri ('livewire::tailwind') SETIAP KALI komponen
 * ber-paginasi di-boot - urutannya: AppServiceProvider::boot() jalan
 * duluan (benar), TAPI Livewire menimpanya lagi belakangan tiap
 * request, PERSIS sebelum {{ $paginator->links() }} dipanggil. Jadi
 * pengaturan di AppServiceProvider itu sama sekali tidak pernah
 * "sempat" dipakai.
 *
 * SOLUSI YANG BENAR: Laravel punya mekanisme baku untuk override view
 * bawaan sebuah paket - taruh file di
 * resources/views/vendor/{namespace}/{nama-view}.blade.php, dan
 * Laravel OTOMATIS memakai file INI dan bukan file bawaan paketnya,
 * TANPA perlu registrasi apapun (ini persis yang dimaksud kalau
 * menjalankan "php artisan vendor:publish --tag=livewire:pagination" -
 * bedanya di sini filenya langsung ditulis manual, isinya sudah versi
 * yang sudah dimodifikasi, jadi tidak perlu publish lalu edit lagi).
 * Livewire meminta view bernama 'livewire::tailwind' (nama themenya
 * "tailwind", bisa dicek di app/Livewire/**, tidak ada satupun
 * komponen yang mengubah $paginationTheme, jadi semua tetap pakai nama
 * default ini) - maka file INI HARUS ada persis di
 * resources/views/vendor/livewire/tailwind.blade.php (BUKAN di
 * resources/views/vendor/pagination/custom.blade.php seperti versi
 * sebelumnya, yang sudah dihapus/tidak dipakai lagi).
 *
 * Sesuai permintaan user 2026-09-28 (disertai contoh gambar) - kartu
 * putih bulat ("pil") berisi Previous/nomor halaman/Next, nomor
 * halaman aktif ditandai kotak bergaris biru. Isi & logika
 * wire:click/previousPage/nextPage/gotoPage/scroll TETAP SAMA PERSIS
 * dengan bawaan Livewire (vendor/livewire/livewire/src/Features/
 * SupportPagination/views/tailwind.blade.php) - HANYA class Tailwind &
 * struktur HTML yang diubah.
 */
if (! isset($scrollTo)) {
    $scrollTo = 'body';
}

$scrollIntoViewJsSnippet = ($scrollTo !== false)
    ? <<<JS
       (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
    JS
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-600">
                <span>{!! __('Showing') !!}</span>
                <span class="font-medium">{{ $paginator->firstItem() }}</span>
                <span>{!! __('to') !!}</span>
                <span class="font-medium">{{ $paginator->lastItem() }}</span>
                <span>{!! __('of') !!}</span>
                <span class="font-medium">{{ $paginator->total() }}</span>
                <span>{!! __('results') !!}</span>
            </p>

            <div class="inline-flex items-center gap-0.5 self-start rounded-full bg-white px-1.5 py-1 shadow-sm ring-1 ring-slate-200 sm:self-auto">
                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}" class="cursor-default rounded-full px-3 py-1.5 text-sm text-slate-300">
                        {!! __('pagination.previous') !!}
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" wire:loading.attr="disabled" dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="rounded-full px-3 py-1.5 text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900" aria-label="{{ __('pagination.previous') }}">
                        {!! __('pagination.previous') !!}
                    </button>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span aria-disabled="true" class="flex h-8 w-8 items-center justify-center text-sm text-slate-400">{{ $element }}</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="flex h-8 w-8 items-center justify-center rounded-full border border-blue-500 bg-white text-sm font-medium text-blue-600">
                                        {{ $page }}
                                    </span>
                                @else
                                    <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" class="flex h-8 w-8 items-center justify-center rounded-full text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </button>
                                @endif
                            </span>
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="{{ $scrollIntoViewJsSnippet }}" dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}.after" class="rounded-full px-3 py-1.5 text-sm font-medium text-blue-600 transition hover:bg-blue-50" aria-label="{{ __('pagination.next') }}">
                        {!! __('pagination.next') !!}
                    </button>
                @else
                    <span aria-disabled="true" aria-label="{{ __('pagination.next') }}" class="cursor-default rounded-full px-3 py-1.5 text-sm text-slate-300">
                        {!! __('pagination.next') !!}
                    </span>
                @endif
            </div>
        </nav>
    @endif
</div>
