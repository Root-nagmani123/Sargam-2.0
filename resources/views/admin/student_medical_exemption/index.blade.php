@extends('admin.layouts.master')

@section('title', 'Student Medical Exemption - Sargam | Lal Bahadur')

@section('setup_content')
<style>
/* Medical Exemption Index - Bootstrap 5.3 enhanced UI/UX */
:root {
    --me-primary: #004a93;
    --me-primary-hover: #003d7a;
    --me-primary-subtle: rgba(0, 74, 147, 0.08);
    --me-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    --me-shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.1);
    --me-radius: 0.5rem;
    --me-radius-lg: 0.75rem;
    --me-transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Card - modern Bootstrap 5.3 style */
.student-medical-exemption-index .datatables .card {
    border-radius: var(--me-radius-lg);
    box-shadow: var(--me-shadow);
    border: 1px solid rgba(0, 0, 0, 0.06);
    border-inline-start: 4px solid var(--me-primary);
    transition: box-shadow var(--me-transition);
}
.student-medical-exemption-index .datatables .card:hover {
    box-shadow: var(--me-shadow-lg);
}
.student-medical-exemption-index .datatables .card-body {
    padding: 1.5rem 1.5rem 1.5rem 1.5rem;
}

/* Page title */
.student-medical-exemption-index .datatables h4 {
    color: var(--bs-body-color);
    font-weight: 700;
    letter-spacing: -0.01em;
}

/* Button group - Active/Archive */
.btn-group[role="group"] .btn {
    transition: all var(--me-transition);
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
    box-shadow: inset 0 0 0 2px #fff, 0 0 0 3px rgba(0, 74, 147, 0.25);
}
.btn:focus-visible {
    outline: 3px solid var(--me-primary);
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

/* Action buttons - Print, Export, Add */
.student-medical-exemption-index .btn-outline-info,
.student-medical-exemption-index .btn-outline-success {
    border-radius: var(--me-radius);
    font-weight: 500;
    transition: all var(--me-transition);
}
.student-medical-exemption-index .btn-outline-info:hover,
.student-medical-exemption-index .btn-outline-success:hover {
    transform: translateY(-1px);
}
.student-medical-exemption-index .btn-primary {
    border-radius: var(--me-radius);
    font-weight: 600;
    transition: all var(--me-transition);
}
.student-medical-exemption-index .btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 74, 147, 0.3);
}

/* Filters section - grouped panel */
.student-medical-exemption-index .filters-panel {
    background: var(--me-primary-subtle);
    border-radius: var(--me-radius);
    padding: 1.25rem;
    border: 1px solid rgba(0, 74, 147, 0.1);
}
.student-medical-exemption-index .form-label {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--bs-body-color);
}
.student-medical-exemption-index .form-control,
.student-medical-exemption-index .form-select {
    border-radius: var(--me-radius);
    border-color: var(--bs-border-color);
    transition: border-color var(--me-transition), box-shadow var(--me-transition);
}
.student-medical-exemption-index .form-control:focus,
.student-medical-exemption-index .form-select:focus {
    border-color: var(--me-primary);
    box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.15);
}
.student-medical-exemption-index .input-group-text {
    border-radius: var(--me-radius) 0 0 var(--me-radius);
    background: var(--bs-body-bg);
}
.student-medical-exemption-index .input-group .form-control {
    border-radius: 0 var(--me-radius) var(--me-radius) 0;
}
.student-medical-exemption-index #resetFilters {
    border-radius: var(--me-radius);
    font-weight: 600;
}

/* Table - single row per record, horizontal scroll on all screen sizes */
.datatables .table-responsive,
.student-medical-exemption-index .dataTables_scrollBody {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch;
}
.datatables #medicalExemptionTable {
    min-width: 100%;
    width: max-content !important;
    table-layout: auto;
    margin-bottom: 0;
}
.datatables #medicalExemptionTable thead th,
.datatables #medicalExemptionTable tbody td {
    white-space: nowrap !important;
}
.datatables #medicalExemptionTable thead th {
    background: linear-gradient(180deg, var(--me-primary) 0%, var(--me-primary-hover) 100%);
    color: #fff !important;
    font-weight: 600;
    font-size: 0.8rem;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    padding: 0.875rem 1rem;
    border: none;
    vertical-align: middle;
}
.datatables #medicalExemptionTable tbody td {
    padding: 0.75rem 1rem;
    vertical-align: middle !important;
    transition: background-color 0.15s ease;
}
.datatables #medicalExemptionTable tbody tr:hover td {
    background-color: var(--me-primary-subtle) !important;
}
/* Action column - 2 distinct Bootstrap 5 buttons */
.datatables #medicalExemptionTable .action-btn-edit,
.datatables #medicalExemptionTable .action-btn-delete {
    padding: 0.35rem 0.65rem;
    border-radius: var(--me-radius);
    font-weight: 500;
    font-size: 0.8125rem;
    transition: all var(--me-transition);
    white-space: nowrap;
}
.datatables #medicalExemptionTable .action-btn-edit:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 74, 147, 0.35);
}
.datatables #medicalExemptionTable .action-btn-delete:hover:not(.disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.35);
}
.datatables #medicalExemptionTable .action-btn-delete.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    pointer-events: none;
}
.datatables #medicalExemptionTable th:nth-child(7),
.datatables #medicalExemptionTable td:nth-child(7) {
    vertical-align: middle !important;
    line-height: 1.4;
}

/* DataTables wrapper - Bootstrap 5.3 consistency */
.student-medical-exemption-index #medicalExemptionTable_wrapper .dataTables_info,
.student-medical-exemption-index #medicalExemptionTable_wrapper .dataTables_length select,
.student-medical-exemption-index #medicalExemptionTable_wrapper .dataTables_filter input {
    font-size: 0.875rem;
}
.student-medical-exemption-index #medicalExemptionTable_wrapper .dataTables_length select,
.student-medical-exemption-index #medicalExemptionTable_wrapper .dataTables_filter input {
    border-radius: var(--me-radius);
    padding: 0.35rem 0.75rem;
    border: 1px solid var(--bs-border-color);
}
.student-medical-exemption-index #medicalExemptionTable_wrapper .dataTables_filter input:focus {
    border-color: var(--me-primary);
    box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.15);
}
.student-medical-exemption-index #medicalExemptionTable_wrapper .dataTables_paginate .page-link {
    border-radius: var(--me-radius);
    font-weight: 500;
}

@media (prefers-reduced-motion: reduce) {
    .btn-group .btn:hover,
    .student-medical-exemption-index .btn-primary:hover,
    .student-medical-exemption-index .btn-outline-info:hover,
    .student-medical-exemption-index .btn-outline-success:hover,
    .datatables #medicalExemptionTable .action-btn-edit:hover,
.datatables #medicalExemptionTable .action-btn-delete:hover { transform: none; }
}

/* Responsive - Tablet (768px - 991px) - keep single row */
@media (max-width: 991.98px) {
    .datatables #medicalExemptionTable th,
    .datatables #medicalExemptionTable td {
        padding: 8px 10px;
        font-size: 0.9rem;
        white-space: nowrap !important;
    }
}

/* Responsive - Small tablet / large phone (576px - 767px) - keep single row */
@media (max-width: 767.98px) {
    .student-medical-exemption-index .datatables .card-body {
        padding: 1rem !important;
    }
    .student-medical-exemption-index .filters-panel {
        padding: 1rem;
    }
    .datatables #medicalExemptionTable th,
    .datatables #medicalExemptionTable td {
        padding: 6px 8px;
        font-size: 0.85rem;
        white-space: nowrap !important;
    }
    .btn-group[role="group"] .btn {
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
        font-size: 0.875rem;
    }
}

/* Responsive - Phone (max 575px) */
@media (max-width: 575.98px) {
    .student-medical-exemption-index.container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    .student-medical-exemption-index .datatables .card-body {
        padding: 0.75rem !important;
    }
    .student-medical-exemption-index .filters-panel {
        padding: 0.75rem;
    }
    .student-medical-exemption-index .row.align-items-center.g-3 {
        gap: 0.75rem !important;
    }
    .student-medical-exemption-index .row.align-items-center.g-3 .col-lg-4:first-child h4 {
        font-size: 1.1rem !important;
    }
    .datatables .btn-group[role="group"] {
        width: 100%;
        justify-content: stretch;
    }
    .datatables .btn-group[role="group"] .btn {
        flex: 1;
        padding: 0.5rem 0.75rem !important;
        font-size: 0.8125rem;
    }
    .datatables .d-flex.justify-content-md-end .btn {
        flex: 1 1 auto;
        min-width: 0;
        justify-content: center;
    }
    .datatables .d-flex.justify-content-md-end .btn span.d-none.d-md-inline {
        display: none !important;
    }
    .datatables #medicalExemptionTable th,
    .datatables #medicalExemptionTable td {
        padding: 6px 8px;
        font-size: 0.8rem;
        white-space: nowrap !important;
    }
    .student-medical-exemption-index .row.mb-3.align-items-end .col-md-3,
    .student-medical-exemption-index .row.mb-3.align-items-end .col-md-2 {
        margin-bottom: 0.5rem;
    }
    .datatables #resetFilters {
        width: 100% !important;
    }
}

