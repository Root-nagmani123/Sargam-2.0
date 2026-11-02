@extends('admin.layouts.master')

@section('title', ($student->display_name ?? 'Officer Trainee') . '’s Medical Exemption Report')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
<style>
/* Medical Exemption Report — OT detail. Page-scoped pieces the mst-* /
   programme-dt-* layers don't cover (KPI grid, period toggle, range
   calendar). Tokens only (docs/design.md). */
.mst-page.mer-page .mer-stats {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr));
    gap: var(--ds-space-3);
}

.mst-page.mer-page .mer-period-toggle {
    display: flex;
    align-items: center;
    gap: var(--ds-space-2);
    width: 100%;
    text-align: left;
}

.mst-page.mer-page .mer-period-toggle > span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* The last column of these grids is data (a count / remarks), not an Action
   stack: undo master-admin.css's right-aligned, nowrap, print-hidden Action
   column rule for this page. */
.mst-page.mer-page .programme-dt-panel .programme-dt-table th:last-child,
.mst-page.mer-page .programme-dt-panel .programme-dt-table td:last-child {
    text-align: left;
    white-space: normal;
}

@media print {
    .mst-page.mer-page .programme-dt-panel .programme-dt-table th:last-child,
    .mst-page.mer-page .programme-dt-panel .programme-dt-table td:last-child {
        display: table-cell !important;
    }
}

/* --- Dual-month range calendar ----------------------------------- */
.mst-page.mer-page .mer-period-menu { min-width: auto; }
.mst-page.mer-page .mer-cal { padding: var(--ds-space-3); }
.mst-page.mer-page .mer-cal-months { display: flex; gap: var(--ds-space-4); }
@media (max-width: 575.98px) { .mst-page.mer-page .mer-cal-months { flex-direction: column; gap: var(--ds-space-3); } }
.mst-page.mer-page .mer-cal-month { width: 14.5rem; }
.mst-page.mer-page .mer-cal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--ds-space-2); }
.mst-page.mer-page .mer-cal-title { font-weight: 600; font-size: 0.875rem; color: var(--ds-ink); }
.mst-page.mer-page .mer-cal-nav {
    border: 0; background: transparent; width: 1.75rem; height: 1.75rem; border-radius: var(--ds-radius-1);
    color: var(--ds-ink-muted); display: inline-flex; align-items: center; justify-content: center;
}
.mst-page.mer-page .mer-cal-nav:hover { background: var(--ds-surface-2); color: var(--ds-ink); }
.mst-page.mer-page .mer-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 0.125rem; }
.mst-page.mer-page .mer-cal-dow { text-align: center; font-size: 0.7rem; font-weight: 600; color: var(--ds-ink-muted); padding: var(--ds-space-1) 0; }
.mst-page.mer-page .mer-cal-day {
    aspect-ratio: 1 / 1; border: 0; background: transparent; border-radius: var(--ds-radius-1);
    font-size: 0.8125rem; color: var(--ds-ink); cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
}
.mst-page.mer-page .mer-cal-day:hover { background: rgba(var(--bs-primary-rgb), 0.1); }
.mst-page.mer-page .mer-cal-day.in-range { background: rgba(var(--bs-primary-rgb), 0.12); border-radius: 0; }
.mst-page.mer-page .mer-cal-day.is-start,
.mst-page.mer-page .mer-cal-day.is-end { background: var(--ds-primary); color: var(--ds-surface); }
.mst-page.mer-page .mer-cal-day.is-start { border-radius: var(--ds-radius-1) 0 0 var(--ds-radius-1); }
.mst-page.mer-page .mer-cal-day.is-end { border-radius: 0 var(--ds-radius-1) var(--ds-radius-1) 0; }
.mst-page.mer-page .mer-cal-day.is-start.is-end { border-radius: var(--ds-radius-1); }
.mst-page.mer-page .mer-cal-footer {
    display: flex; align-items: center; justify-content: space-between; gap: var(--ds-space-2);
    margin-top: var(--ds-space-3); padding-top: var(--ds-space-3); border-top: 1px solid var(--ds-line);
}
.mst-page.mer-page .mer-cal-range { font-size: 0.8125rem; color: var(--ds-ink-muted); }
</style>
@endpush

