@extends('admin.layouts.master')

@section('title', 'My Attendance')

@section('css')

<style>
   
/* Button styling */
.btn-group[role="group"] .btn {
    transition: all 0.3s ease-in-out;
    border-radius: 0;
}

.btn-group[role="group"] .btn:first-child {
    border-top-left-radius: 50rem !important;
    border-bottom-left-radius: 50rem !important;
}

.btn-group[role="group"] .btn:last-child {
    border-top-right-radius: 50rem !important;
    border-bottom-right-radius: 50rem !important;
}

.btn-group .btn:hover {
    transform: translateY(-1px);
}

.btn-group .btn.active {
    box-shadow: inset 0 0 0 2px #fff, 0 0 0 3px rgba(0, 123, 255, 0.3);
}

.btn:focus-visible {
    outline: 3px solid #0d6efd;
    outline-offset: 2px;
}

.btn-outline-secondary {
    color: #333;
    border-color: #999;
}

.btn-outline-secondary:hover {
    background-color: #f8f9fa;
    border-color: #666;
}

/* Responsive table styling */
@media (max-width: 1200px) {
    .table {
        font-size: 0.9rem;
    }
    
    .table th, .table td {
        padding: 0.6rem 0.4rem;
    }
    
    .badge {
        font-size: 0.8rem;
        padding: 0.4rem 0.7rem;
    }
}