@media (min-width: 768px) {
    .datatables #resetFilters {
        width: auto !important;
    }
}

/* Responsive - Very small phone (max 375px) - keep single row */
@media (max-width: 375px) {
    .student-medical-exemption-index .container-fluid {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    .student-medical-exemption-index .datatables .card-body {
        padding: 0.5rem !important;
    }
    .student-medical-exemption-index .filters-panel {
        padding: 0.5rem;
    }
    .datatables #medicalExemptionTable th,
    .datatables #medicalExemptionTable td {
        padding: 4px 6px;
        font-size: 0.75rem;
        white-space: nowrap !important;
    }
}

/* Print Styles */
@media print {
    body * {
        visibility: hidden;
    }

    #medicalExemptionTable,
    #medicalExemptionTable * {
        visibility: visible;
    }

    #medicalExemptionTable {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }

    .table thead {
        background-color: #004a93 !important;
        color: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .table th,
    .table td {
        border: 1px solid #000 !important;
        padding: 8px !important;
    }

    .table {
        border-collapse: collapse !important;
        font-size: 12px !important;
    }

    /* Hide action and status columns in print */
    .table th:nth-child(11),
    .table td:nth-child(11),
    .table th:nth-child(12),
    .table td:nth-child(12) {
        display: none;
    }

    /* Print header */
    @page {
        margin: 1cm;
    }

    .print-header {
        display: block;
        text-align: center;
        margin-bottom: 20px;
        font-size: 18px;
        font-weight: bold;
    }

    .print-footer {
        display: block;
        text-align: center;
        margin-top: 20px;
        font-size: 10px;
    }

}
</style>
<div class="container-fluid student-medical-exemption-index">
    <x-breadcrum title="Medical Exemption Form" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center g-3 mb-3">

                    <!-- Title -->
                    <div class="col-12 col-lg-4 col-md-12">
                        <h4 class="mb-0 fw-bold text-dark">
                            Medical Exemption Form
                        </h4>
                    </div>

                     <!-- Active / Archive -->
                    <div class="col-12 col-lg-4 col-md-6 text-md-end text-start">
                        <div class="btn-group shadow-sm rounded-pill overflow-hidden" role="group"
                            aria-label="Course Status Filter">
                            <a href="javascript:void(0)"
                                class="btn btn-success active px-4 fw-semibold"
                                id="filterActive" aria-pressed="true">
                                <i class="bi bi-check-circle me-1"></i> Active
                            </a>
                            <a href="javascript:void(0)"
                                class="btn btn-outline-secondary px-4 fw-semibold"
                                id="filterArchive" aria-pressed="false">
                                <i class="bi bi-archive me-1"></i> Archive
                            </a>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="col-12 col-lg-4 col-md-6">
                        <div class="d-flex justify-content-md-end justify-content-start flex-wrap gap-2">

                            <button type="button"
        class="btn btn-outline-info d-flex align-items-center gap-1 px-3"
        onclick="printTable()">
    <i class="material-icons material-symbols-rounded"
       style="font-size:22px;"
       aria-hidden="true">print</i>
    <span class="d-none d-md-inline">Print</span>
