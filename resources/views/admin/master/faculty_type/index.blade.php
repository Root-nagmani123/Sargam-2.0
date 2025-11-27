@extends('admin.layouts.master')

@section('title', 'Faculty Type')

@section('content')
<div class="container-fluid">
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

                                <!-- Search Expand -->
                                <div class="search-expand d-flex align-items-center">
                                    <a href="javascript:void(0)" id="searchToggle">
                                        <i class="material-icons menu-icon material-symbols-rounded"
                                            style="font-size: 24px;">search</i>
                                    </a>

                                    <input type="text" class="form-control search-input ms-2" id="searchInput"
                                        placeholder="Search…" aria-label="Search">
                                </div>

                            </div>
                        </div>
                    </div>
                    <hr>
                    <div>
                        <table class="table text-nowrap w-100" style="border-radius: 10px; overflow: hidden;">
                            <thead style="background-color: #af2910;">
                                <!-- start row -->
                                <tr>
                                    <th>S.No.</th>
                                    <th>Full Name</th>
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
                                        <div class="dropdown">

                                            <a href="javascript:void(0)" type="button"
                                                id="actionMenu{{ $facultyType->pk }}" data-bs-toggle="dropdown"
                                                aria-expanded="false" aria-label="Actions">
                                                <i class="material-icons material-symbols-rounded me-2"
                                                    style="font-size: 20px;">more_horiz</i>
                                            </a>

                                            <ul class="dropdown-menu dropdown-menu-end"
                                                aria-labelledby="actionMenu{{ $facultyType->pk }}"
                                                style="min-width: 150px;">

                                                <!-- Edit -->
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center gap-2"
                                                        href="{{ route('master.faculty.type.master.edit', ['id' => encrypt($facultyType->pk)]) }}">
                                                        <i class="material-icons material-symbols-rounded me-2"
                                                            style="font-size: 20px;">edit</i> Edit
                                                    </a>
                                                </li>

                                                <!-- Delete -->
                                                <li>
                                                    <form
                                                        action="{{ route('master.faculty.type.master.delete', ['id' => encrypt($facultyType->pk)]) }}"
                                                        method="POST"
                                                        onsubmit="return confirm('Are you sure you want to delete this record?');">

                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="submit"
                                                            class="dropdown-item text-danger d-flex align-items-center gap-2"
                                                            {{ $facultyType->active_inactive == 1 ? 'disabled' : '' }}
                                                            title="{{ $facultyType->active_inactive == 1 ? 'Cannot delete active Faculty Type' : 'Delete Faculty Type' }}">
                                                            <i class="material-icons material-symbols-rounded me-2"
                                                                style="font-size: 20px;">delete</i> Delete
                                                        </button>
                                                    </form>
                                                </li>

                                            </ul>

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