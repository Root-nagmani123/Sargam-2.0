@extends('admin.layouts.master')
@section('title', 'Requested Vehicle Pass')

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

/* Vehicle Type select styled as a programme-dt filter pill. */
.vehapp-filter-select {
    height: 40px; width: 170px; padding: 0 2rem 0 0.875rem; font-size: 0.9375rem;
    color: #344054; background-color: #fff; border: 1px solid #d0d5dd; border-radius: 8px;
}
.vehapp-filter-select:focus { border-color: #004a93; box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12); }

.vehapp-status-tabs .programme-status-pill.active .badge.bg-white { background-color: #fff !important; color: #004a93 !important; }
.vehapp-status-tabs .programme-status-pill .badge { font-weight: 600; }
.vehapp-legend { font-size: 0.8125rem; }
</style>
@endpush

@section('content')
@php
    $vehActiveTab = $activeTab ?? 'new';
    $wh = $wheeler ?? request('wheeler', 'tw');
@endphp

<div class="container-fluid vehicle-approval-page py-3">
    <x-breadcrum title="Requested Vehicle Pass"></x-breadcrum>
    <x-session_message />

    {{-- Status tabs — above the card --}}
    <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs vehapp-status-tabs bg-white mb-3 flex-wrap"
        id="vehicleApprovalTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link rounded-1 px-3 py-2 fw-semibold programme-status-pill {{ $vehActiveTab === 'new' ? 'active' : '' }}"
                    id="veh-new-tab" data-bs-toggle="tab" data-bs-target="#veh-new-panel" role="tab" data-tab-key="new"
                    aria-selected="{{ $vehActiveTab === 'new' ? 'true' : 'false' }}"
                    title="Passes waiting for your approve or reject action at this stage.">
                Pending — your action
                <span class="badge rounded-1 bg-white text-primary ms-1">{{ $newApplications->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link rounded-1 px-3 py-2 fw-semibold programme-status-pill {{ $vehActiveTab === 'for_approval' ? 'active' : '' }}"
                    id="veh-for-tab" data-bs-toggle="tab" data-bs-target="#veh-for-panel" role="tab" data-tab-key="for_approval"
                    aria-selected="{{ $vehActiveTab === 'for_approval' ? 'true' : 'false' }}"
                    title="Only after Level 1 is approved. Waiting for final approval or view-only here.">
                Pending — other stage
                <span class="badge rounded-1 bg-warning text-dark ms-1">{{ $processedApplications->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link rounded-1 px-3 py-2 fw-semibold programme-status-pill {{ $vehActiveTab === 'issued' ? 'active' : '' }}"
                    id="veh-issued-tab" data-bs-toggle="tab" data-bs-target="#veh-issued-panel" role="tab" data-tab-key="issued"
                    aria-selected="{{ $vehActiveTab === 'issued' ? 'true' : 'false' }}"
                    title="Fully approved vehicle passes.">
                Approved
                <span class="badge rounded-1 bg-success ms-1">{{ $issuedApplications->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link rounded-1 px-3 py-2 fw-semibold programme-status-pill {{ $vehActiveTab === 'rejected' ? 'active' : '' }}"
                    id="veh-rejected-tab" data-bs-toggle="tab" data-bs-target="#veh-rejected-panel" role="tab" data-tab-key="rejected"
                    aria-selected="{{ $vehActiveTab === 'rejected' ? 'true' : 'false' }}"
                    title="Applications rejected at any stage.">
                Rejected
                <span class="badge rounded-1 bg-danger ms-1">{{ $rejectedApplications->count() }}</span>
            </button>
        </li>
    </ul>

    <div class="card overflow-hidden rounded-1">
        <div class="card-body p-3 p-md-4">

            {{-- Filter toolbar (programme-dt design system) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    {{-- Vehicle Type reloads the page: it selects which source tables are queried. --}}
                    <form method="GET" action="{{ route('admin.security.vehicle_pass_approval.index') }}" id="vehicleFilterForm" class="d-inline">
                        <input type="hidden" name="tab" id="vehicleActiveTabInput" value="{{ $vehActiveTab }}">
                        <select name="wheeler" id="wheeler" class="form-select vehapp-filter-select" aria-label="Vehicle Type">
                            <option value="tw" {{ $wh === 'tw' ? 'selected' : '' }}>Two Wheeler</option>
                            <option value="fw" {{ $wh === 'fw' ? 'selected' : '' }}>Four Wheeler</option>
                            <option value="all" {{ $wh === 'all' ? 'selected' : '' }}>Both</option>
                        </select>
                    </form>

                    {{-- Time Period (dual-month range calendar, filters Applied On) --}}
                    <div class="dropdown">
                        <button type="button" class="idcp-toggle dropdown-toggle"
                                id="vehAppTimePeriodToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">calendar_month</i>
                            <span id="vehAppTimePeriodLabel">Time Period</span>
                        </button>
                        <div class="dropdown-menu p-0 idcp-menu">
                            <div class="idcp-cal" id="vehAppCalendar">
                                <div class="idcp-cal-months">
                                    <div class="idcp-cal-month" data-month="0"></div>
                                    <div class="idcp-cal-month" data-month="1"></div>
                                </div>
                                <div class="idcp-cal-footer">
                                    <span class="idcp-cal-range" id="vehAppCalRange">Select a date range</span>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="vehAppClearPeriod">Clear</button>
                                        <button type="button" class="btn btn-sm btn-primary" id="vehAppApplyPeriod">Apply</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Full reset (also clears the server-side Vehicle Type). --}}
                    <a href="{{ route('admin.security.vehicle_pass_approval.index') }}" class="btn programme-dt-btn-reset">Reset Filters</a>

                    {{-- Hidden inputs drive the client-side date filter. --}}
                    <input type="hidden" id="vehAppDateFrom" value="{{ $dateFrom ?? request('date_from', '') }}">
                    <input type="hidden" id="vehAppDateTo" value="{{ $dateTo ?? request('date_to', '') }}">
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="vehAppBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#vehAppColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    {{-- One search slot per table; only the active tab's is visible. --}}
                    <div class="programme-dt-search vehapp-search" data-tab-key="new" data-dt-search-for="vehApprovalTableNew"></div>
                    <div class="programme-dt-search vehapp-search d-none" data-tab-key="for_approval" data-dt-search-for="vehApprovalTableFor"></div>
                    <div class="programme-dt-search vehapp-search d-none" data-tab-key="issued" data-dt-search-for="vehApprovalTableIssued"></div>
                    <div class="programme-dt-search vehapp-search d-none" data-tab-key="rejected" data-dt-search-for="vehApprovalTableRejected"></div>
                </div>
            </div>

            <div class="tab-content">
                <div class="tab-pane {{ $vehActiveTab === 'new' ? 'show active' : '' }}" id="veh-new-panel" role="tabpanel" aria-labelledby="veh-new-tab">
                    @include('admin.security.vehicle_pass_approval._vehicle_pass_table', [
                        'applications' => $newApplications,
                        'tableId' => 'vehApprovalTableNew',
                        'emptyIcon' => 'assignment_turned_in',
                        'emptyText' => 'Nothing is waiting for your action.',
                    ])
                </div>
                <div class="tab-pane {{ $vehActiveTab === 'for_approval' ? 'show active' : '' }}" id="veh-for-panel" role="tabpanel" aria-labelledby="veh-for-tab">
                    @include('admin.security.vehicle_pass_approval._vehicle_pass_table', [
                        'applications' => $processedApplications,
                        'tableId' => 'vehApprovalTableFor',
                        'emptyIcon' => 'hourglass_top',
                        'emptyText' => 'Nothing is pending at another stage.',
                    ])
                </div>
                <div class="tab-pane {{ $vehActiveTab === 'issued' ? 'show active' : '' }}" id="veh-issued-panel" role="tabpanel" aria-labelledby="veh-issued-tab">
                    @include('admin.security.vehicle_pass_approval._vehicle_pass_table', [
                        'applications' => $issuedApplications,
                        'tableId' => 'vehApprovalTableIssued',
                        'emptyIcon' => 'verified',
                        'emptyText' => 'No approved passes in this tab.',
                    ])
                </div>
                <div class="tab-pane {{ $vehActiveTab === 'rejected' ? 'show active' : '' }}" id="veh-rejected-panel" role="tabpanel" aria-labelledby="veh-rejected-tab">
                    @include('admin.security.vehicle_pass_approval._vehicle_pass_table', [
                        'applications' => $rejectedApplications,
                        'tableId' => 'vehApprovalTableRejected',
                        'emptyIcon' => 'cancel',
                        'emptyText' => 'No rejected records found.',
                    ])
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold">Approve Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label for="approve_remarks" class="form-label">Remarks (Optional)</label>
                        <textarea class="form-control" id="approve_remarks" name="veh_approval_remarks" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="forward_status" class="form-label">Status</label>
                        <select class="form-select" id="forward_status" name="forward_status">
                            <option value="">Select Status (Optional)</option>
                            <option value="1">Forwarded</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success px-4">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold">Reject Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body pt-0">
                    <div class="mb-3">
                        <label for="reject_remarks" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_remarks" name="veh_approval_remarks" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger px-4">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="vehAppColumnVisibilityModal" tabindex="-1" aria-labelledby="vehAppColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="vehAppColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="vehAppColumnToggleGrid"></div>
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
    var approveUrlTemplate = "{{ route('admin.security.vehicle_pass_approval.approve', ['id' => '__ID__']) }}";
    var rejectUrlTemplate = "{{ route('admin.security.vehicle_pass_approval.reject', ['id' => '__ID__']) }}";

    $(document).on('click', '.btn-veh-approve', function () {
        var encryptedId = this.getAttribute('data-encrypted-id');
        if (!encryptedId) { return; }
        $('#approveForm').attr('action', approveUrlTemplate.replace('__ID__', encodeURIComponent(encryptedId)));
        $('#approveModal').modal('show');
    });

    $(document).on('click', '.btn-veh-reject', function () {
        var encryptedId = this.getAttribute('data-encrypted-id');
        if (!encryptedId) { return; }
        $('#rejectForm').attr('action', rejectUrlTemplate.replace('__ID__', encodeURIComponent(encryptedId)));
        $('#rejectModal').modal('show');
    });

    // Vehicle Type is a server-side filter — reload with the current tab preserved.
    $('#wheeler').on('change', function () {
        document.getElementById('vehicleFilterForm').submit();
    });

    var TABLES = {
        new:          { id: 'vehApprovalTableNew',      dt: null },
        for_approval: { id: 'vehApprovalTableFor',      dt: null },
        issued:       { id: 'vehApprovalTableIssued',   dt: null },
        rejected:     { id: 'vehApprovalTableRejected', dt: null }
    };
    var TAB_KEYS = ['new', 'for_approval', 'issued', 'rejected'];
    var currentTab = @json($vehActiveTab);

    /* ---- Client-side date-range filter (Applied On), shared by all four tables ---- */
    var TABLE_IDS = TAB_KEYS.map(function (k) { return TABLES[k].id; });
    $.fn.dataTable.ext.search.push(function (settings, searchData, dataIndex) {
        if (TABLE_IDS.indexOf(settings.nTable.id) === -1) { return true; }
        var row = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
        if (!row) { return true; }
        var from = $('#vehAppDateFrom').val();
        var to = $('#vehAppDateTo').val();
        if (from || to) {
            var ts = parseInt(row.getAttribute('data-ts') || '0', 10);
            if (!ts) { return false; }
            if (from && ts < Math.floor(new Date(from + 'T00:00:00').getTime() / 1000)) { return false; }
            if (to && ts > Math.floor(new Date(to + 'T23:59:59').getTime() / 1000)) { return false; }
        }
        return true;
    });

    function initTable(tableId) {
        var $table = $('#' + tableId);
        if (!$table.length || $table.find('tbody tr[data-ts]').length === 0) { return null; }
        return $table.DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            responsive: false,
            order: [[7, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            columnDefs: [
                { targets: [0, 8], orderable: false, searchable: false }
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
    function drawAll() {
        TAB_KEYS.forEach(function (key) { if (TABLES[key].dt) { TABLES[key].dt.draw(); } });
    }

    /* ---- "Time Period" dual-month range calendar ---- */
    function updateTimePeriodLabel() {
        var from = $('#vehAppDateFrom').val();
        var to = $('#vehAppDateTo').val();
        $('#vehAppTimePeriodLabel').text((from || to) ? ((from || '…') + ' → ' + (to || '…')) : 'Time Period');
    }

    var vehAppCal = (function initRangeCalendar() {
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
            $('#vehAppCalendar .idcp-cal-month[data-month="0"]').html(buildMonth(left));
            $('#vehAppCalendar .idcp-cal-month[data-month="1"]').html(buildMonth(right));
            $('#vehAppCalendar .idcp-cal-month[data-month="0"] [data-nav="next"]').css('visibility', 'hidden');
            $('#vehAppCalendar .idcp-cal-month[data-month="1"] [data-nav="prev"]').css('visibility', 'hidden');
            var label = 'Select a date range';
            if (startD && endD) label = ymd(startD) + '  →  ' + ymd(endD);
            else if (startD) label = ymd(startD) + '  → …';
            $('#vehAppCalRange').text(label);
        }

        $('#vehAppCalendar').on('click', '.idcp-cal-nav', function(){
            var dir = $(this).data('nav') === 'prev' ? -1 : 1;
            view = new Date(view.getFullYear(), view.getMonth() + dir, 1);
            render();
        });
        $('#vehAppCalendar').on('click', '.idcp-cal-day', function(){
            var p = String($(this).data('date')).split('-');
            var d = new Date(+p[0], +p[1] - 1, +p[2]);
            if (!startD || (startD && endD)) { startD = d; endD = null; }
            else if (d < startD) { startD = d; }
            else { endD = d; }
            render();
        });
        $('#vehAppApplyPeriod').on('click', function(){
            $('#vehAppDateFrom').val(startD ? ymd(startD) : '');
            $('#vehAppDateTo').val(endD ? ymd(endD) : (startD ? ymd(startD) : ''));
            updateTimePeriodLabel();
            drawAll();
            if (window.bootstrap) { bootstrap.Dropdown.getOrCreateInstance(document.getElementById('vehAppTimePeriodToggle')).hide(); }
        });
        $('#vehAppClearPeriod').on('click', function(){
            startD = null; endD = null; render();
            $('#vehAppDateFrom').val(''); $('#vehAppDateTo').val('');
            updateTimePeriodLabel(); drawAll();
        });

        startD = parseYmd($('#vehAppDateFrom').val());
        endD = parseYmd($('#vehAppDateTo').val());
        if (startD) { view = new Date(startD.getFullYear(), startD.getMonth(), 1); }
        render();
        updateTimePeriodLabel();

        return { reset: function(){ startD = null; endD = null; view = new Date(); view.setDate(1); render(); } };
    })();

    /* ---- Column show / hide (targets the active tab's table) ---- */
    var colKeyPrefix = 'vehApprovalGrid:hiddenColumns:v1:';
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
        var $grid = $('#vehAppColumnToggleGrid');
        $grid.empty();
        if (!dt) { $grid.append('<p class="text-muted mb-0">No data to configure.</p>'); return; }
        var id = TABLES[currentTab].id;
        var hidden = getHidden(id);
        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }
            var inputId = 'vehappcolvis_' + idx;
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
    $('#vehAppColumnVisibilityModal').on('show.bs.modal', buildColumnsModal);

    /* ---- Tab change: track active tab, swap search box, fix widths, sync URL ---- */
    $('#vehicleApprovalTabs .nav-link').on('shown.bs.tab', function () {
        currentTab = this.dataset.tabKey || 'new';
        $('#vehicleActiveTabInput').val(currentTab);
        $('.vehapp-search').each(function () {
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
    $('.vehapp-search').each(function () {
        $(this).toggleClass('d-none', $(this).data('tabKey') !== currentTab);
    });
});
</script>
@endpush
