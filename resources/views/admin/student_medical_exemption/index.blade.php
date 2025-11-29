@extends('admin.layouts.master')

@section('title', 'Student Medical Exemption - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Student Medical Exemption</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">

                                <!-- Add Group Mapping -->
                                <a href="{{route('student.medical.exemption.create')}}"
                                    class="btn btn-primary d-flex align-items-center">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">add</i>
                                    Add Student Medical Exemption
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
                    <div class="table-responsive">
                        <table class="table table-bordered w-100">
                            <thead style="background-color: #af2910;">
                                <tr>
                                    <th class="col">#</th>
                                    <th class="col">Student</th>
                                    <th class="col">Category</th>
                                    <th class="col">Medical Speciality</th>
                                    <th class="col">From-To</th>
                                    <th class="col">OPD Type</th>
                                    <th class="col">Action</th>
                                    <th class="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $index => $row)
                                <tr>
                                    <td>{{ $records->firstItem() + $index }}</td>
                                    <td>{{ $row->student->display_name ?? 'N/A' }}</td>
                                    <td>{{ $row->category->exemp_category_name ?? 'N/A' }}</td>
                                    <td>{{ $row->speciality->speciality_name ?? 'N/A' }}</td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($row->from_date)->format('d-m-Y') }}
                                        to
                                        {{ \Carbon\Carbon::parse($row->to_date)->format('d-m-Y') }}
                                    </td>

                                    <td>{{ $row->opd_category }}</td>
                                    <td>
                                        <a
                                            href="{{ route('student.medical.exemption.edit', ['id' => encrypt(value: $row->pk)])  }}"><i
                                                class="material-icons menu-icon material-symbols-rounded"
                                                style="font-size: 24px;">edit</i></a>

                                        <form
                                            title="{{ $row->active_inactive == 1 ? 'Cannot delete active course group type' : 'Delete' }}"
                                            action="{{ route('student.medical.exemption.delete', 
                                                    ['id' => encrypt($row->pk)]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <a href="javascript:void(0)" onclick="event.preventDefault(); 
                                                        if(confirm('Are you sure you want to delete this record?')) {
                                                            this.closest('form').submit();
                                                        }" {{ $row->active_inactive == 1 ? 'disabled' : '' }}>
                                                <i class="material-icons menu-icon material-symbols-rounded"
                                                    style="font-size: 24px;">delete</i>
                                            </a>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="student_medical_exemption" data-column="active_inactive"
                                                data-id="{{ $row->pk }}"
                                                {{ $row->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                </tr>
                                @empty

                                @endforelse
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                Showing {{ $records->firstItem() ?? 0 }} to {{ $records->lastItem() ?? 0 }} of
                                {{ $records->total() }} entries
                            </div>
                            <div>
                                {{ $records->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection