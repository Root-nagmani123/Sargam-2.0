@extends('admin.layouts.master')

@section('title', 'Active Course - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid active-course-index">
    <x-breadcrum title="Active Course"></x-breadcrum>

    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4>Active Course</h4>
            <hr class="my-2">
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
                                        class="btn btn-sm btn-primary">View Details</a></td>
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
<script src="{{ asset('js/jquery-3.7.1.min.js') }}"></script>

<script>
$(document).ready(function (){
    $('#active_course').DataTable();
});
</script>
@endpush