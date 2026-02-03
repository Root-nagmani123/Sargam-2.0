@extends('admin.layouts.master')

@section('title', 'Faculty Expertise')

@section('setup_content')
<div class="container-fluid faculty-expertise-index">
    <x-breadcrum title="Faculty Expertise"></x-breadcrum>
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row faculty-expertise-header-row">
                        <div class="col-6">
                            <h4>Faculty Expertise</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2 faculty-expertise-actions">

                                <!-- Add Group Mapping -->
                                <a href="{{route('master.faculty.expertise.create')}}"
                                    class="btn btn-primary d-flex align-items-center">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">add</i>
                                    Add Faculty Expertise
                                </a>


                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table text-nowrap" id="faculty-expertise-table">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th>S.No.</th>
                                    <th>Faculty Expertise</th>
                                    <th>Status</th>
                                    <th>Action</th>

                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @if (!empty($faculties) && count($faculties) > 0)
                                @foreach ($faculties as $index => $faculty)
                                <tr class="odd">
                                    <td>{{ $faculties->firstItem() + $index }}</td>
                                    <td>{{ $faculty->expertise_name ?? 'N/A' }}</td>
                                    <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="faculty_expertise_master" data-column="active_inactive"
                                                data-id="{{ $faculty->pk }}"
                                                {{ $faculty->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-inline-flex align-items-center gap-2" role="group"
                                            aria-label="Faculty expertise actions">

                                            <!-- Edit -->
                                            <a href="{{ route('master.faculty.expertise.edit', ['id' => encrypt($faculty->pk)]) }}"
                                                class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                                aria-label="Edit faculty expertise">
                                                <i class="material-icons material-symbols-rounded"
                                                    style="font-size:18px;" aria-hidden="true">edit</i>
                                                <span class="d-none d-md-inline">Edit</span>
                                            </a>

                                            <!-- Delete -->
                                            @if($faculty->active_inactive == 1)
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                                                disabled aria-disabled="true" title="Cannot delete active record">
                                                <i class="material-icons material-symbols-rounded"
                                                    style="font-size:18px;" aria-hidden="true">delete</i>
                                                <span class="d-none d-md-inline">Delete</span>
                                            </button>
                                            @else
                                            <form
                                                action="{{ route('master.faculty.expertise.delete', ['id' => encrypt($faculty->pk)]) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this record?');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                                                    aria-label="Delete faculty expertise">
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
                                @else

                                @endif

                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">

                            <div class="text-muted small mb-2">
                                Showing {{ $faculties->firstItem() ?? 0 }}
                                to {{ $faculties->lastItem() }}
                                of {{ $faculties->total() }} items
                            </div>

                            <div>
                                {{ $faculties->links('vendor.pagination.custom') }}
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