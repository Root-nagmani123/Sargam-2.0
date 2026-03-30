@extends('admin.layouts.master')

{{-- @section('title', 'Pending Feedback Summary - Students') --}}

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Pending Feedback Summary – Students"></x-breadcrum>
    
    <x-session_message />

    <!-- Filters Card -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
            <h2 class="h6 fw-semibold text-body mb-0 d-flex align-items-center gap-2">
                <i class="material-symbols-rounded fs-5">filter_list</i>
                Filters
            </h2>
        </div>
        <div class="card-body p-4">
            <div class="row g-3 g-md-4 align-items-end">
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <label for="filter_course_pk" class="form-label fw-medium">Course</label>
                    <select class="form-select select2-course" id="filter_course_pk">
                        <option value="">— All Courses —</option>
                        @foreach ($courses ?? [] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <label for="filter_session_id" class="form-label fw-medium">Session</label>
                    <select class="form-select select2-session" id="filter_session_id">
                        <option value="">— All Sessions —</option>
                        @foreach ($sessions ?? [] as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <label for="filter_user_name" class="form-label fw-medium">User Name</label>
                    <input type="text" class="form-control" id="filter_user_name" placeholder="Search by name...">
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <label for="filter_email" class="form-label fw-medium">Email</label>
                    <input type="email" class="form-control" id="filter_email" placeholder="Search by email...">
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <label for="filter_from_date" class="form-label fw-medium">From Date</label>
                    <input type="date" class="form-control" id="filter_from_date">
                </div>
                
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <label for="filter_to_date" class="form-label fw-medium">To Date</label>
                    <input type="date" class="form-control" id="filter_to_date">
                </div>
                
                <div class="col-12 col-sm-12 col-md-12 col-lg-2 d-flex flex-wrap gap-2">
                    <button type="button" id="btnApplyFilters" class="btn btn-primary d-inline-flex align-items-center gap-2">
                        <i class="material-symbols-rounded" style="font-size: 1.1rem;">search</i>
                        Apply
                    </button>
                    <button type="button" id="btnResetFilters" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                        <i class="material-symbols-rounded" style="font-size: 1.1rem;">refresh</i>
                        Reset
                    </button>
                </div>
            </div>
            
            <!-- Dynamic Total Records Count -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-info mb-0 py-2">
                        <strong>Total Users with Pending Feedback:</strong> 
                        <span id="totalRecordsCount" class="badge bg-primary ms-1">0</span>
                    </div>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12 d-flex gap-2 justify-content-end">
                    <button type="button" id="exportPDF" class="btn btn-danger d-inline-flex align-items-center gap-2">
                        <i class="material-symbols-rounded" style="font-size: 1.1rem;">picture_as_pdf</i>
                        Export PDF
                    </button>
                    <button type="button" id="exportExcel" class="btn btn-success d-inline-flex align-items-center gap-2">
                        <i class="material-symbols-rounded" style="font-size: 1.1rem;">table_view</i>
                        Export Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable Card -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Pending Feedback Summary</h1>
                    <p class="text-body-secondary small mb-0">Users with pending feedback counts grouped by session date range.</p>
                </div>
            </div>

            <div class="table-responsive">
                {!! $dataTable->table([
                    'class' => 'table align-middle mb-0',
                    'aria-describedby' => 'pending-summary-caption',
                    'width' => '100%',
                    'id' => 'pendingFeedbackSummaryTable'
                ]) !!}
            </div>
            <div id="pending-summary-caption" class="visually-hidden">Pending Feedback Summary Report</div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .table-responsive {
        overflow-x: auto !important;
        overflow-y: visible !important;
    }
    
    .dataTables_wrapper .dataTable {
        width: 100% !important;
        min-width: 800px !important;
    }
    
    @media (max-width: 768px) {
        .dataTables_wrapper .dataTable {
            min-width: 600px !important;
        }
        
        .d-flex.gap-2 {
            flex-direction: column;
        }
        
        .d-flex.gap-2 .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
    }
    
    .table {
        font-size: 0.875rem;
        margin-bottom: 0;
    }
    
    .table thead th {
        background-color: #f8f9fa;
        font-weight: 600;
        border-bottom: 2px solid #dee2e6;
        white-space: nowrap;
        padding: 0.75rem;
    }
    
    .table tbody td {
        vertical-align: middle;
        padding: 0.75rem;
    }
    
    .badge {
        font-size: 0.9rem;
        padding: 0.35rem 0.65rem;
    }
    
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1rem;
    }
    
    .form-label {
        font-size: 0.85rem;
        font-weight: 500;
        margin-bottom: 0.25rem;
    }
    
    .form-control, .form-select {
        font-size: 0.875rem;
        height: 38px;
    }
    
    .btn {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
    }
    
    .card, .card-body {
        overflow: visible !important;
    }

    /* Select2 styling */
    .select2-container {
        width: 100% !important;
        display: block !important;
    }

    .select2-container--open {
        z-index: 9999 !important;
    }

    .select2-dropdown {
        z-index: 9999 !important;
        max-height: 300px;
        overflow-y: auto;
    }

    .select2-container--default .select2-selection--single {
        height: 38px;
        padding: 0.375rem 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        background-color: #fff;
        font-size: 0.875rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5;
        color: #212529;
        padding-left: 0;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px;
        right: 8px;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
{!! $dataTable->scripts() !!}

<script>
$(document).ready(function() {
    // CSRF Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'X-Requested-With': 'XMLHttpRequest'
        }
    });
    
    // Initialize Select2 for Course filter
    function initCourseSelect2() {
        $('#filter_course_pk').select2({
            placeholder: "— All Courses —",
            allowClear: true,
            width: '100%',
            dropdownParent: $('body'),
            dropdownAutoWidth: false,
            language: {
                noResults: function() {
                    return "No courses found";
                }
            }
        });
    }
    
    // Initialize Select2 for Session filter
    function initSessionSelect2() {
        $('#filter_session_id').select2({
            placeholder: "— All Sessions —",
            allowClear: true,
            width: '100%',
            dropdownParent: $('body'),
            dropdownAutoWidth: false,
            language: {
                noResults: function() {
                    return "No sessions found";
                }
            }
        });
    }
    
    // Initialize both Select2
    initCourseSelect2();
    initSessionSelect2();
    
    // Store original sessions and courses for reset
    var originalSessions = $('#filter_session_id').html();
    var originalCourses = $('#filter_course_pk').html();
    
    // Initialize DataTable
    var table = window.LaravelDataTables['pendingFeedbackSummaryTable'];
    
    // Function to update count from DataTable
    function updateCountFromTable() {
        if (table) {
            var pageInfo = table.page.info();
            var total = pageInfo.recordsDisplay;
            $('#totalRecordsCount').text(total.toLocaleString());
        }
    }
    
    // Update count on DataTable events
    if (table) {
        table.on('draw.dt', function() {
            updateCountFromTable();
        });
    }
    
    // Function to reload table with current filters
    function reloadTable() {
        if (table) {
            table.ajax.reload(function() {
                updateCountFromTable();
            });
        }
    }
    
    // Apply filters button
    $('#btnApplyFilters').on('click', function() {
        reloadTable();
    });
    
    // Reset filters button
    $('#btnResetFilters').on('click', function() {
        // Reset course filter
        $('#filter_course_pk').html(originalCourses);
        $('#filter_course_pk').val('').trigger('change.select2');
        
        // Reset session filter
        $('#filter_session_id').html(originalSessions);
        $('#filter_session_id').val('').trigger('change.select2');
        
        // Reset other filters
        $('#filter_user_name, #filter_email, #filter_from_date, #filter_to_date').val('');
        
        reloadTable();
    });
    
    // Course change handler - Load sessions for selected course
    $('#filter_course_pk').on('change', function() {
        var courseId = $(this).val();
        var $sessionSelect = $('#filter_session_id');
        
        if (!courseId) {
            // Reset to original sessions
            $sessionSelect.html(originalSessions);
            $sessionSelect.val('').trigger('change.select2');
            reloadTable();
            return;
        }
        
        // Show loading state
        $sessionSelect.html('<option value="">Loading sessions...</option>');
        $sessionSelect.val('').trigger('change.select2');
        
        // Fetch sessions for selected course
        $.ajax({
            url: "{{ route('admin.get.sessions.by.course') }}",
            type: "GET",
            data: { course_pk: courseId },
            dataType: 'json',
            success: function(response) {
                var options = '<option value="">— All Sessions —</option>';
                if (response && response.length > 0) {
                    $.each(response, function(index, session) {
                        var label = session.subject_topic;
                        if (session.START_DATE) {
                            label += ' (' + session.START_DATE + ')';
                        }
                        options += '<option value="' + session.pk + '">' + label + '</option>';
                    });
                } else {
                    options = '<option value="">No sessions found</option>';
                }
                $sessionSelect.html(options);
                $sessionSelect.val('').trigger('change.select2');
                reloadTable();
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $sessionSelect.html('<option value="">Error loading sessions</option>');
                $sessionSelect.val('').trigger('change.select2');
                reloadTable();
            }
        });
    });
    
    // Auto-reload on filter changes with debounce
    var filterTimeout;
    $('#filter_session_id, #filter_user_name, #filter_email, #filter_from_date, #filter_to_date').on('input change', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function() {
            reloadTable();
        }, 500);
    });
    
    // Export PDF
    $('#exportPDF').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var originalHtml = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm me-2" role="status"></span> Generating PDF...').prop('disabled', true);
        
        var form = $('<form>', {
            method: 'POST',
            action: "{{ route('admin.feedback.summary.export.pdf') }}",
            style: 'display: none'
        });
        
        $('<input>').attr({
            type: 'hidden',
            name: '_token',
            value: "{{ csrf_token() }}"
        }).appendTo(form);
        
        var filters = {
            filter_course_pk: $('#filter_course_pk').val(),
            filter_session_id: $('#filter_session_id').val(),
            filter_user_name: $('#filter_user_name').val(),
            filter_email: $('#filter_email').val(),
            filter_from_date: $('#filter_from_date').val(),
            filter_to_date: $('#filter_to_date').val(),
            ot_name: $('.dataTables_filter input').val()
        };
        
        $.each(filters, function(key, value) {
            if (value && value !== '') {
                $('<input>').attr({
                    type: 'hidden',
                    name: key,
                    value: value
                }).appendTo(form);
            }
        });
        
        $('body').append(form);
        form.submit();
        
        setTimeout(function() {
            $btn.html(originalHtml).prop('disabled', false);
            form.remove();
        }, 3000);
    });
    
    // Export Excel
    $('#exportExcel').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var originalHtml = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm me-2" role="status"></span> Generating Excel...').prop('disabled', true);
        
        var form = $('<form>', {
            method: 'POST',
            action: "{{ route('admin.feedback.summary.export.excel') }}",
            style: 'display: none'
        });
        
        $('<input>').attr({
            type: 'hidden',
            name: '_token',
            value: "{{ csrf_token() }}"
        }).appendTo(form);
        
        var filters = {
            filter_course_pk: $('#filter_course_pk').val(),
            filter_session_id: $('#filter_session_id').val(),
            filter_user_name: $('#filter_user_name').val(),
            filter_email: $('#filter_email').val(),
            filter_from_date: $('#filter_from_date').val(),
            filter_to_date: $('#filter_to_date').val(),
            ot_name: $('.dataTables_filter input').val()
        };
        
        $.each(filters, function(key, value) {
            if (value && value !== '') {
                $('<input>').attr({
                    type: 'hidden',
                    name: key,
                    value: value
                }).appendTo(form);
            }
        });
        
        $('body').append(form);
        form.submit();
        
        setTimeout(function() {
            $btn.html(originalHtml).prop('disabled', false);
            form.remove();
        }, 3000);
    });
    
    // Initial count
    setTimeout(updateCountFromTable, 500);
    
    console.log('Pending Feedback Summary Ready with Select2 Course & Session Filters');
});
</script>
@endpush