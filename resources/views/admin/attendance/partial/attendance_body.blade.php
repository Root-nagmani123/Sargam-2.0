@forelse($attendanceGroup as $index => $group)
    <tr>
        <td>{{ $offset + $loop->iteration }}</td>
        <td>{{ $group['programme_name'] ?? 'N/A' }}</td>
        <td>{{ $group['mannual_starttime'] ?? 'N/A' }}</td>
        <td>{{ $group['session_time'] ?? 'N/A' }}</td>
        <td>{{ $group['venue_name'] ?? 'N/A' }}</td>
        <td>{{ $group['subject_topic'] ?? 'N/A' }}</td>
        <td>{{ $group['faculty_name'] ?? 'N/A' }}</td>
        <td>
            <a href="javascript:void(0)" class="btn btn-success hstack gap-1">
                <i class="material-icons menu-icon">
                    check_circle
                </i>
                Mark
            </a>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center text-muted">No records found.</td>
    </tr>
@endforelse
