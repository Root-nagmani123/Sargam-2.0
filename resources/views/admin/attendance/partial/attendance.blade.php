{{-- {{ route('admin.attendance.mark', $group['id']) }} --}}
@if(!empty($attendanceGroup))
    @foreach($attendanceGroup as $group)

        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $group['programme_name'] ?? 'N/A' }}</td>
            {{-- <td>{{ $group['group_name'] ?? 'N/A' }}</td> --}}
            <td>{{ $group['mannual_starttime'] ?? 'N/A' }}</td>
            <td>{{ $group['session_time'] ?? 'N/A' }}</td>
            <td>{{ $group['venue_name'] ?? 'N/A' }}</td>
            <td>{{ $group['subject_topic'] ?? 'N/A' }}</td>
            <td>{{ $group['faculty_name'] ?? 'N/A' }}</td>
            <td><a href="javascript:void(0);">Mark Attendance</a></td>
        </tr>
    @endforeach
@endif