@extends('admin.layouts.master')

@section('title', 'Assign Role - Sargam | Lal Bahadur')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/users-assign-role-admin.css') }}?v={{ @filemtime(public_path('css/users-assign-role-admin.css')) ?: time() }}">
@endpush

@section('setup_content')

<div class="container-fluid users-assign-page py-4">
    <x-breadcrum title="Assign Role" :showBack="true" />

    <x-session_message />

    <div class="card users-assign-card shadow-sm border-0 overflow-hidden">
        <div class="users-assign-user-strip">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="users-assign-user-name">
                        Assign Role to: {{ trim($user->first_name . ' ' . $user->last_name) }}
                    </div>
                    <div class="users-assign-user-meta">
                        @if(!empty($user->user_name))
                            <span>{{ $user->user_name }}</span>
                            @if(!empty($user->email_id))
                                <span class="mx-1">·</span>
                            @endif
                        @endif
                        @if(!empty($user->email_id))
                            <span>{{ $user->email_id }}</span>
                        @endif
                    </div>
                </div>
                <span id="selectedRoleCount" class="badge rounded-pill users-assign-count-badge">0 selected</span>
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            <form action="{{ route('admin.users.assignRoleSave') }}" method="POST">
                @csrf

                <input type="hidden" name="user_id" value="{{ $user->pk }}">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <label class="users-assign-section-label mb-0">Select Roles</label>
                    <span class="badge rounded-pill users-assign-section-badge">User Access Management</span>
                </div>

                {{-- Search Filter --}}
                <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3 mb-2">
                    <div class="users-assign-search-wrap flex-grow-1">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="text"
                            id="searchRole"
                            class="form-control users-assign-search-input"
                            placeholder="Search roles by name or display name"
                            autocomplete="off"
                            aria-label="Search roles">
                    </div>
                    <button class="btn users-assign-clear-btn" type="button" id="clearSearchBtn">Clear</button>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                    <small class="users-assign-tip">Tip: Select multiple roles to combine permissions.</small>
                    <small id="visibleRoleCount" class="users-assign-visible-count"></small>
                </div>

                {{-- Roles grid --}}
                <div class="row g-3" id="roleContainer">
                    <div class="col-12">
                        <div class="users-assign-loading">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></div>
                            Loading roles... please wait.
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end users-assign-footer gap-2">
                    <a href="{{ route('admin.users.index') }}" class="btn users-assign-cancel-btn d-inline-flex align-items-center justify-content-center">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary users-assign-save-btn d-inline-flex align-items-center gap-2">
                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                        <span>Save Roles</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let userRoles = @json($userRoles);

function loadRoles() {
    fetch("{{ route('admin.users.getRoles') }}")
        .then(res => {
            if (!res.ok) {
                throw new Error('Failed to load roles');
            }
            return res.json();
        })
        .then(data => {
            let html = "";

            if (data.length === 0) {
                html = '<div class="col-12"><div class="alert alert-light border text-muted mb-0 rounded-3">No roles available.</div></div>';
            } else {
                data.forEach(role => {
                    let checked = userRoles.includes(role.pk) ? "checked" : "";
                    html += `
                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 role-item">
                            <label class="role-option">
                                <div class="form-check m-0">
                                    <input class="form-check-input me-2" type="checkbox" name="roles[]" value="${role.pk}" ${checked}>
                                    <span class="form-check-label">${role.user_role_name}</span>
                                </div>
                                <small class="d-block mt-2 ms-4">${role.user_role_display_name}</small>
                            </label>
                        </div>
                    `;
                });
            }

            document.getElementById("roleContainer").innerHTML = html;
            updateRoleCounters();
            bindRoleCardStates();
        })
        .catch(error => {
            console.error('Error loading roles:', error);
            document.getElementById("roleContainer").innerHTML = '<div class="col-12"><div class="alert alert-danger mb-0 rounded-3">Error loading roles. Please refresh the page.</div></div>';
            updateRoleCounters();
        });
}

function updateRoleCounters() {
    const selectedCount = document.querySelectorAll('input[name="roles[]"]:checked').length;
    const visibleCount = Array.from(document.querySelectorAll('.role-item'))
        .filter(item => item.style.display !== 'none').length;
    const totalCount = document.querySelectorAll('.role-item').length;

    const selectedRoleCount = document.getElementById("selectedRoleCount");
    const visibleRoleCount = document.getElementById("visibleRoleCount");

    if (selectedRoleCount) {
        selectedRoleCount.textContent = `${selectedCount} selected`;
    }
    if (visibleRoleCount) {
        visibleRoleCount.textContent = `${visibleCount} of ${totalCount} roles shown`;
    }
}

function bindRoleCardStates() {
    document.querySelectorAll('.role-option').forEach(label => {
        const checkbox = label.querySelector('input[type="checkbox"]');
        if (!checkbox) return;

        const applyState = () => {
            if (checkbox.checked) {
                label.classList.add('border-primary', 'is-selected');
            } else {
                label.classList.remove('border-primary', 'is-selected');
            }
        };

        applyState();
        checkbox.addEventListener('change', function () {
            applyState();
            updateRoleCounters();
        });
    });
}

document.addEventListener("DOMContentLoaded", loadRoles);

document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("searchRole");
    const clearSearchBtn = document.getElementById("clearSearchBtn");

    if (clearSearchBtn && searchInput) {
        clearSearchBtn.addEventListener("click", function () {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('keyup'));
            searchInput.focus();
        });
    }

    if (searchInput) {
        searchInput.addEventListener("keyup", function () {
            let value = this.value.toLowerCase();
            document.querySelectorAll('.role-item').forEach(function (item) {
                item.style.display = item.innerText.toLowerCase().includes(value) ? '' : 'none';
            });
            updateRoleCounters();
        });
    }
});
</script>
@endpush
