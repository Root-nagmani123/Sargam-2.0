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
                        <div class="dataTables_length" id="zero_config_length"><label>Show <select
                                    name="zero_config_length" aria-controls="zero_config" class="">
                                    <option value="10">10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select> entries</label></div>
                        <div id="zero_config_filter" class="dataTables_filter"><label>Search:<input type="search"
                                    class="" placeholder="" aria-controls="zero_config"></label></div>
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
                                            <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-success text-white btn-sm">
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
                                        <td colspan="4" class="text-center">No roles found</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        <div class="dataTables_info" id="zero_config_info" role="status" aria-live="polite">Showing 1 to
                            10 of 57 entries</div>
                        <div class="dataTables_paginate paging_simple_numbers" id="zero_config_paginate"><a
                                class="paginate_button previous disabled" aria-controls="zero_config"
                                aria-disabled="true" role="link" data-dt-idx="previous" tabindex="-1"
                                id="zero_config_previous">Previous</a><span><a class="paginate_button current"
                                    aria-controls="zero_config" role="link" aria-current="page" data-dt-idx="0"
                                    tabindex="0">1</a><a class="paginate_button " aria-controls="zero_config"
                                    role="link" data-dt-idx="1" tabindex="0">2</a><a class="paginate_button "
                                    aria-controls="zero_config" role="link" data-dt-idx="2" tabindex="0">3</a><a
                                    class="paginate_button " aria-controls="zero_config" role="link" data-dt-idx="3"
                                    tabindex="0">4</a><a class="paginate_button " aria-controls="zero_config"
                                    role="link" data-dt-idx="4" tabindex="0">5</a><a class="paginate_button "
                                    aria-controls="zero_config" role="link" data-dt-idx="5" tabindex="0">6</a></span><a
                                class="paginate_button next" aria-controls="zero_config" role="link" data-dt-idx="next"
                                tabindex="0" id="zero_config_next">Next</a></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>


@endsection