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
    
    #medicalExemptionTable, #medicalExemptionTable * {
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
                  <div class="row">
                        <div class="col-6">
                            <h4>Medical Exemption Form</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">

                                <!-- Print Button -->
                                <button type="button" class="btn btn-info d-flex align-items-center" onclick="printTable()">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">print</i>
                                    Print
                                </button>
                                <!-- Export Button -->
                                <a href="{{ route('student.medical.exemption.export', [
                                    'filter' => $filter,
                                    'course_filter' => $courseFilter ?? '',
                                    'faculty_filter' => $facultyFilter ?? '',
                                    'date_filter' => $dateFilter ?? ''
                                ]) }}" class="btn btn-success d-flex align-items-center">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">download</i>
                                    Export Excel
                                </a>
                                <!-- Add Group Mapping -->
                                <a href="{{route('student.medical.exemption.create')}}"
                                    class="btn btn-primary d-flex align-items-center">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 24px;">add</i>
                                    Add Student Medical Exemption
                                </a>

                            </div>
                        </div>
                    </div>

                    <hr>
                    
                    <!-- Filters Section -->
                    <div class="row mb-3 align-items-end">
                        <!-- Course Filter -->
                        <div class="col-md-3">
                            <label for="course_filter" class="form-label fw-semibold">Course</label>
                            <select name="course_filter" id="course_filter" class="form-select">
                                <option value="">-- All Courses --</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->pk }}" {{ $courseFilter == $course->pk ? 'selected' : '' }}>
                                        {{ $course->course_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Faculty Filter -->
                        <div class="col-md-3">
                            <label for="faculty_filter" class="form-label fw-semibold">Faculty</label>
                            <select name="faculty_filter" id="faculty_filter" class="form-select">
                                <option value="">-- All Faculty --</option>
                                @foreach($employees ?? [] as $employee)
                                    <option value="{{ $employee['pk'] }}" {{ (isset($facultyFilter) && $facultyFilter == $employee['pk']) ? 'selected' : '' }}>
                                        {{ $employee['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Today Filter -->
                        <div class="col-md-2">
                            <label for="date_filter" class="form-label fw-semibold">Date Filter</label>
                            <select name="date_filter" id="date_filter" class="form-select">
                                <option value="">-- All Dates --</option>
                                <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>Today</option>
                            </select>
                            @if($dateFilter === 'today')
                                <div class="mt-2">
                                    <span class="badge bg-primary fs-6 px-3 py-2 d-inline-flex align-items-center">
                                        <i class="bi bi-calendar-check me-1"></i> Total Today: <strong class="ms-1">{{ $todayTotalCount }}</strong>
                                    </span>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Reset Button -->
                        <div class="col-md-2">
                            <label class="form-label fw-semibold d-block">&nbsp;</label>
                            <a href="{{ route('student.medical.exemption.index', ['filter' => 'active']) }}"
                                class="btn btn-outline-danger w-100 fw-semibold"
                                title="Reset all filters">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                            </a>
                        </div>
                        
                        <!-- Active/Archive Buttons -->
                        <div class="col-md-2 text-end">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <div class="btn-group shadow-sm rounded-pill overflow-hidden" role="group"
                                    aria-label="Course Status Filter">
                                    @php
                                        $activeParams = ['filter' => 'active'];
                                        $archiveParams = ['filter' => 'archive'];
                                        if ($courseFilter) {
                                            $activeParams['course_filter'] = $courseFilter;
                                            $archiveParams['course_filter'] = $courseFilter;
                                        }
                                        if (isset($facultyFilter) && $facultyFilter) {
                                            $activeParams['faculty_filter'] = $facultyFilter;
                                            $archiveParams['faculty_filter'] = $facultyFilter;
                                        }
                                        if ($dateFilter) {
                                            $activeParams['date_filter'] = $dateFilter;
                                            $archiveParams['date_filter'] = $dateFilter;
                                        }
                                    @endphp
                                    <a href="{{ route('student.medical.exemption.index', $activeParams) }}"
                                        class="btn {{ $filter === 'active' ? 'btn-success active' : 'btn-outline-secondary' }} px-4 fw-semibold"
                                        id="filterActive" aria-pressed="{{ $filter === 'active' ? 'true' : 'false' }}">
                                        <i class="bi bi-check-circle me-1"></i> Active
                                    </a>
                                    <a href="{{ route('student.medical.exemption.index', $archiveParams) }}"
                                        class="btn {{ $filter === 'archive' ? 'btn-success active' : 'btn-outline-secondary' }} px-4 fw-semibold"
                                        id="filterArchive" aria-pressed="{{ $filter === 'archive' ? 'true' : 'false' }}">
                                        <i class="bi bi-archive me-1"></i> Archive
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered w-100" id="medicalExemptionTable">
                            <thead style="background-color: #af2910;">
                                <tr>
                                    <th class="col">#</th>
                                    <th class="col">Student</th>
                                    <th class="col">OT Code</th>
                                    <th class="col">Course</th>
                                    <th class="col">Faculty</th>
                                    <th class="col">Category</th>
                                    <th class="col">Medical Speciality</th>
                                    <th class="col">From-To</th>
                                    <th class="col">OPD Type</th>
                                    <th class="col">Document</th>
                                    <th class="col">Action</th>
                                    <th class="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($records as $index => $row)
                                <tr>
                                    <td>{{ $records->firstItem() + $index }}</td>
                                    <td>{{ $row->student->display_name ?? 'N/A' }}</td>
                                    <td>{{ $row->student->generated_OT_code ?? 'N/A' }}</td>
                                    <td>{{ $row->course->course_name ?? 'N/A' }}</td>
                                    <td>{{ ($row->employee && $row->employee->first_name && $row->employee->last_name) ? trim($row->employee->first_name . ' ' . $row->employee->last_name) : 'N/A' }}</td>
                                    <td>{{ $row->category->exemp_category_name ?? 'N/A' }}</td>
                                    <td>{{ $row->speciality->speciality_name ?? 'N/A' }}</td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($row->from_date)->format('d-m-Y') }}
                                        to
                                        {{ \Carbon\Carbon::parse($row->to_date)->format('d-m-Y') }}
                                    </td>

                                    <td>{{ $row->opd_category }}</td>
                                    <td class="text-center">
                                        @if($row->Doc_upload)
                                            <a href="{{ asset('storage/' . $row->Doc_upload) }}" target="_blank" 
                                               class="btn btn-sm btn-info"
                                               title="View Document"
                                               data-bs-toggle="tooltip" data-bs-placement="top">
                                                <i class="material-icons menu-icon material-symbols-rounded" style="font-size: 20px;">description</i>
                                            </a>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a
                                            href="{{ route('student.medical.exemption.edit', ['id' => encrypt(value: $row->pk)])  }}"><i
                                                class="material-icons menu-icon material-symbols-rounded"
                                                style="font-size: 24px;">edit</i></a>

                                        <form
                                            title="{{ $row->active_inactive == 1 ? 'Cannot delete active course group type' : 'Delete' }}"
                                            action="{{ route('student.medical.exemption.delete', 
                                                    ['id' => encrypt($row->pk)]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <a href="javascript:void(0)" onclick="event.preventDefault(); 
                                                        if(confirm('Are you sure you want to delete this record?')) {
                                                            this.closest('form').submit();
                                                        }" {{ $row->active_inactive == 1 ? 'disabled' : '' }}>
                                                <i class="material-icons menu-icon material-symbols-rounded"
                                                    style="font-size: 24px;">delete</i>
                                            </a>
                                        </form>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                data-table="student_medical_exemption" data-column="active_inactive"
                                                data-id="{{ $row->pk }}"
                                                {{ $row->active_inactive == 1 ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="12" class="text-center py-5">
                                        <div class="d-flex flex-column align-items-center justify-content-center">
                                            <i class="material-icons menu-icon material-symbols-rounded" 
                                               style="font-size: 64px; color: #ccc; margin-bottom: 16px;">
                                                search_off
                                            </i>
                                            <h5 class="text-muted mb-2">No Record Found</h5>
                                            @if($filter || $courseFilter || (isset($facultyFilter) && $facultyFilter) || $dateFilter)
                                                <p class="text-muted small mb-0">
                                                    No records match the applied filters. 
                                                    <a href="{{ route('student.medical.exemption.index') }}" class="text-primary">
                                                        Clear filters
                                                    </a> to see all records.
                                                </p>
                                            @else
                                                <p class="text-muted small mb-0">No medical exemption records available.</p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        @if($records->total() > 0)
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                Showing {{ $records->firstItem() ?? 0 }} to {{ $records->lastItem() ?? 0 }} of
                                {{ $records->total() }} entries
                            </div>
                            <div>
                                {{ $records->appends([
                                    'filter' => $filter,
                                    'course_filter' => $courseFilter,
                                    'faculty_filter' => $facultyFilter ?? '',
                                    'date_filter' => $dateFilter ?? ''
                                ])->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                        @endif
                    </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Get filter information for print header
function getFilterInfo() {
    var info = [];
    var filter = '{{ $filter }}';
    var courseFilter = '';
    var dateFilter = '';
    
    // Get values using vanilla JS to avoid jQuery dependency
    var courseSelect = document.getElementById('course_filter');
    var dateSelect = document.getElementById('date_filter');
    
    if (courseSelect) {
        courseFilter = courseSelect.options[courseSelect.selectedIndex].text;
    }
    if (dateSelect) {
        dateFilter = dateSelect.options[dateSelect.selectedIndex].text;
    }
    
    if (filter) {
        info.push('Status: ' + (filter === 'active' ? 'Active' : 'Archive'));
    }
    if (courseFilter && courseFilter !== '-- All Courses --') {
        info.push('Course: ' + courseFilter);
    }
    if (dateFilter && dateFilter !== '-- All Dates --') {
        info.push('Date: ' + dateFilter);
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

$(document).ready(function() {
    // Function to build and navigate with filters
    function applyFilters() {
        var filter = '{{ $filter }}';
        var courseFilter = $('#course_filter').val();
        var facultyFilter = $('#faculty_filter').val();
        var dateFilter = $('#date_filter').val();
        var url = '{{ route("student.medical.exemption.index") }}';
        var params = { filter: filter };
        
        if (courseFilter) {
            params.course_filter = courseFilter;
        }
        
        if (facultyFilter) {
            params.faculty_filter = facultyFilter;
        }
        
        if (dateFilter) {
            params.date_filter = dateFilter;
        }
        
        // Build URL with query parameters
        var queryString = Object.keys(params).map(key => key + '=' + encodeURIComponent(params[key])).join('&');
        window.location.href = url + '?' + queryString;
    }
    
    // Auto-submit when course filter changes
    $('#course_filter').on('change', function() {
        applyFilters();
    });
    
    // Auto-submit when faculty filter changes
    $('#faculty_filter').on('change', function() {
        applyFilters();
    });
    
    // Auto-submit when date filter changes
    $('#date_filter').on('change', function() {
        applyFilters();
    });
});
</script>
@endpush