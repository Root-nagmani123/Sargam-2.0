@extends('admin.layouts.master')
@section('title', 'Pending Feedback – Students')
@section('setup_content')

<div class="container-fluid">
<x-breadcrum title="Pending Feedback – Students" />
    <!-- Course Filter + Export Buttons -->
    <div class="card mb-3">
        <div class="card-body">
            <form id="exportForm" method="POST">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Filter by Course</label>
                        <select id="course_filter" name="course_pk" class="form-control">
                            <option value="">All Courses</option>
                            @isset($courses)
                                @foreach($courses as $course)
                                    <option value="{{ $course->pk }}" 
                                        {{ request('course_pk') == $course->pk ? 'selected' : '' }}>
                                        {{ $course->course_name }}
                                    </option>
                                @endforeach
                            @endisset
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Filter by Date</label>
                        <input type="date" id="date_filter" name="date_filter" class="form-control" value="{{ request('date_filter') }}">
                    </div>
                    <div class="col-md-6 text-end">
                        <button type="button" id="export_pdf" class="btn btn-primary">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </button>
                        <button type="button" id="export_excel" class="btn btn-success">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="card-body" id="pendingStudentsData">

            <div class="table-responsive">
                <table class="table text-nowrap align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>OT / Participant</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>OT Code</th>
                            <th>Course</th>
                            <th>Date</th>

                        </tr>
                    </thead>

                    <tbody>
                        @forelse($pendingStudents as $index => $row)
                            <tr>
                                <td>{{ $pendingStudents->firstItem() + $index }}</td>
                                <td>{{ $row->student_name }}</td>
                                <td>{{ $row->email }}</td>
                                <td>{{ $row->contact_no }}</td>
                                <td>{{ $row->generated_OT_code }}</td>
                                <td>{{ $row->course_name }}</td>
                                <td>{{ $row->from_date ? \Carbon\Carbon::parse($row->from_date)->format('d-m-Y') : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    No pending feedback found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($pendingStudents->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {!! $pendingStudents->withQueryString()->links() !!}
                </div>
            @endif

        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // Function to submit export form via POST
    function exportData(exportType) {
        // Create a temporary form
        let form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        // Set the action based on export type
        if (exportType === 'pdf') {
            form.action = "{{ route('admin.feedback.export.pdf') }}";
        } else if (exportType === 'excel') {
            form.action = "{{ route('admin.feedback.export.excel') }}";
        }
        
        // Add CSRF token
        let csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = "{{ csrf_token() }}";
        form.appendChild(csrfToken);
        
        // Add course_pk parameter
        let coursePk = $('#course_filter').val();
        let courseInput = document.createElement('input');
        courseInput.type = 'hidden';
        courseInput.name = 'course_pk';
        courseInput.value = coursePk;
        form.appendChild(courseInput);
        
        // Add date_filter parameter
        let dateFilter = $('#date_filter').val();
        let dateInput = document.createElement('input');
        dateInput.type = 'hidden';
        dateInput.name = 'date_filter';
        dateInput.value = dateFilter;
        form.appendChild(dateInput);
        
        // Add the form to body and submit
        document.body.appendChild(form);
        form.submit();
        
        // Clean up
        setTimeout(() => {
            document.body.removeChild(form);
        }, 100);
    }

    // PDF Export button click
    $('#export_pdf').on('click', function(e) {
        e.preventDefault();
        exportData('pdf');
    });

    // Excel Export button click
    $('#export_excel').on('click', function(e) {
        e.preventDefault();
        exportData('excel');
    });

    // Function to fetch pending students (for AJAX filtering)
    function fetchPendingStudents(page = 1) {
        let course_pk = $('#course_filter').val();
        let date_filter = $('#date_filter').val();

        $.ajax({
            url: "{{ route('admin.feedback.pending.students') }}",
            type: "GET",
            data: { 
                page: page, 
                course_pk: course_pk,
                date_filter: date_filter
            },
            beforeSend: function () {
                $('#pendingStudentsData').html(
                    '<div class="text-center py-5">' +
                        '<div class="spinner-border text-primary"></div>' +
                        '<p class="mt-2">Loading...</p>' +
                    '</div>'
                );
            },
            success: function (response) {
                let html = $(response).find('#pendingStudentsData').html();
                $('#pendingStudentsData').html(html);
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
                $('#pendingStudentsData').html(
                    '<div class="alert alert-danger">Error loading data. Please try again.</div>'
                );
            }
        });
    }

    // Pagination click (event delegation)
    $(document).on('click', '.pagination a', function (e) {
        e.preventDefault();
        let page = $(this).attr('href').split('page=')[1];
        fetchPendingStudents(page);
    });

    // Course filter change
    $('#course_filter').change(function () {
        fetchPendingStudents(1);
    });
    
    // Date filter change - automatic filtering
    $('#date_filter').change(function () {
        fetchPendingStudents(1);
    });

});
</script>
@endpush
