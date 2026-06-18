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
</style>
@endpush

@section('setup_content')
<div class="container-fluid users-page py-4">
    <x-breadcrum title="Users"></x-breadcrum>

    <x-session_message />

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

                    <button type="button" class="btn users-reset-btn" id="resetUsersFilters">
                        Reset Filters
                    </button>

                    <button type="button"
                        class="btn btn-outline-secondary rounded-1 d-inline-flex align-items-center ms-md-auto"
                        id="btnUsersColumns" data-bs-toggle="modal" data-bs-target="#usersColumnVisibilityModal"
                        title="Show / hide columns" style="border:1px solid #C6C6C6; color:#727272;background-color:#fff;">
                        <span class="ms-1">Columns</span>
                        <i class="material-icons material-symbols-rounded" style="font-size:18px; color:#727272;" aria-hidden="true">view_column</i>
                    </button>

                    <div class="users-search-wrap is-open" id="usersSearchWrap">
                        <label for="usersSearch" class="visually-hidden">Search users</label>
                        <input type="text"
                            name="search"
                            id="usersSearch"
                            class="form-control users-search-input"
                            placeholder="Search..."
                            value="{{ $search }}"
                            autocomplete="off">
                        <button type="submit" class="btn users-search-btn" id="usersSearchBtn" aria-label="Search users">
                            <i class="material-icons material-symbols-rounded" aria-hidden="true">search</i>
                        </button>
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
});
</script>
@endpush
