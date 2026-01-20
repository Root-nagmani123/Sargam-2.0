@extends('admin.layouts.master')

@section('title', 'Student Medical Exemption - Sargam | Lal Bahadur')

@section('setup_content')
<style>
.btn-group[role="group"] .btn {
    transition: all 0.3s ease-in-out;
    border-radius: 0;
    /* Reset for pill-style container */
}

.btn-group[role="group"] .btn:first-child {
    border-top-left-radius: 50rem !important;
    border-bottom-left-radius: 50rem !important;
}

.btn-group[role="group"] .btn:last-child {
    border-top-right-radius: 50rem !important;
    border-bottom-right-radius: 50rem !important;
}

/* Hover + Active States */
.btn-group .btn:hover {
    transform: translateY(-1px);
}

.btn-group .btn.active {
    box-shadow: inset 0 0 0 2px #fff, 0 0 0 3px rgba(0, 123, 255, 0.3);
}

/* Accessibility: Focus ring */
.btn:focus-visible {
    outline: 3px solid #0d6efd;
    outline-offset: 2px;
}

/* Better contrast for GIGW compliance */
.btn-outline-secondary {
    color: #333;
    border-color: #999;
}

.btn-outline-secondary:hover {
    background-color: #f8f9fa;
    border-color: #666;
}

/* Horizontal Scroll for Table */
.datatables .table-responsive {
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch;
}

.datatables #medicalExemptionTable {
    min-width: 100%;
    width: max-content;
}

.datatables #medicalExemptionTable th,
.datatables #medicalExemptionTable td {
    white-space: nowrap;
    padding: 10px 12px;
    vertical-align: middle;
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
        background-color: #af2910 !important;
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
<div class="container-fluid">
    <x-breadcrum title="Medical Exemption Form" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card" style="border-left:4px solid #004a93;">
            <div class="card-body">
                <div class="row align-items-center g-3 mb-3">

                    <!-- Title -->
                    <div class="col-lg-4 col-md-12">
                        <h4 class="mb-0 fw-bold text-dark">
                            Medical Exemption Form
                        </h4>
                    </div>

                    <!-- Active / Archive -->
                    <div class="col-lg-4 col-md-6 text-end">
                        <div class="btn-group shadow-sm rounded-pill overflow-hidden" role="group"
                            aria-label="Course Status Filter">
                            
                            <a href="javascript:void(0)"
                                class="btn btn-success active px-4 fw-semibold"
                                id="filterActive" aria-pressed="true">
                                <i class="bi bi-check-circle me-1"></i> Active
                            </a>
                            <a href="javascript:void(0)"
                                class="btn btn-outline-secondary"
                                id="filterArchive" aria-pressed="false">
                                <i class="bi bi-archive me-1"></i> Archive
                            </a>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="col-lg-4 col-md-6">
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
                                class="btn btn-primary d-flex align-items-center gap-1 px-4 fw-semibold">
                                <i class="material-icons material-symbols-rounded" style="font-size:22px;"
                                    aria-hidden="true">add</i>
                                Add Student Medical Exemption
                            </a>

                        </div>
                    </div>

                </div>


                <hr>

                <!-- Filters Section -->
                <div class="row mb-3 align-items-end">
                    <!-- Search Filter -->
                    <div class="col-md-3">
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
                    <div class="col-md-3">
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
                    <div class="col-md-2">
                        <label for="from_date_filter" class="form-label fw-semibold">From Date</label>
                        <input type="date" name="from_date_filter" id="from_date_filter" class="form-control"
                            value="">
                    </div>

                    <!-- To Date Filter -->
                    <div class="col-md-2">
                        <label for="to_date_filter" class="form-label fw-semibold">To Date</label>
                        <input type="date" name="to_date_filter" id="to_date_filter" class="form-control"
                            value="">
                    </div>

                    <!-- Reset Button -->
                    <div class="col-md-2">
                        <label class="form-label fw-semibold d-block">&nbsp;</label>
                        <a href="javascript:void(0)" id="resetFilters"
                            class="btn btn-outline-danger w-100 fw-semibold" title="Reset all filters">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </a>
                    </div>
                </div>

                <!-- Total Records Count Row -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary fs-6 px-3 py-2 d-inline-flex align-items-center">
                                <i class="bi bi-list-check me-2"></i> Total Records: <strong
                                    class="ms-1">{{ $records->total() }}</strong>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table" id="medicalExemptionTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>OT Code</th>
                            <th>Course</th>
                            <th>Assigned by</th>
                            <th>Category</th>
                            <th>Medical Speciality</th>
                            <th>From-To</th>
                            <th>OPD Type</th>
                            <th>Document</th>
                            <th>Action</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                </table>
                </div>
                <!-- Pagination -->
            
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function getFilterInfo() {
    var info = [];
    var filter = '{{ $filter }}';
    var courseFilter = '';
    var fromDateFilter = '';
    var toDateFilter = '';
    var search = '';

    // Get values using vanilla JS to avoid jQuery dependency
    var courseSelect = document.getElementById('course_filter');
    var fromDateInput = document.getElementById('from_date_filter');
    var toDateInput = document.getElementById('to_date_filter');
    var searchInput = document.getElementById('search');

    if (courseSelect) {
        courseFilter = courseSelect.options[courseSelect.selectedIndex].text;
    }
    if (fromDateInput && fromDateInput.value) {
        fromDateFilter = fromDateInput.value;
    }
    if (toDateInput && toDateInput.value) {
        toDateFilter = toDateInput.value;
    }
    if (searchInput) {
        search = searchInput.value;
    }

    if (filter) {
        info.push('Status: ' + (filter === 'active' ? 'Active' : 'Archive'));
    }
    if (courseFilter && courseFilter !== '-- All Courses --') {
        info.push('Course: ' + courseFilter);
    }
    if (fromDateFilter || toDateFilter) {
        var dateRange = [];
        if (fromDateFilter) dateRange.push('From: ' + fromDateFilter);
        if (toDateFilter) dateRange.push('To: ' + toDateFilter);
        info.push('Date Range: ' + dateRange.join(' - '));
    }
    if (search) {
        info.push('Search: ' + search);
    }

    return info.length > 0 ? '<strong>Filters Applied:</strong> ' + info.join(' | ') : '';
}
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

