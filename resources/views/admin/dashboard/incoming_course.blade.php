@extends('admin.layouts.master')

@section('title', 'Incoming Course - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Incoming Course"></x-breadcrum>

    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4>Incoming Course</h4>
            <hr class="my-2">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col">Course Name</th>
                            <th scope="col">Short Name</th>
                            <th scope="col">Start Date</th>
                            <th scope="col">End Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($active_courses as $course)
                        <tr>
                            <td>{{ $course->course_name }}</td>
                            <td>{{ $course->couse_short_name }}</td>
                            <td>{{ \Carbon\Carbon::parse($course->start_year)->format('d M Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($course->end_date)->format('d M Y') }}</td>
                        </tr>
                        @endforeach



                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


@endsection