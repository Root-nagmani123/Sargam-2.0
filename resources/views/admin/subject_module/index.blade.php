@extends('admin.layouts.master')

@section('title', 'Subject module - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Subject module</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{route('subject-module.create')}}" class="btn btn-primary">+ Add Subject
                                    module</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">

                        <table class="table table-bordered text-nowrap align-middle dataTable">
                            <thead style="background-color: #af2910;">
                                <!-- start row -->
                                <tr>
                                    <th class="col text-center">S.No.</th>
                                    <th class="col text-center">Stream Name</th>
                                    <th class="col text-center">Action</th>
                                    <th class="col text-center">Status</th>
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
                                        <div class="d-flex justify-content-center align-items-center gap-2">
                                            <a href="{{ route('subject-module.edit', $module->pk) }}">
                                                <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">edit</i>
                                            </a>
                                            <form action="{{ route('subject-module.destroy', $module->pk) }}"
                                                method="POST" class="m-0 delete-form"
                                                data-status="{{ $module->active_inactive }}">
                                                @csrf
                                                @method('DELETE')
                                                <a href="javascript:void(0)" onclick="event.preventDefault();
                                                if(confirm('Are you sure you want to delete this Subject modules?')) {
                                                    this.closest('form').submit();
                                                }" {{ $module->active_inactive == 1 ? 'disabled' : '' }}>
                                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">delete</i>
                                                </a>
                                            </form>

                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch m-auto d-flex justify-content-center align-items-center">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="subject_module_master" data-column="active_inactive"
                                                data-id="{{ $module->pk }}"
                                                {{ $module->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                  

                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Bootstrap 5 Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="text-muted">
                                Showing {{ $modules->firstItem() ?? 0 }} to {{ $modules->lastItem() ?? 0 }} of {{ $modules->total() }} entries
                            </div>
                            <div>
                                {{ $modules->links('pagination::bootstrap-5') }}
                            </div>
                        </div>

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