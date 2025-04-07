@extends('admin.layouts.master')

@section('title', 'Member - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Permissions" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Permissions</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary">+ Add Permissions</a>
                            </div>
                        </div>
                    </div>
                    <!-- Vertically centered modal -->
                    
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
                                    <th class="sorting sorting_asc" tabindex="0" aria-controls="zero_config" rowspan="1"
                                        colspan="1" aria-sort="ascending"
                                        aria-label="Name: activate to sort column descending" style="width: 224.625px;">
                                        S.No.</th>
                                    <th>Permission Name</th>
                                    <th>Action</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @if($permissions->count() > 0)
                                    @foreach($permissions as $permission)
                                        <tr>
                                            <td>{{ $permission->id }}</td>
                                            <td>{{ $permission->name }}</td>
                                            <td>
                                                <a href="{{ route('admin.permissions.edit', $permission->id) }}" class="btn btn-primary">Edit</a>
                                                {{-- <a href="{{ route('admin.permissions.destroy', $permission->id) }}" class="btn btn-danger">Delete</a> --}}
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="3" class="text-center">No permissions found</td>
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