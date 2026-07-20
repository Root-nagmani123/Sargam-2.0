@extends('admin.layouts.master')
@section('title', 'Requested ID Card')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
/* --- "Time Period" dual-month range calendar --- */
.idcp-toggle {
    height: 40px; min-width: 170px;
    display: inline-flex; align-items: center; gap: 0.4rem;
    padding: 0 0.875rem; font-size: 0.9375rem; font-weight: 400;
    color: #344054; background: #fff; border: 1px solid #d0d5dd; border-radius: 8px;
}
.idcp-toggle:hover { border-color: #004a93; color: #344054; }
.idcp-toggle.dropdown-toggle::after { margin-left: auto; color: #667085; }
.idcp-menu { min-width: auto; }
.idcp-cal { padding: var(--ds-space-3, 1rem); }
.idcp-cal-months { display: flex; gap: var(--ds-space-4, 1.5rem); }
@media (max-width: 575.98px) { .idcp-cal-months { flex-direction: column; gap: var(--ds-space-3, 1rem); } }
.idcp-cal-month { width: 232px; }
.idcp-cal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--ds-space-2, 0.5rem); }
.idcp-cal-title { font-weight: 600; font-size: 0.875rem; color: var(--ds-ink, #1f2937); }
.idcp-cal-nav {
    border: 0; background: transparent; width: 28px; height: 28px; border-radius: var(--ds-radius-1, 6px);
    color: var(--ds-ink-muted, #6c757d); display: inline-flex; align-items: center; justify-content: center;
}
.idcp-cal-nav:hover { background: var(--ds-surface-2, #f1f3f5); color: var(--ds-ink, #1f2937); }
.idcp-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.idcp-cal-dow { text-align: center; font-size: 0.7rem; font-weight: 600; color: var(--ds-ink-muted, #6c757d); padding: 4px 0; }
.idcp-cal-day {
    aspect-ratio: 1 / 1; border: 0; background: transparent; border-radius: var(--ds-radius-1, 6px);
    font-size: 0.8125rem; color: var(--ds-ink, #1f2937); cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
}
.idcp-cal-day:hover { background: rgba(var(--bs-primary-rgb, 0 74 147), 0.1); }
.idcp-cal-day.in-range { background: rgba(var(--bs-primary-rgb, 0 74 147), 0.12); border-radius: 0; }
.idcp-cal-day.is-start, .idcp-cal-day.is-end { background: var(--bs-primary, #004a93); color: #fff; }
.idcp-cal-day.is-start { border-radius: var(--ds-radius-1, 6px) 0 0 var(--ds-radius-1, 6px); }
.idcp-cal-day.is-end { border-radius: 0 var(--ds-radius-1, 6px) var(--ds-radius-1, 6px) 0; }
.idcp-cal-day.is-start.is-end { border-radius: var(--ds-radius-1, 6px); }
.idcp-cal-footer {
    display: flex; align-items: center; justify-content: space-between; gap: var(--ds-space-2, 0.5rem);
    margin-top: var(--ds-space-3, 1rem); padding-top: var(--ds-space-3, 1rem); border-top: 1px solid var(--ds-line, #dee2e6);
}
.idcp-cal-range { font-size: 0.8125rem; color: var(--ds-ink-muted, #6c757d); }

.a2-status-tabs .programme-status-pill.active .badge.bg-white { background-color: #fff !important; color: #004a93 !important; }
.a2-status-tabs .programme-status-pill .badge { font-weight: 600; }
</style>
@endpush

@section('content')
@php
    $a2ActiveTab = $activeTab ?? 'new';
    $a2DateFrom = request('date_from', \App\Http\Controllers\Admin\Security\EmployeeIDCardApprovalController::DEFAULT_REQUEST_DATE_FROM);
    $a2DateTo = request('date_to', '');
@endphp

<div class="container-fluid idcard-approval2-page py-3">
    <x-breadcrum title="Requested ID Card">
        <a href="{{ route('admin.security.employee_idcard_approval.all') }}"
           class="btn btn-outline-secondary d-inline-flex align-items-center gap-2 px-4 rounded-1">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">list</i>
            <span>All Requests</span>
        </a>
    </x-breadcrum>
    <x-session_message />

    {{-- Status tabs (left) · Export (right) — above the card --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs a2-status-tabs bg-white mb-0 flex-wrap"
            id="approval2Tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-1 px-3 py-2 fw-semibold programme-status-pill {{ $a2ActiveTab === 'new' ? 'active' : '' }}"
                        id="new-request-tab" data-bs-toggle="tab" data-bs-target="#new-request-panel" type="button" role="tab"
                        aria-controls="new-request-panel" aria-selected="{{ $a2ActiveTab === 'new' ? 'true' : 'false' }}" data-tab-key="new">
                    New Request
                    <span class="badge rounded-1 bg-white text-primary ms-1">{{ $newRequests->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-1 px-3 py-2 fw-semibold programme-status-pill {{ $a2ActiveTab === 'for_approval' ? 'active' : '' }}"
                        id="for-approval-tab" data-bs-toggle="tab" data-bs-target="#for-approval-panel" type="button" role="tab"
                        aria-controls="for-approval-panel" aria-selected="{{ $a2ActiveTab === 'for_approval' ? 'true' : 'false' }}" data-tab-key="for_approval">
                    Processed Request
                    <span class="badge rounded-1 bg-secondary ms-1">{{ $forApprovalRequests->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-1 px-3 py-2 fw-semibold programme-status-pill {{ $a2ActiveTab === 'issued' ? 'active' : '' }}"
                        id="issued-tab" data-bs-toggle="tab" data-bs-target="#issued-panel" type="button" role="tab"
                        aria-controls="issued-panel" aria-selected="{{ $a2ActiveTab === 'issued' ? 'true' : 'false' }}" data-tab-key="issued">
                    Issued
                    <span class="badge rounded-1 bg-success ms-1">{{ $issuedRequests->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-1 px-3 py-2 fw-semibold programme-status-pill {{ $a2ActiveTab === 'rejected' ? 'active' : '' }}"
                        id="rejected-tab" data-bs-toggle="tab" data-bs-target="#rejected-panel" type="button" role="tab"
                        aria-controls="rejected-panel" aria-selected="{{ $a2ActiveTab === 'rejected' ? 'true' : 'false' }}" data-tab-key="rejected">
                    Rejected
                    <span class="badge rounded-1 bg-danger ms-1">{{ $rejectedRequests->count() }}</span>
                </button>
            </li>
        </ul>

        <div class="dropdown">
            <button class="btn programme-dt-btn-columns dropdown-toggle border-0 text-primary" type="button" id="exportDropdown"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Export">
                <i class="bi bi-download" aria-hidden="true"></i> <span>Export</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2" aria-labelledby="exportDropdown">
                <li><h6 class="dropdown-header text-muted small text-uppercase">Export with current filters</h6></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2" target="_blank" rel="noopener"
                       href="{{ route('admin.security.employee_idcard_approval.export', array_merge(request()->query(), ['stage' => '2', 'format' => 'print'])) }}">
                        <i class="bi bi-printer" aria-hidden="true"></i> Print
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                       href="{{ route('admin.security.employee_idcard_approval.export', array_merge(request()->query(), ['stage' => '2', 'format' => 'xlsx'])) }}">
                        <i class="bi bi-file-earmark-spreadsheet text-success" aria-hidden="true"></i> Excel
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center gap-2 py-2"
                       href="{{ route('admin.security.employee_idcard_approval.export', array_merge(request()->query(), ['stage' => '2', 'format' => 'pdf'])) }}">
                        <i class="bi bi-file-earmark-pdf text-danger" aria-hidden="true"></i> PDF
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="card overflow-hidden rounded-1">
        <div class="card-body p-3 p-md-4">

            {{-- Filter toolbar (programme-dt design system) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    {{-- Request Date range. This reloads the page: the server bounds the
                         result set by date (defaulting to DEFAULT_REQUEST_DATE_FROM), so
                         filtering only in the browser would hide older records entirely. --}}
                    <form method="GET" action="{{ route('admin.security.employee_idcard_approval.approval2') }}" id="filterForm" class="d-inline">
                        <input type="hidden" name="tab" id="activeTabInput" value="{{ $a2ActiveTab }}">
                        <input type="hidden" name="date_from" id="a2DateFrom" value="{{ $a2DateFrom }}">
                        <input type="hidden" name="date_to" id="a2DateTo" value="{{ $a2DateTo }}">
                    </form>

                    <div class="dropdown">
                        <button type="button" class="idcp-toggle dropdown-toggle"
                                id="a2TimePeriodToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">calendar_month</i>
                            <span id="a2TimePeriodLabel">Time Period</span>
                        </button>
                        <div class="dropdown-menu p-0 idcp-menu">
                            <div class="idcp-cal" id="a2Calendar">
                                <div class="idcp-cal-months">
                                    <div class="idcp-cal-month" data-month="0"></div>
                                    <div class="idcp-cal-month" data-month="1"></div>
                                </div>
                                <div class="idcp-cal-footer">
                                    <span class="idcp-cal-range" id="a2CalRange">Select a date range</span>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="a2ClearPeriod">Clear</button>
                                        <button type="button" class="btn btn-sm btn-primary" id="a2ApplyPeriod">Apply</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('admin.security.employee_idcard_approval.approval2') }}" class="btn programme-dt-btn-reset">Reset Filters</a>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="a2BtnColumns"
                        data-bs-toggle="modal" data-bs-target="#a2ColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    {{-- One search slot per table; only the active tab's is visible. --}}
                    <div class="programme-dt-search a2-search" data-tab-key="new" data-dt-search-for="a2TableNew"></div>
                    <div class="programme-dt-search a2-search d-none" data-tab-key="for_approval" data-dt-search-for="a2TableFor"></div>
                    <div class="programme-dt-search a2-search d-none" data-tab-key="issued" data-dt-search-for="a2TableIssued"></div>
                    <div class="programme-dt-search a2-search d-none" data-tab-key="rejected" data-dt-search-for="a2TableRejected"></div>
                </div>
            </div>

            <div class="tab-content">
                <div class="tab-pane {{ $a2ActiveTab === 'new' ? 'show active' : '' }}" id="new-request-panel" role="tabpanel" aria-labelledby="new-request-tab">
                    @include('admin.security.employee_idcard_approval._approval_table', [
                        'requests' => $newRequests, 'approvalStage' => 2, 'tableId' => 'a2TableNew',
                    ])
                </div>
                <div class="tab-pane {{ $a2ActiveTab === 'for_approval' ? 'show active' : '' }}" id="for-approval-panel" role="tabpanel" aria-labelledby="for-approval-tab">
                    @include('admin.security.employee_idcard_approval._approval_table', [
                        'requests' => $forApprovalRequests, 'approvalStage' => 2, 'tableId' => 'a2TableFor',
                    ])
                </div>
                <div class="tab-pane {{ $a2ActiveTab === 'issued' ? 'show active' : '' }}" id="issued-panel" role="tabpanel" aria-labelledby="issued-tab">
                    @include('admin.security.employee_idcard_approval._approval_table', [
                        'requests' => $issuedRequests, 'approvalStage' => 2, 'tableId' => 'a2TableIssued',
                    ])
                </div>
                <div class="tab-pane {{ $a2ActiveTab === 'rejected' ? 'show active' : '' }}" id="rejected-panel" role="tabpanel" aria-labelledby="rejected-tab">
                    @include('admin.security.employee_idcard_approval._approval_table', [
                        'requests' => $rejectedRequests, 'approvalStage' => 2, 'tableId' => 'a2TableRejected',
                    ])
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold">Rejection Reason</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body pt-0">
                    <p class="text-muted small" id="rejectModalEmployeeName"></p>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Enter Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="a2ColumnVisibilityModal" tabindex="-1" aria-labelledby="a2ColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="a2ColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="a2ColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    /* ---- Reject modal (rows come from the shared partial) ---- */
    $(document).on('click', '.reject-btn', function () {
        document.getElementById('rejectModalEmployeeName').textContent = 'Rejecting: ' + (this.dataset.name || '');
        document.getElementById('rejectForm').action = this.dataset.url || '#';
        document.getElementById('rejection_reason').value = '';
        new bootstrap.Modal(document.getElementById('rejectModal')).show();
    });

    var TABLES = {
        new:          { id: 'a2TableNew',      dt: null },
        for_approval: { id: 'a2TableFor',      dt: null },
        issued:       { id: 'a2TableIssued',   dt: null },
        rejected:     { id: 'a2TableRejected', dt: null }
    };
    var TAB_KEYS = ['new', 'for_approval', 'issued', 'rejected'];
    var currentTab = @json($a2ActiveTab);

    function initTable(tableId) {
        var $table = $('#' + tableId);
        if (!$table.length) { return null; }
        // Skip the empty-state row so its CTA stays visible.
        if ($table.find('tbody tr').length === 0 || $table.find('tbody td[colspan]').length > 0) { return null; }
        var colCount = $table.find('thead th').length;
        return $table.DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            responsive: false,
            order: [],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            columnDefs: [
                // S.No (0) and the approve/reject action column are not sortable.
                { targets: [0], orderable: false, searchable: false },
                { targets: [colCount - 3], orderable: false, searchable: false }
            ],
            language: {
                search: '',
                searchPlaceholder: 'Search',
                paginate: { previous: '‹', next: '›' },
                lengthMenu: 'Showing _MENU_',
                info: 'of _TOTAL_ items',
                infoEmpty: 'of 0 items',
                infoFiltered: 'of _MAX_ items'
            },
            drawCallback: function () {
                var info = this.api().page.info();
                this.api().column(0, { page: 'current' }).nodes().each(function (cell, i) {
                    cell.innerHTML = info.start + i + 1;
                });
            }
        });
    }

    TAB_KEYS.forEach(function (key) { TABLES[key].dt = initTable(TABLES[key].id); });
    function currentDt() { return TABLES[currentTab] ? TABLES[currentTab].dt : null; }

    /* ---- "Time Period" dual-month range calendar (submits — server-side filter) ---- */
    function updateTimePeriodLabel() {
        var from = $('#a2DateFrom').val();
        var to = $('#a2DateTo').val();
        $('#a2TimePeriodLabel').text((from || to) ? ((from || '…') + ' → ' + (to || '…')) : 'Time Period');
    }

    (function initRangeCalendar() {
        var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        var DOW = ['Mo','Tu','We','Th','Fr','Sa','Su'];
        var view = new Date(); view.setDate(1);
        var startD = null, endD = null;

        function pad(n){ return (n < 10 ? '0' : '') + n; }
        function ymd(d){ return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
        function parseYmd(s){ if (!s) { return null; } var p = String(s).split('-'); if (p.length < 3) { return null; } return new Date(+p[0], +p[1] - 1, +p[2]); }
        function sameDay(a, b){ return a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate(); }

        function buildMonth(base){
            var year = base.getFullYear(), month = base.getMonth();
            var startWeekday = (new Date(year, month, 1).getDay() + 6) % 7;
            var daysInMonth = new Date(year, month + 1, 0).getDate();
            var html = '<div class="idcp-cal-head">' +
                '<button type="button" class="idcp-cal-nav" data-nav="prev" aria-label="Previous month">&lsaquo;</button>' +
                '<span class="idcp-cal-title">' + MONTHS[month] + ' ' + year + '</span>' +
                '<button type="button" class="idcp-cal-nav" data-nav="next" aria-label="Next month">&rsaquo;</button>' +
                '</div><div class="idcp-cal-grid">';
            DOW.forEach(function(d){ html += '<span class="idcp-cal-dow">' + d + '</span>'; });
            for (var i = 0; i < startWeekday; i++) html += '<span></span>';
            for (var day = 1; day <= daysInMonth; day++){
                var d = new Date(year, month, day);
                var cls = 'idcp-cal-day';
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
            $('#a2Calendar .idcp-cal-month[data-month="0"]').html(buildMonth(left));
            $('#a2Calendar .idcp-cal-month[data-month="1"]').html(buildMonth(right));
            $('#a2Calendar .idcp-cal-month[data-month="0"] [data-nav="next"]').css('visibility', 'hidden');
            $('#a2Calendar .idcp-cal-month[data-month="1"] [data-nav="prev"]').css('visibility', 'hidden');
            var label = 'Select a date range';
            if (startD && endD) label = ymd(startD) + '  →  ' + ymd(endD);
            else if (startD) label = ymd(startD) + '  → …';
            $('#a2CalRange').text(label);
        }

        $('#a2Calendar').on('click', '.idcp-cal-nav', function(){
            var dir = $(this).data('nav') === 'prev' ? -1 : 1;
            view = new Date(view.getFullYear(), view.getMonth() + dir, 1);
            render();
        });
        $('#a2Calendar').on('click', '.idcp-cal-day', function(){
            var p = String($(this).data('date')).split('-');
            var d = new Date(+p[0], +p[1] - 1, +p[2]);
            if (!startD || (startD && endD)) { startD = d; endD = null; }
            else if (d < startD) { startD = d; }
            else { endD = d; }
            render();
        });
        $('#a2ApplyPeriod').on('click', function(){
            $('#a2DateFrom').val(startD ? ymd(startD) : '');
            $('#a2DateTo').val(endD ? ymd(endD) : (startD ? ymd(startD) : ''));
            $('#activeTabInput').val(currentTab);
            document.getElementById('filterForm').submit();
        });
        $('#a2ClearPeriod').on('click', function(){
            startD = null; endD = null; render();
            $('#a2DateFrom').val(''); $('#a2DateTo').val('');
            $('#activeTabInput').val(currentTab);
            document.getElementById('filterForm').submit();
        });

        startD = parseYmd($('#a2DateFrom').val());
        endD = parseYmd($('#a2DateTo').val());
        if (startD) { view = new Date(startD.getFullYear(), startD.getMonth(), 1); }
        render();
        updateTimePeriodLabel();
    })();

    /* ---- Column show / hide (targets the active tab's table) ---- */
    var colKeyPrefix = 'a2ApprovalGrid:hiddenColumns:v1:';
    function getHidden(id) { try { var a = JSON.parse(localStorage.getItem(colKeyPrefix + id) || '[]'); return Array.isArray(a) ? a : []; } catch (e) { return []; } }
    function setHidden(id, a) { try { localStorage.setItem(colKeyPrefix + id, JSON.stringify(a)); } catch (e) {} }

    function applyStoredColumns(dt, id) {
        if (!dt) { return; }
        var hidden = getHidden(id);
        dt.columns().every(function () { var idx = this.index(); this.visible(hidden.indexOf(idx) === -1, false); });
        dt.columns.adjust();
    }

    function buildColumnsModal() {
        var dt = currentDt();
        var $grid = $('#a2ColumnToggleGrid');
        $grid.empty();
        if (!dt) { $grid.append('<p class="text-muted mb-0">No data to configure.</p>'); return; }
        var id = TABLES[currentTab].id;
        var hidden = getHidden(id);
        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }
            var inputId = 'a2colvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>').attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0">').attr('id', inputId).prop('checked', hidden.indexOf(idx) === -1);
            $cb.on('change', function () {
                var h = getHidden(id); var pos = h.indexOf(idx);
                if (this.checked) { if (pos !== -1) h.splice(pos, 1); } else { if (pos === -1) h.push(idx); }
                setHidden(id, h); dt.column(idx).visible(this.checked, false); dt.columns.adjust();
            });
            $label.append($cb).append($('<span></span>').text(title));
            $cell.append($label); $grid.append($cell);
        });
    }

    TAB_KEYS.forEach(function (key) { applyStoredColumns(TABLES[key].dt, TABLES[key].id); });
    $('#a2ColumnVisibilityModal').on('show.bs.modal', buildColumnsModal);

    /* ---- Tab change: track active tab, swap search box, fix widths, sync URL ---- */
    $('#approval2Tabs .nav-link').on('shown.bs.tab', function () {
        currentTab = this.dataset.tabKey || 'new';
        $('#activeTabInput').val(currentTab);
        $('.a2-search').each(function () {
            $(this).toggleClass('d-none', $(this).data('tabKey') !== currentTab);
        });
        var dt = currentDt();
        if (dt) { dt.columns.adjust(); }
        try {
            var url = new URL(window.location.href);
            url.searchParams.set('tab', currentTab);
            window.history.replaceState({}, '', url.toString());
        } catch (e) {}
    });

    // Reflect the server-rendered active tab in the search-slot visibility.
    $('.a2-search').each(function () {
        $(this).toggleClass('d-none', $(this).data('tabKey') !== currentTab);
    });
});
</script>
@endpush
