@extends('admin.layouts.master')

@section('title', 'Attendance')

@section('content')
<div class="container-fluid">

    <x-breadcrum title="Attendance" />
    <x-session_message />
    <table id="attendanceTable" class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Programme Name</th>
                <th>Group</th>
                <th>Session</th>
                <th>Venue</th>
                <th>Topic</th>
                <th>Faculty</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @if(!empty($attendanceData))
                @foreach($attendanceData as $group)
                    {{-- @dd($group) --}}
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $group['programme_name'] ?? 'N/A' }}</td>
                        <td>{{ $group['group_name'] ?? 'N/A' }}</td>
                        <td>{{ ($group->classSession->start_time) }} - {{ ($group->classSession->end_time) }}</td>
                        <td>{{ optional($group->venue)->venue_name }}</td>
                        <td>{{ $group->subject_topic ?? 'N/A' }}</td>
                        <td>{{ optional($group->faculty)->full_name ?? 'N/A' }}</td>
                        <td><a href="javascript:void(0);">Mark Attendance</a></td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</div>
{{ $attendanceData->links() }}
@endsection