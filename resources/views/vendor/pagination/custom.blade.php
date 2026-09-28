@php
/**
 * Tampilan pagination baku SELURUH aplikasi (permintaan user 2026-09-28,
 * disertai contoh gambar) - Previous/nomor halaman/Next dalam 1 kartu
 * putih bulat ("pil"), nomor halaman aktif ditandai kotak bergaris biru
 * (bukan kotak abu-abu penuh seperti bawaan Livewire). Didaftarkan lewat
 * Paginator::defaultView() di App\Providers\AppServiceProvider::boot()
 * supaya berlaku OTOMATIS untuk SEMUA `{{ $paginator->links() }}` di
 * seluruh aplikasi tanpa perlu diubah 1-per-1 di tiap menu.
 *
 * SENGAJA menyalin logika wire:click/previousPage/nextPage/gotoPage
 * & $scrollIntoViewJsSnippet APA ADANYA dari
 * vendor/livewire/livewire/src/Features/SupportPagination/views/tailwind.blade.php
 * (bawaan Livewire) - HANYA class Tailwind & struktur HTML yang diubah,
 * supaya perilaku pindah halaman/scroll tetap identik dengan sebelumnya.
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
