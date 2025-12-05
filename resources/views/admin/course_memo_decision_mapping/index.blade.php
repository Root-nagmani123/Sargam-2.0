@extends('admin.layouts.master')

@section('title', 'Course Memo Decision Mapping - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
<x-breadcrum title="Course Memo Decision Mapping" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Course Memo Decision Mapping</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('course.memo.decision.create') }}" class="btn btn-primary">+Add New
                                    Mapping</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div>
                        <table class="table w-100" style="border-radius: 10px; overflow: hidden;">
                            <thead style="background-color: #af2910;">
                                <tr>
                                    <th>S.No.</th>
                                    <th>Course Name</th>
                                    <th>Memo Decision</th>
                                    <th>Memo Conclusion</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mappings as $key => $mapping)
                                <tr class="{{ $loop->odd ? 'odd' : 'even' }}">
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $mapping->course->course_name ?? '-' }}</td>
                                    <td>{{ $mapping->memo->memo_type_name ?? '-' }}</td>
                                    <td>{{ $mapping->memoConclusion->discussion_name ?? '-' }}</td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="course_memo_decision_mapp" data-column="active_inactive"
                                                data-id="{{ $mapping->pk }}" data-id_column="pk"
                                                {{ $mapping->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <a href="javascript:void(0)" data-bs-toggle="dropdown" aria-expanded="false"
                                                style="border-radius: 50%; width: 36px; height: 36px; display: flex; justify-content: center; align-items: center;">
                                                <i class="material-icons material-symbols-rounded"
                                                    style="font-size: 22px;">more_horiz</i>
                                            </a>

                                            <ul class="dropdown-menu dropdown-menu-end shadow-sm">

                                                <!-- Edit -->
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center"
                                                        href="{{ route('course.memo.decision.edit', ['id' => encrypt($mapping->pk)]) }}">
                                                        <i class="material-icons material-symbols-rounded text-warning me-2"
                                                            style="font-size: 20px;">edit</i>
                                                        Edit
                                                    </a>
                                                </li>

                                                <!-- Delete -->
                                                <li>
                                                    <form
                                                        action="{{ route('course.memo.decision.delete', ['id' => encrypt($mapping->pk)]) }}"
                                                        method="POST" class="d-inline w-100">
                                                        @csrf
                                                        @method('DELETE')
                                                        <a class="dropdown-item d-flex align-items-center text-danger"
                                                            href="javascript:void(0)"
                                                            onclick="event.preventDefault(); if(confirm('Are you sure you want to delete this memo type?')) { this.closest('form').submit(); }"
                                                            {{ $mapping->active_inactive == 1 ? 'disabled' : '' }}>
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
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection