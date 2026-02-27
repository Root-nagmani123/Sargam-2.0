@extends('admin.layouts.master')

@section('title', 'Class Session Master')

@section('setup_content')
<div class="container-fluid class-session-master-index">
    <x-breadcrum title="Class Session Master" />
    <x-session_message />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row class-session-header-row align-items-center">
                        <div class="col-12 col-md-6">
                            <h4>Class Session Master</h4>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="d-flex justify-content-md-end align-items-center gap-2 mb-3 mb-md-0">

                                <!-- Add Group Mapping -->
                                <a href="{{route('master.class.session.create')}}"
                                    class="btn btn-primary d-flex align-items-center class-session-add-btn">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 20px; vertical-align: middle;">add</i>
                                    Add Class Session
                                </a>


                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="table-responsive">
                        <table class="table w-100 nowrap" id="class-session-master-table">
                            <thead>
                                <!-- start row -->
                                <tr>
                                    <th class="col">S.No.</th>
                                    <th class="col">Shift Name</th>
                                    <th class="col">Start Time</th>
                                    <th class="col">End Time</th>
                                    <th class="col">Status</th>
                                    <th class="col">Action</th>

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
                                    <td>

                                        <div class="d-inline-flex align-items-center gap-2" role="group"
                                            aria-label="Class session actions">

                                            <!-- Edit -->
                                            <a href="{{ route('master.class.session.edit', ['id' => encrypt($classSession->pk)]) }}"
                                                class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                                aria-label="Edit class session">
                                                <span class="material-symbols-rounded fs-6"
                                                    aria-hidden="true">edit</span>
                                                <span class="d-none d-md-inline">Edit</span>
                                            </a>

                                            <!-- Delete -->
                                            @if($classSession->active_inactive == 1)
                                            <button type="button"
                                                class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                                                disabled aria-disabled="true"
                                                title="Cannot delete active class session">
                                                <span class="material-symbols-rounded fs-6"
                                                    aria-hidden="true">delete</span>
                                                <span class="d-none d-md-inline">Delete</span>
                                            </button>
                                            @else
                                            <form
                                                action="{{ route('master.class.session.delete', ['id' => encrypt($classSession->pk)]) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Are you sure you want to delete this record?');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                                                    aria-label="Delete class session">
                                                    <span class="material-symbols-rounded fs-6"
                                                        aria-hidden="true">delete</span>
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