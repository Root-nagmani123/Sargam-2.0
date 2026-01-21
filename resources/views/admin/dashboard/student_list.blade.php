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
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    @if($availableCourses->isNotEmpty())
                    <div class="d-flex align-items-center gap-2">
                        <label for="courseFilter" class="form-label mb-0 fw-bold">Filter by Course:</label>
                        <select id="courseFilter" class="form-select" style="width: auto; min-width: 250px;">
                            <option value="">All Courses</option>
                            @foreach($availableCourses as $course)
                                <option value="{{ $course['pk'] }}">{{ $course['course_name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="d-flex align-items-center gap-2">
                        <label for="roleFilter" class="form-label mb-0 fw-bold">Role Filter:</label>
                        <select id="roleFilter" class="form-select" style="width: auto; min-width: 250px;">
                            <option value="">All</option>
                            <option value="cc_acc">CC/ACC</option>
                            @if(isset($counsellorTypes) && $counsellorTypes->isNotEmpty())
                                @foreach($counsellorTypes as $type)
                                    <option value="{{ $type->type_pk }}">{{ $type->counsellor_type_name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="d-flex align-items-center gap-2 d-none" id="groupNameFilterContainer">
                        <label for="groupNameFilter" class="form-label mb-0 fw-bold">Cadre/Counsellor List:</label>
                        <select id="groupNameFilter" class="form-select" style="width: auto; min-width: 250px;">
                            <option value="">All</option>
                        </select>
                    </div>
                </div>
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
                                <th scope="col">Email</th>
                                <th scope="col">Cadre</th>
                                <th scope="col">Total Duty (Count)</th>
                                <th scope="col">Total Medical Exception (Count)</th>
                                <th scope="col">Total Memo</th>
                                <th scope="col">Notice (Count)</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $index => $studentMap)
                                @php
                                    $student = $studentMap->studentMaster;
                                    $course = $studentMap->course;
                                    $counsellorTypePk = $studentMap->groupMapping->groupTypeMasterCourseMasterMap->type_name ?? '';
                                    $groupPk = $studentMap->groupMapping->groupTypeMasterCourseMasterMap->pk ?? '';
                                @endphp
                                <tr data-course-id="{{ $course->pk ?? '' }}" data-counsellor-type-id="{{ $counsellorTypePk }}" data-group-pk="{{ $groupPk }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $student->display_name ?? ($student->first_name ?? '') . ' ' . ($student->last_name ?? '') }}</td>
                                    <td>{{ $student->generated_OT_code ?? 'N/A' }}</td>
                                    <td>{{ $student->email ?? 'N/A' }}</td>
                                    <td>{{ $student->cadre->cadre_name ?? 'N/A' }}</td>
                                    <td>{{ $studentMap->total_duty_count ?? 0 }}</td>
                                    <td>{{ $studentMap->total_medical_exception_count ?? 0 }}</td>
                                    <td>{{ $studentMap->total_memo_count ?? 0 }}</td>
                                    <td>{{ $studentMap->total_notice_count ?? 0 }}</td>
                                    <td>
                                        <a href="{{ route('admin.dashboard.students.detail', encrypt($student->pk)) }}" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye me-1"></i> View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">
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
        let currentCounsellorTypeFilter = '';
        let currentGroupFilter = '';
        
        // Group names data from server
        const allGroupNames = @json($groupNames ?? []);
        
        // Custom filter function for course, counsellor type, and group filtering
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'studentListTable') {
                    return true; // Don't apply to other tables
                }
                
                const row = $('#studentListTable').DataTable().row(dataIndex).node();
                const rowCourseId = $(row).attr('data-course-id');
                const rowCounsellorTypeId = $(row).attr('data-counsellor-type-id');
                const rowGroupPk = $(row).attr('data-group-pk');
                
                // Check course filter
                let courseMatch = currentCourseFilter === '' || rowCourseId === currentCourseFilter;
                
                // Check role filter
                let roleMatch = true;
                if (currentCounsellorTypeFilter === '') {
                    roleMatch = true; // Show all
                } else if (currentCounsellorTypeFilter === 'cc_acc') {
                    roleMatch = rowCounsellorTypeId !== ''; // Show only rows with counsellor type assigned
                } else {
                    roleMatch = rowCounsellorTypeId === currentCounsellorTypeFilter;
                }
                
                // Check group filter
                let groupMatch = currentGroupFilter === '' || rowGroupPk === currentGroupFilter;
                
                return courseMatch && roleMatch && groupMatch;
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
                "responsive": false
            });
        @endif
        
        // Simple redirect handler - works on any click
        $(document).on('click', '#studentListTable tbody a.btn-primary', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var url = $(this).attr('href');
            if (url) {
                window.location.href = url;
            }
            return false;
        });

        // Handle course filter change
        $('#courseFilter').on('change', function() {
            currentCourseFilter = $(this).val();
            applyFilters();
        });

        // Handle Role Filter change
        $('#roleFilter').on('change', function() {
            currentCounsellorTypeFilter = $(this).val();
            
            // Reset group filter
            currentGroupFilter = '';
            $('#groupNameFilter').val('');
            
            // Show/hide group name filter based on role selection
            // Group Name filter should ONLY appear when a specific Counsellor type (type_name pk) is selected
            // It should NOT appear when "All" or "CC/ACC" is selected
            if (currentCounsellorTypeFilter !== '' && currentCounsellorTypeFilter !== 'cc_acc') {
                // A specific counsellor type is selected - Show Group Name filter
                // Filter group names by selected counsellor type
                const filteredGroups = allGroupNames.filter(group => 
                    String(group.counsellor_type_pk) === currentCounsellorTypeFilter
                );
                
                // Populate cadre/counsellor list dropdown
                let options = '<option value="">All</option>';
                filteredGroups.forEach(function(group) {
                    options += `<option value="${group.group_pk}">${group.group_name}</option>`;
                });
                $('#groupNameFilter').html(options);
                
                // Show group name filter
                $('#groupNameFilterContainer').removeClass('d-none');
            } else {
                // "All" or "CC/ACC" is selected - Hide Group Name filter
                $('#groupNameFilterContainer').addClass('d-none');
            }
            
            applyFilters();
        });

        // Handle Group Name Filter change
        $('#groupNameFilter').on('change', function() {
            currentGroupFilter = $(this).val();
            applyFilters();
        });

        // Apply filters function
        function applyFilters() {
            if (dataTable) {
                // Redraw the table with the new filters
                dataTable.draw();
            } else {
                // If DataTable is not initialized, use simple filtering
                $('#studentListTable tbody tr').each(function() {
                    const rowCourseId = $(this).attr('data-course-id');
                    const rowCounsellorTypeId = $(this).attr('data-counsellor-type-id');
                    const rowGroupPk = $(this).attr('data-group-pk');
                    
                    let courseMatch = currentCourseFilter === '' || rowCourseId === currentCourseFilter;
                    
                    let roleMatch = true;
                    if (currentCounsellorTypeFilter === '') {
                        roleMatch = true;
                    } else if (currentCounsellorTypeFilter === 'cc_acc') {
                        roleMatch = rowCounsellorTypeId !== '';
                    } else {
                        roleMatch = rowCounsellorTypeId === currentCounsellorTypeFilter;
                    }
                    
                    let groupMatch = currentGroupFilter === '' || rowGroupPk === currentGroupFilter;
                    
                    if (courseMatch && roleMatch && groupMatch) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        }

    });
</script>
@endpush

@endsection