@section('setup_content')
@php
    $otName = trim((string) ($student->display_name ?? '')) ?: 'Officer Trainee';
    $detailUrl = route('medical.exemption.report.detail', ['student' => $studentToken, 'course' => $courseToken]);
    $detailExportUrl = route('medical.exemption.report.detail.export', ['student' => $studentToken, 'course' => $courseToken]);

    // Hero: initials, OT code and the course's running / ended state.
    $otInitials = collect(preg_split('/\s+/', $otName, -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $otCode = trim((string) ($student->generated_OT_code ?? ''));
    $courseEnd = filled(optional($course)->end_date) ? \Carbon\Carbon::parse($course->end_date) : null;
    $courseStart = filled(optional($course)->start_year) ? \Carbon\Carbon::parse($course->start_year) : null;
    $courseRunning = $courseEnd ? $courseEnd->endOfDay()->isFuture() : null;
@endphp

<div class="container-fluid mst-page mer-page mer-detail-page">

    <x-breadcrum :title="$otName . '’s Medical Exemption Report'" :showBack="true" :items="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Academic'],
        ['label' => 'Medical Exemption Report', 'url' => route('medical.exemption.report.index')],
        ['label' => $otName . '’s Medical Exemption Report'],
    ]" />

    <x-session_message />

    {{-- 1. Who: the Officer Trainee and the course this report is scoped to. --}}
    <section class="mst-hero" aria-labelledby="merOtHeading">
        <div class="mst-hero__avatar" aria-hidden="true">
            <span class="mst-hero__initials">{{ $otInitials ?: 'OT' }}</span>
        </div>
        <div class="mst-hero__main">
            <h2 class="mst-hero__title" id="merOtHeading">{{ $otName }}</h2>
            @if ($otCode !== '')
                <span class="mst-hero__code">OT Code: {{ $otCode }}</span>
            @endif
            <div class="mst-facts mst-facts--hero">
                <div>
                    <span class="mst-fact__label">Course</span>
                    <span class="mst-fact__value">{{ optional($course)->course_name ?: '—' }}</span>
                </div>
                @if ($courseStart || $courseEnd)
                    <div>
                        <span class="mst-fact__label">Course Period</span>
                        <span class="mst-fact__value">{{ $courseStart ? $courseStart->format('d-m-Y') : '…' }} – {{ $courseEnd ? $courseEnd->format('d-m-Y') : '…' }}</span>
                    </div>
                @endif
                <div>
                    <span class="mst-fact__label">Exemption Records</span>
                    <span class="mst-fact__value" id="merHeroTotal">—</span>
                </div>
                @if (!is_null($courseRunning))
                    <div>
                        <span class="mst-fact__label">Course Status</span>
                        <span class="mst-fact__value">
                            @include('admin.master.partials.grid-status', [
                                'active' => $courseRunning,
                                'label'  => $courseRunning ? 'Running' : 'Ended',
                            ])
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- 2. Summary: records per medical case (unfiltered totals). --}}
    <h2 class="h6 fw-semibold mb-2">Records by Medical Case</h2>
    <div class="mer-stats mb-4">
        @foreach($stats as $stat)
        <div class="ds-stat-card">
            <div>
                <p class="ds-stat-label">{{ $stat['label'] }}</p>
                <div class="ds-stat-value">{{ str_pad((string) $stat['count'], 2, '0', STR_PAD_LEFT) }}</div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Print / Download, above the card. --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 mst-secondary-actions">
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary"
                onclick="merPrintTable()" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </button>
        <div class="dropdown">
            <button type="button" id="merDownloadBtn"
                    class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm py-2" aria-labelledby="merDownloadBtn">
                <li>
                    <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="merExportPdf">
                        <i class="bi bi-file-earmark-pdf text-danger" aria-hidden="true"></i><span>Download PDF</span>
                    </button>
                </li>
                <li>
                    <button type="button" class="dropdown-item d-flex align-items-center gap-2 py-2" id="merExportCsv">
                        <i class="bi bi-file-earmark-excel text-success" aria-hidden="true"></i><span>Download Excel</span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    {{-- 3. Filters: label over control; every change reloads the records grid. --}}
    <div class="card rounded-3 mb-3">
        <div class="card-body p-3 p-md-4">
            <h2 class="mst-form-section-title h6">Filters</h2>
            <div class="mst-filter-grid">
                <div>
                    <label for="merTimePeriodToggle" class="mst-form-label d-block">Time Period<span class="visually-hidden"> (by exemption from date)</span></label>
                    <div class="dropdown">
                        <button type="button" class="form-select mst-control mer-period-toggle" id="merTimePeriodToggle"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <i class="bi bi-calendar3" aria-hidden="true"></i>
                            <span id="merTimePeriodLabel">All dates</span>
                        </button>
                        <div class="dropdown-menu p-0 mer-period-menu">
                            <div class="mer-cal" id="merCalendar">
                                <div class="mer-cal-months">
                                    <div class="mer-cal-month" data-month="0"></div>
                                    <div class="mer-cal-month" data-month="1"></div>
                                </div>
                                <div class="mer-cal-footer">
                                    <span class="mer-cal-range" id="merCalRange">Select a date range</span>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-1" id="merClearPeriod">Clear</button>
                                        <button type="button" class="btn btn-sm btn-primary rounded-1" id="merApplyPeriod">Apply</button>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="mer_from_date_filter" value="">
                            <input type="hidden" id="mer_to_date_filter" value="">
                        </div>
                    </div>
                </div>

                <div>
                    <label for="mer_category_filter" class="mst-form-label d-block">Exemption Category</label>
                    <select id="mer_category_filter" class="form-select mst-control mst-searchable"
                            data-placeholder="All categories">
                        <option value="">All categories</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->pk }}">{{ $cat->exemp_category_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="mer_case_filter" class="mst-form-label d-block">Medical Case</label>
                    <select id="mer_case_filter" class="form-select mst-control mst-searchable"
                            data-placeholder="All medical cases">
                        <option value="">All medical cases</option>
                        @foreach($medicalCases as $case)
                        <option value="{{ $case }}">{{ $case }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mst-filter-actions">
                    <button type="button" id="merResetFilters" class="btn programme-dt-btn-reset">Reset Filters</button>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. Records. --}}
    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <h2 class="h6 fw-semibold mb-0">Exemption Records</h2>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="merColumnsToggle"
                            data-bs-toggle="modal" data-bs-target="#merColumnsModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    {{-- Server-side search (custom_search, also sent to the exports):
                         the page's own input, dressed as the programme-dt search slot. --}}
                    <div class="programme-dt-search" data-dt-search-for="merDetailTable">
                        <div class="dataTables_filter">
                            <label class="mb-0">
                                <span class="visually-hidden">Search medical case, category or remarks</span>
                                <input type="search" id="mer_search" class="form-control shadow-none"
                                       placeholder="Search case, category, remarks..." autocomplete="off">
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pager and "Showing N of M items" are relocated into the footer
                 slot by public/js/datatable-global-ui.js. --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 w-100 programme-dt-table" id="merDetailTable">
                        <caption class="visually-hidden">Medical exemption records of {{ $otName }}</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap">Date</th>
                                <th scope="col">Medical Case</th>
                                <th scope="col">Exemption Category</th>
                                <th scope="col">Remarks</th>
                            </tr>
                        </thead>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="merDetailTable"></div>
            </div>
        </div>
    </div>
