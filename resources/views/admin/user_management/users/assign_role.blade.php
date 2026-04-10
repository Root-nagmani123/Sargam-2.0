@extends('admin.layouts.master')

@section('title', 'Assign Role - Sargam | Lal Bahadur')

@section('setup_content')

<div class="container-fluid py-4">
    <x-breadcrum title="Assign Role" />
    <x-session_message />
    
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-light border-bottom py-3 px-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="mb-0 fw-semibold text-dark">Assign Role to: {{ $user->first_name }} {{ $user->last_name }}</h4>
                <span id="selectedRoleCount" class="badge rounded-pill text-bg-primary">0 selected</span>
            </div>
        </div>

        <div class="card-body p-4">

        <form action="{{ route('admin.users.assignRoleSave') }}" method="POST">
            @csrf

            <input type="hidden" name="user_id" value="{{ $user->pk }}">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <label class="form-label fw-semibold mb-0">Select Roles</label>
                <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle">User Access Management</span>
            </div>

            {{-- SEARCH FILTER --}}
            <div class="input-group mb-2">
                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="searchRole" class="form-control" placeholder="Search roles by name or display name">
                <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn">Clear</button>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <small class="text-muted">Tip: Select multiple roles to combine permissions.</small>
                <small id="visibleRoleCount" class="text-muted"></small>
            </div>

            {{-- ROLES WILL LOAD HERE --}}
            <div class="row g-3" id="roleContainer">
                <p class="text-muted mb-0">Loading roles... please wait.</p>
            </div>

            <div class="d-flex justify-content-end mt-4 gap-2">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary px-4 py-2 fw-semibold">Cancel</a>
                <button class="btn btn-primary px-4 py-2 fw-semibold">Save Roles</button>
            </div>
        </form>

        </div>
    </div>
</div>

<script>
let userRoles = @json($userRoles);

// Handle fetch errors
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
                html = '<div class="col-12"><div class="alert alert-light border text-muted mb-0">No roles available.</div></div>';
            } else {
                data.forEach(role => {
                    let checked = userRoles.includes(role.id) ? "checked" : "";
                    html += `
                        <div class="col-12 col-sm-3 col-lg-2 col-xl-2 role-item">
                            <label class="w-100 border rounded-3 p-3 h-100 bg-white shadow-sm role-option">
                                <div class="form-check m-0">
                                    <input class="form-check-input me-2" type="checkbox" name="roles[]" value="${role.id}" ${checked}>
                                    <span class="form-check-label fw-semibold text-dark">${role.name}</span>
                                </div>
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
            document.getElementById("roleContainer").innerHTML = '<div class="col-12"><div class="alert alert-danger mb-0">Error loading roles. Please refresh the page.</div></div>';
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
                label.classList.add('border-primary', 'bg-primary-subtle');
            } else {
                label.classList.remove('border-primary', 'bg-primary-subtle');
            }
        };

        applyState();
        checkbox.addEventListener('change', function () {
            applyState();
            updateRoleCounters();
        });
    });
}

// Load roles AFTER page load (super fast)
document.addEventListener("DOMContentLoaded", loadRoles);

// Search Filter - wait for roles to load first
document.addEventListener("DOMContentLoaded", function() {
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
            document.querySelectorAll('.role-item').forEach(function(item){
                item.style.display = item.innerText.toLowerCase().includes(value) ? '' : 'none';
            });
            updateRoleCounters();
        });
    }
});
</script>

@endsection
