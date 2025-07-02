 class="col"@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')
@section('content')
<div class="container-fluid">

    <x-breadcrum title="Users" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Users</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('admin.users.create') }}" class="btn btn-primary">+ Add Users</a>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper">
                        <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable"
                            aria-describedby="zero_config_info">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="col">S.No.</th>
                                    <th class="col">Name</th>
                                    <th class="col">Email</th>
                                    <th class="col">Roles</th>
                                    <th class="col">Created At</th>
                                    <th class="col">Action</th>
                                    <!-- <th class="col">Status</th> -->
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @if($users->count() > 0)
                                @foreach($users as $user)
                                <tr class="odd">
                                    <td>{{ $user->id }}</td>
                                    <td class="sorting_1">{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        @foreach($user->roles as $role)
                                            <span class="badge bg-primary">{{ $role->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>{{ $user->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                    <div class="d-flex justify-content-start align-items-start gap-2">
                                            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-success text-white btn-sm">
                                                Edit
                                            </a>
                                            <form action="#" method="POST" class="m-0">
                                                <input type="hidden" name="_token"
                                                    value="7m53OwU7KaFp1PPyJcyUuVMXW7xvrGr12yL6QycA"> <input
                                                    type="hidden" name="_method" value="DELETE"> <button type="submit"
                                                    class="btn btn-danger text-white btn-sm"
                                                    onclick="event.preventDefault();
                                                if(confirm('Are you sure you want to delete this user?')) {
                                                    this.closest('form').submit();
                                                }"
                                                {{ $user->active_inactive == 1 ? 'disabled' : '' }}>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                @else
                                    <tr>
                                        <td colspan="3" class="text-center">No roles found</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>
@endsection 