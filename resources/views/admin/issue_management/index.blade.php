@extends('admin.layouts.master')

@section('title', 'All Issues')

@push('styles')
{{-- Shared Centcom index chrome — the same file the Centcom queue and the master
     grids use, so the whole module stays consistent. See docs/new-design-index-page.md. --}}
<link rel="stylesheet"
      href="{{ asset('css/issue-management-admin.css') }}?v={{ @filemtime(public_path('css/issue-management-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // Query string carried across sort / paging / per-page changes.
    $baseQuery = [
        'search' => $search,
        'raised_by' => $raisedBy === 'self' ? 'self' : null,
        'status' => request('status'),
        'category' => request('category'),
        'priority' => request('priority'),
        'date_from' => request('date_from'),
        'date_to' => request('date_to'),
        'per_page' => $perPage,
        'sort' => $sortKey,
        'dir' => $sortDir,
    ];

    $sortUrl = function (string $key) use ($baseQuery, $sortKey, $sortDir) {
        $currentAsc = $sortKey === $key && $sortDir === 'asc';

        return request()->fullUrlWithQuery(array_merge($baseQuery, [
            'sort' => $key,
            'dir' => $currentAsc ? 'desc' : 'asc',
            'page' => 1,
        ]));
    };

    $exportQuery = array_filter(array_diff_key($baseQuery, ['per_page' => 1]), fn ($v) => $v !== null && $v !== '');

    $stateClass = [
        0 => 'ic-state--reported',
        1 => 'ic-state--in-progress',
        2 => 'ic-state--completed',
        3 => 'ic-state--pending',
        6 => 'ic-state--reopened',
    ];
