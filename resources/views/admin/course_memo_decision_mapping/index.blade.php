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
                                        <div class="d-inline-flex align-items-center gap-2"
     role="group"
     aria-label="Memo decision actions">

    <!-- Edit -->
    <a href="{{ route('course.memo.decision.edit', ['id' => encrypt($mapping->pk)]) }}"
       class="btn btn-sm btn-outline-warning d-flex align-items-center gap-1"
       aria-label="Edit memo decision">
        <i class="material-icons material-symbols-rounded"
           style="font-size:18px;"
           aria-hidden="true">edit</i>
        <span class="d-none d-md-inline">Edit</span>
    </a>

    <!-- Delete -->
    @if($mapping->active_inactive == 1)
        <button type="button"
                class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1"
                disabled
                aria-disabled="true"
                title="Cannot delete active memo decision">
            <i class="material-icons material-symbols-rounded"
               style="font-size:18px;"
               aria-hidden="true">delete</i>
            <span class="d-none d-md-inline">Delete</span>
        </button>
    @else
        <form action="{{ route('course.memo.decision.delete', ['id' => encrypt($mapping->pk)]) }}"
              method="POST"
              class="d-inline">
            @csrf
            @method('DELETE')

            <button type="submit"
                    class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                    aria-label="Delete memo decision"
                    onclick="return confirm('Are you sure you want to delete this memo type?');">
                <i class="material-icons material-symbols-rounded"
                   style="font-size:18px;"
                   aria-hidden="true">delete</i>
                <span class="d-none d-md-inline">Delete</span>
            </button>
        </form>
    @endif

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