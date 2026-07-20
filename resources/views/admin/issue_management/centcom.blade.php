@extends('admin.layouts.master')

@section('title', 'CENTCOM Complaints - Sargam | Lal Bahadur')

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

.centcom-filter-select {
    height: 40px; padding: 0 2rem 0 0.875rem; font-size: 0.9375rem;
    color: #344054; background-color: #fff; border: 1px solid #d0d5dd; border-radius: 8px;
}
.centcom-filter-select:focus { border-color: #004a93; box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12); }
.centcom-filter-select--status { width: 150px; }
.centcom-filter-select--category { width: 175px; }
.centcom-filter-select--priority { width: 150px; }
.centcom-search {
    height: 40px; width: 230px; padding: 0 0.875rem; font-size: 0.9375rem;
    border: 1px solid #d0d5dd; border-radius: 8px;
}
.centcom-search:focus { border-color: #004a93; box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12); }

/* Keep the whole toolbar on one line; scroll horizontally rather than wrap. */
.master-toolbar { flex-wrap: nowrap; overflow-x: auto; }
.master-toolbar > * { flex: 0 0 auto; }
.master-toolbar::-webkit-scrollbar { height: 6px; }
.master-toolbar::-webkit-scrollbar-thumb { background: #d0d5dd; border-radius: 3px; }

#centcomTable td.issue-desc { white-space: normal; min-width: 260px; max-width: 460px; }
</style>
@endpush

@section('content')
@php
    $ccDateFrom = request('date_from', '');
    $ccDateTo = request('date_to', '');
@endphp

<div class="container-fluid centcom-index-page py-3">
    <x-breadcrum title="CENTCOM — Issues Assigned To You">
        <a href="{{ route('admin.issue-management.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Log New Issue</span>
        </a>
    </x-breadcrum>
    <x-session_message />

    <div class="card overflow-hidden rounded-1">
        <div class="card-body p-3 p-md-4">

            {{-- Filters submit to the server: this list is far too large (65k+ rows)
                 to render in full and filter in the browser. --}}
            <form method="GET" action="{{ route('admin.issue-management.centcom') }}" id="centcomFilterForm"
                  class="d-flex align-items-center gap-2 mb-4 programme-dt-toolbar master-toolbar">
                <span class="programme-dt-filters-label">Filters</span>

                <select name="status" class="form-select centcom-filter-select centcom-filter-select--status centcom-auto" aria-label="Status">
                    <option value="">All Status</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Reported</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>In Progress</option>
                    <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Completed</option>
                    <option value="3" {{ request('status') === '3' ? 'selected' : '' }}>Pending</option>
                    <option value="6" {{ request('status') === '6' ? 'selected' : '' }}>Reopened</option>
                </select>

                <select name="category" class="form-select centcom-filter-select centcom-filter-select--category centcom-auto" aria-label="Category">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->pk }}" {{ (string) request('category') === (string) $category->pk ? 'selected' : '' }}>
                            {{ $category->issue_category }}
                        </option>
                    @endforeach
                </select>

                <select name="priority" class="form-select centcom-filter-select centcom-filter-select--priority centcom-auto" aria-label="Priority">
                    <option value="">All Priorities</option>
                    @foreach($priorities as $p)
                        <option value="{{ $p->pk }}" {{ (string) request('priority') === (string) $p->pk ? 'selected' : '' }}>{{ $p->priority ?? 'N/A' }}</option>
                    @endforeach
                </select>

                {{-- Time Period (dual-month range calendar) → hidden date_from / date_to --}}
                <div class="dropdown">
                    <button type="button" class="idcp-toggle dropdown-toggle"
                            id="ccTimePeriodToggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">calendar_month</i>
                        <span id="ccTimePeriodLabel">Time Period</span>
                    </button>
                    <div class="dropdown-menu p-0 idcp-menu">
                        <div class="idcp-cal" id="ccCalendar">
                            <div class="idcp-cal-months">
                                <div class="idcp-cal-month" data-month="0"></div>
                                <div class="idcp-cal-month" data-month="1"></div>
                            </div>
                            <div class="idcp-cal-footer">
                                <span class="idcp-cal-range" id="ccCalRange">Select a date range</span>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="ccClearPeriod">Clear</button>
                                    <button type="button" class="btn btn-sm btn-primary" id="ccApplyPeriod">Apply</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="date_from" id="ccDateFrom" value="{{ $ccDateFrom }}">
                <input type="hidden" name="date_to" id="ccDateTo" value="{{ $ccDateTo }}">

                <a href="{{ route('admin.issue-management.centcom') }}" class="btn programme-dt-btn-reset">Reset Filters</a>

                <input type="search" name="search" class="form-control centcom-search ms-auto"
                       placeholder="Search ID, description, category…" value="{{ request('search') }}"
                       aria-label="Search">
                <button type="submit" class="btn programme-dt-btn-columns" title="Apply filters">
                    <i class="bi bi-search" aria-hidden="true"></i>
                </button>
            </form>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap align-middle programme-dt-table" id="centcomTable">
                        <thead>
                            <tr>
                                <th>S. No.</th>
                                <th>Issue ID</th>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($issues as $issue)
                                @php
                                    $statusClass = match ((int) $issue->issue_status) {
                                        2 => 'success',
                                        1 => 'info',
                                        6 => 'warning',
                                        default => 'secondary',
                                    };
                                @endphp
                                <tr>
                                    <td class="fw-medium ps-3">{{ $issues->firstItem() + $loop->index }}</td>
                                    <td><code>{{ $issue->pk }}</code></td>
                                    <td>{{ $issue->created_date ? $issue->created_date->format('d-m-Y H:i') : '--' }}</td>
                                    <td>{{ $issue->category->issue_category ?? '--' }}</td>
                                    <td>{{ $issue->priority->priority ?? '--' }}</td>
                                    <td class="issue-desc">{{ Str::limit($issue->description, 90) }}</td>
                                    <td>
                                        <span class="badge rounded-1 bg-{{ $statusClass }}">{{ $issue->status_label }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex align-items-center justify-content-center programme-action-group" role="group" aria-label="Issue actions">
                                            <a href="{{ route('admin.issue-management.show', $issue->pk) }}"
                                               class="programme-action-btn" title="View Details">
                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 table-empty-state">
                                        <div class="d-inline-flex flex-column align-items-center p-5 bg-body-tertiary rounded-4 border border-body-secondary">
                                            <i class="material-icons material-symbols-rounded mb-3 text-body-tertiary" style="font-size:56px;">inbox</i>
                                            <p class="mb-1 fw-semibold text-body-emphasis">No complaints assigned to you.</p>
                                            <small class="text-body-secondary">Try clearing the filters above.</small>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Server-side pagination — the dataset is far too large for a client-side grid. --}}
                @if($issues->total() > 0)
                    <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="programme-dt-pagination">
                            {{ $issues->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                        <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                            <span class="text-muted small">
                                Showing {{ $issues->firstItem() }}–{{ $issues->lastItem() }} of {{ number_format($issues->total()) }} items
                            </span>
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
$(function () {
    // Changing a select re-runs the server-side filter immediately.
    $('.centcom-auto').on('change', function () {
        document.getElementById('centcomFilterForm').submit();
    });

    /* ---- "Time Period" dual-month range calendar (submits — server-side filter) ---- */
    function updateTimePeriodLabel() {
        var from = $('#ccDateFrom').val();
        var to = $('#ccDateTo').val();
        $('#ccTimePeriodLabel').text((from || to) ? ((from || '…') + ' → ' + (to || '…')) : 'Time Period');
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
            $('#ccCalendar .idcp-cal-month[data-month="0"]').html(buildMonth(left));
            $('#ccCalendar .idcp-cal-month[data-month="1"]').html(buildMonth(right));
            $('#ccCalendar .idcp-cal-month[data-month="0"] [data-nav="next"]').css('visibility', 'hidden');
            $('#ccCalendar .idcp-cal-month[data-month="1"] [data-nav="prev"]').css('visibility', 'hidden');
            var label = 'Select a date range';
            if (startD && endD) label = ymd(startD) + '  →  ' + ymd(endD);
            else if (startD) label = ymd(startD) + '  → …';
            $('#ccCalRange').text(label);
        }

        $('#ccCalendar').on('click', '.idcp-cal-nav', function(){
            var dir = $(this).data('nav') === 'prev' ? -1 : 1;
            view = new Date(view.getFullYear(), view.getMonth() + dir, 1);
            render();
        });
        $('#ccCalendar').on('click', '.idcp-cal-day', function(){
            var p = String($(this).data('date')).split('-');
            var d = new Date(+p[0], +p[1] - 1, +p[2]);
            if (!startD || (startD && endD)) { startD = d; endD = null; }
            else if (d < startD) { startD = d; }
            else { endD = d; }
            render();
        });
        $('#ccApplyPeriod').on('click', function(){
            $('#ccDateFrom').val(startD ? ymd(startD) : '');
            $('#ccDateTo').val(endD ? ymd(endD) : (startD ? ymd(startD) : ''));
            document.getElementById('centcomFilterForm').submit();
        });
        $('#ccClearPeriod').on('click', function(){
            startD = null; endD = null; render();
            $('#ccDateFrom').val(''); $('#ccDateTo').val('');
            document.getElementById('centcomFilterForm').submit();
        });

        startD = parseYmd($('#ccDateFrom').val());
        endD = parseYmd($('#ccDateTo').val());
        if (startD) { view = new Date(startD.getFullYear(), startD.getMonth(), 1); }
        render();
        updateTimePeriodLabel();
    })();
});
</script>
@endpush
