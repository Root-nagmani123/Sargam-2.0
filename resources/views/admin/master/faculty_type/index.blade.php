@extends('admin.layouts.master')

@section('title', 'Faculty Type')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Faculty Type" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Faculty Type</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">

                                <!-- Add Group Mapping -->
                                <a href="{{route('master.faculty.type.master.create')}}"
                                    class="btn btn-primary d-flex align-items-center">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">add</i>
                                    Add Faculty Type
                                </a>


                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table text-nowrap w-100">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th>S.No.</th>
                                    <th>Faculty Type</th>
                                    <th>Short Name</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @if (!empty($facultyTypes) && count($facultyTypes) > 0)
                                @foreach ($facultyTypes as $index => $facultyType)
                                <tr class="odd">
                                    <td>{{ $facultyTypes->firstItem() + $index }}</td>
                                    <td>{{ $facultyType->faculty_type_name ?? 'N/A' }}</td>
                                    <td>{{ $facultyType->shot_faculty_type_name ?? 'N/A' }}</td>
                                    <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="faculty_type_master" data-column="active_inactive"
                                                data-id="{{ $facultyType->pk }}"
                                                {{ $facultyType->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-nowrap">

                                        <!-- 3-Dots Dropdown -->
                                        <div class="d-inline-flex align-items-center gap-2" role="group"
                                            aria-label="Faculty type actions">

                                            <!-- Edit -->
                                            <a href="{{ route('master.faculty.type.master.edit', ['id' => encrypt($facultyType->pk)]) }}"
                                                class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                                aria-label="Edit faculty type">
                                                <i class="material-icons material-symbols-rounded"
                                                    style="font-size:18px;" aria-hidden="true">edit</i>
                                                <span class="d-none d-md-inline">Edit</span>
                                            </a>

                                            <!-- Delete -->
                                            @if($facultyType->active_inactive == 1)
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                                                disabled aria-disabled="true" title="Cannot delete active Faculty Type">
                                                <i class="material-icons material-symbols-rounded"
                                                    style="font-size:18px;" aria-hidden="true">delete</i>
                                                <span class="d-none d-md-inline">Delete</span>
                                            </button>
                                            @else
                                            <form
                                                action="{{ route('master.faculty.type.master.delete', ['id' => encrypt($facultyType->pk)]) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this record?');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                                                    aria-label="Delete faculty type" title="Delete Faculty Type">
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
                                @endif
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                            <div class="text-muted small mb-2">
                                Showing {{ $facultyTypes->firstItem() ?? 0 }}
                                to {{ $facultyTypes->lastItem() }}
                                of {{ $facultyTypes->total() }} items
                            </div>

                            <div>
                                {{ $facultyTypes->links('vendor.pagination.custom') }}
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end Zero Configuration -->
    </div>
</div>
@endsection