@extends('admin.layouts.master')

@section('title', 'Subject module - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
<x-breadcrum title="Subject module" />
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

                        <table class="table">
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
                                        <div class="form-check form-switch m-auto d-flex justify-content-center align-items-center">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="subject_module_master" data-column="active_inactive"
                                                data-id="{{ $module->pk }}"
                                                {{ $module->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-center">
    <div class="dropdown">
        <a href="#" data-bs-toggle="dropdown"
                aria-expanded="false">
            <i class="material-icons material-symbols-rounded" style="font-size: 24px;">more_horiz</i>
        </a>

        <ul class="dropdown-menu dropdown-menu-end shadow-sm">

            {{-- Edit --}}
            <li>
                <a class="dropdown-item d-flex align-items-center gap-2"
                   href="{{ route('subject-module.edit', $module->pk) }}">
                    <i class="material-icons material-symbols-rounded" style="font-size: 20px;">edit</i>
                    Edit
                </a>
            </li>

            {{-- Delete --}}
            @if($module->active_inactive == 1)
                <li>
                    <span class="dropdown-item text-muted d-flex align-items-center gap-2"
                          style="cursor: not-allowed;">
                        <i class="material-icons material-symbols-rounded" style="font-size: 20px;">delete</i>
                        Cannot delete (active)
                    </span>
                </li>
            @else
                <li>
                    <form action="{{ route('subject-module.destroy', $module->pk) }}" method="POST"
                          onsubmit="return confirm('Are you sure you want to delete this Subject Module?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="dropdown-item text-danger d-flex align-items-center gap-2">
                            <i class="material-icons material-symbols-rounded" style="font-size: 20px;">delete</i>
                            Delete
                        </button>
                    </form>
                </li>
            @endif

        </ul>
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