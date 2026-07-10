@extends('admin.layouts.master')

@section('title', 'All Issues - Sargam | Lal Bahadur')

@section('content')
<style>
/* =====================================================================
   Issue Management — All Issues.  Page-scoped polish.
   Tokens/components come from sargam-app.css (--ds-*, .ds-*).
   Only what Bootstrap utilities can't express lives here, scoped to
   .issue-management-index so nothing leaks to other pages.
   ===================================================================== */

/* --- Top utility buttons (Download) ------------------------------- */
.issue-management-index .im-util-btn {
    height: 42px;
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-2);
    padding: 0 1rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: #004a93;
    background: #fff;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-1);
    transition: border-color .15s ease, box-shadow .15s ease, color .15s ease;
}
.issue-management-index .im-util-btn:hover {
    color: var(--bs-primary);
    border-color: var(--bs-primary);
    box-shadow: var(--ds-shadow-sm);
}
.issue-management-index .im-util-btn.dropdown-toggle::after { margin-left: 0.35rem; }
.issue-management-index .im-download-menu { min-width: 11rem; border-radius: var(--ds-radius-1); }

/* --- Inline filter toolbar --------------------------------------- */
.issue-management-index .im-filterbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--ds-space-2);
}
.issue-management-index .im-filters-label {
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--ds-ink);
    margin-right: var(--ds-space-1);
}
.issue-management-index .im-filter-form {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--ds-space-2);
}
/* Filters toggle chip (mobile) — carries the active-blue look when open */
.issue-management-index .im-filters-toggle[aria-expanded="true"] {
    color: #fff;
    background: var(--bs-primary);
    border-color: var(--bs-primary);
}
/* Below lg the collapsed filter form drops to its own full-width row so it
   never fights the search/columns cluster for horizontal space. */
@media (max-width: 991.98px) {
    .issue-management-index .im-filter-form {
        flex-basis: 100%;
        order: 3;
        margin-top: var(--ds-space-2);
    }
}

/* Shared control footprint so every filter chip lines up exactly */
.issue-management-index .im-filter-control {
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
.issue-management-index select.im-filter-control {
    display: inline-block;
    min-width: 150px;
    max-width: 220px;
    min-height: 42px;
    padding-right: 2rem;
    text-overflow: ellipsis;
}
.issue-management-index .im-filter-control:hover { border-color: #c4ccd6; }

/* --- Time Period: dual-month range calendar --------------------- */
.issue-management-index .im-filter-control.dropdown-toggle::after { margin-left: auto; }
.im-period-menu { min-width: auto; border-radius: var(--ds-radius-2); }
.im-cal { padding: var(--ds-space-3); }
.im-cal-months { display: flex; gap: var(--ds-space-4); }
@media (max-width: 575.98px) { .im-cal-months { flex-direction: column; gap: var(--ds-space-3); } }
.im-cal-month { width: 236px; }
.im-cal-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--ds-space-2);
}
.im-cal-title { font-weight: 600; font-size: 0.875rem; color: var(--ds-ink); }
.im-cal-navs { display: inline-flex; gap: 2px; }
.im-cal-nav {
    border: 0;
    background: transparent;
    width: 26px;
    height: 26px;
    border-radius: var(--ds-radius-1);
    color: var(--ds-ink-muted);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    line-height: 1;
}
.im-cal-nav:hover { background: var(--ds-surface-2); color: var(--ds-ink); }
.im-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; }
.im-cal-dow {
    text-align: center;
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--ds-ink-muted);
    padding: 4px 0;
}
.im-cal-day {
    aspect-ratio: 1 / 1;
    border: 0;
    background: transparent;
    border-radius: var(--ds-radius-1);
    font-size: 0.8125rem;
    color: var(--ds-ink);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.im-cal-day:hover { background: rgba(var(--bs-primary-rgb, 0 74 147), 0.1); }
.im-cal-day.is-muted { color: var(--ds-ink-muted); opacity: 0.45; }
.im-cal-day.in-range { background: rgba(var(--bs-primary-rgb, 0 74 147), 0.12); border-radius: 0; }
.im-cal-day.is-start,
.im-cal-day.is-end { background: var(--bs-primary); color: #fff; }
.im-cal-day.is-start { border-radius: var(--ds-radius-1) 0 0 var(--ds-radius-1); }
.im-cal-day.is-end { border-radius: 0 var(--ds-radius-1) var(--ds-radius-1) 0; }
.im-cal-day.is-start.is-end { border-radius: var(--ds-radius-1); }
.im-cal-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--ds-space-2);
    margin-top: var(--ds-space-3);
    padding-top: var(--ds-space-3);
    border-top: 1px solid var(--ds-line);
}
.im-cal-range { font-size: 0.8125rem; color: var(--ds-ink-muted); }