</button>

                            <a href="{{ route('student.medical.exemption.export') }}" class="btn btn-outline-success d-flex align-items-center gap-1 px-3">
                                <i class="material-icons material-symbols-rounded" style="font-size:22px;"
                                    aria-hidden="true">download</i>
                                <span class="d-none d-md-inline">Export</span>
                            </a>

                            <a href="{{route('student.medical.exemption.create')}}"
                                class="btn btn-primary d-flex align-items-center gap-1 px-3 px-md-4 fw-semibold">
                                <i class="material-icons material-symbols-rounded" style="font-size:22px;"
                                    aria-hidden="true">add</i>
                                <span class="d-none d-sm-inline">Add Student Medical Exemption</span>
                                <span class="d-inline d-sm-none">Add</span>
                            </a>

                        </div>
                    </div>

                </div>


                <hr class="my-4">

                <!-- Filters Section -->
                <div class="row mb-3 align-items-end g-2 g-sm-3">
                    <!-- Search Filter -->
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="search" class="form-label fw-semibold">Search</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="material-icons menu-icon material-symbols-rounded"
                                    style="font-size: 20px;">search</i>
                            </span>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Search student, OT code, course..." value="">
                        </div>
                    </div>

                       <!-- Course Filter -->
                    <div class="col-12 col-sm-6 col-md-3">
                        <label for="course_filter" class="form-label fw-semibold">Course</label>
                        <select name="course_filter" id="course_filter" class="form-select">
                            <option value="">-- All Courses --</option>
                            @foreach($courses as $course)
                            <option value="{{ $course->pk }}">
                                {{ $course->course_name }}
                            </option>
                            @endforeach
                        </select>
                    </div>


                    <!-- From Date Filter -->
                    <div class="col-12 col-sm-6 col-md-2">
                        <label for="from_date_filter" class="form-label fw-semibold">From Date</label>
                        <input type="date" name="from_date_filter" id="from_date_filter" class="form-control"
                            value="">
                    </div>

                    <!-- To Date Filter -->
                    <div class="col-12 col-sm-6 col-md-2">
                        <label for="to_date_filter" class="form-label fw-semibold">To Date</label>
                        <input type="date" name="to_date_filter" id="to_date_filter" class="form-control"
                            value="">
                    </div>

                    <!-- Reset Button -->
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold d-block">&nbsp;</label>
                        <a href="javascript:void(0)" id="resetFilters"
                                class="btn btn-outline-danger">
                                Reset
                                </a>
                    </div>
                </div>

                <!-- Total Records Count Row -->

                <div class="table-responsive">
                    <table class="table" id="medicalExemptionTable">
                        <thead>
                            <tr>
                                <th class="col">#</th>
                                <th class="col">Student</th>
                                <th class="col">OT Code</th>
                                <th class="col">Course</th>
                                <th class="col">Assigned by</th>
                                <th class="col">Category</th>
                                <th class="col">Medical Speciality</th>
                                <th class="col">From-To</th>
                                <th class="col">OPD Type</th>
                                <th class="col">Document</th>
                                <th class="col">Action</th>
                                <th class="col">Status</th>
                            </tr>
                        </thead>
                       
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function () {

    // ✅ IMPORTANT: global variable (DataTable से पहले)
    let courseStatus = 'active';

    let table = $('#medicalExemptionTable').DataTable({
        processing: true,
        serverSide: true,

        scrollX: true,
        scrollCollapse: true,
        autoWidth: false,

        ajax: {
            url: "{{ route('student.medical.exemption.index') }}",
            data: function (d) {
                d.course_id     = $('#course_filter').val();
                d.custom_search = $('#search').val();
                d.from_date     = $('#from_date_filter').val();
                d.to_date       = $('#to_date_filter').val();

                // ✅ status now properly passed
                d.status        = courseStatus;
            }
        },

        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'student', name: 'student.display_name' },
            { data: 'ot_code', name: 'student.generated_OT_code' },
            { data: 'course', name: 'course.course_name' },
            { data: 'assigned_by', name: 'employee.first_name' },
            { data: 'category', name: 'category.exemp_category_name' },
            { data: 'speciality', name: 'speciality.speciality_name' },
            { data: 'from_to', orderable: false },
            { data: 'opd_type', name: 'opd_category' },
            { data: 'document', orderable: false, searchable: false },
            { data: 'action', orderable: false, searchable: false },
            { data: 'status', orderable: false, searchable: false }
        ]
    });

    // Reload table when course filter changes
    $('#course_filter').on('change', function () {
        table.ajax.reload(null, false);
    });

    $('#from_date_filter, #to_date_filter').on('change', function () {
        table.ajax.reload(null, false);
    });

    // 🔍 Search with debounce
    let delayTimer;
    $('#search').on('keyup', function () {
        clearTimeout(delayTimer);
        delayTimer = setTimeout(function () {
            table.ajax.reload(null, false);
        }, 400);
    });

    // 🔄 Reset filters
    $('#resetFilters').on('click', function () {
        $('#search').val('');
        $('#course_filter').val('').trigger('change');
        $('#from_date_filter').val('');
        $('#to_date_filter').val('');

        table.ajax.reload(null, false);
    });

    // ✅ Active filter
    $('#filterActive').on('click', function () {

        courseStatus = 'active';

        $(this).addClass('btn-success active')
               .removeClass('btn-outline-secondary');

        $('#filterArchive').removeClass('btn-success active')
                           .addClass('btn-outline-secondary');

        table.ajax.reload(null, false);
    });

    // ✅ Archive filter
    $('#filterArchive').on('click', function () {

        courseStatus = 'archive';

        $(this).addClass('btn-success active')
               .removeClass('btn-outline-secondary');

        $('#filterActive').removeClass('btn-success active')
                          .addClass('btn-outline-secondary');

        table.ajax.reload(null, false);
    });

    
