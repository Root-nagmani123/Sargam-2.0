@extends('admin.layouts.master')

@section('title', 'Student Medical Exemption - Sargam | Lal Bahadur')

@section('css')
    @parent
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endsection
@section('setup_content')
<div class="container-fluid student-medical-exemption-index py-2 py-md-3">
    <x-breadcrum title="Medical Exemption Form" />
    <div class="datatables">
        <!-- start Zero Configuration -->
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header border-0 bg-body-tertiary pb-2 pb-md-3">
                <div class="row align-items-center g-3">

                    <!-- Title -->
                    <div class="col-12 col-lg-4 col-md-12">
                        <h4 class="mb-0 fw-bold text-dark">
                            Medical Exemption Form
                        </h4>
                    </div>

                     <!-- Active / Archive -->
                    <div class="col-12 col-lg-4 col-md-6 text-md-end text-start">
                        <div class="btn-group shadow-sm rounded-1 overflow-hidden" role="group"
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
            </div>

            <div class="card-body">
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
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
$(document).ready(function () {

    // Initialize Choices.js on select filters
    if (typeof Choices !== 'undefined') {
        document.querySelectorAll('.student-medical-exemption-index select').forEach(function (el) {
            if (el.dataset.choicesInitialized === 'true') return;

            new Choices(el, {
                allowHTML: false,
                searchPlaceholderValue: 'Search...',
                removeItemButton: !!el.multiple,
                shouldSort: false,
                placeholder: true,
                placeholderValue: el.getAttribute('placeholder') || el.options[0]?.text || 'Select an option',
            });

            el.dataset.choicesInitialized = 'true';
        });
    }

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