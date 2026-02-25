@extends('admin.layouts.master')

@section('title', 'Estate Bill Report - Grid View - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <!-- Breadcrumb -->
    <x-breadcrum title="Estate Bill Report - Grid View"></x-breadcrum>

    <!-- Data Table Card: vertical scroll on small screens -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table text-nowrap" id="estateBillReportTable">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Employee Type</th>
                            <th>Name</th>
                            <th>Section</th>
                            <th>Building</th>
                            <th>House No.</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Meter No.</th>
                            <th>Prev. Reading</th>
                            <th>Curr. Reading</th>
                            <th>Units</th>
                            <th>Total Charge</th>
                            <th>Licence</th>
                            <th>Water</th>
                            <th>Grand Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
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
                            <td class="fw-semibold">₹ 2798.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Hide DataTables responsive control column (no expand arrow) */
#estateBillReportTable .dtr-control,
#estateBillReportTable th.dtr-control,
#estateBillReportTable td.dtr-control { display: none !important; }

/* Vertical scroll on small screens only; horizontal scroll via .table-responsive */
@media (max-width: 767.98px) {
    .table-scroll-vertical-sm {
        max-height: 65vh;
        overflow-y: auto;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}
</style>
@endpush
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
            paginate: { first: "First", last: "Last", next: "Next", previous: "Previous" }
        },
        responsive: false,
        autoWidth: false,
        scrollX: true,
        dom: '<"row flex-nowrap align-items-center py-2"<"col-12 col-sm-6 col-md-6 mb-2 mb-md-0"l><"col-12 col-sm-6 col-md-6"f>>rt<"row align-items-center py-2"<"col-12 col-sm-5 col-md-5"i><"col-12 col-sm-7 col-md-7"p>>'
    });

});
</script>
@endpush
