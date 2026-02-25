@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <iconify-icon icon="solar:shield-user-bold" class="me-2"></iconify-icon>
            Mess Permissions (RBAC)
        </h5>
        <a href="{{ route('admin.mess.permissions.create') }}" class="btn btn-primary btn-sm">
            <iconify-icon icon="solar:add-circle-bold" class="me-1"></iconify-icon>
            Add New Permission
        </a>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <iconify-icon icon="solar:check-circle-bold" class="me-2"></iconify-icon>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <iconify-icon icon="solar:danger-bold" class="me-2"></iconify-icon>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($permissions->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Role</th>
                            <th>Permission Action</th>
                            <th>Display Name</th>
                            <th>Assigned Users</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissions as $permission)
                        <tr>
                            <td>{{ $permission->id }}</td>
                            <td>
                                <strong>{{ $permission->role->user_role_display_name ?? 'N/A' }}</strong>
                            </td>
                            <td>
                                <code class="text-primary">{{ $permission->action_name }}</code>
                            </td>
                            <td>{{ $permission->display_name }}</td>
                            <td>
                                <span class="badge bg-info">
                                    {{ $permission->permissionUsers->count() }} 
                                    {{ Str::plural('User', $permission->permissionUsers->count()) }}
                                </span>
                                @if($permission->permissionUsers->count() > 0)
                                    <button type="button" class="btn btn-sm btn-link p-0" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#usersModal{{ $permission->id }}">
                                        <iconify-icon icon="solar:eye-bold"></iconify-icon>
                                    </button>
                                @endif
                            </td>
                            <td>
                                @if($permission->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $permission->created_at->format('d M Y') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('admin.mess.permissions.edit', $permission->id) }}" 
                                       class="btn btn-outline-primary" title="Edit">
                                        <iconify-icon icon="solar:pen-bold"></iconify-icon>
                                    </a>
                                    <form action="{{ route('admin.mess.permissions.destroy', $permission->id) }}" 
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Are you sure? This will remove all user assignments.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                            <iconify-icon icon="solar:trash-bin-trash-bold"></iconify-icon>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- Users Modal -->
                        <div class="modal fade" id="usersModal{{ $permission->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Assigned Users</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p><strong>Permission:</strong> {{ $permission->display_name }}</p>
                                        <ul class="list-group">
                                            @foreach($permission->permissionUsers as $pu)
                                                <li class="list-group-item">
                                                    <iconify-icon icon="solar:user-bold" class="me-2"></iconify-icon>
                                                    {{ $pu->user->name ?? 'Unknown' }}
                                                    <br>
                                                    <small class="text-muted">{{ $pu->user->email ?? '' }}</small>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $permissions->links() }}
            </div>
        @else
            <div class="alert alert-info">
                <iconify-icon icon="solar:info-circle-bold" class="me-2"></iconify-icon>
                No permissions found. Click "Add New Permission" to create one.
            </div>
        @endif
    </div>
</div>
@endsection
