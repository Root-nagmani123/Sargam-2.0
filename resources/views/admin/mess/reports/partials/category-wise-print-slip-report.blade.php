@if(isset($reportLineCount) && $reportLineCount > 0)
    <p class="small text-body-secondary mb-2 no-print">
        Showing buyers {{ $reportPage->firstItem() }}–{{ $reportPage->lastItem() }} of {{ $reportLineCount }}
        (page {{ $reportPage->currentPage() }} of {{ $reportPage->lastPage() }}).
        PDF / Excel export includes all buyers.
    </p>
@endif

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

@if(isset($reportPage) && $reportPage->hasPages())
    <div class="cw-sale-voucher-pagination px-2 py-3 border-top no-print">
        {{ $reportPage->appends(request()->query())->links('pagination::bootstrap-5') }}
    </div>
@endif
