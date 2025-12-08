@if($enrollments->count() > 0)
    @foreach($enrollments as $index => $enrollment)
        @php
            $student = $enrollment->studentMaster;
            $course = $enrollment->course;
            // Check if course exists and is active (active_inactive == 1)
            $canEdit = $course && $course->active_inactive == 1 && $enrollment->active_inactive == 1;
        @endphp
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>
                {{ $student->display_name ?? 'N/A' }}
                @if($student->email)
                    <br><small class="text-muted">{{ $student->email }}</small>
                @endif
            </td>
            <td>
                {{ $course->course_name ?? 'N/A' }}
              
            </td>
            <td>{{ $student->service->service_name ?? 'N/A' }}</td>
            <td>
                @if($student->generated_OT_code)
                    <span class="#">{{ $student->generated_OT_code }}</span>
                @else
                    N/A
                @endif
            </td>
            <td>
                @if($student->rank)
                    <span class="#">{{ $student->rank }}</span>
                @else
                    N/A
                @endif
            <td>
                <span class="badge {{ $enrollment->active_inactive ? 'bg-success' : 'bg-secondary' }}">
                    {{ $enrollment->active_inactive ? 'Active' : 'Inactive' }}
                </span>
            </td>
            <td>{{ $enrollment->created_date ? date('d-m-Y', strtotime($enrollment->created_date)) : 'N/A' }}</td>
            <td>{{ $enrollment->modified_date ? date('d-m-Y', strtotime($enrollment->modified_date)) : 'N/A' }}</td>
            <td>
                @if($canEdit)
                    <a href="{{ route('enrollment.edit', $student->pk) }}" 
                       class="btn btn-sm btn-warning edit-btn" 
                       title="Edit Enrollment">
                        <i class="fas fa-edit me-1"></i> Edit
                    </a>
                @else
                    @if($course && $course->active_inactive == 0)
                        <span class="badge bg-danger" title="Course is archived">Archived</span>
                    @elseif($enrollment->active_inactive == 0)
                        <span class="badge bg-secondary" title="Enrollment is inactive">Inactive</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                @endif
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="9" class="text-center text-muted">No records found</td>
    </tr>
@endif