{{-- Table --}}
<div class="users-table-outer">
    <div class="table-responsive users-dt-scroll">
        <table class="table align-middle mb-0 programme-dt-table users-table" id="zero_config_table">
            <thead>
                <tr>
                    <th scope="col">S. No.</th>
                    <th scope="col">Username</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Mobile</th>
                    <th scope="col">User Type</th>
                    <th scope="col">Roles</th>
                    <th scope="col">Action</th>
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
                    <td>{{ $users->firstItem() + $index }}</td>
                    <td>{{ $user->user_name }}</td>
                    <td>{{ trim($user->first_name . ' ' . $user->last_name) }}</td>
                    <td>{{ $user->email_id }}</td>
                    <td>{{ $user->mobile_no ?: '—' }}</td>
                    <td>
                        <span class="badge rounded-1 {{ $typeBadge }}">{{ $typeLabel }}</span>
                    </td>
                    <td>
                        @if(!empty($user->roles))
                            <span class="badge rounded-1 users-role-badge">{{ $user->roles }}</span>
                        @else
                            <span class="badge rounded-1 users-role-badge users-role-badge--empty">No Role</span>
                        @endif
                    </td>
                    <td>
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
