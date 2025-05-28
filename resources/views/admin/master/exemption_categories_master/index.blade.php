@extends('admin.layouts.master')

@section('title', 'Exemption categories')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Exemption categories" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Exemption categories</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('master.exemption.category.master.create')}}" class="btn btn-primary">+
                                    Add Exemption categories</a>
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
                                    <th class="col">Short Name</th>
                                    <th class="col">Status</th>
                                    <th class="col">Actions</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @if (!empty($categories) && count($categories) > 0)
                                @foreach ($categories as $cat)
                                <tr class="odd">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $cat->exemp_category_name }}</td>
                                    <td>{{ $cat->exemp_cat_short_name }}</td>
                                    <td>
                                        <a href="{{ route('master.exemption.category.master.edit', 
                                                    ['id' => encrypt(value: $cat->pk)]) }}"
                                            class="btn btn-primary btn-sm">Edit</a>
                                        <form
                                            title="{{ $cat->active_inactive == 1 ? 'Cannot delete active course group type' : 'Delete' }}"
                                            action="{{ route('master.exemption.category.master.delete', 
                                                    ['id' => encrypt($cat->pk)]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-danger btn-sm" onclick="event.preventDefault(); 
                                                        if(confirm('Are you sure you want to delete this record?')) {
                                                            this.closest('form').submit();
                                                        }" {{ $cat->active_inactive == 1 ? 'disabled' : '' }}>
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="exemption_category_master" data-column="active_inactive"
                                                data-id="{{ $cat->pk }}"
                                                {{ $cat->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                                @else

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