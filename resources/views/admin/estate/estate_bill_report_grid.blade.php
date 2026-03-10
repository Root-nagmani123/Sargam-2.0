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

    function initOrReloadBillReportGrid() {
        var billMonth = $('#bill_month').val();
        if (!billMonth) return;

        if (!billReportDt) {
            billReportDt = $('#estateBillReportTable').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                ajax: {
                    url: dataUrl,
                    data: function (d) {
                        d.bill_month = billMonth;
                    }
                },
                columns: [
                    { data: 'sno', orderable: false, searchable: false },
                    { data: 'employee_type' },
                    { data: 'name' },
                    { data: 'section' },
                    { data: 'building_name' },
                    { data: 'house_no' },
                    { data: 'from_date', searchable: false },
                    { data: 'to_date', searchable: false },
                    { data: 'meter_no', searchable: false, render: function(v){ return (v || '—').toString().replace(/\n/g,'<br>'); } },
                    { data: 'prev_reading', searchable: false, render: function(v){ return (v || '—').toString().replace(/\n/g,'<br>'); } },
                    { data: 'curr_reading', searchable: false, render: function(v){ return (v || '—').toString().replace(/\n/g,'<br>'); } },
                    { data: 'unit_consumed', searchable: false },
                    { data: 'total_charge', searchable: false, render: function(v){ return formatMoney(v); } },
                    { data: 'licence_fee', searchable: false, render: function(v){ return formatMoney(v); } },
                    { data: 'water_charges', searchable: false, render: function(v){ return formatMoney(v); } },
                    { data: 'grand_total', searchable: false, render: function(v){ return formatMoney(v); } },
                ],
                order: [[4, 'asc'], [5, 'asc']],
                responsive: false,
                autoWidth: false,
                scrollX: true,
                dom: '<"row flex-nowrap align-items-center py-2"<"col-12 col-sm-6 col-md-6 mb-2 mb-md-0"l><"col-12 col-sm-6 col-md-6"f>>rt<"row align-items-center py-2"<"col-12 col-sm-5 col-md-5"i><"col-12 col-sm-7 col-md-7"p>>'
            });
        } else {
            billReportDt.ajax.reload(null, true);
        }
    }

    $('#billReportGridFilterForm').on('submit', function(e) {
        e.preventDefault();
        initOrReloadBillReportGrid();
    });
});
</script>
@endpush
