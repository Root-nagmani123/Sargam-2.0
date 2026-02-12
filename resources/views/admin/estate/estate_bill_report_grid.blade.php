@extends('admin.layouts.master')

@section('title', 'Estate Bill Report - Grid View - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.reports.pending-meter-reading') }}">Estate Reports</a></li>
            <li class="breadcrumb-item active" aria-current="page">Estate Bill Report - Grid View</li>
        </ol>
    </nav>

    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">List Bill For Other And Lbsna</h2>
        <div>
            <a href="{{ route('admin.estate.reports.pending-meter-reading') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-speedometer2 me-2"></i>Pending Meter Reading
            </a>
            <a href="{{ route('admin.estate.reports.house-status') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-house me-2"></i>House Status
            </a>
            <a href="{{ route('admin.estate.reports.bill-report-print') }}" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-2"></i>Print View
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label for="bill_month" class="form-label">Bill Month <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="bill_month" name="bill_month" value="November 2025" required>
                        <span class="input-group-text">
                            <i class="bi bi-calendar"></i>
                        </span>
                    </div>
                    <small class="text-muted">Select Bill Month</small>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-secondary w-100">Show</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Controls -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <label class="form-label">Show</label>
                    <select class="form-select form-select-sm">
                        <option value="10" selected>10 entries</option>
                        <option value="25">25 entries</option>
                        <option value="50">50 entries</option>
                        <option value="100">100 entries</option>
                    </select>
                </div>
                <div class="col-md-9 text-end">
                    <button class="btn btn-outline-secondary btn-sm me-2">Show / hide columns</button>
                    <button class="btn btn-outline-secondary btn-sm me-2">
                        <i class="bi bi-grid-3x3-gap"></i>
                    </button>
                    <button class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-printer"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <label class="form-label">Search with in table:</label>
                    <input type="text" class="form-control form-control-sm" placeholder="Search...">
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped" id="estateBillReportTable">
                    <thead class="table-primary">
                        <tr>
                            <th>
                                <input type="checkbox" class="form-check-input" id="select_all">
                            </th>
                            <th>S.NO.</th>
                            <th>EMPLOYEE TYPE</th>
                            <th>NAME</th>
                            <th>SECTION</th>
                            <th>BUILDING NAME</th>
                            <th>HOUSE NO.</th>
                            <th>FROM DATE</th>
                            <th>TO DATE</th>
                            <th>METER NO.</th>
                            <th>PREVIOUS METER READING</th>
                            <th>CURRENT METER READING</th>
                            <th>UNIT CONSUMED</th>
                            <th>TOTAL CHARGE</th>
                            <th>LICENCE FEE</th>
                            <th>WATER CHARGE</th>
                            <th>GRAND TOTAL</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td>1</td>
                            <td>Other Employee</td>
                            <td>Dr. VIPUL TOMAR</td>
                            <td>Medical Officer</td>
                            <td>Himgiri Avas</td>
                            <td>HG-01</td>
                            <td>08-11-2025</td>
                            <td>30-11-2025</td>
                            <td>50302<br>50302</td>
                            <td>50250<br>50250</td>
                            <td>50302<br>50302</td>
                            <td>491<br>491</td>
                            <td>₹ 48.00<br>₹ 880.00</td>
                            <td>₹ 300.00</td>
                            <td>₹ 20.00</td>
                            <td>₹ 2798.00</td>
                            <td>
                                <a href="{{ route('admin.estate.reports.bill-report-print') }}" class="btn btn-sm btn-info" title="Print">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td>2</td>
                            <td>Other Employee</td>
                            <td>Harish Joshi PCS</td>
                            <td>PCS</td>
                            <td>River View New Type-III</td>
                            <td>RVN-12</td>
                            <td>08-11-2025</td>
                            <td>30-11-2025</td>
                            <td>50302</td>
                            <td>50250</td>
                            <td>50302</td>
                            <td>491</td>
                            <td class="text-end">₹ 880.00</td>
                            <td class="text-end">₹ 300.00</td>
                            <td class="text-end">₹ 20.00</td>
                            <td class="text-end fw-bold">₹ 1200.00</td>
                            <td>
                                <a href="{{ route('admin.estate.reports.bill-report-print') }}" class="btn btn-sm btn-info" title="Print">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td><input type="checkbox" class="form-check-input"></td>
                            <td>3</td>
                            <td>Other Employee</td>
                            <td>Mamta Senwal</td>
                            <td>CPWD(C)</td>
                            <td>Alakhnanda Awas</td>
                            <td>AA-06</td>
                            <td>08-11-2025</td>
                            <td>30-11-2025</td>
                            <td>50302</td>
                            <td>50250</td>
                            <td>50302</td>
                            <td>491</td>
                            <td class="text-end">₹ 880.00</td>
                            <td class="text-end">₹ 300.00</td>
                            <td class="text-end">₹ 20.00</td>
                            <td class="text-end fw-bold">₹ 1200.00</td>
                            <td>
                                <a href="{{ route('admin.estate.reports.bill-report-print') }}" class="btn btn-sm btn-info" title="Print">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#estateBillReportTable').DataTable({
        order: [[1, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        responsive: true,
        autoWidth: false,
        scrollX: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });
});
</script>
@endpush
