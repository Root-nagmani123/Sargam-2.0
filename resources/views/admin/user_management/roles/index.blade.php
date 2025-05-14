@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Roles" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Roles</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">+ Add Roles</a>
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
                                    <th class="col">Role Name</th>
                                    <th class="col">Permission</th>
                                    <th class="col">Action</th>
                                    <th class="col">Status</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @if($roles->count() > 0)
                                @foreach($roles as $role)
                                <tr class="odd">
                                    <td>{{ $role->id }}</td>
                                    <td class="sorting_1">{{ $role->name }}</td>
                                    <td>
                                        @foreach($role->permissions as $permission)
                                        <span class="badge bg-primary">{{ $permission->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-start align-items-start gap-2">
                                            <a href="{{ route('admin.roles.edit', $role->id) }}"
                                                class="btn btn-success text-white btn-sm">
                                                Edit
                                            </a>
                                            <form action="#" method="POST" class="m-0">
                                                <input type="hidden" name="_token"
                                                    value="7m53OwU7KaFp1PPyJcyUuVMXW7xvrGr12yL6QycA"> <input
                                                    type="hidden" name="_method" value="DELETE"> <button type="submit"
                                                    class="btn btn-danger text-white btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="news" data-column="status" data-id="21" checked="">
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                @else
                                <tr>
                                    <td colspan="5" class="text-center">No roles found</td>
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