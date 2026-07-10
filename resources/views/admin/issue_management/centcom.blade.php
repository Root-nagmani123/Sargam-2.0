@extends('admin.layouts.master')

@section('title', 'CENTCOM Complaints - Sargam | Lal Bahadur')

@push('styles')
<style>
/* =====================================================================
   CENTCOM — Issues Assigned To You.  Page-scoped polish.
   Tokens/components come from sargam-app.css (--ds-*, .ds-*).
   Scoped to .issue-centcom-index so nothing leaks to other pages.
   ===================================================================== */

/* --- Inline filter toolbar --------------------------------------- */
.issue-centcom-index .im-filterbar { display: flex; flex-wrap: wrap; align-items: center; gap: var(--ds-space-2); }
.issue-centcom-index .im-filters-label { font-weight: 600; font-size: 0.9rem; color: var(--ds-ink); margin-right: var(--ds-space-1); }
.issue-centcom-index .im-filter-form { display: flex; flex-wrap: wrap; align-items: center; gap: var(--ds-space-2); }
.issue-centcom-index .im-filters-toggle[aria-expanded="true"] { color: #fff; background: var(--bs-primary); border-color: var(--bs-primary); }
@media (max-width: 991.98px) {
    .issue-centcom-index .im-filter-form { flex-basis: 100%; order: 3; margin-top: var(--ds-space-2); }
}

.issue-centcom-index .im-filter-control {
    height: 42px;
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-1);
    padding: 0 0.85rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--ds-ink);
    background: #fff;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-1);
    line-height: 1;
}
.issue-centcom-index select.im-filter-control {
    display: inline-block;
    min-width: 150px;
    max-width: 220px;
    min-height: 42px;
    padding-right: 2rem;
    text-overflow: ellipsis;
}
.issue-centcom-index .im-filter-control:hover { border-color: #c4ccd6; }

.issue-centcom-index .im-btn-apply {
    height: 42px;
    padding: 0 1.25rem;
    font-weight: 600;
    font-size: 0.875rem;
    border-radius: var(--ds-radius-1);
    background: var(--bs-primary);
    color: #fff;
    border: 1px solid var(--bs-primary);
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-1);
}
.issue-centcom-index .im-btn-apply:hover { box-shadow: var(--ds-shadow-sm); }
.issue-centcom-index a.im-btn-clear.im-filter-control { color: var(--bs-danger); border-color: var(--bs-danger); font-weight: 600; }
.issue-centcom-index a.im-btn-clear.im-filter-control:hover { background: var(--bs-danger); color: #fff; }

/* Search box with a leading icon */
.issue-centcom-index .im-search-box { position: relative; display: inline-flex; align-items: center; }
.issue-centcom-index .im-search-ico { position: absolute; left: 12px; font-size: 18px; color: var(--ds-ink-muted); pointer-events: none; }
.issue-centcom-index .im-search-field {
    height: 42px;
    width: 230px;
    padding-left: 38px;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-1);
    font-size: 0.875rem;
}
.issue-centcom-index .im-search-field:focus { border-color: #86b7fe; box-shadow: var(--ds-focus-ring); outline: none; }
@media (max-width: 575.98px) { .issue-centcom-index .im-search-field { width: 100%; } }

/* --- Time Period: dual-month range calendar --------------------- */
.issue-centcom-index .im-filter-control.dropdown-toggle::after { margin-left: auto; }
.im-period-menu { min-width: auto; border-radius: var(--ds-radius-2); }
.im-cal { padding: var(--ds-space-3); }
.im-cal-months { display: flex; gap: var(--ds-space-4); }
@media (max-width: 575.98px) { .im-cal-months { flex-direction: column; gap: var(--ds-space-3); } }
.im-cal-month { width: 236px; }
.im-cal-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--ds-space-2); }
.im-cal-title { font-weight: 600; font-size: 0.875rem; color: var(--ds-ink); }
.im-cal-navs { display: inline-flex; gap: 2px; }
.im-cal-nav {
    border: 0; background: transparent; width: 26px; height: 26px;
    border-radius: var(--ds-radius-1); color: var(--ds-ink-muted);
    display: inline-flex; align-items: center; justify-content: center; font-size: 0.95rem; line-height: 1;
}
.im-cal-nav:hover { background: var(--ds-surface-2); color: var(--ds-ink); }
.im-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.im-cal-dow { text-align: center; font-size: 0.7rem; font-weight: 600; color: var(--ds-ink-muted); padding: 4px 0; }
.im-cal-day {
    aspect-ratio: 1 / 1; border: 0; background: transparent; border-radius: var(--ds-radius-1);
    font-size: 0.8125rem; color: var(--ds-ink); cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
}
.im-cal-day:hover { background: rgba(var(--bs-primary-rgb, 0 74 147), 0.1); }
.im-cal-day.in-range { background: rgba(var(--bs-primary-rgb, 0 74 147), 0.12); border-radius: 0; }
.im-cal-day.is-start, .im-cal-day.is-end { background: var(--bs-primary); color: #fff; }
.im-cal-day.is-start { border-radius: var(--ds-radius-1) 0 0 var(--ds-radius-1); }
.im-cal-day.is-end { border-radius: 0 var(--ds-radius-1) var(--ds-radius-1) 0; }
.im-cal-day.is-start.is-end { border-radius: var(--ds-radius-1); }
.im-cal-footer {
    display: flex; align-items: center; justify-content: space-between; gap: var(--ds-space-2);
    margin-top: var(--ds-space-3); padding-top: var(--ds-space-3); border-top: 1px solid var(--ds-line);
}
.im-cal-range { font-size: 0.8125rem; color: var(--ds-ink-muted); }

/* --- Table with sticky header ------------------------------------ */
.issue-centcom-index #centcomTable { min-width: 100%; margin-bottom: 0; }
.issue-centcom-index #centcomTable thead th {
    position: sticky; top: 0; z-index: 10;
    background: var(--ds-surface-2); border-bottom: 1px solid var(--ds-line);
    font-size: 0.8125rem; text-transform: uppercase; letter-spacing: 0.02em;
    white-space: nowrap; padding: 12px 14px; vertical-align: middle;
    color: var(--ds-ink-muted); font-weight: 600;
}
.issue-centcom-index #centcomTable td { padding: 12px 14px; vertical-align: middle; font-size: 0.9rem; color: var(--ds-ink); }
.issue-centcom-index #centcomTable td.im-desc { white-space: normal; min-width: 200px; max-width: 340px; }

