@extends('admin.layouts.master')

@section('title', 'Permissions')
@section('page_title', 'Permissions')
@section('page_subtitle', 'Manage permissions data')

@section('setup_content')
<div class="container-fluid profile-page">
    <x-breadcrum
        title="Permissions"
        :items="[
            'Home',
            ['label' => 'Protocol', 'url' => route('protocol.dashboard')],
            'Permissions',
        ]"
    />
    

    <style>
        .perm-page .card-panel {
            border: 1px solid #eef0f3;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(16, 24, 40, 0.04);
        }

        .perm-page .search-bar .form-control {
            border-radius: 10px 0 0 10px;
            border-right: 0;
        }
        .perm-page .search-bar {
            display: flex;
        }
        .perm-page .search-bar .input-icon {
            display: flex;
            align-items: center;
            padding: 0 12px;
            background: #fff;
            border: 1px solid #dee1e6;
            border-right: 0;
            border-radius: 10px 0 0 10px;
            color: #9aa1ab;
        }
        .perm-page .search-bar .form-control {
            border-left: 0;
            border-radius: 0 10px 10px 0;
        }
        .perm-page .search-bar .form-control:focus {
            box-shadow: none;
            border-color: #dee1e6;
        }

        .perm-page .btn-outline-brand {
            border-radius: 10px;
            border: 1px solid #d3455b;
            color: #d3455b;
            font-weight: 600;
            background: #fff;
        }
        .perm-page .btn-outline-brand:hover {
            background: #d3455b;
            color: #fff;
        }
        .perm-page .btn-brand {
            border-radius: 10px;
            background: #16406b;
            border-color: #16406b;
            font-weight: 600;
        }
        .perm-page .btn-brand:hover {
            background: #0f2f50;
            border-color: #0f2f50;
        }

        .perm-page .user-list-header {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #9aa1ab;
            font-weight: 700;
            padding: 14px 16px 10px;
        }

        .perm-page .user-list-item {
            border: 0;
            border-left: 3px solid transparent;
            padding: 12px 16px;
            transition: background-color .15s ease;
        }
        .perm-page .user-list-item:hover {
            background-color: #f6f8fb;
        }
        .perm-page .user-list-item.active {
            background: #eef4fb;
            border-left-color: #16406b;
            color: inherit;
        }
        .perm-page .user-list-item.active .fw-semibold { color: #16406b; }

        .perm-page .avatar-circle {
            width: 38px; height: 38px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: #fff;
            flex-shrink: 0;
        }
        .perm-page .avatar-circle.a1 { background: linear-gradient(135deg,#16406b,#2a6bab); }
        .perm-page .avatar-circle.a2 { background: linear-gradient(135deg,#7a7f89,#9aa1ab); }
        .perm-page .avatar-circle.a3 { background: linear-gradient(135deg,#c97a3a,#e0a05c); }
        .perm-page .avatar-circle.lg { width: 48px; height: 48px; font-size: 15px; }

        .perm-page .role-chip {
            display: inline-flex; align-items: center; gap: 8px;
            background: #eef4fb; color: #16406b;
            font-size: 0.8rem; font-weight: 600;
            padding: 6px 12px; border-radius: 20px;
            border: 1px solid #d7e6f5;
        }
        .perm-page .role-chip .btn-close {
            font-size: 0.5rem;
            opacity: 0.5;
        }
        .perm-page .role-chip .btn-close:hover { opacity: 0.9; }

        .perm-page .module-title {
            font-size: 0.85rem; font-weight: 700; color: #16406b;
            margin-bottom: 10px;
            display: flex; align-items: center; gap: 8px;
        }
        .perm-page .module-title i { font-size: 0.95rem; color: #7a8aa0; }

        .perm-page .perm-section {
            background: #fbfcfd;
            border: 1px solid #eef0f3;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 14px;
        }

        .perm-page .form-check-input:checked {
            background-color: #16406b;
            border-color: #16406b;
        }
        .perm-page .form-check-input:disabled {
            background-color: #c7d3e0;
            border-color: #c7d3e0;
            opacity: 1;
        }
        .perm-page .form-check-label { font-size: 0.85rem; }
        .perm-page .form-check-label.text-muted-disabled { color: #9aa1ab; }

        .perm-page .section-label {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #9aa1ab;
        }
        .skeleton-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 0;
        }
        .skeleton-box {
            width: 18px; height: 18px;
            border-radius: 4px;
            flex-shrink: 0;
        }
        .skeleton-line {
            height: 12px;
            width: 20%;
            border-radius: 4px;
        }
        .skeleton-box, .skeleton-line {
            background: linear-gradient(90deg, #eef0f3 25%, #f7f8fa 37%, #eef0f3 63%);
            background-size: 400% 100%;
            animation: skeleton-shimmer 1.4s ease infinite;
        }
        @keyframes skeleton-shimmer {
            0%   { background-position: 100% 50%; }
            100% { background-position: 0 50%; }
        }
    </style>

   <div class="perm-page">
 
        <h4 class="mb-3 fw-bold">Permission assignment</h4>
        <div class="row g-3">
            <!-- Left: user list -->
            <div class="col-md-4">
                <div class="card-panel bg-white overflow-hidden">
                    <div class="user-list-header">Protocol Employees</div>
                    <div class="list-group list-group-flush" id="employeeList">
                        @if(isset($employees))
                        @forelse ($employees as $employee)
                        <button type="button"
                            class="list-group-item list-group-item-action user-list-item d-flex align-items-center gap-2 {{ $loop->first ? 'active' : '' }}"
                            data-id="{{ $employee->employee_pk }}"
                            data-user_id="{{ $employee->user_credentials_pk }}"
                            data-name="{{ $employee->employee_name }}"
                            data-designation="{{ $employee->designation_name }}"
                            data-email="{{ $employee->email ?? '' }}"
                            data-image="{{ !empty($employee->image_path) ? asset('images/'.$employee->image_path) : '' }}"
                            data-initials="{{ substr($employee->employee_name, 0, 2) }}"
                            data-avatar-class="a{{ (($loop->iteration - 1) % 3) + 1 }}"
                            onclick="selectEmployee(this)">
                            @if(!empty($employee->image_path))
                            <img src="{{ asset('images/'.$employee->image_path) }}" class="avatar-circle a{{ (($loop->iteration - 1) % 3) + 1}}" alt="User Image">
                            @else
                            <div class="avatar-circle a{{ (($loop->iteration - 1) % 3) + 1}}">{{ substr($employee->employee_name, 0, 2) }}</div>
                            @endif
                            <div class="text-start">
                                <div class="fw-semibold small">{{ $employee->employee_name }}</div>
                                <div class="text-muted" style="font-size:0.75rem;">{{ $employee->designation_name }}</div>
                            </div>
                        </button>
                        @empty
                        <div class="empty-state p-3 text-center text-muted">
                            <i class="bi bi-inbox"></i>
                            No protocol users found.
                        </div>
                        @endforelse
                        @endif
                    </div>
                </div>
            </div>
 
            <!-- Right: role + permission editor -->
            <div class="col-md-4">
                <div class="card-panel bg-white p-4">
 
                    <!-- Selected user header -->
                    <div class="d-flex align-items-center gap-3 mb-4" id="selectedUserHeader">
                        <div class="avatar-circle a1 lg" id="selectedUserAvatarWrap">--</div>
                        <div>
                            <div class="fw-bold" id="selectedUserName">Select an employee</div>
                            <div class="text-secondary small" id="selectedUserMeta">&nbsp;</div>
                        </div>
                    </div>
 
                    <!-- Direct permission overrides -->
                    <div class="section-label mb-2">Direct permission overrides <span class="text-muted fw-normal text-lowercase">(optional)</span></div>
 
                    <div id="permissionPanel">
                        <div class="perm-section mb-4">
                            <div class="module-title"><i class="bi bi-clipboard-data"></i>Booking Module</div>
                            <div class="row row-cols-1 g-2" id="permissionCheckboxes">
                                <div class="col">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="GuestHouseBooking" value="guest_house_booking">
                                        <label class="form-check-label" for="GuestHouseBooking">Guest House Booking</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="VehicleBooking" value="vehicle_booking">
                                        <label class="form-check-label" for="VehicleBooking">Vehicle Booking</label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="TicketBooking" value="ticket_booking">
                                        <label class="form-check-label" for="TicketBooking">Ticket Booking</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
 
                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <button type="button" class="btn btn-outline-brand px-3 mt-3" onclick="location.reload()">Cancel</button>
                        <button type="button" class="btn btn-brand text-white px-3 mt-3" onclick="savePermissions()">Save changes</button>
                    </div>
 
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script>
    // Currently selected employee id, used by savePermissions()
    let selectedEmployeeId = null;
 
    function selectEmployee(btn) {
        // Highlight the clicked row only
        document.querySelectorAll('#employeeList .user-list-item').forEach(el => el.classList.remove('active'));
        btn.classList.add('active');
 
        const id           = btn.dataset.id;
        const user_id      = btn.dataset.user_id;
        const name         = btn.dataset.name;
        const designation  = btn.dataset.designation;
        const email        = btn.dataset.email;
        const image        = btn.dataset.image;
        const initials     = btn.dataset.initials;
        const avatarClass  = btn.dataset.avatarClass;
 
        selectedEmployeeId = user_id;
 
        // Update header immediately with what we already have (no wait for server)
        document.getElementById('selectedUserName').textContent = name;
        document.getElementById('selectedUserMeta').textContent = email || designation;
 
        const avatarWrap = document.getElementById('selectedUserAvatarWrap');
        avatarWrap.className = 'avatar-circle lg ' + avatarClass;
        if (image) {
            avatarWrap.innerHTML = `<img src="${image}" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`;
        } else {
            avatarWrap.textContent = initials;
        }
 
        // Fetch this employee's saved permissions from the server and re-render checkboxes
        loadPermissions(user_id);
    }

    function loadPermissions(employeeId) {
        const panel = document.getElementById('permissionCheckboxes');
        // Skeleton placeholder — 3 fake checkbox rows while the real data loads
        panel.innerHTML = `
            <div class="col-12">
                <div class="skeleton-row"><div class="skeleton-box"></div><div class="skeleton-line"></div></div>
                <div class="skeleton-row"><div class="skeleton-box"></div><div class="skeleton-line"></div></div>
                <div class="skeleton-row"><div class="skeleton-box"></div><div class="skeleton-line"></div></div>
                <div class="skeleton-row"><div class="skeleton-box"></div><div class="skeleton-line"></div></div>
            </div>`;

        fetch(`{{ url('protocol/permissions/employee') }}/${employeeId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            renderPermissions(data.permissions);
        })
        .catch(() => {
            panel.innerHTML = `<div class="col text-danger small py-2">Could not load permissions. Try again.</div>`;
        });
    }
 
    function renderPermissions(permissions) {
        const panel = document.getElementById('permissionCheckboxes');
        panel.innerHTML = '';
 
        permissions.forEach(p => {
            const col = document.createElement('div');
            col.className = 'col';
            p.label = p.name.replace('protocol.', '');
            p.label = p.label.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
            col.innerHTML = `
                <div class="form-check">
                    <input class="form-check-input" type="checkbox"
                        id="perm_${p.id}" value="${p.id}"
                        ${p.checked ? 'checked' : ''} ${p.locked ? 'disabled' : ''}>
                    <label class="form-check-label ${p.locked ? 'text-muted-disabled' : ''}" for="perm_${p.id}">${p.label}</label>
                </div>`;
            panel.appendChild(col);
        });
    }
 
    function savePermissions() {
        if (!selectedEmployeeId) return;
 
        const checked = Array.from(document.querySelectorAll('#permissionCheckboxes input[type="checkbox"]:checked:not(:disabled)'))
            .map(cb => cb.value);
 
        fetch(`{{ url('protocol/permissions/employee') }}/${selectedEmployeeId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ permissions: checked })
        })
        .then(res => res.json())
        .then(() => {
            alert('Permissions saved');
        })
        .catch(() => alert('Could not save permissions. Try again.'));
    }
 

    document.addEventListener('DOMContentLoaded', () => {
        const first = document.querySelector('#employeeList .user-list-item.active');
        if (first) selectEmployee(first);
    });
</script>
@endpush
 