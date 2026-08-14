@include('admin.mess.reports.partials.category-wise-print-slip-body', [
    'sectionsToShow' => collect($sectionsToShow ?? []),
    'fromDateFormatted' => $fromDateFormatted,
    'toDateFormatted' => $toDateFormatted,
    'otCourses' => $otCourses ?? collect(),
    'grandTotal' => $grandTotal ?? 0,
    'filtersApplied' => $filtersApplied ?? false,
    'printPageBreakPerBuyer' => request('print_all'),
    'freezeSaleVoucherTableHeader' => (bool) ($freezeSaleVoucherTableHeader ?? false),
])

{{-- Footer chrome: same shape as the other report/index pages —
     pager on the left, buyers-per-page + total on the right. --}}
@if(isset($reportPage) && ! request()->boolean('print_all'))
    @php
        $cwPerPageOptions = $reportPerPageOptions ?? [8, 10, 25, 50, 100];
        $cwPagerQuery = collect(request()->query())->except('page')->all();
    @endphp
    <div class="ssr-pagination-bar px-3 px-lg-4 py-3 border-top d-flex flex-wrap align-items-center justify-content-between gap-3 no-print">
        @if($reportPage->hasPages())
            <div class="ssr-pagination-links order-2 order-md-1">{{ $reportPage->appends($cwPagerQuery)->links('pagination::bootstrap-5') }}</div>
        @else
            <span class="order-2 order-md-1"></span>
        @endif
        <div class="d-flex align-items-center gap-2 small text-body-secondary order-1 order-md-2 ms-md-auto">
            <span>Showing</span>
            <select id="cwPerPage" class="form-select form-select-sm ssr-perpage-select" aria-label="Buyers per page">
                @foreach($cwPerPageOptions as $pp)
                    <option value="{{ $pp }}" {{ (int) $reportPage->perPage() === (int) $pp ? 'selected' : '' }}>{{ $pp }}</option>
                @endforeach
            </select>
            <span>of {{ number_format($reportPage->total()) }} buyers</span>
        </div>
    </div>
@endif
