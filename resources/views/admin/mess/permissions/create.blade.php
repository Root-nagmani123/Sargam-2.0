@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:shield-user-bold" class="me-2"></iconify-icon>
            Create Mess Permission
        </h5>
    </div>
    <div class="card-body">
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <iconify-icon icon="solar:danger-bold" class="me-2"></iconify-icon>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form action="{{ route('admin.mess.permissions.store') }}" method="POST" id="permissionForm">
            @csrf
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="role_id" class="form-label">Select Role <span class="text-danger">*</span></label>
                        <select class="form-select select2 @error('role_id') is-invalid @enderror" 
                                id="role_id" name="role_id" required>
                            <option value="">-- Select Role --</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->pk }}" {{ old('role_id') == $role->pk ? 'selected' : '' }}>
                                    {{ $role->user_role_display_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Select the role that will have this permission</small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="action_name" class="form-label">Permission Action <span class="text-danger">*</span></label>
                        <select class="form-select select2 @error('action_name') is-invalid @enderror" 
                                id="action_name" name="action_name" required>
                            <option value="">-- Select Action --</option>
                            @foreach($actions as $key => $value)
                                <option value="{{ $key }}" {{ old('action_name') == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                            @endforeach
                        </select>
                        @error('action_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">What action this permission allows</small>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="display_name" class="form-label">Display Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control @error('display_name') is-invalid @enderror" 
                       id="display_name" name="display_name" value="{{ old('display_name') }}" 
                       placeholder="e.g., Approve Purchase Orders" required>
                @error('display_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <small class="text-muted">Human-readable name for this permission</small>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" 
                          placeholder="Optional description">{{ old('description') }}</textarea>
                <small class="text-muted">Optional notes about this permission</small>
            </div>

            <hr class="my-4">

            <div class="mb-3">
                <label class="form-label">
                    Assign Users <span class="text-danger">*</span>
                    <span id="userCountBadge" class="badge bg-info ms-2">0 selected</span>
                </label>
                <div id="usersContainer" class="border rounded p-3" style="min-height: 150px;">
                    <div class="text-center text-muted py-4">
                        <iconify-icon icon="solar:user-bold" width="48" class="mb-2"></iconify-icon>
                        <p>Please select a role first to load users</p>
                    </div>
                </div>
                @error('user_ids')
                    <div class="text-danger mt-2">{{ $message }}</div>
                @enderror
                <small class="text-muted">Select specific users from the role who will get this permission</small>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                           value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        Active
                    </label>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.mess.permissions.index') }}" class="btn btn-secondary">
                    <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
                    Back
                </a>
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="solar:diskette-bold" class="me-1"></iconify-icon>
                    Create Permission
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-fill display name based on action selection
    $('#action_name').on('change', function() {
        const selectedText = $(this).find('option:selected').text();
        if (selectedText && !$('#display_name').val()) {
            $('#display_name').val(selectedText);
        }
    });

    // Load users when role is selected
    $('#role_id').on('change', function() {
        const roleId = $(this).val();
        
        if (!roleId) {
            $('#usersContainer').html(`
                <div class="text-center text-muted py-4">
                    <iconify-icon icon="solar:user-bold" width="48" class="mb-2"></iconify-icon>
                    <p>Please select a role first to load users</p>
                </div>
            `);
            updateUserCount();
            return;
        }

        // Show loading
        $('#usersContainer').html(`
            <div class="text-center text-muted py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading users...</p>
            </div>
        `);

        // Fetch users via AJAX
        $.ajax({
            url: '{{ route("admin.mess.permissions.getUsersByRole") }}',
            type: 'GET',
            data: { role_id: roleId },
            success: function(users) {
                if (users.length === 0) {
                    $('#usersContainer').html(`
                        <div class="alert alert-warning mb-0">
                            <iconify-icon icon="solar:info-circle-bold" class="me-2"></iconify-icon>
                            No users found for this role
                        </div>
                    `);
                    return;
                }

                let html = '<div class="row">';
                users.forEach(user => {
                    html += `
                        <div class="col-md-6 mb-2">
                            <div class="form-check">
                                <input class="form-check-input user-checkbox" type="checkbox" 
                                       name="user_ids[]" value="${user.pk}" id="user_${user.pk}">
                                <label class="form-check-label" for="user_${user.pk}">
                                    <strong>${user.name}</strong><br>
                                    <small class="text-muted">${user.email || ''}</small>
                                </label>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                html += `
                    <div class="mt-3">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="selectAll">
                            Select All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">
                            Deselect All
                        </button>
                    </div>
                `;

                $('#usersContainer').html(html);
                updateUserCount();

                // Select/Deselect all handlers
                $('#selectAll').on('click', function() {
                    $('.user-checkbox').prop('checked', true);
                    updateUserCount();
                });

                $('#deselectAll').on('click', function() {
                    $('.user-checkbox').prop('checked', false);
                    updateUserCount();
                });

                // Update count on checkbox change
                $(document).on('change', '.user-checkbox', updateUserCount);
            },
            error: function() {
                $('#usersContainer').html(`
                    <div class="alert alert-danger mb-0">
                        <iconify-icon icon="solar:danger-bold" class="me-2"></iconify-icon>
                        Error loading users. Please try again.
                    </div>
                `);
            }
        });
    });

    function updateUserCount() {
        const count = $('.user-checkbox:checked').length;
        $('#userCountBadge').text(count + ' selected');
    }
});
</script>
@endpush
