@forelse ($enrollments as $index => $row)
    @php
        $student = $row->studentMaster;
        $course = $row->course;
    @endphp
    <tr>
        <td>{{ $index + 1 }}</td>
        <td>{{ trim(($student->first_name ?? '') . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? '')) }}</td>
        <td>{{ $course->course_name ?? '' }}</td>
        <td>{{ $student->service->service_name ?? 'N/A' }}</td>
        <td>{{ $student->generated_OT_code ?? '-' }}</td>
        <td>
            <span class="badge {{ (int) $row->active_inactive === 1 ? 'bg-success' : 'bg-danger' }}">
                {{ (int) $row->active_inactive === 1 ? 'Active' : 'Inactive' }}
            </span>
        </td>
        <td>{{ \Carbon\Carbon::parse($row->created_date)->format('d M Y H:i') }}</td>
        <td>{{ \Carbon\Carbon::parse($row->modified_date)->format('d M Y H:i') }}</td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center text-muted">No records found.</td>
    </tr>
@endforelse