/* Apply = brand blue chip, Clear = quiet danger chip */
.issue-management-index .im-btn-apply {
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
.issue-management-index .im-btn-apply:hover { box-shadow: var(--ds-shadow-sm); }
.issue-management-index a.im-btn-clear.im-filter-control {
    color: var(--bs-danger);
    border-color: var(--bs-danger);
    font-weight: 600;
}
.issue-management-index a.im-btn-clear.im-filter-control:hover { background: var(--bs-danger); color: #fff; }

.issue-management-index .im-icon-only { padding: 0 0.7rem; }

/* Always-visible client-side search box with a leading icon */
.issue-management-index .im-search-box { position: relative; display: inline-flex; align-items: center; }
.issue-management-index .im-search-ico {
    position: absolute;
    left: 12px;
    font-size: 18px;
    color: var(--ds-ink-muted);
    pointer-events: none;
}
.issue-management-index .im-search-field {
    height: 42px;
    width: 230px;
    padding-left: 38px;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-1);
    font-size: 0.875rem;
}
.issue-management-index .im-search-field:focus { border-color: #86b7fe; box-shadow: var(--ds-focus-ring); outline: none; }
@media (max-width: 575.98px) { .issue-management-index .im-search-field { width: 160px; } }

/* --- Scrollable table with sticky header ------------------------- */
.issue-management-index .im-scroll {
    max-height: 70vh;
    overflow: auto;
    -webkit-overflow-scrolling: touch;
}
.issue-management-index .table-responsive { overflow: visible; }
.issue-management-index #issueManagementTable { min-width: 100%; margin-bottom: 0; }
.issue-management-index #issueManagementTable thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background: var(--ds-surface-2);
    border-bottom: 1px solid var(--ds-line);
    font-size: 0.8125rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    white-space: nowrap;
    padding: 12px 14px;
    vertical-align: middle;
    color: var(--ds-ink-muted);
    font-weight: 600;
}
.issue-management-index #issueManagementTable td {
    padding: 12px 14px;
    vertical-align: middle;
    font-size: 0.9rem;
    color: var(--ds-ink);
}
.issue-management-index #issueManagementTable td.im-desc { white-space: normal; min-width: 200px; max-width: 340px; }