</div>

{{-- Column Visibility --}}
<div class="modal fade" id="merColumnsModal" tabindex="-1" aria-labelledby="merColumnsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="merColumnsModalLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3 mst-colvis-grid" id="merColumnsGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
<script>
$(document).ready(function () {

    var table = $('#merDetailTable').DataTable({
        processing: true,
        serverSide: true,
        // The grid is searched by the page's own #mer_search (custom_search),
        // never by DataTables' global search box.
        searching: false,
        responsive: false,
        scrollX: false,
        autoWidth: false,
        lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
        pageLength: 10,
        order: [[0, 'desc']],
        language: {
            lengthMenu: "Showing _MENU_",
            info: "of _TOTAL_ items",
            infoEmpty: "of 0 items",
            infoFiltered: "",
            zeroRecords: "No matching records found",
            emptyTable: "No records available",
            paginate: { previous: "<span aria-hidden='true'>&lsaquo;</span>", next: "<span aria-hidden='true'>&rsaquo;</span>" },
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div>'
        },
        ajax: {
            url: @json($detailUrl),
            data: function (d) {
                d.custom_search = $('#mer_search').val();
                d.category_id = $('#mer_category_filter').val();
                d.medical_case = $('#mer_case_filter').val();
                d.from_date = $('#mer_from_date_filter').val();
                d.to_date = $('#mer_to_date_filter').val();
            }
        },
        columnDefs: [{ defaultContent: '—', targets: '_all' }],
        columns: [
            { data: 'date', name: 'from_date' },
            { data: 'medical_case', name: 'opd_category' },
            { data: 'category', name: 'category', orderable: false },
            { data: 'remarks', name: 'Description', orderable: false, className: 'mer-remarks mst-col-wrap' }
        ]
    });

    // Hero fact: the OT's total records for this course. The filters live in
    // the base query, so recordsTotal follows them: read it once, from the
    // first (unfiltered) load only.
    var merHeroTotalSet = false;
    table.on('xhr', function (e, settings, json) {
        if (!merHeroTotalSet && json && json.recordsTotal !== undefined) {
            merHeroTotalSet = true;
            $('#merHeroTotal').text(Number(json.recordsTotal).toLocaleString());
        }
    });

    // jQuery bindings: the two selects are searchable (Select2 fires a jQuery change).
    $('#mer_category_filter, #mer_case_filter, #mer_from_date_filter, #mer_to_date_filter').on('change', function () { table.ajax.reload(null, false); });

    var delayTimer;
    $('#mer_search').on('keyup search', function () {
        clearTimeout(delayTimer);
        delayTimer = setTimeout(function () { table.ajax.reload(null, false); }, 400);
    });

    $('#merResetFilters').on('click', function () {
        $('#mer_search').val('');
        $('#mer_category_filter').val('').trigger('change.select2');
        $('#mer_case_filter').val('').trigger('change.select2');
        $('#mer_from_date_filter').val('');
        $('#mer_to_date_filter').val('');
        updateTimePeriodLabel();
        table.ajax.reload(null, false);
    });

    function updateTimePeriodLabel() {
        var from = $('#mer_from_date_filter').val();
        var to = $('#mer_to_date_filter').val();
        $('#merTimePeriodLabel').text((from || to) ? ((from || '…') + ' → ' + (to || '…')) : 'All dates');
    }

    // ===== Dual-month range calendar =====
    (function initRangeCalendar() {
        var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        var DOW = ['Mo','Tu','We','Th','Fr','Sa','Su'];
        var view = new Date(); view.setDate(1);
        var startD = null, endD = null;
        function pad(n){ return (n < 10 ? '0' : '') + n; }
        function ymd(d){ return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
        function sameDay(a, b){ return a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate(); }
        function buildMonth(base){
            var year = base.getFullYear(), month = base.getMonth();
            var startWeekday = (new Date(year, month, 1).getDay() + 6) % 7;
            var daysInMonth = new Date(year, month + 1, 0).getDate();
            var html = '<div class="mer-cal-head">' +
                '<button type="button" class="mer-cal-nav" data-nav="prev" aria-label="Previous month">&lsaquo;</button>' +
                '<span class="mer-cal-title">' + MONTHS[month] + ' ' + year + '</span>' +
                '<button type="button" class="mer-cal-nav" data-nav="next" aria-label="Next month">&rsaquo;</button>' +
                '</div><div class="mer-cal-grid">';
            DOW.forEach(function(d){ html += '<span class="mer-cal-dow">' + d + '</span>'; });
            for (var i = 0; i < startWeekday; i++) html += '<span></span>';
            for (var day = 1; day <= daysInMonth; day++){
                var d = new Date(year, month, day);
                var cls = 'mer-cal-day';
                if (startD && endD && d > startD && d < endD) cls += ' in-range';
                if (sameDay(d, startD)) cls += ' is-start';
                if (sameDay(d, endD)) cls += ' is-end';
                html += '<button type="button" class="' + cls + '" data-date="' + ymd(d) + '">' + day + '</button>';
            }
            return html + '</div>';
        }
        function render(){
            var left = new Date(view.getFullYear(), view.getMonth(), 1);
            var right = new Date(view.getFullYear(), view.getMonth() + 1, 1);
            $('#merCalendar .mer-cal-month[data-month="0"]').html(buildMonth(left));
            $('#merCalendar .mer-cal-month[data-month="1"]').html(buildMonth(right));
            $('#merCalendar .mer-cal-month[data-month="0"] [data-nav="next"]').css('visibility', 'hidden');
            $('#merCalendar .mer-cal-month[data-month="1"] [data-nav="prev"]').css('visibility', 'hidden');
            var label = 'Select a date range';
            if (startD && endD) label = ymd(startD) + '  ->  ' + ymd(endD);
            else if (startD) label = ymd(startD) + '  -> ...';
            $('#merCalRange').text(label);
        }
        $('#merCalendar').on('click', '.mer-cal-nav', function(){
            var dir = $(this).data('nav') === 'prev' ? -1 : 1;
            view = new Date(view.getFullYear(), view.getMonth() + dir, 1); render();
        });
        $('#merCalendar').on('click', '.mer-cal-day', function(){
            var p = String($(this).data('date')).split('-');
            var d = new Date(+p[0], +p[1] - 1, +p[2]);
            if (!startD || (startD && endD)) { startD = d; endD = null; }
            else if (d < startD) { startD = d; }
            else { endD = d; }
            render();
        });
        $('#merApplyPeriod').on('click', function(){
            $('#mer_from_date_filter').val(startD ? ymd(startD) : '');
            $('#mer_to_date_filter').val(endD ? ymd(endD) : (startD ? ymd(startD) : ''));
            updateTimePeriodLabel();
            table.ajax.reload(null, false);
            if (window.bootstrap) { bootstrap.Dropdown.getOrCreateInstance(document.getElementById('merTimePeriodToggle')).hide(); }
        });
        $('#merClearPeriod').on('click', function(){
            startD = null; endD = null; render();
            $('#mer_from_date_filter').val(''); $('#mer_to_date_filter').val('');
            updateTimePeriodLabel(); table.ajax.reload(null, false);
        });
        render();
    })();

    // ===== Column Visibility =====
    var $columnsGrid = $('#merColumnsGrid');
    table.columns().every(function (idx) {
        var title = $.trim($(this.header()).text()) || ('Column ' + (idx + 1));
        var visible = this.visible();
        var $cb = $('<input class="form-check-input m-0 mer-col-toggle" type="checkbox">')
            .attr({ id: 'merColToggle' + idx, 'data-column': idx })
            .prop('checked', visible);
        $columnsGrid.append(
            $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                $('<label class="colvis-item mer-col-chip d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', 'merColToggle' + idx)
                    .append($cb)
                    .append($('<span></span>').text(title))
            )
        );
    });
    $columnsGrid.on('change', '.mer-col-toggle', function () {
        table.column($(this).data('column')).visible(this.checked);
    });

    // ===== Export =====
    var merExportBase = @json($detailExportUrl);
    function merExportUrl(format) {
        var params = new URLSearchParams();
        params.set('format', format);
        var search = $('#mer_search').val();
        var category = $('#mer_category_filter').val();
        var medicalCase = $('#mer_case_filter').val();
        var from = $('#mer_from_date_filter').val();
        var to = $('#mer_to_date_filter').val();
        if (search) params.set('custom_search', search);
        if (category) params.set('category_id', category);
        if (medicalCase) params.set('medical_case', medicalCase);
        if (from) params.set('from_date', from);
        if (to) params.set('to_date', to);
        var visibleCols = [];
        table.columns().every(function (idx) { if (this.visible()) visibleCols.push(idx); });
        params.set('columns', visibleCols.join(','));
        return merExportBase + '?' + params.toString();
    }
    $('#merExportPdf').on('click', function (e) { e.preventDefault(); window.location.href = merExportUrl('pdf'); });
    $('#merExportCsv').on('click', function (e) { e.preventDefault(); window.location.href = merExportUrl('excel'); });
});