$(document).on('click', '.delete-btn', function () {
    

    let deleteUrl = $(this).data('url');

    Swal.fire({
        title: 'Are you sure?',
        text: "This record will be permanently deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {

        if (result.isConfirmed) {

            $.ajax({
                url: deleteUrl,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function (response) {

                    Swal.fire(
                        'Deleted!',
                        response.message ?? 'Record deleted successfully.',
                        'success'
                    );

                      table.ajax.reload(null, false);
                },
                error: function () {
                    Swal.fire(
                        'Error!',
                        'Something went wrong.',
                        'error'
                    );
                }
            });
        }
    });
});

});


</script>

<script>

    // Print function - defined globally so it can be called from onclick
function printTable() {
    // Create a new window for printing
    var printWindow = window.open('', '_blank');
    var table = document.getElementById('medicalExemptionTable');

    if (!table) {
        alert('Table not found!');
        return;
    }

    // Clone the table to avoid modifying the original
    var tableClone = table.cloneNode(true);

    // Remove Action and Status columns (11th and 12th columns)
    var rows = tableClone.querySelectorAll('tr');
    rows.forEach(function(row) {
        var cells = row.querySelectorAll('th, td');
        if (cells.length >= 12) {
            // Remove Action column (11th) and Status column (12th)
            if (cells[10]) cells[10].remove(); // Action
            if (cells[10]) cells[10].remove(); // Status (now at index 10 after first removal)
        }
    });

    var tableHTML = tableClone.outerHTML;

    // Get current date for header
    var today = new Date();
    var dateStr = today.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });

    // Build print content
    var printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Medical Exemption Form - Print</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }
                .print-header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 10px;
                }
                .print-header h2 {
                    margin: 0;
                    color: #004a93;
                }
                .print-header p {
                    margin: 5px 0;
                    color: #666;
                }
                .print-info {
                    margin-bottom: 15px;
                    font-size: 12px;
                    color: #666;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                table thead {
                    background-color: #af2910 !important;
                    color: white !important;
                }
                table th,
                table td {
                    border: 1px solid #000;
                    padding: 8px;
                    text-align: left;
                    font-size: 11px;
                }
                table th {
                    font-weight: bold;
                    background-color: #af2910;
                    color: white;
                }
                table tbody tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .print-footer {
                    margin-top: 20px;
                    text-align: center;
                    font-size: 10px;
                    color: #666;
                    border-top: 1px solid #ccc;
                    padding-top: 10px;
                }
                @media print {
                    @page {
                        margin: 1cm;
                    }
                    body {
                        margin: 0;
                    }
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h2>Medical Exemption Form</h2>
                <p>Lal Bahadur Shastri National Academy of Administration</p>
                <p>Print Date: ${dateStr}</p>
            </div>
            <div class="print-info">
                ${getFilterInfo()}
            </div>
            ${tableHTML}
            <div class="print-footer">
                <p>Generated on ${new Date().toLocaleString()}</p>
            </div>
        </body>
        </html>
    `;

    printWindow.document.write(printContent);
    printWindow.document.close();

    // Wait for content to load, then print
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
}


</script>

@endpush