/* --- Status / priority pills ------------------------------------- */
.issue-centcom-index .im-pill { display: inline-block; padding: 0.3rem 0.75rem; border-radius: 50rem; font-size: 0.8125rem; font-weight: 600; line-height: 1.2; white-space: nowrap; }
.issue-centcom-index .im-pill--success   { color: #0f7b3e; background: #e3f5ea; }
.issue-centcom-index .im-pill--danger    { color: #c0392b; background: #fde6e4; }
.issue-centcom-index .im-pill--warning   { color: #9a6a00; background: #fff3d6; }
.issue-centcom-index .im-pill--info      { color: #0d5bbd; background: #e6f0fd; }
.issue-centcom-index .im-pill--secondary { color: #475467; background: #eef1f5; }

/* --- Row actions ------------------------------------------------- */
.issue-centcom-index .im-row-actions { display: inline-flex; align-items: center; gap: var(--ds-space-2); }
.issue-centcom-index .im-act {
    display: inline-flex; align-items: center; justify-content: center;
    width: 34px; height: 34px; border-radius: var(--ds-radius-1);
    text-decoration: none; transition: background-color .15s ease;
}
.issue-centcom-index .im-act i { font-size: 20px; line-height: 1; }
.issue-centcom-index .im-act-view { color: #004a93; }
.issue-centcom-index .im-act-view:hover { background: rgba(0, 74, 147, 0.12); }

/* --- Empty state ------------------------------------------------- */
.issue-centcom-index .im-empty {
    display: inline-flex; flex-direction: column; align-items: center; padding: 3rem;
    background: var(--ds-surface-2); border: 1px solid var(--ds-line); border-radius: var(--ds-radius-2);
}
.issue-centcom-index .im-empty i { font-size: 56px; color: #98a2b3; margin-bottom: 0.75rem; }

/* --- Footer: pagination (left) + count (right) ------------------- */
.issue-centcom-index .im-table-footer { margin-top: var(--ds-space-3); }
.issue-centcom-index .im-count { color: var(--ds-ink-muted); font-size: 0.875rem; white-space: nowrap; }
.issue-centcom-index .pagination { margin: 0; gap: var(--ds-space-1); flex-wrap: wrap; }
.issue-centcom-index .pagination .page-item .page-link {
    margin-left: 0; min-width: 36px; height: 36px;
    display: inline-flex; align-items: center; justify-content: center; padding: 0 0.5rem;
    border: 1px solid var(--ds-line); border-radius: var(--ds-radius-1);
    color: var(--ds-ink); font-size: 0.875rem; background: #fff;
}
.issue-centcom-index .pagination .page-item .page-link:hover { background: var(--ds-surface-2); border-color: #c4ccd6; }
.issue-centcom-index .pagination .page-item.active .page-link { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
.issue-centcom-index .pagination .page-item.disabled .page-link { color: var(--ds-ink-muted); background: var(--ds-surface-2); opacity: 0.6; }
.issue-centcom-index .pagination .page-link:focus { box-shadow: var(--ds-focus-ring); z-index: 2; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid issue-centcom-index">

    {{-- Page header + primary action --}}
    <x-breadcrum title="CENTCOM - Issues Assigned To You">
        <a href="{{ route('admin.issue-management.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Log New Issue</span>
        </a>
    </x-breadcrum>

    <div class="datatables">
        <div class="ds-card">
            <div class="ds-card-body">

                {{-- Filters: single inline toolbar (GET, server-side). --}}
                <div class="im-filterbar mb-3">

                    {{-- On narrow viewports the filter row collapses behind this toggle. --}}
                    <button type="button" class="im-filter-control im-filters-toggle d-lg-none collapsed"
                            data-bs-toggle="collapse" data-bs-target="#centcomFilterForm"
                            aria-expanded="false" aria-controls="centcomFilterForm">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">tune</i>
                        <span>Filters</span>
                    </button>

                    <form method="GET" action="{{ route('admin.issue-management.centcom') }}" class="im-filter-form collapse d-lg-flex" id="centcomFilterForm">
                        <span class="im-filters-label d-none d-lg-inline-flex align-items-center">Filters</span>
                        {{-- Hidden date range consumed by the GET filter, written by the Time Period calendar --}}
                        <input type="hidden" name="date_from" id="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" id="date_to" value="{{ request('date_to') }}">

                        <select name="status" class="form-select im-filter-control" aria-label="Status">
                            <option value="">All Status</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Reported</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>In Progress</option>
                            <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Completed</option>
                            <option value="3" {{ request('status') === '3' ? 'selected' : '' }}>Pending</option>
                            <option value="6" {{ request('status') === '6' ? 'selected' : '' }}>Reopened</option>
                        </select>

                        <select name="category" class="form-select im-filter-control" aria-label="Category">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->pk }}" {{ request('category') == $category->pk ? 'selected' : '' }}>
                                    {{ $category->issue_category }}
                                </option>
                            @endforeach
                        </select>

                        <select name="priority" class="form-select im-filter-control" aria-label="Priority">
                            <option value="">All Priorities</option>
                            @foreach($priorities as $p)
                                <option value="{{ $p->pk }}" {{ request('priority') == $p->pk ? 'selected' : '' }}>{{ $p->priority ?? 'N/A' }}</option>
                            @endforeach
                        </select>

                        <div class="dropdown">
                            <button type="button" class="im-filter-control dropdown-toggle" id="timePeriodToggle"
                                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">calendar_month</i>
                                <span id="timePeriodLabel">Time Period</span>
                            </button>
                            <div class="dropdown-menu p-0 im-period-menu">
                                <div class="im-cal" id="issueCalendar">
                                    <div class="im-cal-months">
                                        <div class="im-cal-month" data-month="0"></div>
                                        <div class="im-cal-month" data-month="1"></div>
                                    </div>
                                    <div class="im-cal-footer">
                                        <span class="im-cal-range" id="issueCalRange">Select a date range</span>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clearPeriod">Clear</button>
                                            <button type="button" class="btn btn-sm btn-primary" id="applyPeriod">Apply</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="im-btn-apply">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">filter_list</i>
                            Apply
                        </button>
                        <a href="{{ route('admin.issue-management.centcom') }}" class="im-btn-clear im-filter-control" title="Clear filters">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">restart_alt</i>
                            Clear
                        </a>

                        {{-- Search (submits with the form) --}}
                        <div class="ms-lg-auto im-search-box">
                            <i class="material-icons material-symbols-rounded im-search-ico" aria-hidden="true">search</i>
                            <input type="text" name="search" class="form-control im-search-field"
                                   placeholder="ID, description, category..." value="{{ request('search') }}"
                                   aria-label="Search issues">
                        </div>
                    </form>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table class="table align-middle" id="centcomTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($issues as $issue)
                            <tr>
                                <td class="fw-semibold">#{{ $issue->pk }}</td>
                                <td>{{ $issue->created_date->format('d M Y H:i') }}</td>
                                <td>{{ $issue->category->issue_category ?? 'N/A' }}</td>
                                <td class="im-desc">{{ Str::limit($issue->description, 80) }}</td>
                                <td>
                                    @php
                                        $pr = $issue->priority->priority ?? 'N/A';
                                        $priorityClass = $pr == 'High' ? 'danger' : ($pr == 'Medium' ? 'warning' : 'info');
                                    @endphp
                                    <span class="im-pill im-pill--{{ $priorityClass }}">{{ $pr }}</span>
                                </td>
                                <td>
                                    @php
                                        $s = (int) $issue->issue_status;
                                        $statusClass = $s == 2 ? 'success' : ($s == 1 ? 'info' : ($s == 6 ? 'warning' : 'secondary'));
                                    @endphp
                                    <span class="im-pill im-pill--{{ $statusClass }}">{{ $issue->status_label }}</span>
                                </td>
                                <td class="text-end">
                                    <div class="im-row-actions justify-content-end">
                                        <a href="{{ route('admin.issue-management.show', $issue->pk) }}" class="im-act im-act-view" title="View Details">
                                            <i class="material-icons material-symbols-rounded" aria-hidden="true">visibility</i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 border-0">
                                    <div class="im-empty">
                                        <i class="material-icons material-symbols-rounded" aria-hidden="true">inbox</i>
                                        <p class="mb-1 fw-semibold text-body-emphasis">No complaints assigned to you</p>
                                        <small class="text-body-secondary">New complaints assigned to you will appear here.</small>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer: pagination (left) + count (right) --}}
                @if($issues->total() > 0)
                <div class="im-table-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="im-pagination">
                        {{ $issues->appends(request()->query())->links() }}
                    </div>
                    <div class="im-count">
                        Showing {{ $issues->firstItem() ?? 0 }} to {{ $issues->lastItem() ?? 0 }} of {{ $issues->total() }} items
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Time Period: dual-month range calendar. Writes the picked range to the hidden
// date_from / date_to inputs and submits the GET filter form on Apply.
$(document).ready(function () {
    var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var DOW = ['Mo','Tu','We','Th','Fr','Sa','Su'];

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function ymd(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
    function parseYmd(s) {
        if (!s) { return null; }
        var p = String(s).split('-');
        if (p.length !== 3) { return null; }
        var d = new Date(+p[0], +p[1] - 1, +p[2]);
        return isNaN(d.getTime()) ? null : d;
    }
    function sameDay(a, b) {
        return a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    }

    var startD = parseYmd($('#date_from').val());
    var endD = parseYmd($('#date_to').val());
    var view = new Date(startD || new Date());
    view.setDate(1);

    function buildMonth(base) {
        var year = base.getFullYear(), month = base.getMonth();
        var startWeekday = (new Date(year, month, 1).getDay() + 6) % 7;
        var daysInMonth = new Date(year, month + 1, 0).getDate();
        var html = '<div class="im-cal-head">' +
            '<span class="im-cal-navs">' +
                '<button type="button" class="im-cal-nav" data-nav="prevYear" aria-label="Previous year">&laquo;</button>' +
                '<button type="button" class="im-cal-nav" data-nav="prev" aria-label="Previous month">&lsaquo;</button>' +
            '</span>' +
            '<span class="im-cal-title">' + MONTHS[month] + ' ' + year + '</span>' +
            '<span class="im-cal-navs">' +
                '<button type="button" class="im-cal-nav" data-nav="next" aria-label="Next month">&rsaquo;</button>' +
                '<button type="button" class="im-cal-nav" data-nav="nextYear" aria-label="Next year">&raquo;</button>' +
            '</span>' +
            '</div><div class="im-cal-grid">';
        DOW.forEach(function (d) { html += '<span class="im-cal-dow">' + d + '</span>'; });
        for (var i = 0; i < startWeekday; i++) { html += '<span></span>'; }
        for (var day = 1; day <= daysInMonth; day++) {
            var d = new Date(year, month, day);
            var cls = 'im-cal-day';
            if (startD && endD && d > startD && d < endD) { cls += ' in-range'; }
            if (sameDay(d, startD)) { cls += ' is-start'; }
            if (sameDay(d, endD)) { cls += ' is-end'; }
            html += '<button type="button" class="' + cls + '" data-date="' + ymd(d) + '">' + day + '</button>';
        }
        return html + '</div>';
    }

    function render() {
        var left = new Date(view.getFullYear(), view.getMonth(), 1);
        var right = new Date(view.getFullYear(), view.getMonth() + 1, 1);
        $('#issueCalendar .im-cal-month[data-month="0"]').html(buildMonth(left));
        $('#issueCalendar .im-cal-month[data-month="1"]').html(buildMonth(right));
        $('#issueCalendar .im-cal-month[data-month="0"] [data-nav="next"]').css('visibility', 'hidden');
        $('#issueCalendar .im-cal-month[data-month="0"] [data-nav="nextYear"]').css('visibility', 'hidden');
        $('#issueCalendar .im-cal-month[data-month="1"] [data-nav="prev"]').css('visibility', 'hidden');
        $('#issueCalendar .im-cal-month[data-month="1"] [data-nav="prevYear"]').css('visibility', 'hidden');
        var label = 'Select a date range';
        if (startD && endD) { label = ymd(startD) + '  →  ' + ymd(endD); }
        else if (startD) { label = ymd(startD) + '  → ...'; }
        $('#issueCalRange').text(label);
    }

    function updateTimePeriodLabel() {
        var from = $('#date_from').val();
        var to = $('#date_to').val();
        if (from || to) { $('#timePeriodLabel').text((from || '…') + ' → ' + (to || '…')); }
        else { $('#timePeriodLabel').text('Time Period'); }
    }

    $('#issueCalendar').on('click', '.im-cal-nav', function () {
        var nav = $(this).data('nav');
        var step = nav === 'prev' ? -1 : (nav === 'next' ? 1 : 0);
        var yearStep = nav === 'prevYear' ? -1 : (nav === 'nextYear' ? 1 : 0);
        view = new Date(view.getFullYear() + yearStep, view.getMonth() + step, 1);
        render();
    });

    $('#issueCalendar').on('click', '.im-cal-day', function () {
        var d = parseYmd($(this).data('date'));
        if (!d) { return; }
        if (!startD || (startD && endD)) { startD = d; endD = null; }
        else if (d < startD) { startD = d; }
        else { endD = d; }
        render();
    });

    $('#applyPeriod').on('click', function () {
        $('#date_from').val(startD ? ymd(startD) : '');
        $('#date_to').val(endD ? ymd(endD) : (startD ? ymd(startD) : ''));
        updateTimePeriodLabel();
        var form = document.getElementById('centcomFilterForm');
        if (form) { form.submit(); }
    });

    $('#clearPeriod').on('click', function () {
        startD = null;
        endD = null;
        $('#date_from').val('');
        $('#date_to').val('');
        updateTimePeriodLabel();
        render();
    });

    updateTimePeriodLabel();
    render();
});
</script>
@endpush