<script>

$(document).ready(function () {
    let currentFilter = 'active'; // default
    let table = $('#medicalExemptionTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('student.medical.exemption.index') }}",
            data: function (d) {
                d.from_date_filter = $('#from_date_filter').val();
                d.to_date_filter   = $('#to_date_filter').val();
                d.course_filter    = $('#course_filter').val();
                d.filter = currentFilter; 
            }
        },
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'student' },
            { data: 'ot_code' },
            { data: 'course' },
            { data: 'assigned_by' },
            { data: 'category' },
            { data: 'speciality' },
            { data: 'from_to' },
            { data: 'opd_category' },
            { data: 'document', orderable:false, searchable:false },
            { data: 'action', orderable:false, searchable:false },
            { data: 'status', orderable:false, searchable:false }
        ]
    });

    $('#course_filter').change(function () {
        table.ajax.reload();
    });

     // 🔄 Reload table when date changes
    $('#from_date_filter, #to_date_filter').on('change', function () {
        table.ajax.reload();
    });

     $('#search').on('keyup', function () {
        table.search(this.value).draw();
    });

    $('#resetFilters').on('click', function () {

        // clear inputs
        $('#search').val('');
        $('#course_filter').val('');
        $('#from_date_filter').val('');
        $('#to_date_filter').val('');

        // clear yajra search also
        table.search('').columns().search('');

        // reload table
        table.ajax.reload();
    });

        // ✅ ACTIVE BUTTON
    $('#filterActive').on('click', function () {
        currentFilter = 'active';

        // button UI
        $('#filterActive')
            .addClass('btn-success active')
            .removeClass('btn-outline-secondary');

        $('#filterArchive')
            .addClass('btn-outline-secondary')
            .removeClass('btn-success active');

        table.ajax.reload();
    });

    // ✅ ARCHIVE BUTTON
    $('#filterArchive').on('click', function () {
        currentFilter = 'archive';

        // button UI
        $('#filterArchive')
            .addClass('btn-success active')
            .removeClass('btn-outline-secondary');

        $('#filterActive')
            .addClass('btn-outline-secondary')
            .removeClass('btn-success active');

        table.ajax.reload();
    });

   function getFilterInfo() {
    let info = [];
    if ($('#search').val()) info.push('Search: ' + $('#search').val());
    if ($('#course_filter').val())
        info.push('Course: ' + $('#course_filter option:selected').text());
    if ($('#from_date_filter').val())
        info.push('From: ' + $('#from_date_filter').val());
    if ($('#to_date_filter').val())
        info.push('To: ' + $('#to_date_filter').val());
    info.push('Status: ' + currentFilter.toUpperCase());
    return info.join(' | ');
}

function printTable() {
    let table = document.getElementById('medicalExemptionTable');
    let clone = table.cloneNode(true);

    clone.querySelectorAll('tr').forEach(row => {
        let cells = row.querySelectorAll('th,td');
        if (cells[10]) cells[10].remove();
        if (cells[10]) cells[10].remove();
    });

    let w = window.open('', '_blank');
    w.document.write(`
        <html>
        <head>
            <title>Medical Exemption</title>
            <style>
                body{font-family:Arial;margin:20px}
                table{width:100%;border-collapse:collapse}
                th,td{border:1px solid #000;padding:6px;font-size:11px}
                th{background:#af2910;color:#fff}
            </style>
        </head>
        <body>
            <h3>Medical Exemption Form</h3>
            <p>${getFilterInfo()}</p>
            ${clone.outerHTML}
        </body>
        </html>
    `);
    w.document.close();
    w.print();
    w.close();
}


});


</script>



@endpush