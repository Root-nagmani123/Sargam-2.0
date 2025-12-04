@extends('admin.layouts.master')

@section('title', 'Faculty Expertise')

@section('setup_content')
<div class="container-fluid">

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Faculty Expertise</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">

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
                        <table class="table text-nowrap" style="border-radius: 10px; overflow: hidden; width: 100%;">
                            <thead style="background-color: #af2910;">
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
                                        <div class="dropdown">
                                            <a href="javascript:void(0)" type="button" data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                                style="border-radius: 50%; width: 34px; height: 34px; display: flex; justify-content: center; align-items: center;">
                                                <i class="material-icons material-symbols-rounded"
                                                    style="font-size: 22px;">more_horiz</i>
                                            </a>

                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">

                                                <!-- Edit -->
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center"
                                                        href="{{ route('master.faculty.expertise.edit', ['id' => encrypt($faculty->pk)]) }}">
                                                        <i class="material-icons material-symbols-rounded text-primary me-2"
                                                            style="font-size: 20px;">edit</i>
                                                        Edit
                                                    </a>
                                                </li>

                                                <!-- Delete -->
                                                <li>
                                                    <form
                                                        action="{{ route('master.faculty.expertise.delete', ['id' => encrypt($faculty->pk)]) }}"
                                                        method="POST" class="d-inline w-100">
                                                        @csrf
                                                        @method('DELETE')

                                                        <a class="dropdown-item d-flex align-items-center text-danger {{ $faculty->active_inactive == 1 ? 'disabled' : '' }}"
                                                            href="javascript:void(0)" onclick="event.preventDefault(); 
                                if(!this.classList.contains('disabled') && confirm('Are you sure you want to delete this record?')) {
                                    this.closest('form').submit();
                                }">
                                                            <i class="material-icons material-symbols-rounded me-2"
                                                                style="font-size: 20px;">delete</i>
                                                            Delete
                                                        </a>
                                                    </form>
                                                </li>

                                            </ul>
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