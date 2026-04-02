@extends('admin.layouts.master')
@section('title', 'Sale Voucher Report')
@section('setup_content')
@php
    $cwBuyerReqTop = is_array(request('buyer_name')) ? request('buyer_name') : (request('buyer_name') !== null && request('buyer_name') !== '' ? [request('buyer_name')] : []);
    $preservedBuyerNames = array_values(array_filter(array_map(static fn ($n) => trim((string) $n), $cwBuyerReqTop), static fn ($n) => $n !== ''));
@endphp
<div class="container-fluid py-3 py-md-4 {{ request('print_all') ? 'print-all-mode' : '' }}">
    <x-breadcrum title="Sale Voucher Report"></x-breadcrum>
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(!request('print_all'))
    <!-- Header Section -->
    <div class="card mb-4 border-0 shadow-sm rounded-3 no-print">
        <div class="card-header bg-white border-0 pb-0">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-semibold text-dark">Filter Sale Voucher Report</h5>
                    <p class="mb-0 text-muted small">Refine results by date, client type &amp; buyer name</p>
                </div>
                <span class="badge bg-light text-secondary fw-normal d-flex align-items-center">
                    <span class="material-icons me-1" style="font-size: 16px;">info</span>
                    Smart filters
                </span>
            </div>
        </div>
        <div class="card-body pt-3">
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
            <form method="GET" action="{{ route('admin.mess.reports.category-wise-print-slip') }}" id="filterForm">
                <div class="row g-3 g-md-4">
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
                                    <option value="{{ $course->pk }}" @selected(in_array((int) $course->pk, $selCoursePks, true))>{{ $course->course_name }}</option>
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
                <div class="mt-3 pt-2 border-top d-flex flex-wrap gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center">
                        <span class="material-icons me-1" style="font-size: 18px;">filter_list</span>
                        Apply Filters
                    </button>
                    <a href="{{ route('admin.mess.reports.category-wise-print-slip') }}" class="btn btn-outline-secondary d-inline-flex align-items-center">
                        <span class="material-icons me-1" style="font-size: 18px;">refresh</span>
                        Reset
                    </a>
                    <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center" id="btnPrintAll" title="Print or Save as PDF">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">print</span>
                        Print
                    </button>
                    <a href="{{ route('admin.mess.reports.category-wise-print-slip.excel', request()->query()) }}" class="btn btn-success d-inline-flex align-items-center" title="Export to Excel">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">table_view</span>
                        Export Excel
                    </a>
                    <a href="{{ route('admin.mess.reports.category-wise-print-slip.pdf', request()->query()) }}" class="btn btn-danger d-inline-flex align-items-center" title="Download PDF">
                        <span class="material-symbols-rounded me-1" style="font-size: 18px;">picture_as_pdf</span>
                        Download PDF
                    </a>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-body">
        @php
        $fromDateFormatted = request('from_date') ? \Carbon\Carbon::parse(request('from_date'))->format('d-F-Y') : 'Start';
        $toDateFormatted = request('to_date') ? \Carbon\Carbon::parse(request('to_date'))->format('d-F-Y') : 'End';
        // Always show all buyers' sections in one go (no pagination)
        $sectionsToShow = isset($allBuyersSections) ? $allBuyersSections : collect([$groupedSections]);
    @endphp

    @include('admin.mess.reports.partials.category-wise-print-slip-body', [
        'sectionsToShow' => $sectionsToShow,
        'fromDateFormatted' => $fromDateFormatted,
        'toDateFormatted' => $toDateFormatted,
        'otCourses' => $otCourses ?? collect(),
        'grandTotal' => $grandTotal ?? 0,
        'filtersApplied' => $filtersApplied ?? false,
        'printPageBreakPerBuyer' => request('print_all'),
    ])

    <!-- Pagination removed: all data loaded in a single view -->
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

    /* Table – light blue header like reference image */

    .print-slip-table {
        font-size: 0.9375rem;
        table-layout: fixed;
        width: 100%;
    }
    .table-responsive {
        overflow-x: visible !important;
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
    .print-slip-table .th-buyer, .print-slip-table .buyer-name-cell { width: 22%; }
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

    .print-page-break { page-break-after: always; }
    .print-all-mode .print-page-wrap { margin-bottom: 0; }
    .print-grand-total-block { display: block; margin-top: 12px; page-break-inside: avoid; }

    /* Impressive print layout */
    @media print {
        .no-print { display: none !important; }
        @page { size: A4; margin: 12mm; }
        body { font-size: 13px; background: #fff !important; }
        .container-fluid { max-width: 100% !important; padding: 0 !important; }
        .print-page-wrap {
            page-break-after: auto;
            padding: 0;
            margin: 0 0 8px 0;
        }
        .print-page-break {
            page-break-after: always;
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
        .print-slip-section {
            page-break-inside: avoid;
            margin-bottom: 14px;
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
</style>

@if(request('print_all'))
<script>
window.addEventListener('load', function() {
    setTimeout(function() { window.print(); }, 300);
});
</script>
@endif

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
function printCategoryWiseSlip() {
    var base = @json(route('admin.mess.reports.category-wise-print-slip.print'));
    var url = new URL(base, window.location.origin);
    var params = new URLSearchParams(window.location.search);
    params.forEach(function (value, key) {
        url.searchParams.set(key, value);
    });
    window.open(url.toString(), '_blank');
}

document.addEventListener('DOMContentLoaded', function() {
    var btnPrintAll = document.getElementById('btnPrintAll');
    if (btnPrintAll) {
        btnPrintAll.addEventListener('click', function(e) {
            e.preventDefault();
            printCategoryWiseSlip();
        });
    }

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
                { value: '{{ $course->pk }}', text: @json($course->course_name) },
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
            sortField: { field: 'text', direction: 'asc' }
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
    } else {
        fillBuyerNameSelect();
    }
});
</script>
@endsection
