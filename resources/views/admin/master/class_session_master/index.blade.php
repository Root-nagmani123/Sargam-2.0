@extends('admin.layouts.master')

@section('title', 'Class Session Master')

@section('setup_content')
<div class="container-fluid">
<x-breadcrum title="Class Session Master" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Class Session Master</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">

                                <!-- Add Group Mapping -->
                                <a href="{{route('master.class.session.create')}}"
                                    class="btn btn-primary d-flex align-items-center">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 20px; vertical-align: middle;">add</i>
                                    Add Class Session
                                </a>


                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table w-100 nowrap">
                            <thead class="table-light">
                                <!-- start row -->
                                <tr>
                                    <th style="background-color: #af2910; font-weight: 600;">S.No.</th>
                                    <th style="background-color: #af2910; font-weight: 600;">Shift Name</th>
                                    <th style="background-color: #af2910; font-weight: 600;">Start Time</th>
                                    <th style="background-color: #af2910; font-weight: 600;">End Time</th>
                                    <th style="background-color: #af2910; font-weight: 600;">Status</th>
                                    <th style="background-color: #af2910; font-weight: 600;">Action</th>

                                </tr>
                                <!-- end row -->
                            </thead>
                            <tbody>
                                @if (!empty($classSessionMaster) && count($classSessionMaster) > 0)
                                @foreach ($classSessionMaster as $index => $classSession)
                                <tr class="odd">
                                    <td>{{ $classSessionMaster->firstItem() + $index }}</td>
                                    <td>{{ $classSession->shift_name ?? 'N/A' }}</td>
                                    <td>{{ $classSession->start_time ?? 'N/A' }}</td>
                                    <td>{{ $classSession->end_time ?? 'N/A' }}</td>
                                    <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="class_session_master" data-column="active_inactive"
                                                data-id="{{ $classSession->pk }}"
                                                {{ $classSession->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-center">

                                        <div class="dropdown">
                                            <a href="javascript:void(0)" class="btn px-2"
                                                id="actionMenu{{ $classSession->pk }}" data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                                <span class="material-symbols-rounded fs-5">more_horiz</span>
                                            </a>

                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm"
                                                aria-labelledby="actionMenu{{ $classSession->pk }}">

                                                <!-- Edit -->
                                                <li>
                                                    <a href="{{ route('master.class.session.edit', ['id' => encrypt($classSession->pk)]) }}"
                                                        class="dropdown-item d-flex align-items-center gap-2">
                                                        <span
                                                            class="material-symbols-rounded text-primary fs-6">edit</span>
                                                        Edit
                                                    </a>
                                                </li>

                                                <!-- Delete -->
                                                <li>
                                                    <form
                                                        action="{{ route('master.class.session.delete', ['id' => encrypt($classSession->pk)]) }}"
                                                        method="POST" class="d-inline">

                                                        @csrf
                                                        @method('DELETE')

                                                        <button type="button"
                                                            class="dropdown-item d-flex align-items-center gap-2 text-danger"
                                                            onclick="event.preventDefault();
                                if({{ $classSession->active_inactive }} == 1) return;
                                if(confirm('Are you sure you want to delete this record?')) {
                                    this.closest('form').submit();
                                }" {{ $classSession->active_inactive == 1 ? 'disabled' : '' }}>
                                                            <span class="material-symbols-rounded fs-6">delete</span>
                                                            Delete
                                                        </button>

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
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                Showing {{ $classSessionMaster->firstItem() ?? 0 }} to
                                {{ $classSessionMaster->lastItem() ?? 0 }} of {{ $classSessionMaster->total() }} entries
                            </div>
                            <div>
                                {{ $classSessionMaster->links('pagination::bootstrap-5') }}
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