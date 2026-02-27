@extends('admin.layouts.master')

@section('title', 'Estate Bill Report - Grid View - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <!-- Breadcrumb -->
    <x-breadcrum title="Estate Bill Report - Grid View"></x-breadcrum>

    <!-- Filter: Bill Month + Show -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold text-dark mb-1">List Bill For Other And Lbsna</h1>
            <p class="text-muted small mb-4">Only notified bills are listed. Select Bill Month and click Show.</p>
            <form id="billReportGridFilterForm" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="bill_month" class="form-label">Bill Month <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="month" class="form-control" id="bill_month" name="bill_month" value="{{ date('Y-m') }}" required>
                        <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                    </div>
                    <small class="text-muted d-block">Select Bill Month</small>
                </div>
                <div class="col-12 col-md-4">
                    <button type="submit" class="btn btn-primary" id="btnShow">
                        <i class="bi bi-search me-1"></i> Show
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table text-nowrap mb-0" id="estateBillReportTable">
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
                        <tr id="noDataRow">
                            <td colspan="16" class="text-center text-muted py-4">Select Bill Month and click Show to load data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
#estateBillReportTable .dtr-control,
#estateBillReportTable th.dtr-control,
#estateBillReportTable td.dtr-control { display: none !important; }
@media (max-width: 767.98px) {
    .table-scroll-vertical-sm { max-height: 65vh; overflow-y: auto; overflow-x: auto; -webkit-overflow-scrolling: touch; }
}
</style>
@endpush
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var billReportDt = null;
    var dataUrl = "{{ route('admin.estate.reports.bill-report-grid.data') }}";

    function formatMoney(n) {
        if (n == null || n === '' || isNaN(n)) return '—';
        return '₹ ' + parseFloat(n).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    function loadBillReportGrid() {
        var billMonth = $('#bill_month').val();
        if (!billMonth) {
            return;
        }
        $.ajax({
            url: dataUrl,
            type: 'GET',
            data: { bill_month: billMonth },
            dataType: 'json',
            success: function(res) {
                var data = (res && res.data) ? res.data : [];
                if (billReportDt) {
                    billReportDt.destroy();
                    billReportDt = null;
                }
                var tbody = $('#estateBillReportTable tbody');
                tbody.empty();
                if (data.length === 0) {
                    tbody.append('<tr id="noDataRow"><td colspan="16" class="text-center text-muted py-4">No notified bills for the selected month.</td></tr>');
                } else {
                    data.forEach(function(row) {
                        tbody.append(
                            '<tr>' +
                            '<td>' + (row.sno || '') + '</td>' +
                            '<td>' + (row.employee_type || '—') + '</td>' +
                            '<td>' + (row.name || '—') + '</td>' +
                            '<td>' + (row.section || '—') + '</td>' +
                            '<td>' + (row.building_name || '—') + '</td>' +
                            '<td>' + (row.house_no || '—') + '</td>' +
                            '<td>' + (row.from_date || '—') + '</td>' +
                            '<td>' + (row.to_date || '—') + '</td>' +
                            '<td>' + (String(row.meter_no || '').replace(/\n/g, '<br>') || '—') + '</td>' +
                            '<td>' + (String(row.prev_reading || '').replace(/\n/g, '<br>') || '—') + '</td>' +
                            '<td>' + (String(row.curr_reading || '').replace(/\n/g, '<br>') || '—') + '</td>' +
                            '<td>' + (row.unit_consumed ?? '—') + '</td>' +
                            '<td>' + formatMoney(row.total_charge) + '</td>' +
                            '<td>' + formatMoney(row.licence_fee) + '</td>' +
                            '<td>' + formatMoney(row.water_charges) + '</td>' +
                            '<td class="fw-semibold">' + formatMoney(row.grand_total) + '</td>' +
                            '</tr>'
                        );
                    });
                }
                billReportDt = $('#estateBillReportTable').DataTable({
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
            },
            error: function() {
                if (billReportDt) {
                    billReportDt.destroy();
                    billReportDt = null;
                }
                $('#estateBillReportTable tbody').empty().append(
                    '<tr id="noDataRow"><td colspan="16" class="text-center text-danger py-4">Failed to load data. Please try again.</td></tr>'
                );
            }
        });
    }

    $('#billReportGridFilterForm').on('submit', function(e) {
        e.preventDefault();
        loadBillReportGrid();
    });
});
</script>
@endpush
