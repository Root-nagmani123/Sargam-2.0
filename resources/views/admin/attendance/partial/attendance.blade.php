@extends('admin.layouts.master')

@section('title', 'Attendance')

@section('setup_content')
<div class="container-fluid">
    <table id="attendanceTable" class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Programme Name</th>
                <th>Date</th>
                <th>Session</th>
                <th>Venue</th>
                <th>Topic Details</th>
                <th>Name of Faculty</th>
                <th>Attendance</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($attendanceData))
                @foreach($attendanceData as $group)
                    {{-- @dd($group) --}}
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $group->course->course_name }}</td>
                        <td>{{ optional($group->timetable)->mannual_starttime }}</td>
                        <td>{{ optional($group->timetable)->classSession->start_time }} - {{ optional($group->timetable)->classSession->end_time }}</td>
                        <td>{{ optional($group->timetable)->venue->venue_name }}</td>
                        <td>{{ optional($group->timetable)->subject_topic }}</td>
                        <td>{{ optional($group->timetable)->faculty->full_name }}</td>
                        <th><a href="javascript:void(0);">Mark Attendance</a></th>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</div>
{{ $attendanceData->links() }}
@endsection