@extends('admin.layouts.master')

@section('title', 'Subject module - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Subject module" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Subject module</h4>
                        </div>
                        @can('subject-module.create')
                            <div class="col-6">
                                <div class="float-end gap-2">
                                    <a href="{{route('subject-module.create')}}" class="btn btn-primary">+ Add Subject
                                        module</a>
                                </div>
                            </div>
                        @endcan
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper">

                        <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable"
                            aria-describedby="zero_config_info">
                            <thead>
                                <!-- start row -->
                                <tr >
                                    <th class="col text-center">S.No.</th>
                                    <th class="col text-center">Stream Name</th>
                                    <th class="col text-center">Status</th>
                                    <th class="col text-center">Action</th>


                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @foreach($modules as $key => $modules)
                                <tr class="{{ $loop->odd ? 'odd' : 'even' }} ">
                                    <td class="text-center">{{ $key + 1 }}</td>
                                    <td class="text-center">
                                        {{ $modules->module_name }}
                                    </td>
                                    <td>
                                        @can('subject-module.active_inactive')
                                            <div class="form-check form-switch">
                                                <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                    data-table="subject_module_master" data-column="active_inactive"
                                                    data-id="{{ $modules->pk }}"
                                                    {{ $modules->active_inactive == 1 ? 'checked' : '' }}>
                                            </div>    
                                        @endcan
                                    </td>
                                     <td class="text-center">
                                        <div class="d-flex justify-content-start align-items-start gap-2">
                                            @can('subject-module.edit')
                                                <a href="{{ route('subject-module.edit', $modules->pk) }}"
                                                    class="btn btn-primary text-white btn-sm">
                                                    Edit
                                                </a>
                                            @endcan
                                            @can('subject-module.delete')
                                                <form action="{{ route('subject-module.destroy', $modules->pk) }}"
                                                    method="POST" class="m-0 delete-form"
                                                    data-status="{{ $modules->active_inactive }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger text-white btn-sm" onclick="event.preventDefault();
                                                    if(confirm('Are you sure you want to delete this Subject modules?')) {
                                                        this.closest('form').submit();
                                                    }"
                                                    {{ $modules->active_inactive == 1 ? 'disabled' : '' }}>
                                                        Delete
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>

                                </tr>
                                @endforeach
                            </tbody>


                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>

<script>
window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";
</script>
@endsection