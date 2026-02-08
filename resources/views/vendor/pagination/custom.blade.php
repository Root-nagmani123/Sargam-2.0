@if ($paginator->hasPages())
<nav aria-label="Pagination">
    <ul class="pagination pagination-sm mb-0 align-items-center">

        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <li class="page-item disabled" aria-disabled="true">
                <span class="page-link" aria-hidden="true">&lsaquo;</span>
            </li>
        @else
            <li class="page-item">
                <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">
                    <span aria-hidden="true">&lsaquo;</span>
                </a>
            </li>
        @endif

        {{-- Page Numbers --}}
        @foreach ($elements as $element)
            {{-- Ellipsis (skip empty) --}}
            @if (is_string($element) && $element !== '')
                <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
            @endif

            {{-- Array of Links --}}
            @if (is_array($element) && count($element) > 0)
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="page-item active">
                            <span class="page-link current-page" aria-current="page">{{ $page }}</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        </li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <li class="page-item">
                <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">
                    <span aria-hidden="true">&rsaquo;</span>
                </a>
            </li>
        @else
            <li class="page-item disabled" aria-disabled="true">
                <span class="page-link" aria-hidden="true">&rsaquo;</span>
            </li>
        @endif

    </ul>
</nav>
@endif
