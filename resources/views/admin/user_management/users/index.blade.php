@extends('admin.layouts.master')

@section('title', 'User Management - Sargam | Lal Bahadur')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/users-admin.css') }}?v={{ @filemtime(public_path('css/users-admin.css')) ?: time() }}">
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
                </div>
            </form>

            {{-- Table --}}
            <div class="users-table-outer">
                <div class="table-responsive users-dt-scroll">
                    <table class="table table-hover align-middle mb-0 programme-dt-table users-table" id="zero_config_table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Username</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Mobile</th>
                                <th scope="col">User Type</th>
                                <th scope="col">Roles</th>
                                <th scope="col" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $index => $user)
                            <tr>
                                <td>{{ $users->firstItem() + $index }}</td>
                                <td>{{ $user->user_name }}</td>
                                <td>{{ trim($user->first_name . ' ' . $user->last_name) }}</td>
                                <td>{{ $user->email_id }}</td>
                                <td>{{ $user->mobile_no ?: '—' }}</td>
                                <td>
                                    @if($user->User_type === 'S')
                                        <span class="badge rounded-pill bg-primary">Student</span>
                                    @elseif($user->User_type === 'E')
                                        <span class="badge rounded-pill bg-success">Employee</span>
                                    @else
                                        <span class="badge rounded-pill bg-secondary">Unknown</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($user->roles))
                                        <span class="badge rounded-pill users-role-badge">{{ $user->roles }}</span>
                                    @else
                                        <span class="badge rounded-pill users-role-badge users-role-badge--empty">No Role</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.users.assignRole', encrypt($user->pk)) }}"
                                        class="btn btn-outline-primary users-assign-btn d-inline-flex align-items-center gap-2"
                                        aria-label="Assign role to {{ $user->user_name }}">
                                        <i class="bi bi-person-gear" aria-hidden="true"></i>
                                        <span>Assign Role</span>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="users-empty-state text-center">
                                    <i class="bi bi-people display-4 text-secondary opacity-50 d-block mb-3" aria-hidden="true"></i>
                                    <h5 class="fw-semibold text-dark mb-1">No Users Found</h5>
                                    <p class="text-secondary mb-0">Try adjusting your search or filters.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Pagination Footer --}}
            @if($users->total() > 0)
            <div class="users-dt-footer programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3">
                <nav aria-label="Users pagination" class="users-pagination-links order-2 order-md-1">
                    {{ $users->withQueryString()->links() }}
                </nav>
                <div class="users-pagination-info d-flex align-items-center gap-2 order-1 order-md-2 ms-md-auto text-muted small">
                    <span>Showing</span>
                    <select class="form-select form-select-sm users-per-page-select" id="usersPerPageFooter" aria-label="Items per page">
                        <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                        <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                        <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                    </select>
                    <span>of <strong class="text-dark">{{ $users->total() }}</strong> items</span>
                </div>
            </div>
            @endif

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
});
</script>
@endpush