/* --- Status / priority pills (soft) ------------------------------ */
.issue-management-index .im-pill {
    display: inline-block;
    padding: 0.3rem 0.75rem;
    border-radius: 50rem;
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1.2;
    white-space: nowrap;
}
.issue-management-index .im-pill--success   { color: #0f7b3e; background: #e3f5ea; }
.issue-management-index .im-pill--danger    { color: #c0392b; background: #fde6e4; }
.issue-management-index .im-pill--warning   { color: #9a6a00; background: #fff3d6; }
.issue-management-index .im-pill--info      { color: #0d5bbd; background: #e6f0fd; }
.issue-management-index .im-pill--secondary { color: #475467; background: #eef1f5; }

/* --- Row actions: view (indigo) · edit (amber) ------------------- */
.issue-management-index .im-row-actions { display: inline-flex; align-items: center; gap: var(--ds-space-2); }
.issue-management-index .im-act {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: var(--ds-radius-1);
    text-decoration: none;
    transition: background-color .15s ease;
}
.issue-management-index .im-act i { font-size: 20px; line-height: 1; }
.issue-management-index .im-act-view { color: #004a93; }
.issue-management-index .im-act-view:hover { background: rgba(0, 74, 147, 0.12); }
.issue-management-index .im-act-edit { color: #b7791f; }
.issue-management-index .im-act-edit:hover { background: rgba(240, 165, 0, 0.14); }

/* --- Empty state -------------------------------------------------- */
.issue-management-index .im-empty {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    padding: 3rem;
    background: var(--ds-surface-2);
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-2);
}
.issue-management-index .im-empty i { font-size: 56px; color: #98a2b3; margin-bottom: 0.75rem; }

/* Column Visibility modal — grid of bordered checkbox chips */
.issue-management-index .im-col-grid,
#issueColumnsModal .im-col-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: var(--ds-space-3);
}
#issueColumnsModal .im-col-chip {
    display: flex;
    align-items: center;
    gap: var(--ds-space-2);
    margin: 0;
    padding: 0.65rem 0.85rem;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-1);
    background: #fff;
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 500;
    color: var(--ds-ink);
    user-select: none;
    transition: border-color .15s ease, background-color .15s ease, box-shadow .15s ease;
}
#issueColumnsModal .im-col-chip:hover { border-color: #c4ccd6; background: var(--ds-surface-2); }
#issueColumnsModal .im-col-chip.is-checked { border-color: var(--bs-primary); box-shadow: inset 0 0 0 1px var(--bs-primary); }
#issueColumnsModal .im-col-chip .form-check-input { margin: 0; flex-shrink: 0; cursor: pointer; }
@media (max-width: 767.98px) { #issueColumnsModal .im-col-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 479.98px) { #issueColumnsModal .im-col-grid { grid-template-columns: 1fr; } }

/* --- Bottom bar: pagination (left) + "Showing n of N items" ------ */
.issue-management-index .im-table-footer { margin-top: var(--ds-space-3); }
.issue-management-index .im-count { gap: var(--ds-space-2); color: var(--ds-ink-muted); font-size: 0.875rem; }
.issue-management-index .dataTables_length,
.issue-management-index .dataTables_info { margin: 0; padding: 0; color: var(--ds-ink-muted); font-size: 0.875rem; white-space: nowrap; }
.issue-management-index .dataTables_length label { margin: 0; display: inline-flex; align-items: center; gap: var(--ds-space-2); }
.issue-management-index .dataTables_length select.form-select { width: auto; min-width: 76px; display: inline-block; border-radius: var(--ds-radius-1); }
.issue-management-index .dataTables_paginate { margin: 0; }
.issue-management-index .pagination { margin: 0; gap: var(--ds-space-1); flex-wrap: wrap; }
.issue-management-index .pagination .page-item .page-link {
    margin-left: 0;
    min-width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 0.5rem;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-1);
    color: var(--ds-ink);
    font-size: 0.875rem;
    background: #fff;
}
.issue-management-index .pagination .page-item .page-link:hover { background: var(--ds-surface-2); border-color: #c4ccd6; }
.issue-management-index .pagination .page-item.active .page-link { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
.issue-management-index .pagination .page-item.disabled .page-link { color: var(--ds-ink-muted); background: var(--ds-surface-2); opacity: 0.6; }
.issue-management-index .pagination .page-link:focus { box-shadow: var(--ds-focus-ring); z-index: 2; }
</style>

<div class="container-fluid issue-management-index">

    {{-- Page header + primary action --}}
    <x-breadcrum title="All Issues">
        <a href="{{ route('admin.issue-management.create') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add New Issue</span>
        </a>
    </x-breadcrum>

    @php
        $exportParams = array_filter([
            'search'    => request('search'),
            'status'    => request('status'),
            'category'  => request('category'),
            'priority'  => request('priority'),
            'date_from' => request('date_from'),
            'date_to'   => request('date_to'),
            'raised_by' => request('raised_by'),
        ]);
    @endphp

    {{-- Toolbar: title (left) + utility actions (right) --}}
    <div class="d-flex flex-wrap align-items-end justify-content-end gap-3 mb-3">

        <div class="dropdown">
            <button type="button" class="im-util-btn dropdown-toggle border-0" id="issueDownloadBtn"
                    data-bs-toggle="dropdown" aria-expanded="false">
                <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">download</i>
                <span class="d-none d-sm-inline">Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm im-download-menu py-2" aria-labelledby="issueDownloadBtn">
                <li>
                    <a href="{{ route('admin.issue-management.export.excel', $exportParams) }}"
                       class="dropdown-item d-flex align-items-center gap-2 py-2">
                        <i class="material-icons material-symbols-rounded text-success" style="font-size:18px;" aria-hidden="true">table_chart</i>
                        <span>Download Excel</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.issue-management.export.pdf', $exportParams) }}"
                       class="dropdown-item d-flex align-items-center gap-2 py-2">
                        <i class="material-icons material-symbols-rounded text-danger" style="font-size:18px;" aria-hidden="true">picture_as_pdf</i>
                        <span>Download PDF</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="datatables">
        <div class="ds-card">
            <div class="ds-card-body">

                {{-- Filters: single inline toolbar. The GET form filters server-side
                     (unchanged params); the search box + Columns are client-side. --}}
                <div class="im-filterbar mb-3">

                    {{-- On narrow viewports the filter row can't fit inline, so it
                         collapses behind this toggle. On lg+ it's always shown. --}}
                    <button type="button" class="im-filter-control im-filters-toggle d-lg-none collapsed"
                            data-bs-toggle="collapse" data-bs-target="#issueFilterForm"
                            aria-expanded="false" aria-controls="issueFilterForm">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">tune</i>
                        <span>Filters</span>
                    </button>

                    <form method="GET" action="{{ route('admin.issue-management.index') }}" class="im-filter-form collapse d-lg-flex" id="issueFilterForm">
                        <span class="im-filters-label d-none d-lg-inline-flex align-items-center">Filters</span>
                        {{-- Hidden date range consumed by the GET filter, written by the Time Period calendar --}}
                        <input type="hidden" name="date_from" id="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" id="date_to" value="{{ request('date_to') }}">

                        <select name="raised_by" class="form-select im-filter-control" aria-label="Show issues">
                            <option value="all" {{ request('raised_by', 'all') == 'all' ? 'selected' : '' }}>All issues</option>
                            <option value="self" {{ request('raised_by') == 'self' ? 'selected' : '' }}>Raised by me</option>
                        </select>

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
                            @foreach($priorities as $priority)
                                <option value="{{ $priority->pk }}" {{ request('priority') == $priority->pk ? 'selected' : '' }}>
                                    {{ $priority->priority }}
                                </option>
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
                        <a href="{{ route('admin.issue-management.index') }}" class="im-btn-clear im-filter-control" title="Clear filters">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">restart_alt</i>
                            Clear
                        </a>
                    </form>

                    {{-- Right cluster: Columns + client-side search --}}
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <button type="button" class="im-filter-control" id="issueColumnsToggle"
                                data-bs-toggle="modal" data-bs-target="#issueColumnsModal">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">view_column</i>
                            <span class="d-none d-md-inline">Columns</span>
                        </button>
                        <div class="im-search-box">
                            <i class="material-icons material-symbols-rounded im-search-ico" aria-hidden="true">search</i>
                            <input type="text" id="issueSearch" class="form-control im-search-field"
                                   placeholder="Search issues..." aria-label="Search issues">
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="table-responsive">
                    <table class="table align-middle" id="issueManagementTable">
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
                            <tr data-issue-id="{{ $issue->pk }}">
                                <td class="fw-semibold">#{{ $issue->pk }}</td>
                                <td data-order="{{ $issue->created_date->format('Y-m-d H:i:s') }}">{{ $issue->created_date->format('d M Y') }}</td>
                                <td>{{ $issue->category->issue_category ?? '-' }}</td>
                                <td class="im-desc">{{ Str::limit($issue->description, 80) }}</td>
                                <td>
                                    @php
                                        $p = $issue->priority->priority ?? 'N/A';
                                        $priorityClass = $p == 'High' ? 'danger' : ($p == 'Medium' ? 'warning' : 'info');
                                    @endphp
                                    <span class="im-pill im-pill--{{ $priorityClass }}">{{ $p }}</span>
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
                                        <a href="{{ route('admin.issue-management.show', $issue->pk) }}" class="im-act im-act-view" title="View">
                                            <i class="material-icons material-symbols-rounded" aria-hidden="true">visibility</i>
                                        </a>
                                        @if($issue->issue_logger == Auth::user()->user_id || $issue->created_by == Auth::user()->user_id)
                                        <a href="{{ route('admin.issue-management.edit', $issue->pk) }}" class="im-act im-act-edit" title="Edit">
                                            <i class="material-icons material-symbols-rounded" aria-hidden="true">edit</i>
                                        </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 border-0">
                                    <div class="im-empty">
                                        <i class="material-icons material-symbols-rounded" aria-hidden="true">inbox</i>
                                        <p class="mb-1 fw-semibold text-body-emphasis">No issues found.</p>
                                        <small class="text-body-secondary mb-4">Try adjusting your filters or log a new issue.</small>
                                        <a href="{{ route('admin.issue-management.create') }}" class="btn btn-primary rounded-1 px-4">
                                            <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:16px;">add</i>
                                            Log New Issue
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Column Visibility modal --}}
    <div class="modal fade" id="issueColumnsModal" tabindex="-1" aria-labelledby="issueColumnsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="issueColumnsModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="im-col-grid" id="issueColumnsGrid"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    var $table = $('#issueManagementTable');

    // No real rows → keep the clean empty state, skip DataTables entirely
    // (a single colspan row fails DataTables' column-count check).
    if ($table.find('tbody tr[data-issue-id]').length === 0) {
        return;
    }

    if ($.fn.DataTable.isDataTable('#issueManagementTable')) {
        return;
    }

    var table = $('#issueManagementTable').DataTable({
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[1, 'desc']],
        columnDefs: [
            { orderable: false, searchable: false, targets: 6 }
        ],
        // We provide our own search box + Columns control, so DataTables only
        // renders the table (in a scroll wrapper) + a bottom bar holding
        // pagination (left) and "Showing [n] of N items" count (right).
        dom: "<'im-scroll't>" +
             "<'im-table-footer row align-items-center g-2 mt-3'" +
                 "<'col-12 col-md-auto me-md-auto order-2 order-md-1'p>" +
                 "<'col-12 col-md-auto order-1 order-md-2 d-flex justify-content-md-end align-items-center im-count'li>" +
             ">",
        language: {
            lengthMenu: "Showing _MENU_",
            info: "of _TOTAL_ items",
            infoEmpty: "of 0 items",
            infoFiltered: "",
            zeroRecords: "No matching issues found",
            paginate: {
                previous: "<span aria-hidden='true'>&lsaquo;</span>",
                next: "<span aria-hidden='true'>&rsaquo;</span>"
            }
        }
    });

    // Wire the always-visible search box to the client-side table search.
    var searchTimer;
    $('#issueSearch').on('keyup', function () {
        var value = this.value;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            table.search(value).draw();
        }, 250);
    });

    // Column Visibility modal — chips built from the live DataTable.
    var $grid = $('#issueColumnsGrid');
    table.columns().every(function (idx) {
        var title = $.trim($(this.header()).text()) || ('Column ' + (idx + 1));
        var visible = this.visible();
        $grid.append(
            '<label class="im-col-chip' + (visible ? ' is-checked' : '') + '" for="issueColToggle' + idx + '">' +
                '<input class="form-check-input im-col-toggle" type="checkbox" ' +
                       (visible ? 'checked ' : '') +
                       'id="issueColToggle' + idx + '" data-column="' + idx + '">' +
                '<span>' + title + '</span>' +
            '</label>'
        );
    });

    $grid.on('change', '.im-col-toggle', function () {
        var col = table.column($(this).data('column'));
        col.visible(this.checked);
        $(this).closest('.im-col-chip').toggleClass('is-checked', this.checked);
    });
});

// Time Period: dual-month range calendar. Runs independently of the table so
// it works even when there are no rows. Writes the picked range to the hidden
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

    // Seed the selection from any values already applied via the URL filter.
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
        // Keep the two panes locked as consecutive months.
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
        var form = document.getElementById('issueFilterForm');
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
