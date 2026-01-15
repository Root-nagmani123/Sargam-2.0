@extends('admin.layouts.master')

@section('title', 'Student List - Sargam | Lal Bahadur')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="Student List"></x-breadcrum>
    <x-session_message />

    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Student List</h4>
                @if($availableCourses->isNotEmpty())
                <div class="d-flex align-items-center gap-3">
                    <label for="courseFilter" class="form-label mb-0 fw-bold">Filter by Course:</label>
                    <select id="courseFilter" class="form-select" style="width: auto; min-width: 250px;">
                        <option value="">All Courses</option>
                        @foreach($availableCourses as $course)
                            <option value="{{ $course['pk'] }}">{{ $course['course_name'] }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            <hr class="my-2">
            <div class="datatables">
                <div class="table-responsive">
                    <table class="table table-hover" id="studentListTable">
                        <thead>
                            <tr>
                                <th scope="col">Sl. No.</th>
                                <th scope="col">Student Name</th>
                                <th scope="col">OT Code</th>
                                <th scope="col">Course Name</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $index => $studentMap)
                                @php
                                    $student = $studentMap->studentMaster;
                                    $course = $studentMap->course;
                                @endphp
                                <tr data-course-id="{{ $course->pk ?? '' }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $student->display_name ?? ($student->first_name ?? '') . ' ' . ($student->last_name ?? '') }}</td>
                                    <td>{{ $student->generated_OT_code ?? 'N/A' }}</td>
                                    <td>{{ $course->course_name ?? 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('admin.dashboard.students.detail', encrypt($student->pk)) }}" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            No students found. You are not assigned as Course Coordinator or Assistant Course Coordinator for any active courses.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        let dataTable = null;
        let currentCourseFilter = '';
        
        // Custom filter function for course filtering
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'studentListTable') {
                    return true; // Don't apply to other tables
                }
                
                if (currentCourseFilter === '') {
                    return true; // Show all rows when no filter is selected
                }
                
                const row = $('#studentListTable').DataTable().row(dataIndex).node();
                const rowCourseId = $(row).attr('data-course-id');
                return rowCourseId === currentCourseFilter;
            }
        );
        
        // Initialize DataTable if there are students
        @if($students->isNotEmpty())
            dataTable = $('#studentListTable').DataTable({
                "pageLength": 25,
                "order": [[0, "asc"]],
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search students..."
                },
                "responsive": true
            });
        @endif

        // Handle course filter change
        $('#courseFilter').on('change', function() {
            currentCourseFilter = $(this).val();
            
            if (dataTable) {
                // Redraw the table with the new filter
                dataTable.draw();
            } else {
                // If DataTable is not initialized, use simple filtering
                if (currentCourseFilter === '') {
                    $('#studentListTable tbody tr').show();
                } else {
                    $('#studentListTable tbody tr').each(function() {
                        const rowCourseId = $(this).attr('data-course-id');
                        if (rowCourseId === currentCourseFilter) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                }
            }
        });

    });
</script>
@endpush

@endsection

