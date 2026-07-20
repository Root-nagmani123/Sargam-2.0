@extends('admin.layouts.master')
@section('title', 'Requested Family ID')

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

/* Status pills keep their own badge colours when active. */
.famapp-status-tabs .programme-status-pill.active .badge.bg-white { background-color: #fff !important; color: #004a93 !important; }
.famapp-status-tabs .programme-status-pill .badge { font-weight: 600; }
.famapp-legend { font-size: 0.8125rem; }
</style>
@endpush

@section('content')
@php
    $familyApprovalReturn = in_array(request('return'), ['approval2', 'approval3'], true) ? request('return') : null;
    $familyMembersQs = ['from' => 'family_approval'];
    if ($familyApprovalReturn) {
        $familyMembersQs['return'] = $familyApprovalReturn;
    }
    $familyMembersQueryString = '?' . http_build_query($familyMembersQs);
    $famActiveTab = $activeTab ?? 'new';
@endphp

<div class="container-fluid family-approval-page py-3">
    <x-breadcrum title="Requested Family ID"></x-breadcrum>
    <x-session_message />

    {{-- Status tabs — above the card --}}
    <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs famapp-status-tabs bg-white mb-3 flex-wrap"
        id="familyApprovalTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link rounded-1 px-3 py-2 fw-semibold programme-status-pill {{ $famActiveTab === 'new' ? 'active' : '' }}"
                    id="fam-new-tab" data-bs-toggle="tab" data-bs-target="#fam-new-panel" role="tab" data-tab-key="new"
                    aria-selected="{{ $famActiveTab === 'new' ? 'true' : 'false' }}"
                    title="Applications waiting for your approve or reject action at this stage.">
                Pending — your action
                <span class="badge rounded-1 bg-white text-primary ms-1">{{ $newFamilyGroups->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link rounded-1 px-3 py-2 fw-semibold programme-status-pill {{ $famActiveTab === 'for_approval' ? 'active' : '' }}"
                    id="fam-for-tab" data-bs-toggle="tab" data-bs-target="#fam-for-panel" role="tab" data-tab-key="for_approval"
                    aria-selected="{{ $famActiveTab === 'for_approval' ? 'true' : 'false' }}"
                    title="Shows only requests where Level 1 is already approved. Waiting for final approval, or view-only until the other officer acts.">
                Pending — other stage
                <span class="badge rounded-1 bg-warning text-dark ms-1">{{ $processedFamilyGroups->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link rounded-1 px-3 py-2 fw-semibold programme-status-pill {{ $famActiveTab === 'issued' ? 'active' : '' }}"
                    id="fam-issued-tab" data-bs-toggle="tab" data-bs-target="#fam-issued-panel" role="tab" data-tab-key="issued"
                    aria-selected="{{ $famActiveTab === 'issued' ? 'true' : 'false' }}"
                    title="Fully approved / issued family ID requests.">
                Approved
                <span class="badge rounded-1 bg-success ms-1">{{ $issuedFamilyGroups->count() }}</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button type="button" class="nav-link rounded-1 px-3 py-2 fw-semibold programme-status-pill {{ $famActiveTab === 'rejected' ? 'active' : '' }}"
                    id="fam-rejected-tab" data-bs-toggle="tab" data-bs-target="#fam-rejected-panel" role="tab" data-tab-key="rejected"
                    aria-selected="{{ $famActiveTab === 'rejected' ? 'true' : 'false' }}"
                    title="Applications rejected at any stage.">
                Rejected
                <span class="badge rounded-1 bg-danger ms-1">{{ $rejectedFamilyGroups->count() }}</span>
            </button>
        </li>
    </ul>

    <div class="card overflow-hidden rounded-1">
        <div class="card-body p-3 p-md-4">

            {{-- Filter toolbar (programme-dt design system) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    {{-- Time Period (dual-month range calendar, filters Applied On) --}}
                    <div class="dropdown">
                        <button type="button" class="idcp-toggle dropdown-toggle"
                                id="famAppTimePeriodToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">calendar_month</i>
                            <span id="famAppTimePeriodLabel">Time Period</span>
                        </button>
                        <div class="dropdown-menu p-0 idcp-menu">
                            <div class="idcp-cal" id="famAppCalendar">
                                <div class="idcp-cal-months">
                                    <div class="idcp-cal-month" data-month="0"></div>
                                    <div class="idcp-cal-month" data-month="1"></div>
                                </div>
                                <div class="idcp-cal-footer">
                                    <span class="idcp-cal-range" id="famAppCalRange">Select a date range</span>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="famAppClearPeriod">Clear</button>
                                        <button type="button" class="btn btn-sm btn-primary" id="famAppApplyPeriod">Apply</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="famAppResetFilters">Reset Filters</button>

                    {{-- Hidden inputs drive the client-side date filter. --}}
                    <input type="hidden" id="famAppDateFrom" value="{{ $dateFrom ?? request('date_from', '') }}">
                    <input type="hidden" id="famAppDateTo" value="{{ $dateTo ?? request('date_to', '') }}">
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="famAppBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#famAppColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    {{-- One search slot per table; only the active tab's is visible. --}}
                    <div class="programme-dt-search famapp-search" data-tab-key="new" data-dt-search-for="famApprovalTableNew"></div>
                    <div class="programme-dt-search famapp-search d-none" data-tab-key="for_approval" data-dt-search-for="famApprovalTableFor"></div>
                    <div class="programme-dt-search famapp-search d-none" data-tab-key="issued" data-dt-search-for="famApprovalTableIssued"></div>
                    <div class="programme-dt-search famapp-search d-none" data-tab-key="rejected" data-dt-search-for="famApprovalTableRejected"></div>
                </div>
            </div>

            <div class="tab-content">
                <div class="tab-pane {{ $famActiveTab === 'new' ? 'show active' : '' }}" id="fam-new-panel" role="tabpanel" aria-labelledby="fam-new-tab">
                    @include('admin.security.family_idcard_approval._family_approval_table', [
                        'groups' => $newFamilyGroups,
                        'familyMembersQueryString' => $familyMembersQueryString,
                        'tableId' => 'famApprovalTableNew',
                        'emptyIcon' => 'assignment_turned_in',
                        'emptyText' => 'Nothing is waiting for your action.',
                    ])
                </div>
                <div class="tab-pane {{ $famActiveTab === 'for_approval' ? 'show active' : '' }}" id="fam-for-panel" role="tabpanel" aria-labelledby="fam-for-tab">
                    @include('admin.security.family_idcard_approval._family_approval_table', [
                        'groups' => $processedFamilyGroups,
                        'familyMembersQueryString' => $familyMembersQueryString,
                        'tableId' => 'famApprovalTableFor',
                        'emptyIcon' => 'hourglass_top',
                        'emptyText' => 'Nothing is pending at another stage.',
                    ])
                </div>
                <div class="tab-pane {{ $famActiveTab === 'issued' ? 'show active' : '' }}" id="fam-issued-panel" role="tabpanel" aria-labelledby="fam-issued-tab">
                    @include('admin.security.family_idcard_approval._family_approval_table', [
                        'groups' => $issuedFamilyGroups,
                        'familyMembersQueryString' => $familyMembersQueryString,
                        'tableId' => 'famApprovalTableIssued',
                        'emptyIcon' => 'verified',
                        'emptyText' => 'No approved records in this tab.',
                    ])
                </div>
                <div class="tab-pane {{ $famActiveTab === 'rejected' ? 'show active' : '' }}" id="fam-rejected-panel" role="tabpanel" aria-labelledby="fam-rejected-tab">
                    @include('admin.security.family_idcard_approval._family_approval_table', [
                        'groups' => $rejectedFamilyGroups,
                        'familyMembersQueryString' => $familyMembersQueryString,
                        'tableId' => 'famApprovalTableRejected',
                        'emptyIcon' => 'cancel',
                        'emptyText' => 'No rejected records found.',
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
                <h5 class="modal-title fw-bold">Reject Family ID Card Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body pt-0">
                    <p class="text-muted mb-2" id="rejectMemberInfo"></p>
                    <div class="mb-3">
                        <label for="reject_remarks" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_remarks" name="approval_remarks" rows="3" required></textarea>
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
<div class="modal fade" id="famAppColumnVisibilityModal" tabindex="-1" aria-labelledby="famAppColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="famAppColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="famAppColumnToggleGrid"></div>
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
function openRejectModal(btn) {
    var encryptedId = btn.getAttribute('data-encrypted-id');
    var memberCount = btn.getAttribute('data-member-count') || 'all';
    var url = "{{ route('admin.security.family_idcard_approval.reject_group', ':id') }}".replace(':id', encryptedId);
    document.getElementById('rejectForm').action = url;
    document.getElementById('reject_remarks').value = '';
    document.getElementById('rejectMemberInfo').textContent = 'This will reject ' + memberCount + ' family member(s) in this application.';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

$(function () {
    var TABLES = {
        new:          { id: 'famApprovalTableNew',      dt: null },
        for_approval: { id: 'famApprovalTableFor',      dt: null },
        issued:       { id: 'famApprovalTableIssued',   dt: null },
        rejected:     { id: 'famApprovalTableRejected', dt: null }
    };
    var TAB_KEYS = ['new', 'for_approval', 'issued', 'rejected'];
    var currentTab = @json($famActiveTab);

    /* ---- Client-side date-range filter (Applied On), shared by all four tables ---- */
    var TABLE_IDS = TAB_KEYS.map(function (k) { return TABLES[k].id; });
    $.fn.dataTable.ext.search.push(function (settings, searchData, dataIndex) {
        if (TABLE_IDS.indexOf(settings.nTable.id) === -1) { return true; }
        var row = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
        if (!row) { return true; }
        var from = $('#famAppDateFrom').val();
        var to = $('#famAppDateTo').val();
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
            order: [[6, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            columnDefs: [
                { targets: [0, 7], orderable: false, searchable: false }
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
        var from = $('#famAppDateFrom').val();
        var to = $('#famAppDateTo').val();
        $('#famAppTimePeriodLabel').text((from || to) ? ((from || '…') + ' → ' + (to || '…')) : 'Time Period');
    }

    var famAppCal = (function initRangeCalendar() {
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
            $('#famAppCalendar .idcp-cal-month[data-month="0"]').html(buildMonth(left));
            $('#famAppCalendar .idcp-cal-month[data-month="1"]').html(buildMonth(right));
            $('#famAppCalendar .idcp-cal-month[data-month="0"] [data-nav="next"]').css('visibility', 'hidden');
            $('#famAppCalendar .idcp-cal-month[data-month="1"] [data-nav="prev"]').css('visibility', 'hidden');
            var label = 'Select a date range';
            if (startD && endD) label = ymd(startD) + '  →  ' + ymd(endD);
            else if (startD) label = ymd(startD) + '  → …';
            $('#famAppCalRange').text(label);
        }

        $('#famAppCalendar').on('click', '.idcp-cal-nav', function(){
            var dir = $(this).data('nav') === 'prev' ? -1 : 1;
            view = new Date(view.getFullYear(), view.getMonth() + dir, 1);
            render();
        });
        $('#famAppCalendar').on('click', '.idcp-cal-day', function(){
            var p = String($(this).data('date')).split('-');
            var d = new Date(+p[0], +p[1] - 1, +p[2]);
            if (!startD || (startD && endD)) { startD = d; endD = null; }
            else if (d < startD) { startD = d; }
            else { endD = d; }
            render();
        });
        $('#famAppApplyPeriod').on('click', function(){
            $('#famAppDateFrom').val(startD ? ymd(startD) : '');
            $('#famAppDateTo').val(endD ? ymd(endD) : (startD ? ymd(startD) : ''));
            updateTimePeriodLabel();
            drawAll();
            if (window.bootstrap) { bootstrap.Dropdown.getOrCreateInstance(document.getElementById('famAppTimePeriodToggle')).hide(); }
        });
        $('#famAppClearPeriod').on('click', function(){
            startD = null; endD = null; render();
            $('#famAppDateFrom').val(''); $('#famAppDateTo').val('');
            updateTimePeriodLabel(); drawAll();
        });

        startD = parseYmd($('#famAppDateFrom').val());
        endD = parseYmd($('#famAppDateTo').val());
        if (startD) { view = new Date(startD.getFullYear(), startD.getMonth(), 1); }
        render();
        updateTimePeriodLabel();

        return { reset: function(){ startD = null; endD = null; view = new Date(); view.setDate(1); render(); } };
    })();

    $('#famAppResetFilters').on('click', function () {
        $('#famAppDateFrom').val('');
        $('#famAppDateTo').val('');
        if (famAppCal) { famAppCal.reset(); }
        updateTimePeriodLabel();
        TAB_KEYS.forEach(function (key) { if (TABLES[key].dt) { TABLES[key].dt.search('').draw(); } });
    });

    /* ---- Column show / hide (targets the active tab's table) ---- */
    var colKeyPrefix = 'famApprovalGrid:hiddenColumns:v1:';
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
        var $grid = $('#famAppColumnToggleGrid');
        $grid.empty();
        if (!dt) { $grid.append('<p class="text-muted mb-0">No data to configure.</p>'); return; }
        var id = TABLES[currentTab].id;
        var hidden = getHidden(id);
        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }
            var inputId = 'famappcolvis_' + idx;
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
    $('#famAppColumnVisibilityModal').on('show.bs.modal', buildColumnsModal);

    /* ---- Tab change: track active tab, swap search box, fix widths, sync URL ---- */
    $('#familyApprovalTabs .nav-link').on('shown.bs.tab', function () {
        currentTab = this.dataset.tabKey || 'new';
        $('.famapp-search').each(function () {
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
    $('.famapp-search').each(function () {
        $(this).toggleClass('d-none', $(this).data('tabKey') !== currentTab);
    });
});
</script>
@endpush
