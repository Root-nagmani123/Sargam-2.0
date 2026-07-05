@if ($paginator->hasPages())
    @php
        $from = $paginator->firstItem() ?? 0;
        $to = $paginator->lastItem() ?? 0;
        $total = $paginator->total();
    @endphp
    <nav class="fc-status-pagination-nav" aria-label="Participant list pages">
        <p class="fc-status-pagination-summary mb-0">
            Showing <strong>{{ number_format($from) }}</strong> to <strong>{{ number_format($to) }}</strong>
            of <strong>{{ number_format($total) }}</strong> records
        </p>
        <ul class="pagination fc-status-pagination-list mb-0">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link" aria-hidden="true">
                        <i class="bi bi-chevron-left"></i>
                        <span class="fc-status-pagination-label">Previous</span>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link fc-status-page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page" data-fc-page="prev">
                        <i class="bi bi-chevron-left"></i>
                        <span class="fc-status-pagination-label">Previous</span>
                    </a>
                </li>
            @endif

            {{-- Page numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link fc-status-page-ellipsis">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link fc-status-page-link" href="{{ $url }}" aria-label="Go to page {{ $page }}" data-fc-page="{{ $page }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link fc-status-page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page" data-fc-page="next">
                        <span class="fc-status-pagination-label">Next</span>
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link" aria-hidden="true">
                        <span class="fc-status-pagination-label">Next</span>
                        <i class="bi bi-chevron-right"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
