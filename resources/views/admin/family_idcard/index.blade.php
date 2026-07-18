@extends('admin.layouts.master')
@section('title', 'Request For Family Id Card - Sargam | Lal Bahadur Shastri')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<style>
/* --- "Time Period" dual-month range calendar --- */
.idcp-toggle { min-width: 150px; }
.idcp-toggle.dropdown-toggle::after { margin-left: auto; }
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

/* --- Active / Archive tab pills --- */
.family-idcard-tabs .nav-link { border-radius: 8px; color: #6c757d; font-weight: 500; padding: 0.5rem 1.1rem; }
.family-idcard-tabs .nav-link.active { background-color: var(--bs-primary, #004a93); color: #fff; }
.family-idcard-tabs .nav-link:hover:not(.active) { color: var(--bs-primary, #004a93); }
</style>
@endpush

@section('content')
<div class="container-fluid family-idcard-index-page">
    <x-breadcrum title="Request For Family Id Card">
        <a href="{{ route('admin.family_idcard.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>New Family ID Card</span>
        </a>
    </x-breadcrum>
    <x-session_message />

    <div class="card overflow-hidden rounded-1">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: filters (left) · print/download/columns (right) --}}
            <div class="d-flex flex-column flex-xl-row align-items-xl-end justify-content-between gap-3 mb-3">

                {{-- Filters (applied instantly in-browser — no page reload) --}}
                <div class="d-flex flex-wrap align-items-end gap-2">
                    <div>
                        <label class="form-label small text-muted mb-1">Time Period</label>
                        <div class="dropdown">
                            <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1 idcp-toggle dropdown-toggle"
                                    id="familyTimePeriodToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">calendar_month</i>
                                <span id="familyTimePeriodLabel">Time Period</span>
                            </button>
                            <div class="dropdown-menu p-0 idcp-menu">
                                <div class="idcp-cal" id="familyCalendar">
                                    <div class="idcp-cal-months">
                                        <div class="idcp-cal-month" data-month="0"></div>
                                        <div class="idcp-cal-month" data-month="1"></div>
                                    </div>
                                    <div class="idcp-cal-footer">
                                        <span class="idcp-cal-range" id="familyCalRange">Select a date range</span>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="familyClearPeriod">Clear</button>
                                            <button type="button" class="btn btn-sm btn-primary" id="familyApplyPeriod">Apply</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        {{-- Hidden inputs drive the client-side date filter + export URLs. --}}
                        <input type="hidden" id="familyDateFrom" value="{{ $dateFrom ?? request('date_from', '') }}">
                        <input type="hidden" id="familyDateTo" value="{{ $dateTo ?? request('date_to', '') }}">
                    </div>
                    <div>
                        <button type="button" id="familyClearFilters" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
                            <i class="bi bi-x-circle" aria-hidden="true"></i> Clear
                        </button>
                    </div>
                </div>

                {{-- Print · Download · Columns --}}
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <button type="button" class="btn programme-dt-btn-columns" id="familyPrintBtn" title="Print">
                        <i class="bi bi-printer" aria-hidden="true"></i> <span>Print</span>
                    </button>
                    <div class="dropdown">
                        <button type="button" class="btn programme-dt-btn-columns dropdown-toggle" id="familyDownloadBtn"
                            data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                            <i class="bi bi-download" aria-hidden="true"></i> <span>Download</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-1 py-2" aria-labelledby="familyDownloadBtn">
                            <li>
                                <a href="#" class="dropdown-item d-flex align-items-center gap-2 py-2 export-link"
                                   data-format="pdf"
                                   data-base-url="{{ route('admin.family_idcard.export', ['format' => 'pdf']) }}">
                                    <i class="bi bi-file-earmark-pdf text-danger" aria-hidden="true"></i> Download PDF
                                </a>
                            </li>
                            <li>
                                <a href="#" class="dropdown-item d-flex align-items-center gap-2 py-2 export-link"
                                   data-format="xlsx"
                                   data-base-url="{{ route('admin.family_idcard.export', ['format' => 'xlsx']) }}">
                                    <i class="bi bi-file-earmark-spreadsheet text-success" aria-hidden="true"></i> Download Excel
                                </a>
                            </li>
                        </ul>
                    </div>
                    <button type="button" class="btn programme-dt-btn-columns" id="familyBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#familyColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            {{-- Active / Archive tabs --}}
            <ul class="nav nav-pills family-idcard-tabs gap-2 mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-panel" type="button" role="tab" aria-controls="active-panel" aria-selected="true">
                        Active
                        @if($activeRequests->count() > 0)
                            <span class="badge bg-white text-primary ms-1">{{ $activeRequests->count() }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="archive-tab" data-bs-toggle="tab" data-bs-target="#archive-panel" type="button" role="tab" aria-controls="archive-panel" aria-selected="false">
                        Archive
                        @if($archivedRequests->count() > 0)
                            <span class="badge bg-secondary ms-1">{{ $archivedRequests->count() }}</span>
                        @endif
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                {{-- Active Tab --}}
                <div class="tab-pane show active" id="active-panel" role="tabpanel" aria-labelledby="active-tab">
                    <div class="programme-dt-panel">
                        <div class="d-flex justify-content-end mb-2">
                            <div class="programme-dt-search" data-dt-search-for="familyActiveTable"></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table text-nowrap align-middle programme-dt-table" id="familyActiveTable">
                                <thead>
                                    <tr>
                                        <th>S.No.</th>
                                        <th>Request Date</th>
                                        <th>Employee ID</th>
                                        <th>Employee Name</th>
                                        <th>Designation</th>
                                        <th>Department</th>
                                        <th>No of Members</th>
                                        <th>ID Type</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($activeRequests as $req)
                                        @php $ts = $req->created_at ? \Carbon\Carbon::parse($req->created_at)->timestamp : 0; @endphp
                                        <tr data-ts="{{ $ts }}">
                                            <td class="fw-medium ps-3">{{ $loop->iteration }}</td>
                                            <td data-order="{{ $ts }}">{{ $req->created_at ? \Carbon\Carbon::parse($req->created_at)->format('d-m-Y') : '--' }}</td>
                                            <td>{{ $req->employee_id ?? '--' }}</td>
                                            <td>{{ $req->employee_name ?? '--' }}</td>
                                            <td>{{ $req->designation ?? '--' }}</td>
                                            <td>{{ $req->section ?? '--' }}</td>
                                            <td><a href="{{ route('admin.family_idcard.members', $req->first_id) }}" class="text-primary fw-medium">{{ $req->member_count }}</a></td>
                                            <td>{{ $req->card_type ?? 'Family Card' }}</td>
                                            <td class="text-center">
                                                <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">
                                                    <a href="{{ route('admin.family_idcard.members', $req->first_id) }}" class="programme-action-btn" title="View members"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                                    @if($req->can_delete ?? true)
                                                        <a href="{{ route('admin.family_idcard.edit', $req->first_id) }}" class="programme-action-btn" title="Edit"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                                        <form action="{{ route('admin.family_idcard.destroy', $req->first_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to archive this request?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="programme-action-btn programme-action-btn--danger" title="Archive"><i class="bi bi-trash3" aria-hidden="true"></i></button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-5 table-empty-state">
                                                <div class="d-inline-flex flex-column align-items-center p-5 bg-body-tertiary rounded-4 border border-body-secondary">
                                                    <i class="material-icons material-symbols-rounded mb-3 text-body-tertiary" style="font-size:56px;">inbox</i>
                                                    <p class="mb-1 fw-semibold text-body-emphasis">No family ID card requests found.</p>
                                                    <a href="{{ route('admin.family_idcard.create') }}" class="btn btn-primary rounded-1 px-4 py-2 mt-2">Add Request</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="familyActiveTable"></div>
                    </div>
                </div>

                {{-- Archive Tab --}}
                <div class="tab-pane fade" id="archive-panel" role="tabpanel" aria-labelledby="archive-tab">
                    <div class="programme-dt-panel">
                        <div class="d-flex justify-content-end mb-2">
                            <div class="programme-dt-search" data-dt-search-for="familyArchiveTable"></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table text-nowrap align-middle programme-dt-table" id="familyArchiveTable">
                                <thead>
                                    <tr>
                                        <th>S.No.</th>
                                        <th>Request Date</th>
                                        <th>Employee ID</th>
                                        <th>Employee Name</th>
                                        <th>Designation</th>
                                        <th>Department</th>
                                        <th>No of Members</th>
                                        <th>ID Type</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($archivedRequests as $req)
                                        @php $ts = $req->created_at ? \Carbon\Carbon::parse($req->created_at)->timestamp : 0; @endphp
                                        <tr data-ts="{{ $ts }}">
                                            <td class="fw-medium ps-3">{{ $loop->iteration }}</td>
                                            <td data-order="{{ $ts }}">{{ $req->created_at ? \Carbon\Carbon::parse($req->created_at)->format('d-m-Y') : '--' }}</td>
                                            <td>{{ $req->employee_id ?? '--' }}</td>
                                            <td>{{ $req->employee_name ?? '--' }}</td>
                                            <td>{{ $req->designation ?? '--' }}</td>
                                            <td>{{ $req->section ?? '--' }}</td>
                                            <td><a href="{{ route('admin.family_idcard.members', $req->first_id) }}" class="text-primary fw-medium">{{ $req->member_count }}</a></td>
                                            <td>{{ $req->card_type ?? 'Family Card' }}</td>
                                            <td class="text-center">
                                                <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Row actions">
                                                    <a href="{{ route('admin.family_idcard.members', $req->first_id) }}" class="programme-action-btn" title="View members"><i class="bi bi-eye" aria-hidden="true"></i></a>
                                                    @if(($req->id_status ?? 1) === 3)
                                                        <form action="{{ route('admin.family_idcard.restore', $req->first_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Restore this request?');">
                                                            @csrf
                                                            <button type="submit" class="programme-action-btn" title="Restore"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i></button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center py-5 table-empty-state">
                                                <div class="d-inline-flex flex-column align-items-center p-5 bg-body-tertiary rounded-4 border border-body-secondary">
                                                    <i class="material-icons material-symbols-rounded mb-3 text-body-tertiary" style="font-size:56px;">inventory_2</i>
                                                    <p class="mb-1 fw-semibold text-body-emphasis">No archived requests.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="familyArchiveTable"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="familyColumnVisibilityModal" tabindex="-1" aria-labelledby="familyColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="familyColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="familyColumnToggleGrid"></div>
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
    var TABLES = {
        active: { id: 'familyActiveTable', dt: null },
        archive: { id: 'familyArchiveTable', dt: null }
    };
    var currentTab = 'active';

    /* ---- Client-side date-range filter (shared by both tables) ---- */
    $.fn.dataTable.ext.search.push(function (settings, searchData, dataIndex) {
        var id = settings.nTable.id;
        if (id !== TABLES.active.id && id !== TABLES.archive.id) { return true; }
        var row = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
        if (!row) { return true; }
        var from = $('#familyDateFrom').val();
        var to = $('#familyDateTo').val();
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

        var dt = $table.DataTable({
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            autoWidth: false,
            order: [[1, 'desc']],
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

        // Client-side Print (data columns only) for this table.
        if (typeof $.fn.dataTable.Buttons !== 'undefined') {
            new $.fn.dataTable.Buttons(dt, {
                buttons: [{
                    extend: 'print',
                    className: 'family-btn-print',
                    title: 'Family ID Card Requests',
                    exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7] }
                }]
            });
        }
        return dt;
    }

    TABLES.active.dt = initTable(TABLES.active.id);
    TABLES.archive.dt = initTable(TABLES.archive.id);

    function currentDt() { return TABLES[currentTab] ? TABLES[currentTab].dt : null; }
    function drawAll() {
        if (TABLES.active.dt) { TABLES.active.dt.draw(); }
        if (TABLES.archive.dt) { TABLES.archive.dt.draw(); }
    }

    /* ---- "Time Period" dual-month range calendar ---- */
    function updateTimePeriodLabel() {
        var from = $('#familyDateFrom').val();
        var to = $('#familyDateTo').val();
        $('#familyTimePeriodLabel').text((from || to) ? ((from || '…') + ' → ' + (to || '…')) : 'Time Period');
    }

    var familyCal = (function initRangeCalendar() {
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
            $('#familyCalendar .idcp-cal-month[data-month="0"]').html(buildMonth(left));
            $('#familyCalendar .idcp-cal-month[data-month="1"]').html(buildMonth(right));
            $('#familyCalendar .idcp-cal-month[data-month="0"] [data-nav="next"]').css('visibility', 'hidden');
            $('#familyCalendar .idcp-cal-month[data-month="1"] [data-nav="prev"]').css('visibility', 'hidden');
            var label = 'Select a date range';
            if (startD && endD) label = ymd(startD) + '  →  ' + ymd(endD);
            else if (startD) label = ymd(startD) + '  → …';
            $('#familyCalRange').text(label);
        }

        $('#familyCalendar').on('click', '.idcp-cal-nav', function(){
            var dir = $(this).data('nav') === 'prev' ? -1 : 1;
            view = new Date(view.getFullYear(), view.getMonth() + dir, 1);
            render();
        });
        $('#familyCalendar').on('click', '.idcp-cal-day', function(){
            var p = String($(this).data('date')).split('-');
            var d = new Date(+p[0], +p[1] - 1, +p[2]);
            if (!startD || (startD && endD)) { startD = d; endD = null; }
            else if (d < startD) { startD = d; }
            else { endD = d; }
            render();
        });
        $('#familyApplyPeriod').on('click', function(){
            $('#familyDateFrom').val(startD ? ymd(startD) : '');
            $('#familyDateTo').val(endD ? ymd(endD) : (startD ? ymd(startD) : ''));
            updateTimePeriodLabel();
            drawAll();
            if (window.bootstrap) { bootstrap.Dropdown.getOrCreateInstance(document.getElementById('familyTimePeriodToggle')).hide(); }
        });
        $('#familyClearPeriod').on('click', function(){
            startD = null; endD = null; render();
            $('#familyDateFrom').val(''); $('#familyDateTo').val('');
            updateTimePeriodLabel(); drawAll();
        });

        // Seed from any request date range so the picker shows selected on load.
        startD = parseYmd($('#familyDateFrom').val());
        endD = parseYmd($('#familyDateTo').val());
        if (startD) { view = new Date(startD.getFullYear(), startD.getMonth(), 1); }
        render();
        updateTimePeriodLabel();

        return { reset: function(){ startD = null; endD = null; view = new Date(); view.setDate(1); render(); } };
    })();

    $('#familyClearFilters').on('click', function () {
        $('#familyDateFrom').val('');
        $('#familyDateTo').val('');
        if (familyCal) { familyCal.reset(); }
        updateTimePeriodLabel();
        if (TABLES.active.dt) { TABLES.active.dt.search('').draw(); }
        if (TABLES.archive.dt) { TABLES.archive.dt.search('').draw(); }
    });

    /* ---- Print (active tab) ---- */
    $('#familyPrintBtn').on('click', function () {
        var dt = currentDt();
        if (dt) { dt.button('.family-btn-print').trigger(); }
    });

    /* ---- Download (PDF / Excel) with the live filters + active tab ---- */
    $('.export-link').on('click', function (e) {
        e.preventDefault();
        var baseUrl = this.dataset.baseUrl || '';
        if (!baseUrl) { return; }
        try {
            var url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('tab', currentTab);
            var fromVal = $('#familyDateFrom').val();
            var toVal = $('#familyDateTo').val();
            var dt = currentDt();
            var searchVal = dt ? dt.search() : '';
            if (fromVal) { url.searchParams.set('date_from', fromVal); } else { url.searchParams.delete('date_from'); }
            if (toVal) { url.searchParams.set('date_to', toVal); } else { url.searchParams.delete('date_to'); }
            if (searchVal) { url.searchParams.set('search', searchVal); } else { url.searchParams.delete('search'); }
            window.location.href = url.toString();
        } catch (err) {
            var separator = baseUrl.indexOf('?') === -1 ? '?' : '&';
            window.location.href = baseUrl + separator + 'tab=' + encodeURIComponent(currentTab);
        }
    });

    /* ---- Column show / hide (targets the active tab's table) ---- */
    var colKeyPrefix = 'familyIdcardGrid:hiddenColumns:v1:';
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
        var $grid = $('#familyColumnToggleGrid');
        $grid.empty();
        if (!dt) { $grid.append('<p class="text-muted mb-0">No data to configure.</p>'); return; }
        var id = TABLES[currentTab].id;
        var hidden = getHidden(id);
        dt.columns().every(function () {
            var idx = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }
            var inputId = 'familycolvis_' + idx;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>').attr('for', inputId);
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

    applyStoredColumns(TABLES.active.dt, TABLES.active.id);
    applyStoredColumns(TABLES.archive.dt, TABLES.archive.id);
    $('#familyColumnVisibilityModal').on('show.bs.modal', buildColumnsModal);

    /* ---- Tab change: track active tab + fix column widths of the shown table ---- */
    $('#active-tab, #archive-tab').on('shown.bs.tab', function () {
        currentTab = (this.id === 'archive-tab') ? 'archive' : 'active';
        var dt = currentDt();
        if (dt) { dt.columns.adjust(); }
    });
});
</script>
@endpush
