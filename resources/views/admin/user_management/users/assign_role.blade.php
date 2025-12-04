@extends('admin.layouts.master')

@section('title', 'Assign Role - Sargam | Lal Bahadur')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Users" />
    <x-session_message />
    
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-header">
            <h4>Assign Role to: {{ $user->name }}</h4>
        </div>

        <div class="card-body mt-4 mb-4">

        <form action="{{ route('admin.users.assignRoleSave') }}" method="POST">
            @csrf

            <input type="hidden" name="user_id" value="{{ $user->pk }}">

            <label><strong>Select Roles</strong></label>

            {{-- SEARCH FILTER --}}
            <input type="text" id="searchRole" class="form-control mb-3" placeholder="Search Roles…">

            {{-- ROLES WILL LOAD HERE --}}
            <div class="row" id="roleContainer">
                <p class="text-muted">Loading roles… please wait.</p>
            </div>

            <button class="btn btn-primary mt-3">Save Roles</button>
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
                html = '<p class="text-muted">No roles available.</p>';
            } else {
                data.forEach(role => {
                    let checked = userRoles.includes(role.pk) ? "checked" : "";
                    html += `
                        <div class="col-md-3 mb-2 role-item">
                            <label class="small">
                                <input type="checkbox" name="roles[]" value="${role.pk}" ${checked}>
                                ${role.user_role_name}
                                <small class="text-muted d-block">(${role.user_role_display_name})</small>
                            </label>
                        </div>
                    `;
                });
            }

            document.getElementById("roleContainer").innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading roles:', error);
            document.getElementById("roleContainer").innerHTML = '<p class="text-danger">Error loading roles. Please refresh the page.</p>';
        });
}

// Load roles AFTER page load (super fast)
document.addEventListener("DOMContentLoaded", loadRoles);

// Search Filter - wait for roles to load first
document.addEventListener("DOMContentLoaded", function() {
    const searchInput = document.getElementById("searchRole");
    if (searchInput) {
        searchInput.addEventListener("keyup", function () {
            let value = this.value.toLowerCase();
            document.querySelectorAll('.role-item').forEach(function(item){
                item.style.display = item.innerText.toLowerCase().includes(value) ? '' : 'none';
            });
        });
    }
});
</script>

@endsection
