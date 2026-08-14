@extends('admin.layouts.master')
@section('title', 'Sale Voucher Report')
@section('content')
@php
    $cwBuyerReqTop = is_array(request('buyer_name')) ? request('buyer_name') : (request('buyer_name') !== null && request('buyer_name') !== '' ? [request('buyer_name')] : []);
    $preservedBuyerNames = array_values(array_filter(array_map(static fn ($n) => trim((string) $n), $cwBuyerReqTop), static fn ($n) => $n !== ''));
@endphp
<div class="container-fluid py-3 py-md-4 cw-report-page {{ request('print_all') ? 'print-all-mode' : '' }}">
    <x-breadcrum title="Sale Voucher Report" :showBack="false"></x-breadcrum>
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(!request('print_all'))
    @php
        // Exports and the print window take the FILTERS only — pagination / transport params
        // would otherwise ride along and (for print_all) re-enter the browser-print branch.
        $cwExportQ = collect(request()->query())
            ->except(['page', 'ajax', 'print_all', 'refresh', 'per_page'])
            ->all();
        $cwExportQuery = $cwExportQ ? '?' . http_build_query($cwExportQ) : '';
    @endphp
    {{-- Download / Print bar --}}
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <a href="{{ route('admin.mess.reports.category-wise-print-slip.excel') }}{{ $cwExportQuery }}" class="btn cw-export-btn" title="Download (Excel)">
            <i class="material-symbols-rounded">download</i><span>Download</span>
        </a>
        <button type="button" class="btn cw-export-btn" id="cwPrintBtn" title="Print (or Save as PDF)">
            <i class="material-symbols-rounded">print</i><span>Print</span>
        </button>
    </div>
    <div class="card mb-4 border-0 shadow-sm rounded-3 no-print cw-sale-voucher-filter-card">
        <div class="card-body p-3 p-lg-4">
            @php
                $cwSlugs = array_values(array_unique(array_filter(array_map(
                    static fn ($s) => strtolower(trim((string) $s)),
                    is_array(request('client_type_slug')) ? request('client_type_slug') : (request('client_type_slug') !== null && request('client_type_slug') !== '' ? [request('client_type_slug')] : [])
                ))));
                $selClientPks = array_values(array_filter(array_map('intval', (array) request('client_type_pk', []))));
                $selCoursePks = array_values(array_filter(array_map('intval', (array) request('course_master_pk', []))));
                $cwNeedCourse = count(array_intersect($cwSlugs, ['ot', 'course'])) > 0;
                $cwNeedCat = count(array_diff($cwSlugs, ['ot', 'course'])) > 0;
                $cwMergedCats = collect();
                foreach ($cwSlugs as $sg) {
                    if (in_array($sg, ['ot', 'course'], true)) {
                        continue;
                    }
                    if (isset($clientTypeCategories[$sg])) {
                        $cwMergedCats = $cwMergedCats->concat($clientTypeCategories[$sg]);
                    }
                }
                $cwMergedCats = $cwMergedCats->unique('id')->values();
            @endphp
            <form method="GET" action="{{ route('admin.mess.reports.category-wise-print-slip') }}" id="filterForm" class="d-flex flex-wrap align-items-end gap-2 cw-filter-form">
                <span class="programme-dt-filters-label flex-shrink-0 align-self-center">Filter</span>
                <div class="d-flex flex-wrap align-items-end gap-2 cw-filter-grid">
                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-1">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-12 col-md-3 col-lg-2">
                        <label class="form-label fw-semibold small text-uppercase text-muted mb-1">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-12 col-md-3 col-lg-2">
                        <label for="clientTypeSlug" class="form-label fw-semibold small text-uppercase text-muted mb-1">Employee / OT / Course</label>
                        <select name="client_type_slug[]" id="clientTypeSlug" class="form-select w-100 cw-report-multiselect" multiple data-placeholder="All Client Types">
                            @foreach($clientTypes as $key => $label)
                                <option value="{{ $key }}" @selected(in_array($key, $cwSlugs, true))>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3 col-lg-3" id="wrapClientTypePks" style="{{ $cwNeedCat ? '' : 'display:none' }}">
                        <label for="clientTypePk" class="form-label fw-semibold small text-uppercase text-muted mb-1">Client category</label>
                        <select id="clientTypePk" name="client_type_pk[]" class="form-select w-100 cw-report-multiselect" multiple data-placeholder="All categories">
                            @foreach($cwMergedCats as $category)
                                <option value="{{ $category->id }}" data-client-name="{{ strtolower($category->client_name ?? '') }}" @selected(in_array((int) $category->id, $selClientPks, true))>{{ $category->client_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-3 col-lg-3" id="wrapCoursePks" style="{{ $cwNeedCourse ? '' : 'display:none' }}">
                        <label for="courseMasterPk" class="form-label fw-semibold small text-uppercase text-muted mb-1">Course (OT / Course)</label>
                        <select id="courseMasterPk" name="course_master_pk[]" class="form-select w-100 cw-report-multiselect" multiple data-placeholder="All courses">
                            @isset($otCourses)
                                @foreach($otCourses as $course)
                                    <option value="{{ $course->pk }}" @selected(in_array((int) $course->pk, $selCoursePks, true))>{{ $course->course_name }} [{{ (int)($course->active_inactive ?? 0) === 1 ? 'Active' : 'Archived' }}]</option>
                                @endforeach
                            @endisset
                        </select>
                    </div>
                    <div class="col-12 col-md-3 col-lg-3">
                        <label for="clientTypePkBuyer" class="form-label fw-semibold small text-uppercase text-muted mb-1">Buyer Name (Selling Voucher)</label>
                        <select name="buyer_name[]" id="clientTypePkBuyer" class="form-select w-100 cw-report-multiselect" multiple data-placeholder="All Buyers">
                            @if(in_array('employee', $cwSlugs, true) && count($selClientPks) === 1 && isset($clientTypeCategories['employee']))
                                @php
                                    $cat = $clientTypeCategories['employee']->firstWhere('id', $selClientPks[0]);
                                    $catName = $cat ? strtolower(trim($cat->client_name ?? '')) : '';
                                @endphp
                                @if($catName === 'academy staff' && isset($employees))
                                    @foreach($employees as $e)
                                        <option value="{{ $e->full_name }}" @selected(in_array($e->full_name, $preservedBuyerNames, true))>{{ $e->full_name }}</option>
                                    @endforeach
                                @elseif($catName === 'faculty' && isset($faculties))
                                    @foreach($faculties as $f)
                                        <option value="{{ $f->full_name }}" @selected(in_array($f->full_name, $preservedBuyerNames, true))>{{ $f->full_name }}</option>
                                    @endforeach
                                @elseif($catName === 'mess staff' && isset($messStaff))
                                    @foreach($messStaff as $m)
                                        <option value="{{ $m->full_name }}" @selected(in_array($m->full_name, $preservedBuyerNames, true))>{{ $m->full_name }}</option>
                                    @endforeach
                                @endif
                            @elseif(in_array('course', $cwSlugs, true) && isset($courseBuyerNames) && $courseBuyerNames->isNotEmpty())
                                @foreach($courseBuyerNames as $buyerName)
                                    <option value="{{ $buyerName }}" @selected(in_array($buyerName, $preservedBuyerNames, true))>{{ $buyerName }}</option>
                                @endforeach
                            @elseif(in_array('other', $cwSlugs, true) && isset($otherBuyerNames) && $otherBuyerNames->isNotEmpty())
                                @foreach($otherBuyerNames as $buyerName)
                                    <option value="{{ $buyerName }}" @selected(in_array($buyerName, $preservedBuyerNames, true))>{{ $buyerName }}</option>
                                @endforeach
                            @elseif(in_array('section', $cwSlugs, true) && isset($sectionBuyerNames) && $sectionBuyerNames->isNotEmpty())
                                @foreach($sectionBuyerNames as $buyerName)
                                    <option value="{{ $buyerName }}" @selected(in_array($buyerName, $preservedBuyerNames, true))>{{ $buyerName }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                {{-- Buyers-per-page rides in the filter form so cwApplyFilters() carries it
                     (and resets to page 1) exactly like every other filter. --}}
                <input type="hidden" name="per_page" id="cwPerPageHidden" value="{{ (int) request('per_page', 8) }}">
                <a href="{{ route('admin.mess.reports.category-wise-print-slip') }}" id="cwRemoveFilter" class="programme-dt-btn-reset flex-shrink-0 align-self-center d-inline-flex align-items-center justify-content-center text-decoration-none" title="Remove all filters">Remove Filter</a>
                <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0 align-self-center">
                    <input type="search" name="search" id="cwSearch" class="form-control cw-search-input" placeholder="Search item…" autocomplete="off" value="{{ request('search') }}">
                </div>
            </form>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-body">
            @if(config('app.debug') && isset($reportTimingMs))
                <p class="small text-body-secondary mb-2 no-print" id="cw-sale-voucher-timing-hint">
                    Server: {{ $reportTimingMs }} ms
                    @if(isset($reportCacheStatus)) · cache {{ $reportCacheStatus }} @endif
                    @if(isset($reportLineCount)) · {{ $reportLineCount }} buyer section(s) @endif
                </p>
            @endif
            <div id="cw-sale-voucher-report-wrap">
                @include('admin.mess.reports.partials.category-wise-print-slip-report', [
                    'sectionsToShow' => $sectionsToShow ?? [],
                    'fromDateFormatted' => $fromDateFormatted ?? (request('from_date') ? \Carbon\Carbon::parse(request('from_date'))->format('d-F-Y') : 'Start'),
                    'toDateFormatted' => $toDateFormatted ?? (request('to_date') ? \Carbon\Carbon::parse(request('to_date'))->format('d-F-Y') : 'End'),
                    'otCourses' => $otCourses ?? collect(),
                    'grandTotal' => $grandTotal ?? 0,
                    'filtersApplied' => $filtersApplied ?? false,
                    'reportPage' => $reportPage ?? null,
                    'reportLineCount' => $reportLineCount ?? 0,
                    'freezeSaleVoucherTableHeader' => $freezeSaleVoucherTableHeader ?? false,
                ])
            </div>
        </div>
    </div>
</div>

<style>
    /* Report header – same on screen and print */
    .report-mess-title {
        color: #000;
        font-size: 1.35rem;
        font-weight: bold;
    }
    .report-title-bar {
        background-color: #004a93;
        color: #fff;
        padding: 8px 12px;
        font-size: 1rem;
        margin-top: 6px;
    }
    .report-details-row {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
    }
    .report-buyer-label { font-weight: 500; }
    .report-client-type { font-weight: 500; }

    /* Buyer banner: category left, buyer right; stack on small screens */
    @media screen and (max-width: 767.98px) {
        .report-buyer-client-banner .report-banner-client,
        .report-buyer-client-banner .report-banner-buyer {
            display: block;
            width: 100% !important;
            border-right: none !important;
            box-sizing: border-box;
        }
        .report-buyer-client-banner .report-banner-buyer {
            text-align: left !important;
            border-top: 1px solid #dee2e6 !important;
        }
    }

    /* Table – light blue header like reference image */

    .print-slip-table {
        font-size: 0.9375rem;
        table-layout: fixed;
        width: 100%;
    }
    /* Split header/body tables: shared column widths (no sticky bugs from border-collapse) */
    .print-slip-table.cw-slip-col-sync col.cw-slip-col-slip { width: 10%; }
    .print-slip-table.cw-slip-col-sync col.cw-slip-col-item { width: 25%; }
    .print-slip-table.cw-slip-col-sync col.cw-slip-col-date { width: 13%; }
    .print-slip-table.cw-slip-col-sync col.cw-slip-col-qty { width: 10%; }
    .print-slip-table.cw-slip-col-sync col.cw-slip-col-price { width: 10%; }
    .print-slip-table.cw-slip-col-sync col.cw-slip-col-amount { width: 12%; }
    .print-slip-table.cw-slip-col-sync col.cw-slip-col-remark2 { width: 28%; }

    /* Screen: frozen header = separate head table; body scrolls in sibling pane */
    @media screen {
        .print-slip-section .cw-slip-table-split {
            display: block;
            border: 1px solid var(--bs-border-color, #dee2e6);
            border-radius: 0.25rem;
            background: #fff;
        }
        .print-slip-section .cw-slip-table-head-wrap {
            flex: 0 0 auto;
            overflow: hidden;
            background: #fff;
        }
        .print-slip-section .cw-slip-table-head-wrap .print-slip-table {
            margin-bottom: 0 !important;
        }
        .print-slip-section .cw-slip-table-head-wrap thead th {
            border-bottom-color: #8eb8d0 !important;
        }
        .print-slip-section .cw-slip-table-body-scroll {
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
        }
        .print-slip-section .cw-slip-table-body-scroll .print-slip-table {
            margin-bottom: 0 !important;
        }
        .print-slip-section .cw-slip-table-body-scroll tbody tr:first-child td {
            border-top: 0 !important;
        }
        /* Fallback single-table mode (PDF/print routes): sticky header inside scroll box */
        .print-slip-section .cw-slip-table-scroll {
            overflow-x: auto !important;
            overflow-y: visible !important;
            -webkit-overflow-scrolling: touch;
        }
        .print-slip-section .cw-slip-table-scroll .print-slip-table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background: #e8f4fc !important;
            background-clip: padding-box;
            box-shadow: 0 1px 0 rgba(0, 74, 147, 0.12);
        }
        .print-grand-total-block .table-responsive {
            overflow-x: auto !important;
        }
        .print-grand-total-block .print-slip-table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            background: #e8f4fc !important;
        }
    }
    .print-slip-table thead th {
        border-color: #8eb8d0 !important;
        color: #1a1a1a;
        font-weight: 600;
        padding: 8px 6px;
    }
    .print-slip-table th, .print-slip-table td {
        white-space: normal !important;
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    .print-slip-table .th-item { width: 22%; }
    .print-slip-table .th-remark { width: 14%; }
    .print-slip-table .th-slip-no, .print-slip-table .th-date { text-align: center; }
    .print-slip-table .th-qty, .print-slip-table .th-price, .print-slip-table .th-amount { text-align: right; }
    .print-slip-table tbody td { padding: 6px 8px; vertical-align: middle; }
    .print-slip-table .total-row { background-color: #f0f0f0; font-weight: bold; }
    .print-slip-table .grand-total-row { background-color: #e2e8f0; font-weight: bold; border-top: 2px solid #004a93; }

    .pagination-custom {
        background-color: #f5f5f5;
        padding: 8px 12px;
        border-radius: 4px;
    }
    .pagination-custom .pagination-page-input { text-align: center; }
    .pagination-custom .pagination-arrow { padding: 4px 10px; }

    /* Tom Select: keep dropdown anchored to filters (avoid body-append + wrong position at page bottom) */
    .cw-sale-voucher-filter-card .card-body {
        overflow: visible;
    }
    .cw-sale-voucher-filter-card .ts-dropdown {
        z-index: 1056;
    }

    .print-page-break { page-break-after: always; }
    .print-all-mode .print-page-wrap { margin-bottom: 0; }
    .print-grand-total-block { display: block; margin-top: 12px; page-break-inside: avoid; }

    /* Impressive print layout */
    @media print {
        .no-print { display: none !important; }
        @page { size: A4; margin: 12mm; }
        /* Avoid phantom pages from full-viewport heights / clipped cards */
        html, body {
            height: auto !important;
            min-height: 0 !important;
        }
        body { font-size: 13px; background: #fff !important; }
        #main-wrapper, .page-wrapper, .body-wrapper {
            min-height: 0 !important;
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
        }
        .container-fluid { max-width: 100% !important; padding: 0 !important; }
        .card, .card-body {
            overflow: visible !important;
            page-break-inside: auto;
            break-inside: auto;
        }
        .print-page-wrap {
            page-break-after: auto;
            padding: 0;
            margin: 0 0 8px 0;
            break-inside: auto;
            page-break-inside: auto;
        }
        .print-page-break {
            page-break-after: always;
            break-after: page;
        }
        .report-header {
            margin-top: 0;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #2c3e50;
        }
        .report-mess-title {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
            letter-spacing: 0.5px;
        }
        .report-title-bar {
            font-size: 13px;
            padding: 8px 14px;
            margin-top: 6px;
            background: #2c3e50 !important;
            color: #fff !important;
            border-radius: 2px;
            letter-spacing: 0.3px;
        }
        .report-details-row {
            padding: 8px 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 3px;
            margin-bottom: 10px;
        }
        /* Long buyer tables must split across pages; "avoid" caused blank pages in Chrome / PDF */
        .print-slip-section {
            page-break-inside: auto;
            break-inside: auto;
            margin-bottom: 14px;
        }
        .report-buyer-client-banner {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .print-slip-section .table-responsive,
        .print-slip-section .cw-slip-table-scroll,
        .print-slip-section .cw-slip-table-split,
        .print-slip-section .cw-slip-table-body-scroll,
        .print-grand-total-block .table-responsive {
            max-height: none !important;
            overflow: visible !important;
        }
        .print-slip-section .cw-slip-table-split {
            display: block !important;
            border: none !important;
        }
        .print-slip-section .cw-slip-table-body-scroll tbody tr:first-child td {
            border-top: none !important;
        }
        .print-slip-section .print-slip-table thead th,
        .print-grand-total-block .print-slip-table thead th {
            position: static !important;
            box-shadow: none !important;
        }
        .print-grand-total-block {
            display: block !important;
            margin-top: 12px;
            page-break-inside: avoid;
        }
        .print-slip-table {
            font-size: 12px;
            border-collapse: collapse;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            page-break-inside: auto;
            break-inside: auto;
        }
        .print-slip-table thead {
            display: table-header-group;
        }
        .print-slip-table tfoot {
            display: table-footer-group;
        }
        .print-slip-table thead tr {
            background: #2c3e50 !important;
            color: #fff !important;
        }
        .print-slip-table thead th {
            border: 1px solid #1a252f !important;
            padding: 8px 6px !important;
            font-weight: 600;
        }
        .print-slip-table tbody td {
            padding: 6px 8px !important;
            border: 1px solid #dee2e6;
        }
        .print-slip-table .total-row {
            background: #e9ecef !important;
            font-weight: bold;
            border-top: 2px solid #2c3e50;
        }
        .print-slip-table .grand-total-row {
            background: #d8e4ef !important;
            font-weight: bold;
            border-top: 3px solid #004a93;
        }
    }

    /* ── New-design chrome: Download/Print bar + single-row filter toolbar (token-based per design.md) ── */
    .cw-report-page .cw-export-btn {
        background: var(--ds-surface, #fff);
        border: 1px solid var(--ds-line, #e5e7eb);
        color: var(--ds-primary, #004a93);
        border-radius: var(--ds-radius, 4px);
        min-height: var(--ds-control-h, 40px);
        padding: 0 1rem;
        font-weight: 500;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .cw-report-page .cw-export-btn:hover { background: var(--ds-surface-2, #f8fafc); border-color: var(--ds-primary, #004a93); }
    .cw-report-page .cw-export-btn .material-symbols-rounded { font-size: 1.15rem; }
    /* filter grid → compact single-row toolbar: hide the tall labels, use the control's own placeholder */
    .cw-report-page .cw-filter-grid { row-gap: 0.5rem; }
    .cw-report-page .cw-filter-grid > [class*="col-"] { flex: 0 0 auto; width: auto; }
    .cw-report-page .cw-filter-grid .form-label { display: none; }
    .cw-report-page .cw-filter-grid .form-control,
    .cw-report-page .cw-filter-grid .form-select,
    .cw-report-page .cw-filter-grid .ts-wrapper { min-width: 10rem; min-height: var(--ds-control-h, 40px); }
    .cw-report-page .cw-filter-grid input[type="date"] { min-width: 8.5rem; width: auto; }
    .cw-report-page .cw-search-input {
        min-height: var(--ds-control-h, 40px);
        height: var(--ds-control-h, 40px);
        width: 13rem;
        border-radius: var(--ds-radius, 4px);
        border: 1px solid var(--ds-line, #e5e7eb);
        font-size: 0.85rem;
    }
    /* Footer bar: pager left, "Showing [n] of N buyers" right — same chrome as the other report pages. */
    .cw-report-page .ssr-pagination-bar {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        font-size: 0.8125rem;
    }
    .cw-report-page .ssr-pagination-bar .pagination {
        margin-bottom: 0;
        --bs-pagination-font-size: 0.8125rem;
    }
    .cw-report-page .ssr-pagination-bar .page-link { transition: all 0.15s ease; }
    .cw-report-page .ssr-pagination-bar .page-link:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    }
    .cw-report-page .ssr-perpage-select { width: auto; min-width: 4.25rem; }
    /* Keep only the page-number links from Laravel's paginator (its own "Showing X to Y of Z results"
       text is replaced by the count we render on the right). */
    .cw-report-page .ssr-pagination-links p { display: none !important; }
    .cw-report-page .ssr-pagination-links nav > div { justify-content: flex-start !important; }
    /* LBSNAA branding header + blue title band are for the PRINT/PDF layout — hide them on the
       normal on-screen view (the clean mock goes straight to the buyer sections). They still show
       in print_all mode (browser print) and the dedicated print window builds its own header. */
    .cw-report-page:not(.print-all-mode) #cw-sale-voucher-report-wrap .report-header { display: none !important; }
    /* Trim the debug timing hint on screen */
    .cw-report-page #cw-sale-voucher-timing-hint { display: none !important; }
</style>

@if(request('print_all'))
<script>
window.addEventListener('load', function() {
    setTimeout(function() { window.print(); }, 300);
});
</script>
@endif

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js" defer></script>

<script>
// Print opens the standalone print view, which always renders EVERY buyer for the current
// filters (not just the page on screen). Pagination / transport params are dropped.
var CW_NON_FILTER_PARAMS = ['page', 'ajax', 'print_all', 'refresh', 'per_page'];
function printCategoryWiseSlip() {
    var base = @json(route('admin.mess.reports.category-wise-print-slip.print'));
    var url = new URL(base, window.location.origin);
    new URLSearchParams(window.location.search).forEach(function (value, key) {
        if (CW_NON_FILTER_PARAMS.indexOf(key) !== -1) return;
        url.searchParams.append(key, value);
    });
    window.open(url.toString(), '_blank');
}

document.addEventListener('DOMContentLoaded', function() {
    @if(isset($reportTimingMs))
    console.info(
        '[Sale Voucher Report] server {{ $reportTimingMs }} ms'
        @if(isset($reportCacheStatus)) + ', cache {{ $reportCacheStatus }}' @endif
        @if(isset($reportLineCount)) + ', {{ $reportLineCount }} buyers total' @endif
    );
    @endif

    var reportWrap = document.getElementById('cw-sale-voucher-report-wrap');

    function hookReportPagination() {
        if (!reportWrap) return;
        reportWrap.querySelectorAll('.pagination a').forEach(function (a) {
            a.addEventListener('click', function (e) { e.preventDefault(); ajaxLoadReport(this.href); });
        });
    }
    function ajaxLoadReport(url) {
        if (!reportWrap || !url) return;
        var targetUrl = url;
        if (!/[?&]ajax=1(?:&|$)/.test(url)) {
            var sep = url.indexOf('?') === -1 ? '?' : '&';
            targetUrl = url + sep + 'ajax=1';
        }
        reportWrap.style.opacity = '0.55';
        fetch(targetUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) {
                var ms = r.headers.get('X-Sale-Voucher-Report-Ms');
                var cache = r.headers.get('X-Sale-Voucher-Report-Cache');
                if (ms) console.info('[Sale Voucher Report] page ' + ms + ' ms' + (cache ? ', cache ' + cache : ''));
                return r.text();
            })
            .then(function (html) { reportWrap.innerHTML = html; reportWrap.style.opacity = ''; hookReportPagination(); })
            .catch(function (e) { reportWrap.style.opacity = ''; console.error('Sale voucher report failed', e); });
    }
    hookReportPagination();

    // Apply filters via AJAX — updates the report in place, no full page reload.
    var cwForm = document.getElementById('filterForm');
    function cwApplyFilters() {
        if (!cwForm || !reportWrap) return;
        var params = new URLSearchParams();
        new FormData(cwForm).forEach(function (v, k) { if (v === '' || v === null) return; params.append(k, v); });
        var qs = params.toString();
        var url = cwForm.action + (qs ? '?' + qs : '');
        if (window.history && window.history.pushState) { try { window.history.pushState({ cw: true }, '', url); } catch (e) {} }
        ajaxLoadReport(url);
    }
    window.cwApplyFilters = cwApplyFilters;
    window.addEventListener('popstate', function () { ajaxLoadReport(window.location.href); });

    if (cwForm) {
        var cwTimer = null;
        cwForm.addEventListener('change', function (e) {
            if (!e.target || !e.target.name || e.target.name === 'search') return;
            if (cwTimer) clearTimeout(cwTimer);
            cwTimer = setTimeout(cwApplyFilters, 500);
        });
        var cwSearchEl = document.getElementById('cwSearch');
        if (cwSearchEl) {
            var cwSearchTimer = null;
            cwSearchEl.addEventListener('input', function () {
                if (cwSearchTimer) clearTimeout(cwSearchTimer);
                cwSearchTimer = setTimeout(cwApplyFilters, 400);
            });
        }
    }
    // Buyers-per-page select — re-rendered by every AJAX swap, so listen on the document.
    document.addEventListener('change', function (e) {
        if (!e.target || e.target.id !== 'cwPerPage') return;
        var hidden = document.getElementById('cwPerPageHidden');
        if (hidden) hidden.value = e.target.value;
        cwApplyFilters();
    });

    var cwRemove = document.getElementById('cwRemoveFilter');
    if (cwRemove) {
        cwRemove.addEventListener('click', function (e) {
            e.preventDefault();
            var baseUrl = this.getAttribute('href');
            if (window.history && window.history.pushState) { try { window.history.pushState({ cw: true }, '', baseUrl); } catch (e2) {} }
            window.location.href = baseUrl; // full reset (clears cascade + tom-selects cleanly)
        });
    }
    var cwPrintBtn = document.getElementById('cwPrintBtn');
    if (cwPrintBtn) { cwPrintBtn.addEventListener('click', function (e) { e.preventDefault(); printCategoryWiseSlip(); }); }

    var filtersAlreadyApplied = {{ ($filtersApplied ?? false) ? 'true' : 'false' }};

    function initSaleVoucherFilters() {
    if (typeof TomSelect === 'undefined') return;

    var clientTypeSlug = document.getElementById('clientTypeSlug');
    var clientTypePk = document.getElementById('clientTypePk');
    var courseMasterPk = document.getElementById('courseMasterPk');
    var clientTypePkBuyer = document.getElementById('clientTypePkBuyer');
    var wrapClientTypePks = document.getElementById('wrapClientTypePks');
    var wrapCoursePks = document.getElementById('wrapCoursePks');
    if (!clientTypeSlug || !clientTypePk || !courseMasterPk || !clientTypePkBuyer) return;

    var studentsByCourseUrl = "{{ url('/admin/mess/selling-voucher-date-range/students-by-course') }}";
    var courseBuyersByCourseUrl = "{{ url('/admin/mess/reports/category-wise-print-slip/course-buyers') }}";
    var buyersForReportUrl = "{{ url('/admin/mess/reports/category-wise-print-slip/buyers') }}";
    var preservedBuyerNames = {!! json_encode($preservedBuyerNames ?? [], JSON_UNESCAPED_UNICODE) !!};

    var clientTypeOptions = {
        @foreach($clientTypes as $key => $label)
            '{{ $key }}': [
                @if(isset($clientTypeCategories[$key]))
                    @foreach($clientTypeCategories[$key] as $category)
                        { value: '{{ $category->id }}', text: @json($category->client_name), dataClientName: '{{ strtolower(trim($category->client_name ?? '')) }}' },
                    @endforeach
                @endif
            ],
        @endforeach
    };
    var otCourseOptions = [
        @if(isset($otCourses))
            @foreach($otCourses as $course)
                { value: '{{ $course->pk }}', text: @json($course->course_name . ' [' . ((int)($course->active_inactive ?? 0) === 1 ? 'Active' : 'Archived') . ']') },
            @endforeach
        @endif
    ];
    var courseBuyerNames = {!! json_encode(($courseBuyerNames ?? collect())->values()->all(), JSON_UNESCAPED_UNICODE) !!};
    var employeeNames = {
        'academy staff': [ @foreach($employees ?? [] as $e){ value: @json($e->full_name), text: @json($e->full_name) },@endforeach ],
        'faculty': [ @foreach($faculties ?? [] as $f){ value: @json($f->full_name), text: @json($f->full_name) },@endforeach ],
        'mess staff': [ @foreach($messStaff ?? [] as $m){ value: @json($m->full_name), text: @json($m->full_name) },@endforeach ]
    };

    var tsSlug, tsCat, tsCourse, tsBuyer;

    function destroyTom(sel) {
        if (sel && sel.tomselect) sel.tomselect.destroy();
    }

    function initTomMulti(sel, onChange) {
        if (!sel) return null;
        destroyTom(sel);
        var inst = new TomSelect(sel, {
            create: false,
            maxItems: null,
            placeholder: sel.getAttribute('data-placeholder') || 'Select',
            plugins: ['remove_button', 'dropdown_input'],
            sortField: { field: 'text', direction: 'asc' },
            hideSelected: false
        });
        if (typeof onChange === 'function') inst.on('change', onChange);
        return inst;
    }

    function getSlugs() {
        return tsSlug ? (tsSlug.getValue() || []) : [];
    }
    function getClientPks() {
        return tsCat ? (tsCat.getValue() || []) : [];
    }
    function getCoursePks() {
        return tsCourse ? (tsCourse.getValue() || []) : [];
    }

    function syncPkWrappers(slugs) {
        var needCat = slugs.some(function(s) { return s !== 'ot' && s !== 'course'; });
        var needCourse = slugs.some(function(s) { return s === 'ot' || s === 'course'; });
        if (wrapClientTypePks) wrapClientTypePks.style.display = needCat ? '' : 'none';
        if (wrapCoursePks) wrapCoursePks.style.display = needCourse ? '' : 'none';
    }

    function rebuildPkSelects() {
        var slugs = getSlugs();
        syncPkWrappers(slugs);
        var prevCat = getClientPks();
        var prevCourse = getCoursePks();

        destroyTom(clientTypePk);
        clientTypePk.innerHTML = '';
        var seenCat = {};
        slugs.forEach(function(slug) {
            if (slug === 'ot' || slug === 'course') return;
            (clientTypeOptions[slug] || []).forEach(function(o) {
                var k = String(o.value);
                if (seenCat[k]) return;
                seenCat[k] = true;
                var opt = document.createElement('option');
                opt.value = o.value;
                opt.textContent = o.text;
                opt.dataset.clientName = o.dataClientName || '';
                clientTypePk.appendChild(opt);
            });
        });
        tsCat = initTomMulti(clientTypePk, function() { fillBuyerNameSelect(); });
        if (prevCat.length) tsCat.setValue(prevCat.filter(function(v) { return seenCat[String(v)]; }), true);

        destroyTom(courseMasterPk);
        courseMasterPk.innerHTML = '';
        if (slugs.some(function(s) { return s === 'ot' || s === 'course'; })) {
            otCourseOptions.forEach(function(o) {
                var opt = document.createElement('option');
                opt.value = o.value;
                opt.textContent = o.text;
                courseMasterPk.appendChild(opt);
            });
        }
        tsCourse = initTomMulti(courseMasterPk, function() { fillBuyerNameSelect(); });
        if (prevCourse.length) {
            var validC = prevCourse.filter(function(v) {
                return otCourseOptions.some(function(o) { return String(o.value) === String(v); });
            });
            if (validC.length) tsCourse.setValue(validC, true);
        }
    }

    function appendPkParams(qs, slug) {
        var cps = getCoursePks();
        var kps = getClientPks();
        if (slug === 'course' || slug === 'ot') {
            cps.forEach(function(id) { qs.append('course_master_pk[]', id); });
        } else {
            kps.forEach(function(id) { qs.append('client_type_pk[]', id); });
        }
    }

    function setBuyerOptions(list) {
        destroyTom(clientTypePkBuyer);
        clientTypePkBuyer.innerHTML = '';
        (list || []).forEach(function(o) {
            var opt = document.createElement('option');
            opt.value = o.value;
            opt.textContent = o.text;
            clientTypePkBuyer.appendChild(opt);
        });
        tsBuyer = initTomMulti(clientTypePkBuyer);
        if (preservedBuyerNames && preservedBuyerNames.length) {
            var ok = preservedBuyerNames.filter(function(b) {
                return Array.from(clientTypePkBuyer.options).some(function(opt) { return opt.value === b; });
            });
            if (ok.length) tsBuyer.setValue(ok, true);
        }
    }

    function mergeUniqueBuyerRows(rows) {
        var map = new Map();
        (rows || []).forEach(function(o) {
            var k = String(o.value || '').trim();
            if (!k) return;
            var key = k.toLowerCase();
            if (!map.has(key)) map.set(key, { value: k, text: o.text || k });
        });
        return Array.from(map.values()).sort(function(a, b) {
            return String(a.text).localeCompare(String(b.text), undefined, { sensitivity: 'base' });
        });
    }

    function loadBuyersFromReportEndpoint(slugToLoad) {
        var qs = new URLSearchParams();
        qs.set('client_type_slug', slugToLoad);
        var fromEl = document.querySelector('input[name="from_date"]');
        var toEl = document.querySelector('input[name="to_date"]');
        if (fromEl && fromEl.value) qs.set('from_date', fromEl.value);
        if (toEl && toEl.value) qs.set('to_date', toEl.value);
        appendPkParams(qs, slugToLoad);
        return fetch(buyersForReportUrl + '?' + qs.toString(), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                return (data.buyers || []).map(function(name) {
                    var n = String(name || '').trim();
                    return n ? { value: n, text: n } : null;
                }).filter(Boolean);
            })
            .catch(function() { return []; });
    }

    function fillBuyerNameSelect() {
        var slugs = getSlugs();
        if (!slugs.length) {
            setBuyerOptions([]);
            return;
        }

        if (slugs.length > 1) {
            Promise.all(slugs.map(function(s) { return loadBuyersFromReportEndpoint(s); }))
                .then(function(arr) {
                    var merged = mergeUniqueBuyerRows([].concat.apply([], arr));
                    setBuyerOptions(merged);
                });
            return;
        }

        var slug = slugs[0];
        var clientPks = getClientPks();
        var coursePks = getCoursePks();

        if (slug === 'employee') {
            if (clientPks.length === 1) {
                var list = clientTypeOptions['employee'] || [];
                var match = list.find(function(o) { return String(o.value) === String(clientPks[0]); });
                var dcn = match ? (match.dataClientName || '') : '';
                if (dcn && employeeNames[dcn]) {
                    setBuyerOptions(employeeNames[dcn]);
                    return;
                }
            }
            var all = [].concat(employeeNames['academy staff'] || [], employeeNames['faculty'] || [], employeeNames['mess staff'] || []);
            setBuyerOptions(mergeUniqueBuyerRows(all));
            return;
        }

        if (slug === 'ot') {
            if (coursePks.length === 1) {
                fetch(studentsByCourseUrl + '/' + coursePks[0], { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        var students = (data.students || []).map(function(s) {
                            var n = String(s.display_name || '').trim();
                            return n ? { value: n, text: n } : null;
                        }).filter(Boolean);
                        setBuyerOptions(students);
                    })
                    .catch(function() { setBuyerOptions([]); });
                return;
            }
            loadBuyersFromReportEndpoint('ot').then(function(rows) {
                if (rows.length) { setBuyerOptions(rows); return; }
                var pks = otCourseOptions.map(function(o) { return o.value; }).filter(Boolean);
                if (!pks.length) { setBuyerOptions([]); return; }
                Promise.all(pks.map(function(pk) {
                    return fetch(studentsByCourseUrl + '/' + pk, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            return (data.students || []).map(function(s) {
                                var n = String(s.display_name || '').trim();
                                return n ? { value: n, text: n } : null;
                            }).filter(Boolean);
                        })
                        .catch(function() { return []; });
                })).then(function(results) {
                    setBuyerOptions(mergeUniqueBuyerRows([].concat.apply([], results)));
                });
            });
            return;
        }

        if (slug === 'course') {
            if (coursePks.length === 1) {
                var qs = new URLSearchParams();
                if (document.querySelector('input[name="from_date"]')?.value) qs.set('from_date', document.querySelector('input[name="from_date"]').value);
                if (document.querySelector('input[name="to_date"]')?.value) qs.set('to_date', document.querySelector('input[name="to_date"]').value);
                var url = courseBuyersByCourseUrl + '/' + coursePks[0] + (qs.toString() ? ('?' + qs.toString()) : '');
                fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        var buyers = (data.buyers || []).map(function(name) {
                            var n = String(name || '').trim();
                            return n ? { value: n, text: n } : null;
                        }).filter(Boolean);
                        setBuyerOptions(buyers);
                    })
                    .catch(function() {
                        var list = (courseBuyerNames || []).map(function(name) { return { value: name, text: name }; });
                        setBuyerOptions(list);
                    });
                return;
            }
            loadBuyersFromReportEndpoint('course').then(function(rows) { setBuyerOptions(rows); });
            return;
        }

        if (slug === 'other' || slug === 'section') {
            loadBuyersFromReportEndpoint(slug).then(function(rows) { setBuyerOptions(rows); });
            return;
        }

        if (clientTypeOptions[slug]) {
            setBuyerOptions(clientTypeOptions[slug].map(function(o) { return { value: o.text, text: o.text }; }));
            return;
        }

        setBuyerOptions([]);
    }

    var hadServerCat = clientTypePk.options.length > 0;
    var hadServerCourse = courseMasterPk.options.length > 0;

    tsSlug = initTomMulti(clientTypeSlug);
    tsCat = initTomMulti(clientTypePk, function() { fillBuyerNameSelect(); });
    tsCourse = initTomMulti(courseMasterPk, function() { fillBuyerNameSelect(); });
    tsBuyer = initTomMulti(clientTypePkBuyer);

    tsSlug.on('change', function() {
        rebuildPkSelects();
        fillBuyerNameSelect();
    });

    if (getSlugs().length && !hadServerCat && !hadServerCourse) {
        rebuildPkSelects();
    } else {
        syncPkWrappers(getSlugs());
    }

    if (clientTypePkBuyer.options.length > 0) {
        if (preservedBuyerNames && preservedBuyerNames.length && tsBuyer) {
            var ok = preservedBuyerNames.filter(function(b) {
                return Array.from(clientTypePkBuyer.options).some(function(opt) { return opt.value === b; });
            });
            if (ok.length) tsBuyer.setValue(ok, true);
        }
    } else if (!filtersAlreadyApplied) {
        fillBuyerNameSelect();
    }
    }

    if ('requestIdleCallback' in window) {
        requestIdleCallback(initSaleVoucherFilters, { timeout: 2000 });
    } else {
        setTimeout(initSaleVoucherFilters, 150);
    }
});
</script>
@endsection