@media (max-width: 991px) {
    .row.g-3, .row.g-4 {
        gap: 1rem !important;
    }
    
    .col-lg-5, .col-lg-2, .col-md-6, .col-md-4 {
        width: 100%;
        margin-bottom: 1rem;
    }
    
    .table {
        font-size: 0.85rem;
    }
    
    .table th, .table td {
        padding: 0.5rem 0.3rem;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 0.35rem 0.6rem;
    }
    
    .btn-sm {
        padding: 0.35rem 0.6rem;
        font-size: 0.75rem;
    }
    
    .btn-lg {
        padding: 0.6rem 1rem;
        font-size: 0.95rem;
    }
    
    .d-flex.justify-content-between {
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .form-control-lg, .form-select-lg {
        font-size: 0.95rem;
        padding: 0.6rem;
    }
    
    h4.card-title {
        font-size: 1.1rem;
    }
    
    h5 {
        font-size: 1rem;
    }
}

@media (max-width: 768px) {
    .card-body {
        padding: 1rem !important;
    }
    
    .card-header {
        padding: 0.75rem 1rem !important;
    }
    
    .table {
        font-size: 0.8rem;
    }
    
    .table th, .table td {
        padding: 0.4rem 0.2rem;
        word-break: break-word;
    }
    
    h4.card-title {
        font-size: 1rem;
    }
    
    h5 {
        font-size: 0.95rem;
    }
    
    .btn {
        padding: 0.4rem 0.8rem;
        font-size: 0.85rem;
    }
    
    .btn-lg {
        padding: 0.5rem 0.9rem;
        font-size: 0.9rem;
    }
    
    .btn-group {
        width: 100%;
        flex-wrap: wrap;
    }
    
    .btn-group .btn {
        flex: 1;
        min-width: 140px;
    }
    
    .form-label {
        font-size: 0.9rem;
        margin-bottom: 0.4rem;
    }
    
    .form-control,
    .form-select,
    .form-control-lg,
    .form-select-lg {
        font-size: 0.9rem;
        padding: 0.5rem;
    }
    
    .col-lg-2.d-flex,
    .col-md-12.d-flex {
        width: 100%;
        flex-direction: row;
        gap: 0.5rem;
    }
    
    .col-lg-2.d-flex .btn {
        flex: 1;
    }
    
    .badge {
        font-size: 0.7rem;
        padding: 0.3rem 0.5rem;
    }
    
    .bi {
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    .d-flex.justify-content-between.align-items-center {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 0.75rem;
    }
    
    .table {
        font-size: 0.75rem;
    }
    
    .table th, .table td {
        padding: 0.3rem 0.15rem;
        white-space: normal;
        min-width: 80px;
    }
    
    .table th:first-child,
    .table td:first-child {
        position: sticky;
        left: 0;
        background: white;
        z-index: 1;
    }
    
    .table thead th {
        position: sticky;
        top: 0;
        background: #f8f9fa;
        z-index: 2;
    }
    
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .col-md-4 {
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
    }
    
    .btn {
        padding: 0.35rem 0.7rem;
        font-size: 0.8rem;
        width: 100%;
    }
    
    .btn-sm {
        padding: 0.3rem 0.5rem;
        font-size: 0.75rem;
    }
    
    .btn-lg {
        padding: 0.45rem 0.8rem;
        font-size: 0.85rem;
    }
    
    .badge {
        font-size: 0.65rem;
        padding: 0.25rem 0.45rem;
    }
    
    strong {
        font-size: 0.85rem;
    }
    
    .text-primary {
        font-size: 0.85rem;
    }
    
    .btn-group[role="group"] {
        width: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .btn-group[role="group"] .btn {
        font-size: 0.8rem;
        padding: 0.5rem;
        border-radius: 0 !important;
        width: 100%;
    }
    
    .btn-group[role="group"] .btn:first-child {
        border-top-left-radius: 0.5rem !important;
        border-top-right-radius: 0.5rem !important;
        border-bottom-left-radius: 0 !important;
    }
    
    .btn-group[role="group"] .btn:last-child {
        border-top-right-radius: 0 !important;
        border-bottom-left-radius: 0.5rem !important;
        border-bottom-right-radius: 0.5rem !important;
    }
    
    .form-label {
        font-size: 0.85rem;
    }
    
    .form-control,
    .form-select,
    .form-control-lg,
    .form-select-lg {
        font-size: 0.85rem;
        padding: 0.45rem;
    }
    
    .table-responsive {
        margin-bottom: 1rem;
    }
    
    .text-muted.small {
        font-size: 0.7rem;
    }
    
    .bi {
        font-size: 0.85rem;
    }
    
    .card-header h4,
    .card-header h5 {
        font-size: 0.9rem;
    }
    
    hr {
        margin: 1rem 0;
    }
    
    .rounded-4 {
        border-radius: 0.75rem !important;
    }
    
    .fw-bold {
        font-weight: 600 !important;
    }
    
    .col-lg-2.d-flex {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .col-lg-2.d-flex .btn {
        width: 100%;
        margin: 0;
    }
}

/* Ensure pagination responsive */
.pagination {
    flex-wrap: wrap;
    gap: 0.25rem;
}

.pagination .page-link {
    padding: 0.5rem 0.75rem;
}

@media (max-width: 576px) {
    .pagination .page-link {
        padding: 0.35rem 0.5rem;
        font-size: 0.75rem;
    }
}

.table-responsive {
    -webkit-overflow-scrolling: touch;
    min-height: 0;
}

/* Accessibility improvements */
.form-control:focus,
.form-select:focus,
.btn:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Smooth transitions */
.btn, .badge, .card {
    transition: all 0.2s ease-in-out;
}
</style>
@endsection

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="My Attendance Record" />
    <x-session_message />

    {{-- Student Information Header --}}
    <div class="card shadow mb-4" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <strong>Course Name:</strong>
                    <span class="text-primary">
                        {{ $course->course_name ?? 'N/A' }}
                    </span>
                </div>
                <div class="col-md-4">
                    <strong>Student Name:</strong>
                    <span class="text-primary">
                        {{ $student->display_name ?? 'N/A' }}
                    </span>
                </div>
                <div class="col-md-4">
                    <strong>OT Code:</strong>
                    <span class="text-primary">
                        {{ $student->generated_OT_code ?? 'N/A' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Form --}}
   <div class="card shadow-lg mb-4 border-0 rounded-4">
        <div class="card-header bg-primary text-white p-3 rounded-top-4">
            <h5 class="mb-0 fw-bold d-flex align-items-center text-white">
                <i class="bi bi-funnel-fill me-2"></i> Attendance Filters
            </h5>
        </div>
        <div class="card-body p-4">
            <form method="GET" action="{{ route('attendance.OT.student_mark.student', [
            'group_pk' => $group_pk,
            'course_pk' => $course_pk,
            'timetable_pk' => $timetable_pk,
            'student_pk' => $student_pk
        ]) }}" id="filterForm">
                <input type="hidden" name="archive_mode" id="archive_mode_input" value="{{ $archiveMode ?? 'active' }}">

                <div class="row mb-4">
                    <div class="col-12 text-end">
                        <label class="form-label d-block text-muted small fw-semibold">View Mode:</label>
                        <div class="btn-group border border-1 border-primary rounded-pill overflow-hidden" role="group"
                            aria-label="Attendance Status Filter">
                            <button type="button"
                                class="btn btn-sm text-decoration-none px-4 fw-semibold"
                                id="filterArchive_active"
                                aria-pressed="active">
                                <i class="bi bi-check-circle me-1"></i> Active 
                            </button>
                            <button type="button"
                                class="btn btn-sm text-decoration-none px-4 fw-semibold"
                                id="filterArchive"
                                aria-pressed="archive">
                                <i class="bi bi-archive me-1"></i> Archive 
                            </button>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="row g-4">
                    <div class="coursehide col-lg-4 col-md-6">
                        <label for="filter_course" class="form-label fw-semibold">
                            <i class="bi bi-book me-1 text-primary"></i> Course:
                        </label>
                         <select class="form-select form-select-lg select2" id="filter_course"
                            name="filter_course" aria-label="Filter by Course">
                            <option value="">-- Select Course --</option>
                            @foreach($archivedCourses as $archivedCourse)
                            <option value="{{ $archivedCourse->pk }}">
                                {{ $archivedCourse->course_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
               
                    
                    <div class="archive-column col-md-6">
                        <label for="filter_date" class="form-label fw-semibold">
                            <i class="bi bi-calendar-date me-1 text-primary"></i> Date:
                        </label>
                        <input type="date" class="form-control form-control-lg" id="filter_date" name="filter_date"
                            value="{{ $filterDate ?? '' }}" max="{{ date('Y-m-d') }}" aria-label="Filter by Date">
                    </div>
                    <div class="archive-column col-md-6">
                        <label for="filter_session_time" class="form-label fw-semibold">
                            <i class="bi bi-clock-history me-1 text-primary"></i> Session Time:
                        </label>
                        <select class="form-select form-select-lg select2" id="filter_session_time"
                            name="filter_session_time" aria-label="Filter by Session Time">
                            <option value="">-- Select Session Time --</option>
                            @foreach($maunalSessions as $manualSession)
                            <option value="{{ $manualSession->class_session }}">
                                {{ $manualSession->class_session }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-12 d-flex align-items-end">
                        <!-- <button type="submit" class="btn btn-primary w-100 fw-bold btn-lg me-2" id="applyFilters">
                            <i class="bi bi-search"></i> Apply
                        </button> -->
                        <button type="button" class="btn btn-outline-secondary w-100 btn-lg" id="clearFilters">
                            <i class="bi bi-x-circle"></i> Clear
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>



    {{-- Attendance Details Table --}}
    <div class="card shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-semibold">Attendance Details</h4>
            </div>
        </div>
        <div class="card-body p-0">
          <input type="hidden" name="group_pk" id="group_pk" value="{{ $group_pk }}">
          <input type="hidden" name="course_pk" id="course_pk" value="{{ $course_pk }}">
          <input type="hidden" name="timetable_pk" id="timetable_pk" value="{{ $timetable_pk }}">
          <input type="hidden" name="student_pk" id="student_pk" value="{{ $student_pk }}">
            <div class="table-responsive">
                <table style="width:1580px;" class="table table-hover align-middle mb-0 border-light" id="attendanceTable">
                    <thead>
                        <tr> 
                            <th>#</th>
                            <th>Date & Time</th>
                            <th>Venue</th>
                            <th>Group</th>
                            <th>Topic</th>
                            <th>Faculty</th>
                            <th class="text-center text-nowrap">Attendance Status</th>
                            <th class="text-center text-nowrap">Duty Type (MDO/Escort)</th>
                            <th class="text-center">Exemption</th>
                            <th class="text-center">Doc / Comment</th>
                        </tr>
                    </thead>
                    
                </table>
            </div>
           
        </div>
    </div>
</div>

@endsection
@section('scripts')

<script>
$(function () {
    //alert('sfsdf');
    let table = $('#attendanceTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('ot.student.attendance.data')}}",
            data: function (d) {
                d.filter_session_time = $('#filter_session_time').val();
                d.archive_mode = $('#archive_mode').val();
                d.filter_date = $('#filter_date').val();

                d.group_pk = $('#group_pk').val();
                d.course_pk = $('#course_pk').val();
                d.timetable_pk = $('#filter_date').val();
                d.student_pk = $('#student_pk').val();
            }
        },
        columns: [
    {
        data: 'DT_RowIndex',
        orderable: false,
        searchable: false   // ⭐ IMPORTANT
    },
    { data: 'date', name: 'date' },
    { data: 'venue', name: 'venue' },
    { data: 'group', name: 'group' },
    { data: 'topic', name: 'topic' },
    { data: 'faculty', name: 'faculty' },
    { data: 'attendance_status', name: 'attendance_status' },
    { data: 'duty_type', name: 'duty_type' },
    { data: 'exemption_type', name: 'exemption_type' }
]
    });


    $('#filter_date').on('change', function() {
         var date = $('#filter_date').val();
        table.ajax.reload();
    });

    $('#filterArchive_active').on('click', function () {
        let archive_mode = $(this).attr('aria-pressed');
      //   alert(archive_mode);
        table.ajax.reload();
    });

    $('#filterArchive').on('click', function () {
        let archive_mode = $(this).attr('aria-pressed');
     //    alert(archive_mode);
        table.ajax.reload();
    });

    $('#filter_course').on('change', function () {
        //alert('sfsdf');
        let course_pk =  $('#filter_course').val();
     //    alert(archive_mode);
        table.ajax.reload();
    });

    $('#filter_session_time').on('change', function () {
        //alert('sfsdf');
        let filter_session_time =  $('#filter_session_time').val();
     //    alert(archive_mode);
        table.ajax.reload();
    });
    
  $('#clearFilters').on('click', function () {
        // Reset filters
        $('#filter_date').val('');
        $('#filter_session_time').val('');
        $('#filter_course').val('');
        $('#archive_mode').val('');
        // Reload full data (preloaded AJAX)
        table.ajax.reload(null, true); // true = go to page 1
    });
    
});


$(document).ready(function () {
    $('.coursehide').hide();
    $('#filterArchive_active').addClass('bg-primary text-white shadow-sm');
    $('#filterArchive').on('click', function () {
        let isArchiveActive = $(this).attr('aria-pressed');
        if(isArchiveActive == 'archive'){
            $('.archive-column')
                .removeClass('col-lg-5')
                .addClass('col-lg-4');
            $('#filterArchive_active').removeClass('bg-primary text-white shadow-sm');
            $('#filterArchive').addClass('bg-primary text-white shadow-sm');
            $('.coursehide').show();
   }
});
    $('#filterArchive_active').on('click', function () {
            let isArchiveActive = $(this).attr('aria-pressed');
            if(isArchiveActive == 'active'){
                $('.archive-column')
                    .removeClass('col-lg-4');
                    $('.coursehide').hide();
            $('#filterArchive').removeClass('bg-primary text-white shadow-sm');
                $('#filterArchive_active').addClass('bg-primary text-white shadow-sm');
    }
    });

});
</script>

@endsection