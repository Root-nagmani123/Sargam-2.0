@extends('admin.layouts.master')

@section('title', 'Active Course - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Active Course"></x-breadcrum>

    <div class="card">
        <div class="card-body">
            <div class="datatables">
                <div class="table-responsive">
                    <table class="table" id="active_course">
                        <thead>
                            <tr>
                                <th scope="col">Sl. No.</th>
                                <th scope="col">Course Name</th>
                                <th scope="col">Short Name</th>
                                <th scope="col">Start Date</th>
                                <th scope="col">End Date</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($active_courses as $course)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $course->course_name }}</td>
                                <td>{{ $course->couse_short_name }}</td>
                                <td>{{ \Carbon\Carbon::parse($course->start_year)->format('d M Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($course->end_date)->format('d M Y') }}</td>
                                <td><a href="{{ route('programme.show', encrypt($course->pk)) }}"
                                        class="btn btn-sm btn-primary" target="_blank">View Details</a></td>
                            </tr>
                            @endforeach



                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection

@push('scripts')
{{-- jQuery is already loaded globally by admin.layouts.footer (vendor.min.js,
     jQuery 3.7.1), which also registers DataTables on that instance. This stack
     renders AFTER the footer, so re-loading jQuery here would replace the global
     instance with a plugin-less one and break $().DataTable() below. --}}

<script>
$(document).ready(function (){
    $('#active_course').DataTable();
});
</script>
@endpush