@extends('admin.layouts.master')

@section('title', 'Subject - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Subject module" />
    <x-session_message />

    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Subject</h4>
                        </div>
                        <div class="col-6">
                            <div class="float-end gap-2">
                                <a href="{{ route('subject.create') }}" class="btn btn-primary">+ Add Subject</a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div id="zero_config_wrapper" class="dataTables_wrapper"  style="max-height: 500px; overflow-y: auto;">

                        <table id="zero_config"
                            class="table table-striped table-bordered text-nowrap align-middle dataTable"
                            aria-describedby="zero_config_info">
                            <thead>
                                <tr>
                                    <th class="sorting sorting_asc" tabindex="0" aria-controls="zero_config" rowspan="1"
                                        colspan="1" aria-sort="ascending"
                                        aria-label="S.No.: activate to sort column descending" style="width: 80px;">
                                        S.No.</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Major Subject Name: activate to sort column ascending"
                                        style="width: 200px;">
                                        Major Subject Name</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Short Name: activate to sort column ascending"
                                        style="width: 150px;">
                                        Short Name</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Topic Name: activate to sort column ascending"
                                        style="width: 150px;">
                                        Topic Name</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Subject Module: activate to sort column ascending"
                                        style="width: 180px;">
                                        Subject Module</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Action: activate to sort column ascending" style="width: 120px;">
                                        Action</th>
                                    <th class="sorting" tabindex="0" aria-controls="zero_config" rowspan="1" colspan="1"
                                        aria-label="Status: activate to sort column ascending" style="width: 100px;">
                                        Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach($subjects as $key => $subject)
                                <tr>
                                    <td>{{ $key + 1 }}</td>

                                    <td>{{ $subject->subject_name }}</td>

                                    <td>{{ $subject->sub_short_name }}</td>

                                    <td>{{ $subject->Topic_name }}</td>

                                    <td>{{ $subject->module->module_name ?? 'N/A' }}</td>

                                    <td>
                                        <div class="d-flex justify-content-start align-items-start gap-2">
                                            <a href="{{ route('subject.edit', $subject->pk) }}"
                                                class="btn btn-success text-white btn-sm">Edit</a>
                                            <form action="{{ route('subject.destroy', $subject->pk) }}" method="POST"
                                                class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger text-white btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
<td>
                                    <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" 
                                                type="checkbox" 
                                                role="switch"
                                                data-table="subject_master" 
                                                data-column="active_inactive" 
                                                data-id="{{ $subject->pk }}"
                                                {{ $subject->active_inactive == 1 ? 'checked' : '' }}>
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
        <!-- end Zero Configuration -->
    </div>
</div>

<script>
    window.statusToggleUrl = "{{ route('admin.toggleStatus') }}";
</script>
@endsection