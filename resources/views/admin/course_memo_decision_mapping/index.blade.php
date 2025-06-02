@extends('admin.layouts.master')

@section('title', 'Course Memo Decision Mapping - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Course Memo Decision Mapping" />
    <x-session_message />

    <div class="datatables">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Course Memo Decision Mapping</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('course.memo.decision.create') }}" class="btn btn-primary">+Add New Mapping</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper">
                        <table id="zero_config" class="table table-striped table-bordered table-responsive">
                            <thead>
                                <tr>
                                    <th>S.No.</th>
                                    <th>Course Name</th>
                                    <th>Memo Decision</th>
                                    <th>Memo Conclusion</th>
                                    <th>Action</th>
                                    <th>Status</th>
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
                                        <div class="d-flex justify-content-start align-items-start gap-2">
                                       
                                             <a href="{{ route('course.memo.decision.edit', ['id' => encrypt($mapping->pk)]) }}"
                                                class="btn btn-primary btn-sm">Edit</a>
                                          
                                            <form action="{{ route('course.memo.decision.delete', ['id' => encrypt($mapping->pk)]) }}"
                                                method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-danger btn-sm" onclick="event.preventDefault();
                                                    if(confirm('Are you sure you want to delete this memo type?')) {
                                                        this.closest('form').submit();
                                                    }" {{ $mapping->active_inactive == 1 ? 'disabled' : '' }}>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td> 
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="course_memo_decision_mapp" data-column="active_inactive"
                                                data-id="{{ $mapping->pk }}" data-id_column="pk"
                                                {{ $mapping->active_inactive == 1 ? 'checked' : '' }}>
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