@endphp
<div class="container-fluid ic-page">
    <x-breadcrum title="All Requests" :showBack="false">
        <a href="{{ route('admin.issue-management.create') }}"
           class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Log New Issue</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    {{-- Scope tabs (left) + exports (right) — above the card, per §1 --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 ic-secondary-actions">
        <nav class="ic-tabs" aria-label="Issue scope">
            <a href="{{ route('admin.issue-management.index') }}"
               class="ic-tab {{ $raisedBy === 'all' ? 'is-active' : '' }}"
               @if($raisedBy === 'all') aria-current="page" @endif>All Requests</a>
            <a href="{{ route('admin.issue-management.index', ['raised_by' => 'self']) }}"
               class="ic-tab {{ $raisedBy === 'self' ? 'is-active' : '' }}"
               @if($raisedBy === 'self') aria-current="page" @endif>Raised By You</a>
            <a href="{{ route('admin.issue-management.centcom') }}" class="ic-tab">Assign to you</a>
        </nav>

        <div class="d-flex flex-wrap gap-2">
            {{-- Dropdown, not a plain button: this page already shipped Excel and PDF
                 exports and dropping them would lose working features (§1). --}}
            <div class="dropdown">
                <button type="button" class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                        data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('admin.issue-management.export.list', array_merge(['format' => 'csv'], $exportQuery)) }}">
                        <i class="bi bi-filetype-csv me-2" aria-hidden="true"></i>CSV</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.issue-management.export.excel', $exportQuery) }}">
                        <i class="bi bi-file-earmark-excel me-2" aria-hidden="true"></i>Excel</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.issue-management.export.pdf', $exportQuery) }}">
                        <i class="bi bi-filetype-pdf me-2" aria-hidden="true"></i>PDF</a></li>
                </ul>
            </div>
            <a href="{{ route('admin.issue-management.export.list', array_merge(['format' => 'print'], $exportQuery)) }}"
               target="_blank" rel="noopener" class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </a>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: filters left, columns + search right (§2) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 ic-toolbar ic-toolbar--compact">
                <form method="GET" action="{{ route('admin.issue-management.index') }}"
                      class="d-flex flex-wrap align-items-center gap-2" id="imFilterForm">
                    <input type="hidden" name="search" value="{{ $search }}">
                    @if($raisedBy === 'self')
                        <input type="hidden" name="raised_by" value="self">
                    @endif
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <input type="hidden" name="sort" value="{{ $sortKey }}">
                    <input type="hidden" name="dir" value="{{ $sortDir }}">

                    <span class="programme-dt-filters-label">Filter</span>

                    <div class="programme-dt-filter-select">
                        <select name="status" class="form-select im-auto-filter" aria-label="Filter by status">
                            <option value="">Status</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Reported</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>In Progress</option>
                            <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>Completed</option>
                            <option value="3" {{ request('status') === '3' ? 'selected' : '' }}>Pending</option>
                            <option value="6" {{ request('status') === '6' ? 'selected' : '' }}>Reopened</option>
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select name="category" class="form-select im-auto-filter" aria-label="Filter by category">
                            <option value="">Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->pk }}" {{ (string) request('category') === (string) $category->pk ? 'selected' : '' }}>
                                    {{ $category->issue_category }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select name="priority" class="form-select im-auto-filter" aria-label="Filter by priority">
                            <option value="">Priority</option>
                            @foreach($priorities as $p)
                                <option value="{{ $p->pk }}" {{ (string) request('priority') === (string) $p->pk ? 'selected' : '' }}>
                                    {{ $p->priority ?? 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ic-filter-date">
                        <input type="date" name="date_from" class="form-control im-auto-filter"
                               value="{{ request('date_from') }}" aria-label="From date">
                    </div>
                    <span class="ic-filter-sep">–</span>
                    <div class="ic-filter-date">
                        <input type="date" name="date_to" class="form-control im-auto-filter"
                               value="{{ request('date_to') }}" aria-label="To date">
                    </div>

                    @if($hasFilters)
                        <a href="{{ route('admin.issue-management.index', $raisedBy === 'self' ? ['raised_by' => 'self'] : []) }}"
                           class="btn programme-dt-btn-reset">Remove Filter</a>
                    @endif
                </form>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="imBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#imColumnVisibilityModal"
                            title="Show / hide columns"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <button type="button" class="btn programme-dt-btn-columns" id="imSearchToggle"
                            aria-label="Search issues" title="Search"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>

                    <form method="GET" action="{{ route('admin.issue-management.index') }}"
                          class="ic-search-wrap {{ filled($search) ? '' : 'd-none' }}" id="imSearchWrap">
                        @if($raisedBy === 'self')
                            <input type="hidden" name="raised_by" value="self">
                        @endif
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        <input type="hidden" name="category" value="{{ request('category') }}">
                        <input type="hidden" name="priority" value="{{ request('priority') }}">
                        <input type="hidden" name="date_from" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" value="{{ request('date_to') }}">
                        <input type="hidden" name="per_page" value="{{ $perPage }}">
                        <input type="hidden" name="sort" value="{{ $sortKey }}">
                        <input type="hidden" name="dir" value="{{ $sortDir }}">
                        <input type="search" class="ic-search-input" id="imSearchInput" name="search"
                               value="{{ $search }}" placeholder="ID, description, category…" autocomplete="off"
                               aria-label="Search issues">
                    </form>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="issueManagementTable" data-sargam-dt-ui="false"
                           class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">
                                    <a class="ic-sort {{ $sortKey === 'id' ? 'is-active' : '' }}" href="{{ $sortUrl('id') }}">
                                        ID No.
                                        <i class="bi {{ $sortKey === 'id' && $sortDir === 'desc' ? 'bi-caret-down-fill' : 'bi-caret-up-fill' }}" aria-hidden="true"></i>
                                    </a>
                                </th>
                                <th scope="col">
                                    <a class="ic-sort {{ $sortKey === 'date' ? 'is-active' : '' }}" href="{{ $sortUrl('date') }}">
                                        Date &amp; Time
                                        <i class="bi {{ $sortKey === 'date' && $sortDir === 'desc' ? 'bi-caret-down-fill' : 'bi-caret-up-fill' }}" aria-hidden="true"></i>
                                    </a>
                                </th>
                                {{-- Category / Complainant / Nodal / Priority live in joined tables and
                                     issue_log_management has no secondary indexes — sorting on them
                                     measured 110-470ms, so they get no caret. --}}
                                <th scope="col">Category</th>
                                <th scope="col">
                                    <a class="ic-sort {{ $sortKey === 'description' ? 'is-active' : '' }}" href="{{ $sortUrl('description') }}">
                                        Description
                                        <i class="bi {{ $sortKey === 'description' && $sortDir === 'desc' ? 'bi-caret-down-fill' : 'bi-caret-up-fill' }}" aria-hidden="true"></i>
                                    </a>
                                </th>
                                <th scope="col">Complainant</th>
                                <th scope="col">Nodal Employee</th>
                                <th scope="col">Priority</th>
                                <th scope="col">
                                    <a class="ic-sort {{ $sortKey === 'status' ? 'is-active' : '' }}" href="{{ $sortUrl('status') }}">
                                        Status
                                        <i class="bi {{ $sortKey === 'status' && $sortDir === 'desc' ? 'bi-caret-down-fill' : 'bi-caret-up-fill' }}" aria-hidden="true"></i>
                                    </a>
                                </th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($issues as $issue)
                                <tr>
                                    <td>{{ $issue->pk }}</td>
                                    <td>{{ optional($issue->created_date)->format('d-m-Y H:i') ?: '—' }}</td>
                                    <td>{{ $issue->category->issue_category ?? '—' }}</td>
                                    <td class="ic-col-wrap">{{ $issue->description ?: '—' }}</td>
                                    <td>{{ $issue->creator->name ?? '—' }}</td>
                                    <td>{{ $issue->nodal_officer->name ?? '—' }}</td>
                                    <td>{{ $issue->priority->priority ?? '—' }}</td>
                                    <td data-order="{{ (int) $issue->issue_status }}">
                                        <span class="ic-state {{ $stateClass[(int) $issue->issue_status] ?? 'ic-state--reported' }}">
                                            {{ $issue->status_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="ic-act-group" role="group" aria-label="Row actions">
                                            <a href="{{ route('admin.issue-management.show', $issue->pk) }}"
                                               class="ic-act ic-act--view" aria-label="View issue {{ $issue->pk }}">
                                                <span class="ic-act__icon"><i class="bi bi-eye" aria-hidden="true"></i></span>
                                                <span class="ic-act__label">View</span>
                                            </a>

                                            {{-- Edit stays available to the raiser/logger while the issue is open —
                                                 the old grid offered it and dropping it would lose the feature. --}}
                                            @if($issue->issue_logger == Auth::user()->user_id || $issue->created_by == Auth::user()->user_id)
                                                @if((int) $issue->issue_status !== 2)
                                                    <a href="{{ route('admin.issue-management.edit', $issue->pk) }}"
                                                       class="ic-act ic-act--edit" aria-label="Edit issue {{ $issue->pk }}">
                                                        <span class="ic-act__icon"><i class="bi bi-pencil-square" aria-hidden="true"></i></span>
                                                        <span class="ic-act__label">Edit</span>
                                                    </a>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="ic-empty">
                                        <i class="bi bi-inbox d-block mb-2" aria-hidden="true"></i>
                                        <h6 class="fw-semibold mb-1">No Issues Found</h6>
                                        <p class="mb-0 small">
                                            {{ $hasFilters ? 'No issue matches the current filters.' : 'Nothing here yet — log a new issue to get started.' }}
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Footer variant B — Laravel paginates this grid (§4).
                     The old page rendered no paginator at all, so only the first
                     INDEX_PER_PAGE rows were ever reachable. --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                    <div class="programme-dt-pagination">
                        {{ $issues->links('vendor.pagination.custom') }}
                    </div>
                    <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <div class="dataTables_length">
                            <label class="mb-0">Showing
                                <select id="imPerPage" class="form-select form-select-sm" aria-label="Rows per page">
                                    @foreach($perPageOptions as $option)
                                        <option value="{{ $option }}" {{ (int) $perPage === (int) $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                        <div class="dataTables_info">of {{ number_format($issues->total()) }} items</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="imColumnVisibilityModal" tabindex="-1" aria-labelledby="imColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="imColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="issueMgmtColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    'use strict';

    /* ── Toolbar: every filter control submits its form ──────────────────── */
    $('.im-auto-filter').on('change', function () {
        $('#imFilterForm').trigger('submit');
    });

    /* ── Footer: rows-per-page ───────────────────────────────────────────── */
    $('#imPerPage').on('change', function () {
        var url = new URL(window.location.href);
        url.searchParams.set('per_page', this.value);
        url.searchParams.set('page', '1');
        window.location.href = url.toString();
    });

    /* ── Toolbar: search toggle (server-side ?search=) ───────────────────── */
    $('#imSearchToggle').on('click', function () {
        var $wrap = $('#imSearchWrap');
        $wrap.toggleClass('d-none');
        if (!$wrap.hasClass('d-none')) {
            $('#imSearchInput').trigger('focus');
        }
    });

    // Clearing the box (the native "x" or emptying it) returns to the unfiltered list.
    $('#imSearchInput').on('search', function () {
        if (this.value === '') {
            $('#imSearchWrap').trigger('submit');
        }
    });

    /* ── Column visibility (plain table → toggle by column index) ────────── */
    var COL_KEY = 'issueMgmtGrid:hiddenColumns:v1';
    var $table = $('#issueManagementTable');

    function getHiddenCols() {
        try {
            var parsed = JSON.parse(localStorage.getItem(COL_KEY) || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) { return []; }
    }

    function persistHiddenCols(cols) {
        try { localStorage.setItem(COL_KEY, JSON.stringify(cols)); } catch (e) { /* noop */ }
    }

    function applyColumnVisibility(index, visible) {
        var nth = index + 1;
        $table.find('thead th:nth-child(' + nth + '), tbody td:nth-child(' + nth + ')')
              .toggle(visible);
    }

    function buildColumnToggles() {
        var $grid = $('#issueMgmtColumnToggleGrid');
        if (!$grid.length) { return; }
        var hidden = getHiddenCols();
        $grid.empty();

        $table.find('thead th').each(function (index) {
            var title = $(this).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var visible = hidden.indexOf(index) === -1;
            applyColumnVisibility(index, visible);

            var inputId = 'imcolvis_' + index;
            var $checkbox = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', visible);

            $checkbox.on('change', function () {
                var cols = getHiddenCols();
                var pos = cols.indexOf(index);
                if (this.checked) {
                    if (pos !== -1) { cols.splice(pos, 1); }
                } else if (pos === -1) {
                    cols.push(index);
                }
                persistHiddenCols(cols);
                applyColumnVisibility(index, this.checked);
            });

            $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId)
                    .append($checkbox)
                    .append($('<span></span>').text(title))
            ).appendTo($grid);
        });
    }

    buildColumnToggles();
});
</script>
@endpush
