@extends('admin.layouts.master')

@section('title', 'User Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/users-admin.css') }}?v={{ @filemtime(public_path('css/users-admin.css')) ?: time() }}">
<style>
    #usersTableContainer { transition: opacity .15s ease; }
    #usersTableContainer.users-loading { opacity: .55; pointer-events: none; }
    /* Column Visibility modal items */
    #usersColumnToggleGrid .colvis-item { cursor: pointer; transition: border-color .15s ease, background-color .15s ease; }
    #usersColumnToggleGrid .colvis-item:hover { border-color: #004a93 !important; background-color: rgba(0,74,147,.04); }
    #usersColumnToggleGrid .colvis-item .form-check-input { cursor: pointer; flex-shrink: 0; }

    /* ===== Reference-matched polish (presentation only) ===== */
    /* Print / Download utility buttons */
    .users-page .users-util-btn {
        height: 44px; display: inline-flex; align-items: center; gap: 8px;
        padding: 0 18px; font-weight: 600; font-size: 0.9rem; color: #004a93;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .users-page .users-util-btn:hover { border-color: #004a93; box-shadow: 0 1px 3px rgba(16,24,40,.08); }

    /* Filter toolbar */
    .users-page .users-filters-label { font-weight: 600; color: #1f2937; font-size: 0.9rem; }
    .users-page .users-filter-select {
        height: 42px; min-width: 150px; border: 1px solid #d0d5dd; border-radius: 8px;
        font-size: 0.875rem; color: #1f2937;
    }
    .users-page .users-reset-btn {
        height: 42px; border: 1px solid var(--bs-danger); color: var(--bs-danger);
        border-radius: 8px; font-weight: 600; font-size: 0.875rem; padding: 0 16px; background: #fff;
    }
    .users-page .users-reset-btn:hover { background: var(--bs-danger); color: #fff; }
    .users-page .users-tool-btn {
        height: 42px; display: inline-flex; align-items: center; gap: 8px; padding: 0 14px;
        font-size: 0.875rem; font-weight: 600; color: #344054; background: #fff;
        border: 1px solid #d0d5dd; border-radius: 8px;
    }
    .users-page .users-tool-btn:hover { border-color: #b6c0cc; }
    .users-page .users-search-icon-btn {
        height: 42px; width: 42px; display: inline-flex; align-items: center; justify-content: center;
        border: 1px solid #d0d5dd; border-radius: 8px; background: #f3f4f6; color: #475467;
    }
    .users-page .users-search-icon-btn:hover { background: #e9eaee; }
    .users-page .users-search-box { position: relative; display: inline-flex; align-items: center; }
    .users-page .users-search-box .users-search-input {
        height: 42px; width: 240px; max-width: 100%; padding-left: 38px; border: 1px solid #d0d5dd; border-radius: 8px; font-size: 0.875rem;
    }
    .users-page .users-search-box .users-search-input:focus { border-color: #86b7fe; box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.18); outline: none; }
    @media (max-width: 575.98px) { .users-page .users-search-box .users-search-input { width: 160px; } }
    .users-page .users-search-box .users-search-ico {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #667085; font-size: 18px; pointer-events: none;
    }

    /* Table */
    .users-page .users-table thead th {
        background: #f3f4f6; color: #667085; text-transform: none;
        font-weight: 600; font-size: 0.8125rem; padding: 14px; border-bottom: 1px solid #e5e7eb; white-space: nowrap;
    }
    .users-page .users-table tbody td { padding: 14px; vertical-align: middle; font-size: 0.9rem; color: #1f2937; border-bottom: 1px solid #f0f1f3; }
    .users-page .users-usertype { color: #475467; }
    .users-page .users-assign-link {
        display: inline-flex; align-items: center; gap: 6px; color: var(--bs-primary);
        font-weight: 600; font-size: 0.9rem; text-decoration: none;
    }
    .users-page .users-assign-link:hover { text-decoration: underline; }

    /* Footer pagination */
    .users-page .pagination { gap: 4px; margin: 0; flex-wrap: wrap; }
    .users-page .pagination .page-link {
        border: 1px solid #e2e8f0; border-radius: 8px; min-width: 36px; height: 36px;
        display: inline-flex; align-items: center; justify-content: center; color: #1f2937; margin-left: 0;
    }
    .users-page .pagination .page-item.active .page-link { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
    .users-page .pagination .page-item.disabled .page-link { color: #98a2b3; background: #f8fafc; }
    .users-page .users-per-page-select { width: auto; min-width: 72px; border-radius: 6px; }
</style>
@endpush

@section('setup_content')
<div class="container-fluid users-page py-4">
    <x-breadcrum title="Users"></x-breadcrum>

    <x-session_message />

    {{-- Print / Download --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        <button type="button" id="usersPrintBtn" class="users-util-btn">
            <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">print</i>
            <span>Print</span>
        </button>
        <button type="button" id="usersDownloadBtn" class="users-util-btn">
            <i class="material-icons material-symbols-rounded" style="font-size:20px;" aria-hidden="true">download</i>
            <span>Download</span>
        </button>
    </div>

    <div class="card users-dt-card shadow-sm rounded-3 overflow-hidden border-0">
        <div class="card-body p-3 p-md-4">

            {{-- Filters (reference layout) --}}
            <form method="GET" id="usersFilterForm" class="mb-3 mb-md-4">
                <input type="hidden" name="per_page" value="{{ $perPage }}" id="usersFilterPerPage">

                <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3">
                    <span class="users-filters-label me-1">Filters</span>

                    <label for="User_type" class="visually-hidden">User type</label>
                    <select name="User_type" id="User_type" class="form-select users-filter-select" aria-label="Filter by user type">
                        <option value="">User Type</option>
                        <option value="S" {{ $user_type === 'S' ? 'selected' : '' }}>Student</option>
                        <option value="E" {{ $user_type === 'E' ? 'selected' : '' }}>Employee</option>
                    </select>

                    <button type="button" class="users-reset-btn" id="resetUsersFilters">
                        Reset Filters
                    </button>

                    <div class="ms-md-auto d-flex align-items-center gap-2">
                        <button type="button" class="users-tool-btn"
                            id="btnUsersColumns" data-bs-toggle="modal" data-bs-target="#usersColumnVisibilityModal"
                            title="Show / hide columns">
                            <span>Columns</span>
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">view_column</i>
                        </button>

                        <div class="users-search-box" id="usersSearchWrap">
                            <i class="material-icons material-symbols-rounded users-search-ico" aria-hidden="true">search</i>
                            <label for="usersSearch" class="visually-hidden">Search users</label>
                            <input type="text"
                                name="search"
                                id="usersSearch"
                                class="form-control users-search-input"
                                placeholder="Search..."
                                value="{{ $search }}"
                                autocomplete="off">
                        </div>
                    </div>
                </div>
            </form>

            {{-- Table + pagination (AJAX-swapped; partial reused for live search) --}}
            <div id="usersTableContainer">
                @include('admin.user_management.users._table')
            </div>

        </div>
    </div>

    <!-- Column Visibility Modal -->
    <div class="modal fade" id="usersColumnVisibilityModal" tabindex="-1" aria-labelledby="usersColumnVisibilityLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="usersColumnVisibilityLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="usersColumnToggleGrid"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var filterForm = document.getElementById('usersFilterForm');
    var searchInput = document.getElementById('usersSearch');
    var userTypeSelect = document.getElementById('User_type');
    var resetBtn = document.getElementById('resetUsersFilters');
    var perPageHidden = document.getElementById('usersFilterPerPage');
    var container = document.getElementById('usersTableContainer');

    if (!filterForm || !container) {
        return;
    }

    var baseUrl = "{{ route('admin.users.index') }}";
    var ajaxToken = 0;

    // Build a URL from the current filter state (optionally a specific page).
    function buildUrl(pageUrl) {
        if (pageUrl) return pageUrl;
        var params = new URLSearchParams();
        if (searchInput && searchInput.value.trim() !== '') params.set('search', searchInput.value.trim());
        if (userTypeSelect && userTypeSelect.value) params.set('User_type', userTypeSelect.value);
        if (perPageHidden && perPageHidden.value) params.set('per_page', perPageHidden.value);
        var qs = params.toString();
        return baseUrl + (qs ? ('?' + qs) : '');
    }

    // Fetch the table partial and swap it in — no page reload.
    function loadUsers(pageUrl) {
        var url = buildUrl(pageUrl);
        var token = ++ajaxToken;
        container.classList.add('users-loading');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
            .then(function (r) { return r.text(); })
            .then(function (html) {
                if (token !== ajaxToken) return; // ignore stale (out-of-order) responses
                container.innerHTML = html;
                container.classList.remove('users-loading');
                applyColumnVisibility();
                try { window.history.replaceState({}, '', url); } catch (e) {}
            })
            .catch(function () {
                container.classList.remove('users-loading');
            });
    }

    if (searchInput) {
        // Live search as you type (debounced) — and immediately on Enter.
        searchInput.addEventListener('input', function () {
            clearTimeout(searchInput._debounce);
            searchInput._debounce = setTimeout(loadUsers, 350);
        });
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchInput._debounce);
                loadUsers();
            }
        });
    }

    if (userTypeSelect) {
        userTypeSelect.addEventListener('change', function () { loadUsers(); });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (searchInput) searchInput.value = '';
            if (userTypeSelect) userTypeSelect.value = '';
            if (perPageHidden) perPageHidden.value = '10';
            loadUsers();
        });
    }

    // Delegated handlers — the table/pagination is replaced on every fetch.
    container.addEventListener('change', function (e) {
        if (e.target && e.target.id === 'usersPerPageFooter') {
            if (perPageHidden) perPageHidden.value = e.target.value;
            loadUsers();
        }
    });
    container.addEventListener('click', function (e) {
        var link = e.target.closest('.users-pagination-links a');
        if (link && link.getAttribute('href')) {
            e.preventDefault();
            loadUsers(link.getAttribute('href'));
        }
    });

    /* ---------------- Column hide / show ---------------- */
    var columnStorageKey = 'usersGrid:hiddenColumns:v1';
    var hiddenCols = [];
    try {
        var raw = localStorage.getItem(columnStorageKey);
        if (raw) hiddenCols = JSON.parse(raw) || [];
    } catch (e) { hiddenCols = []; }
    if (!Array.isArray(hiddenCols)) hiddenCols = [];

    function persistColumns() {
        try { localStorage.setItem(columnStorageKey, JSON.stringify(hiddenCols)); } catch (e) {}
    }

    // Show/hide each column's header + body cells by index (no DataTables here).
    function applyColumnVisibility() {
        var table = container.querySelector('#zero_config_table');
        if (!table) return;
        var headers = table.querySelectorAll('thead th');
        headers.forEach(function (th, i) {
            th.style.display = hiddenCols.indexOf(i) !== -1 ? 'none' : '';
        });
        table.querySelectorAll('tbody tr').forEach(function (tr) {
            if (tr.children.length <= 1) return; // skip the colspanned empty-state row
            Array.prototype.forEach.call(tr.children, function (td, i) {
                td.style.display = hiddenCols.indexOf(i) !== -1 ? 'none' : '';
            });
        });
    }

    // Build the modal checkboxes once from the table headers.
    function buildColumnsModal() {
        var table = container.querySelector('#zero_config_table');
        var grid = document.getElementById('usersColumnToggleGrid');
        if (!table || !grid) return;
        grid.innerHTML = '';
        table.querySelectorAll('thead th').forEach(function (th, i) {
            var header = (th.textContent || '').trim();
            if (!header) return;
            var id = 'usercolvis_' + i;
            var cell = document.createElement('div');
            cell.className = 'col-12 col-sm-6 col-md-4';
            var label = document.createElement('label');
            label.className = 'colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100';
            label.setAttribute('for', id);
            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'form-check-input m-0';
            cb.id = id;
            cb.checked = hiddenCols.indexOf(i) === -1;
            cb.addEventListener('change', function () {
                var pos = hiddenCols.indexOf(i);
                if (cb.checked) { if (pos !== -1) hiddenCols.splice(pos, 1); }
                else { if (pos === -1) hiddenCols.push(i); }
                persistColumns();
                applyColumnVisibility();
            });
            var span = document.createElement('span');
            span.textContent = header;
            label.appendChild(cb);
            label.appendChild(span);
            cell.appendChild(label);
            grid.appendChild(cell);
        });
    }

    buildColumnsModal();
    applyColumnVisibility();

    /* ---------------- Print (current table) ---------------- */
    var printBtn = document.getElementById('usersPrintBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            var table = container.querySelector('#zero_config_table');
            if (!table) return;
            var clone = table.cloneNode(true);
            clone.querySelectorAll('tr').forEach(function (tr) {
                if (tr.children.length > 1) tr.removeChild(tr.children[tr.children.length - 1]); // drop Action
            });
            var w = window.open('', '_blank');
            if (!w) return;
            w.document.write('<!DOCTYPE html><html><head><title>Users</title>' +
                '<style>body{font-family:Arial,sans-serif;margin:20px}table{width:100%;border-collapse:collapse}' +
                'th,td{border:1px solid #ccc;padding:8px;text-align:left;font-size:12px}th{background:#004a93;color:#fff}h2{color:#004a93}</style>' +
                '</head><body><h2>Users</h2>' + clone.outerHTML + '</body></html>');
            w.document.close();
            w.onload = function () { w.print(); };
        });
    }

    /* ---------------- Download (current table → CSV) ---------------- */
    var downloadBtn = document.getElementById('usersDownloadBtn');
    if (downloadBtn) {
        downloadBtn.addEventListener('click', function () {
            var table = container.querySelector('#zero_config_table');
            if (!table) return;
            var rows = [];
            table.querySelectorAll('tr').forEach(function (tr) {
                if (tr.children.length <= 1) return; // skip empty-state row
                var cells = [];
                for (var i = 0; i < tr.children.length - 1; i++) { // exclude Action column
                    var txt = (tr.children[i].innerText || '').replace(/\s+/g, ' ').trim().replace(/"/g, '""');
                    cells.push('"' + txt + '"');
                }
                rows.push(cells.join(','));
            });
            if (!rows.length) return;
            var blob = new Blob(['﻿' + rows.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'users_' + new Date().toISOString().slice(0, 10) + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    }
});
</script>
@endpush
