@extends('admin.layouts.master')

@section('title', 'Assign Role - Sargam | Lal Bahadur')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/users-assign-role-admin.css') }}?v={{ @filemtime(public_path('css/users-assign-role-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<style>
/* ===== Assign Role — reference-matched (presentation only) ===== */
.uar-page .uar-search { position: relative; width: 340px; max-width: 100%; }
.uar-page .uar-search .uar-search-ico {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: #667085; font-size: 18px; pointer-events: none;
}
.uar-page .uar-search-input {
    height: 44px; padding-left: 42px; border: 1px solid #d0d5dd;
    border-radius: 8px; font-size: 0.9rem; background: #fff;
}
.uar-page .uar-search-input:focus { border-color: #86b7fe; box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.18); }

.uar-page .uar-section-head {
    display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    border-bottom: 1px solid #e5e7eb; padding-bottom: 12px; margin-bottom: 20px;
}
.uar-page .uar-section-title { font-size: 1.05rem; font-weight: 600; color: #1f2937; }
.uar-page .uar-count { font-size: 0.8125rem; color: #667085; white-space: nowrap; }

/* Role chips */
.uar-page .uar-chip-grid { display: flex; flex-wrap: wrap; gap: 12px; }
.uar-page .role-item { display: inline-flex; }
.uar-page .role-option {
    display: inline-flex; align-items: center; gap: 10px; margin: 0;
    padding: 12px 16px; min-height: 48px;
    border: 1px solid #d0d5dd; border-radius: 8px; background: #fff;
    cursor: pointer; transition: background-color .15s ease, border-color .15s ease, color .15s ease;
    color: #344054;
}
.uar-page .role-option:hover { border-color: #9aa7b8; background: #fcfdff; }
.uar-page .role-option .form-check-input { margin: 0; flex-shrink: 0; cursor: pointer; }
.uar-page .role-option .role-option-label { font-size: 0.9rem; font-weight: 500; white-space: nowrap; line-height: 1.2; }
.uar-page .role-option.is-selected { background: #e8f1fd; border-color: var(--bs-primary); }
.uar-page .role-option.is-selected .role-option-label { color: var(--bs-primary); }

/* Footer */
.uar-page .uar-footer { border-top: 1px solid #e5e7eb; padding-top: 20px; margin-top: 28px; }
.uar-page .uar-footer .btn { min-width: 110px; height: 44px; border-radius: 8px; font-weight: 600; }

.uar-page .uar-loading { color: #667085; display: inline-flex; align-items: center; }
</style>

<div class="container-fluid users-assign-page uar-page py-4">
    <x-breadcrum title="{{ trim($user->first_name . ' ' . $user->last_name) }}'s Assigned Role" :showBack="true" />

    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('admin.users.assignRoleSave') }}" method="POST">
                @csrf

                <input type="hidden" name="user_id" value="{{ $user->pk }}">

                {{-- Search (top-right) --}}
                <div class="d-flex justify-content-end mb-4">
                    <div class="uar-search">
                        <i class="material-icons material-symbols-rounded uar-search-ico" aria-hidden="true">search</i>
                        <input type="text"
                            id="searchRole"
                            class="form-control uar-search-input"
                            placeholder="Search"
                            autocomplete="off"
                            aria-label="Search roles">
                    </div>
                </div>

                {{-- Section heading --}}
                <div class="uar-section-head">
                    <span class="uar-section-title">Basic Information</span>
                    <span id="selectedRoleCount" class="uar-count">0 selected</span>
                </div>

                {{-- Roles --}}
                <div class="uar-chip-grid" id="roleContainer">
                    <div class="uar-loading">
                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status" aria-hidden="true"></div>
                        Loading roles... please wait.
                    </div>
                </div>

                {{-- Footer --}}
                <div class="d-flex justify-content-end uar-footer gap-2">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2">
                        <span>Save Role</span>
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
                html = '<div class="alert alert-light border text-muted mb-0 rounded-3 w-100">No roles available.</div>';
            } else {
                data.forEach(role => {
                    let label = role.display_name || role.name || ('Role #' + role.id);
                    let checked = userRoles.includes(role.id) ? "checked" : "";
                    html += `
                        <div class="role-item">
                            <label class="role-option" title="${label}">
                                <input class="form-check-input" type="checkbox" name="roles[]" value="${role.id}" ${checked}>
                                <span class="role-option-label">${label}</span>
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
            document.getElementById("roleContainer").innerHTML = '<div class="alert alert-danger mb-0 rounded-3 w-100">Error loading roles. Please refresh the page.</div>';
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
