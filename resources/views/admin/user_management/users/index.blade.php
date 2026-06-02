@extends('admin.layouts.master')

@section('title', 'User Management - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Users"></x-breadcrum>

    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">

                <div class="row mb-2">
                    <div class="col-6">
                        <h4 class="mb-0">Users</h4>
                    </div>
                    <div class="col-md-6 text-end">
                        <form method="GET" class="d-flex justify-content-end gap-2">

                            <input type="text" name="search" class="form-control" placeholder="Search..."
                                value="{{ $search }}">
                           <select name="User_type" class="form-select">
                                <option value="">All Users</option>
                                <option value="S" {{ $user_type === 'S' ? 'selected' : '' }}>Student</option>
                                <option value="E" {{ $user_type === 'E' ? 'selected' : '' }}>Employee</option>
                            </select>

                            <select name="per_page" class="form-select">
                                <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                                <option value="20" {{ $perPage == 20 ? 'selected' : '' }}>20</option>
                                <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                            </select>
                            <button class="btn btn-primary">Go</button>
                        </form>
                    </div>
                    <div class="col-6 text-end">
                        {{-- Optional Add Button --}}
                        {{-- <a href="{{ route('admin.users.create') }}" class="btn btn-primary">+ Add Users</a> --}}
                    </div>
                </div>

                <hr>

                <div class="table-responsive datatables">
                    <table class="table" id="zero_config_table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Username</th>
                                <th scope="col">Name</th>
                                <th scope="col">Email</th>
                                <th scope="col">Mobile</th>
                                <th scope="col">User Type</th>
                                <th scope="col">Roles</th>
                                <th scope="col" class="text-center">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($users as $index => $user)
                            <tr>
                                <td>{{ $users->firstItem() + $index }}</td>
                                <td>{{ $user->user_name }}</td>
                                <td>{{ $user->first_name }} {{ $user->last_name }}</td>
                                <td>{{ $user->email_id }}</td>
                                <td>{{ $user->mobile_no }}</td>
                                <td>
                                    @if($user->User_type === 'S')
                                        <span class="badge rounded-pill bg-primary">Student</span>
                                    @elseif($user->User_type === 'E')
                                        <span class="badge rounded-pill bg-success">Employee</span>
                                    @else
                                        <span class="badge rounded-pill bg-secondary">Unknown</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($user->roles))
                                        <span class="badge rounded-pill users-role-badge">{{ $user->roles }}</span>
                                    @else
                                        <span class="badge rounded-pill users-role-badge users-role-badge--empty">No Role</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.users.assignRole', encrypt($user->pk)) }}"
                                        class="btn btn-sm btn-primary">
                                        Assign Role
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-danger">
                                    No users found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-3">
                    {{ $users->links() }}
                </div>

            </div>
        </div>
    </div>
</div>
@endsection


{{-- @section('scripts')
    {!! $dataTable->scripts() !!}
@endsection --}}