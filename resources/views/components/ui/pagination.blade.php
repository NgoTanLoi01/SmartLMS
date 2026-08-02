@props([
    'paginator',
    'itemLabel' => 'kết quả',
])

@if ($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();
        $startPage = max(1, $currentPage - 1);
        $endPage = min($lastPage, $currentPage + 1);

        if ($currentPage <= 2) {
            $endPage = min($lastPage, 3);
        }

        if ($currentPage >= $lastPage - 1) {
            $startPage = max(1, $lastPage - 2);
        }
    @endphp

    <footer {{ $attributes->class(['lms-pagination']) }}>
        <p class="lms-pagination__summary">
            Hiển thị <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong>
            trong <strong>{{ $paginator->total() }}</strong> {{ $itemLabel }}
        </p>

        <nav class="lms-pagination__nav" aria-label="Điều hướng trang">
            @if ($paginator->onFirstPage())
                <span class="lms-pagination__button is-disabled" aria-disabled="true" aria-label="Trang trước">
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </span>
            @else
                <a class="lms-pagination__button" href="{{ $paginator->previousPageUrl() }}" rel="prev"
                    aria-label="Trang trước">
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </a>
            @endif

            @if ($startPage > 1)
                <a class="lms-pagination__button" href="{{ $paginator->url(1) }}" aria-label="Trang 1">1</a>
                @if ($startPage > 2)
                    <span class="lms-pagination__ellipsis" aria-hidden="true">…</span>
                @endif
            @endif

            @foreach (range($startPage, $endPage) as $page)
                @if ($page === $currentPage)
                    <span class="lms-pagination__button is-active" aria-current="page"
                        aria-label="Trang {{ $page }}">{{ $page }}</span>
                @else
                    <a class="lms-pagination__button" href="{{ $paginator->url($page) }}"
                        aria-label="Trang {{ $page }}">{{ $page }}</a>
                @endif
            @endforeach

            @if ($endPage < $lastPage)
                @if ($endPage < $lastPage - 1)
                    <span class="lms-pagination__ellipsis" aria-hidden="true">…</span>
                @endif
                <a class="lms-pagination__button" href="{{ $paginator->url($lastPage) }}"
                    aria-label="Trang {{ $lastPage }}">{{ $lastPage }}</a>
            @endif

            @if ($paginator->hasMorePages())
                <a class="lms-pagination__button" href="{{ $paginator->nextPageUrl() }}" rel="next"
                    aria-label="Trang sau">
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
            @else
                <span class="lms-pagination__button is-disabled" aria-disabled="true" aria-label="Trang sau">
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </span>
            @endif
        </nav>
    </footer>
@endif
