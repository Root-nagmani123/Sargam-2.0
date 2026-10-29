{{-- Table --}}
<div class="users-table-outer">
    <div class="table-responsive users-dt-scroll">
        <table class="table align-middle mb-0 programme-dt-table users-table" id="zero_config_table">
            <thead>
                <tr>
                    <th scope="col">S. No.</th>
                    <th scope="col">User Name</th>
                    <th scope="col">Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Contact Number</th>
                    {{-- User Type is the user_category code (Student / Employee /
                         Faculty / Admin); User Role is the Spatie role. The role
                         column used to be headed "User Type", which named the
                         wrong thing entirely. --}}
                    <th scope="col">User Type</th>
                    <th scope="col">User Role</th>
                    <th scope="col">Action</th>
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
                    <td class="users-usertype">{{ \App\Http\Controllers\Admin\UserController::userTypeLabel($user->User_type ?? '') }}</td>
                    <td class="users-usertype">{{ !empty($user->roles) ? $user->roles : 'No Role' }}</td>
                    <td>
                        <a href="{{ route('admin.users.assignRole', encrypt($user->pk)) }}"
                            class="users-assign-link"
                            aria-label="Assign role to {{ $user->user_name }}">
                            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">assignment_ind</i>
                            <span>Assign</span>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="users-empty-state text-center">
                        <i class="material-icons material-symbols-rounded text-secondary opacity-50 d-block mb-2" style="font-size:48px;" aria-hidden="true">groups</i>
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
        <select class="form-select form-select-sm users-per-page-select select2"
                id="usersPerPageFooter" data-placeholder="Rows…" aria-label="Items per page">
            {{-- Rendered from the controller's allow-list, never written out by
                 hand: a size offered here that the controller does not accept is
                 served as the default instead, with nothing on screen saying so.
                 See UserController::ADMIN_USERS_PER_PAGE_OPTIONS. --}}
            @foreach(($perPageOptions ?? \App\Http\Controllers\Admin\UserController::ADMIN_USERS_PER_PAGE_OPTIONS) as $option)
                <option value="{{ $option }}" {{ $perPage == $option ? 'selected' : '' }}>{{ $option }}</option>
            @endforeach
        </select>
        <span>of <strong class="text-dark">{{ $users->total() }}</strong> items</span>
    </div>
</div>
@endif