// ===== Print =====
function merPrintTable() {
    var table = document.getElementById('merDetailTable');
    if (!table) { alert('Table not found!'); return; }
    var printWindow = window.open('', '_blank');
    if (!printWindow) { alert('Please allow pop-ups for this site to print the report.'); return; }

    // The screen-reader caption is hidden by Bootstrap on screen; the print
    // window has no Bootstrap, so drop it rather than print it as a stray line.
    var tableClone = table.cloneNode(true);
    var tableCaption = tableClone.querySelector('caption');
    if (tableCaption) { tableCaption.parentNode.removeChild(tableCaption); }
    var tableHTML = tableClone.outerHTML;
    var dateStr = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
    var logoLeft  = @json(asset('admin_assets/images/logos/logo_new.png'));
    var logoRight = @json(file_exists(public_path('admin_assets/images/logos/constitution-75.png'))
        ? asset('admin_assets/images/logos/constitution-75.png')
        : asset('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png'));
    var titleHindi = @json(asset('admin_assets/images/logos/lbsnaa-title-hi.png'));
    var reportTitle = @json($otName . '’s Medical Exemption Report');
    var courseName = @json(optional($course)->course_name ?? '');

    var printContent =
        '<!DOCTYPE html><html><head><title>' + reportTitle + ' - Print</title><style>' +
        'body{font-family:Arial,sans-serif;margin:16px;color:#1f2937;}' +
        '.pdf-hdr{width:100%;border-collapse:collapse;margin-bottom:4px;}' +
        '.pdf-hdr td{vertical-align:middle;} .pdf-hdr .logo{width:90px;text-align:center;}' +
        '.pdf-hdr .logo img{max-height:64px;max-width:84px;} .pdf-hdr .center{text-align:center;padding:0 8px;}' +
        '.pdf-hdr .inst-hi-img{height:18px;width:auto;margin-bottom:2px;}' +
        '.pdf-hdr .inst-en{font-size:16px;font-weight:bold;color:#102a43;line-height:1.25;}' +
        '.pdf-hdr .course-line{font-size:12px;font-weight:bold;color:#243b53;margin-top:4px;}' +
        '.report-title{text-align:center;font-size:20px;font-weight:bold;color:#004a93;margin:8px 0 6px;padding-bottom:8px;border-bottom:2px solid #004a93;}' +
        '.print-info{margin-bottom:12px;font-size:11px;color:#666;text-align:center;}' +
        'table{width:100%;border-collapse:collapse;margin-top:10px;}' +
        'table th,table td{border:1px solid #8fa3bd;padding:6px 8px;text-align:left;font-size:12px;}' +
        'table thead th{font-weight:bold;background-color:#004a93 !important;color:#fff !important;text-align:center;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
        'table tbody tr:nth-child(even){background-color:#eef2f8;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
        '.print-footer{margin-top:18px;text-align:center;font-size:10px;color:#666;border-top:1px solid #ccc;padding-top:10px;}' +
        '@media print{@page{size:A4 landscape;margin:10mm;} body{margin:0;}}' +
        '</style></head><body onload="window.focus();window.print();">' +
        '<table class="pdf-hdr"><tr>' +
            '<td class="logo"><img src="' + logoLeft + '" alt=""></td>' +
            '<td class="center"><img class="inst-hi-img" src="' + titleHindi + '" alt="">' +
                '<div class="inst-en">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div>' +
                (courseName ? '<div class="course-line">' + courseName + '</div>' : '') +
            '</td>' +
            '<td class="logo"><img src="' + logoRight + '" alt=""></td>' +
        '</tr></table>' +
        '<div class="report-title">' + reportTitle + '</div>' +
        '<div class="print-info"><div>Print Date: ' + dateStr + '</div></div>' +
        tableHTML +
        '<div class="print-footer"><p>Generated on ' + new Date().toLocaleString() + '</p></div>' +
        '</body></html>';

    printWindow.document.open();
    printWindow.document.write(printContent);
    printWindow.document.close();
}
</script>
@endpush
