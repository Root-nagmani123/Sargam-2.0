@extends('admin.layouts.master')

@section('title', 'User Management - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Users"></x-breadcrum>

    <x-session_message />

    <div class="card users-dt-card shadow-sm rounded-3 overflow-hidden border-0">
        <div class="card-body p-3 p-md-4">

            @php
                // Columns that can be shown/hidden and exported, keyed by the
                // class suffix used on the matching <th>/<td> cells.
                $columnDefs = [
                    'username' => 'Username',
                    'name'     => 'Name',
                    'email'    => 'Email',
                    'mobile'   => 'Mobile',
                    'usertype' => 'User Type',
                    'roles'    => 'Roles',
                ];
            @endphp

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
                        <option value="F" {{ $user_type === 'F' ? 'selected' : '' }}>Faculty</option>
                        <option value="A" {{ $user_type === 'A' ? 'selected' : '' }}>Admin</option>
                    </select>

                    <button type="button" class="btn users-reset-btn" id="resetUsersFilters">
                        Reset Filters
                    </button>

                    <div class="users-search-wrap ms-md-auto {{ $search ? 'is-open' : '' }}" id="usersSearchWrap">
                        <label for="usersSearch" class="visually-hidden">Search users</label>
                        <input type="text"
                            name="search"
                            id="usersSearch"
                            class="form-control users-search-input"
                            placeholder="Search..."
                            value="{{ $search }}"
                            autocomplete="off">
                        <button type="submit" class="btn users-search-btn" id="usersSearchBtn" aria-label="Search users">
                            <i class="bi bi-search" aria-hidden="true"></i>
                        </button>
                    </div>

                    {{-- Toolbar: column visibility, export, print --}}
                    <div class="d-flex align-items-center gap-2"
                        data-export-base="{{ url('admin/users/export') }}">

                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-inline-flex align-items-center gap-1"
                                type="button" id="usersColumnsBtn" data-bs-toggle="dropdown"
                                data-bs-auto-close="outside" aria-expanded="false" title="Manage columns">
                                <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                                <span class="d-none d-md-inline">Columns</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end p-2" aria-labelledby="usersColumnsBtn">
                                <li class="dropdown-header px-2 pt-0 pb-1 text-uppercase small">Show / Hide</li>
                                @foreach($columnDefs as $key => $label)
                                <li>
                                    <label class="dropdown-item d-flex align-items-center gap-2 mb-0">
                                        <input type="checkbox" class="form-check-input m-0 js-col-toggle" data-col="{{ $key }}" checked>
                                        <span>{{ $label }}</span>
                                    </label>
                                </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-inline-flex align-items-center gap-1"
                                type="button" id="usersExportBtn" data-bs-toggle="dropdown" aria-expanded="false" title="Export">
                                <i class="bi bi-download" aria-hidden="true"></i>
                                <span class="d-none d-md-inline">Export</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="usersExportBtn">
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 js-users-export" data-format="xlsx" href="#">
                                        <i class="bi bi-file-earmark-excel text-success" aria-hidden="true"></i> Excel (.xlsx)
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 js-users-export" data-format="csv" href="#">
                                        <i class="bi bi-filetype-csv text-primary" aria-hidden="true"></i> CSV (.csv)
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center gap-2 js-users-export" data-format="pdf" href="#">
                                        <i class="bi bi-file-earmark-pdf text-danger" aria-hidden="true"></i> PDF (.pdf)
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1"
                            id="usersPrintBtn" title="Print">
                            <i class="bi bi-printer" aria-hidden="true"></i>
                            <span class="d-none d-md-inline">Print</span>
                        </button>
                    </div>
                </div>

                <hr>

                <div class="table-responsive datatables">
                    <table class="table" id="zero_config_table">
                        <thead>
                            <tr>
                                <th scope="col" class="col-sno">S. No.</th>
                                <th scope="col" class="col-username">Username</th>
                                <th scope="col" class="col-name">Name</th>
                                <th scope="col" class="col-email">Email</th>
                                <th scope="col" class="col-mobile">Mobile</th>
                                <th scope="col" class="col-usertype">User Type</th>
                                <th scope="col" class="col-roles">Roles</th>
                                <th scope="col" class="text-center col-action">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($users as $index => $user)
                            @php
                                $typeBadgeClasses = [
                                    'S' => 'bg-primary',
                                    'E' => 'bg-success',
                                    'F' => 'bg-info',
                                    'A' => 'bg-danger',
                                ];
                                $typeBadge = $typeBadgeClasses[$user->User_type] ?? 'bg-secondary';
                                $typeLabel = \App\Http\Controllers\Admin\UserController::userTypeLabel($user->User_type);
                            @endphp
                            <tr>
                                <td class="col-sno">{{ $users->firstItem() + $index }}</td>
                                <td class="col-username">{{ $user->user_name }}</td>
                                <td class="col-name">{{ trim($user->first_name . ' ' . $user->last_name) }}</td>
                                <td class="col-email">{{ $user->email_id }}</td>
                                <td class="col-mobile">{{ $user->mobile_no ?: '—' }}</td>
                                <td class="col-usertype">
                                    <span class="badge rounded-pill {{ $typeBadge }}">{{ $typeLabel }}</span>
                                </td>
                                <td class="col-roles">
                                    @if(!empty($user->roles))
                                        <span class="badge rounded-pill users-role-badge">{{ $user->roles }}</span>
                                    @else
                                        <span class="badge rounded-pill users-role-badge users-role-badge--empty">No Role</span>
                                    @endif
                                </td>
                                <td class="text-center col-action">
                                    <a href="{{ route('admin.users.assignRole', encrypt($user->pk)) }}"
                                        class="btn btn-sm btn-primary">
                                        Assign Role
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="users-empty-state text-center">
                                    <i class="bi bi-people display-4 text-secondary opacity-50 d-block mb-3" aria-hidden="true"></i>
                                    <h5 class="fw-semibold text-dark mb-1">No Users Found</h5>
                                    <p class="text-secondary mb-0">Try adjusting your search or filters.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    {{ $users->links() }}
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
    var searchWrap = document.getElementById('usersSearchWrap');
    var searchInput = document.getElementById('usersSearch');
    var searchBtn = document.getElementById('usersSearchBtn');
    var userTypeSelect = document.getElementById('User_type');
    var resetBtn = document.getElementById('resetUsersFilters');
    var perPageHidden = document.getElementById('usersFilterPerPage');
    var perPageFooter = document.getElementById('usersPerPageFooter');

    if (!filterForm) {
        return;
    }

    if (searchBtn && searchWrap) {
        searchBtn.addEventListener('click', function (e) {
            if (!searchWrap.classList.contains('is-open')) {
                e.preventDefault();
                searchWrap.classList.add('is-open');
                if (searchInput) {
                    searchInput.focus();
                }
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                filterForm.submit();
            }
        });
    }

    if (userTypeSelect) {
        userTypeSelect.addEventListener('change', function () {
            filterForm.submit();
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (searchInput) {
                searchInput.value = '';
            }
            if (userTypeSelect) {
                userTypeSelect.value = '';
            }
            if (perPageHidden) {
                perPageHidden.value = '10';
            }
            if (searchWrap) {
                searchWrap.classList.remove('is-open');
            }
            filterForm.submit();
        });
    }

    if (perPageFooter && perPageHidden) {
        perPageFooter.addEventListener('change', function () {
            perPageHidden.value = this.value;
            filterForm.submit();
        });
    }

    // ---------------------------------------------------------------
    // Column show / hide (immediate, persisted to localStorage)
    // ---------------------------------------------------------------
    var COLS_STORAGE_KEY = 'usersGridHiddenColumns';
    var colToggles = document.querySelectorAll('.js-col-toggle');

    function readHiddenCols() {
        try {
            return JSON.parse(localStorage.getItem(COLS_STORAGE_KEY)) || [];
        } catch (e) {
            return [];
        }
    }

    function applyColumn(col, visible) {
        document.querySelectorAll('.col-' + col).forEach(function (cell) {
            cell.classList.toggle('d-none', !visible);
        });
    }

    function persistHiddenCols() {
        var hidden = [];
        colToggles.forEach(function (cb) {
            if (!cb.checked) {
                hidden.push(cb.dataset.col);
            }
        });
        try {
            localStorage.setItem(COLS_STORAGE_KEY, JSON.stringify(hidden));
        } catch (e) {}
    }

    if (colToggles.length) {
        var hiddenCols = readHiddenCols();
        colToggles.forEach(function (cb) {
            var col = cb.dataset.col;
            if (hiddenCols.indexOf(col) !== -1) {
                cb.checked = false;
                applyColumn(col, false);
            }
            cb.addEventListener('change', function () {
                applyColumn(col, cb.checked);
                persistHiddenCols();
            });
        });
    }

    function getVisibleColumns() {
        var visible = [];
        colToggles.forEach(function (cb) {
            if (cb.checked) {
                visible.push(cb.dataset.col);
            }
        });
        return visible;
    }

    // ---------------------------------------------------------------
    // Export (xlsx / csv / pdf) — respects active filters, search and
    // the currently visible columns.
    // ---------------------------------------------------------------
    var toolbar = document.querySelector('[data-export-base]');
    var exportBase = toolbar ? toolbar.getAttribute('data-export-base') : '';

    document.querySelectorAll('.js-users-export').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            var format = link.dataset.format;
            var applied = new URLSearchParams(window.location.search);
            var params = new URLSearchParams();

            if (applied.get('search')) {
                params.set('search', applied.get('search'));
            }
            if (applied.get('User_type')) {
                params.set('User_type', applied.get('User_type'));
            }

            var cols = getVisibleColumns();
            if (cols.length) {
                params.set('columns', cols.join(','));
            }

            var qs = params.toString();
            window.location.href = exportBase + '/' + format + (qs ? ('?' + qs) : '');
        });
    });

    // ---------------------------------------------------------------
    // Print — current page, visible columns, clean A4 output.
    // ---------------------------------------------------------------
    var printBtn = document.getElementById('usersPrintBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            var table = document.getElementById('zero_config_table');
            if (!table) {
                return;
            }

            var clone = table.cloneNode(true);
            // Remove the action column and any hidden columns from the printout.
            clone.querySelectorAll('.col-action, .d-none').forEach(function (cell) {
                cell.remove();
            });

            var win = window.open('', '_blank');
            if (!win) {
                return;
            }

            win.document.write(
                '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Users</title>' +
                '<style>' +
                '@page{size:A4 landscape;margin:12mm;}' +
                'body{font-family:Arial,Helvetica,sans-serif;color:#1f2937;margin:0;}' +
                'h1{font-size:18px;margin:0 0 12px;}' +
                'table{width:100%;border-collapse:collapse;font-size:12px;}' +
                'th,td{border:1px solid #d1d5db;padding:6px 8px;text-align:left;vertical-align:top;}' +
                'thead th{background:#f3f4f6;}' +
                '.badge{display:inline-block;border:1px solid #d1d5db;border-radius:10px;padding:1px 7px;font-size:11px;}' +
                '</style></head><body>' +
                '<h1>Users</h1>' + clone.outerHTML + '</body></html>'
            );
            win.document.close();
            win.focus();
            setTimeout(function () {
                try {
                    win.print();
                } catch (e) {}
            }, 350);
        });
    }
});
</script>
@endpush
