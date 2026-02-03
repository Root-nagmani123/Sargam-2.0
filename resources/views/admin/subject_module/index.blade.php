@extends('admin.layouts.master')

@section('title', 'Subject module - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid subject-module-index">
    <x-breadcrum title="Subject module" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row subject-module-header">
                        <div class="col-12 col-md-6">
                            <h4>Subject module</h4>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="float-md-end gap-2 subject-module-actions">
                                <a href="{{route('subject-module.create')}}" class="btn btn-primary">+ Add Subject
                                    module</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="zero_config_table">
                        <div class="table-responsive" style="overflow-x: auto">

                            <table class="table text-nowrap mb-0" id="zero_config">
                                <thead>
                                    <!-- start row -->
                                    <tr>
                                        <th class="col text-center">S.No.</th>
                                        <th class="col text-center">Stream Name</th>
                                        <th class="col text-center">Status</th>
                                        <th class="col text-center">Action</th>
                                    </tr>
                                    <!-- end row -->
                                </thead>
                                <tbody>
                                    @foreach($modules as $key => $module)
                                    <tr class="{{ $loop->odd ? 'odd' : 'even' }} ">
                                        <td class="text-center">{{ $modules->firstItem() + $key }}</td>
                                        <td class="text-center">
                                            {{ $module->module_name }}
                                        </td>
                                        <td class="text-center">
                                            <div
                                                class="form-check form-switch m-auto d-flex justify-content-center align-items-center">
                                                <input class="form-check-input status-toggle" type="checkbox"
                                                    role="switch" data-table="subject_module_master"
                                                    data-column="active_inactive" data-id="{{ $module->pk }}"
                                                    {{ $module->active_inactive == 1 ? 'checked' : '' }}>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-inline-flex align-items-center gap-2" role="group"
                                                aria-label="Subject module actions">

                                                <!-- Edit -->
                                                <a href="{{ route('subject-module.edit', $module->pk) }}"
                                                    class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                                    aria-label="Edit subject module">
                                                    <i class="material-icons material-symbols-rounded"
                                                        style="font-size:18px;" aria-hidden="true">edit</i>
                                                    <span class="d-none d-md-inline">Edit</span>
                                                </a>

                                                <!-- Delete -->
                                                @if($module->active_inactive == 1)
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                                                    disabled aria-disabled="true"
                                                    title="Cannot delete active subject module">
                                                    <i class="material-icons material-symbols-rounded"
                                                        style="font-size:18px;" aria-hidden="true">delete</i>
                                                    <span class="d-none d-md-inline">Delete</span>
                                                </button>
                                                @else
                                                <form action="{{ route('subject-module.destroy', $module->pk) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this Subject Module?');">
                                                    @csrf
                                                    @method('DELETE')

                                                    <button type="submit"
                                                        class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                                                        aria-label="Delete subject module">
                                                        <i class="material-icons material-symbols-rounded"
                                                            style="font-size:18px;" aria-hidden="true">delete</i>
                                                        <span class="d-none d-md-inline">Delete</span>
                                                    </button>
                                                </form>
                                                @endif

                                            </div>

                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- end Zero Configuration -->
</div>

<script>
window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";
</script>
@endsection