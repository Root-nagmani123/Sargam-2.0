@extends('layouts.app')

@section('title', 'Assign Role')

@section('content')

<div class="card">
    <div class="card-header">
        <h4>Assign Role to: {{ $user->first_name }} {{ $user->last_name }}</h4>
    </div>

    <div class="container-fluid mt-4 mb-4">

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

<script>
let userRoles = @json($userRoles);

function loadRoles() {
   fetch("{{ route('admin.users.getRoles') }}")
        .then(res => res.json())
        .then(data => {
            let html = "";

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

            document.getElementById("roleContainer").innerHTML = html;
        });
}

// Load roles AFTER page load (super fast)
document.addEventListener("DOMContentLoaded", loadRoles);

// Search Filter
document.getElementById("searchRole").addEventListener("keyup", function () {
    let value = this.value.toLowerCase();
    document.querySelectorAll('.role-item').forEach(function(item){
        item.style.display = item.innerText.toLowerCase().includes(value) ? '' : 'none';
    });
});
</script>

@endsection
