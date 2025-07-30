@extends('admin.layouts.master')

@section('title', 'Subject - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Subject module" />
    <x-session_message />
    <div class="card dataTables_wrapper" id="alt_pagination_wrapper" style="border-left:4px solid #004a93;">
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <h4>Subject</h4>
                </div>
                @can('subject.create')
                    <div class="col-6">
                        <div class="float-end gap-2">
                            <a href="{{ route('subject.create') }}" class="btn btn-primary">+ Add Subject</a>
                        </div>
                    </div>
                @endcan
                
            </div>
            <hr>
            <div class="table-responsive">
                
                <table class="table table-bordered text-nowrap table-striped">
                    <thead>
                        <tr>
                            <th class="col">S.No.</th>
                            <th class="col">Major Subject Name</th>
                            <th class="col">Short Name</th>
                            <th class="col">Subject Module</th>
                            <th class="col">Action</th>
                            <th class="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subjects as $key => $subject)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td>{{ $subject->subject_name }}</td>
                            <td>{{ $subject->sub_short_name }}</td>
                            <td>{{ $subject->module->module_name ?? 'N/A' }}</td>
                            <td>
                                <div class="d-flex justify-content-start align-items-start gap-2">
                                    @can('subject.edit')
                                        <a href="{{ route('subject.edit', $subject->pk) }}"
                                        class="btn btn-primary text-white btn-sm">Edit</a>
                                    @endcan
                                    @can('subject.delete')
                                        <form action="{{ route('subject.destroy', $subject->pk) }}" method="POST"
                                            class="m-0 delete-form" data-status="{{ $subject->active_inactive }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger text-white btn-sm" onclick="event.preventDefault();
                                                    if(confirm('Are you sure you want to delete this subject?')) {
                                                        this.closest('form').submit();
                                                    }"
                                                    {{ $subject->active_inactive == 1 ? 'disabled' : '' }}>
                                                Delete
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                            <td>
                                @can('subject.active_inactive')
                                    <div class="form-check form-switch">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-table="subject_master" data-column="active_inactive"
                                            data-id="{{ $subject->pk }}"
                                            {{ $subject->active_inactive == 1 ? 'checked' : '' }}>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";
</script>
@endsection