@extends('admin.layouts.master')

@section('title', 'Pending Meter Reading - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <x-breadcrum title="Pending Meter Reading"></x-breadcrum>

    <!-- Filter Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label for="bill_month" class="form-label">Select Bill Month <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="month" class="form-control" id="bill_month" name="bill_month" value="{{ date('Y-m') }}" required>
                        <button type="button" class="btn btn-primary" id="btnLoadPending">Load</button>
                    </div>
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> Select Bill Month and click Load to see pending readings.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div id="pendingTableWrap">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="pendingMeterReadingTable">
                        <thead class="table-primary">
                            <tr>
                                <th>S.No.</th>
                                <th>Employee Type</th>
                                <th>Name</th>
                                <th>House No.</th>
                                <th>Meter Reading Date</th>
                                <th>Last Meter Reading</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr id="noDataRow">
                                <td colspan="6" class="text-center text-muted">Select Bill Month and click Load to view pending meter readings.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var dataTableInstance = null;

    function destroyDataTable() {
        if (dataTableInstance && $.fn.DataTable.isDataTable('#pendingMeterReadingTable')) {
            dataTableInstance.destroy();
            $('#pendingMeterReadingTable tbody').empty();
            dataTableInstance = null;
        }
    }

    function loadPendingMeterReading() {
        var billMonth = $('#bill_month').val();
        if (!billMonth) {
            alert('Please select Bill Month.');
            return;
        }
        var parts = billMonth.split('-');
        var billYear = parts.length >= 1 ? parts[0] : '';

        $('#noDataRow').remove();
        $('#pendingMeterReadingTable tbody').html('<tr><td colspan="6" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: '{{ route("admin.estate.reports.pending-meter-reading.data") }}',
            type: 'GET',
            data: { bill_month: billMonth, bill_year: billYear },
            dataType: 'json',
            success: function(res) {
                destroyDataTable();
                var tbody = $('#pendingMeterReadingTable tbody');
                tbody.empty();
                if (res.status && res.data && res.data.length > 0) {
                    $.each(res.data, function(i, row) {
                        tbody.append(
                            '<tr>' +
                                '<td>' + (row.sno || (i + 1)) + '</td>' +
                                '<td>' + (row.employee_type || 'N/A') + '</td>' +
                                '<td>' + (row.name || 'N/A') + '</td>' +
                                '<td>' + (row.house_no || 'N/A') + '</td>' +
                                '<td>' + (row.meter_reading_date || '-') + '</td>' +
                                '<td>' + (row.last_meter_reading || 'N/A') + '</td>' +
                            '</tr>'
                        );
                    });
                } else {
                    tbody.append('<tr id="noDataRow"><td colspan="6" class="text-center text-muted">No pending meter readings for the selected month.</td></tr>');
                }
                dataTableInstance = $('#pendingMeterReadingTable').DataTable({
                    order: [[0, 'asc']],
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
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
                });
            },
            error: function(xhr) {
                destroyDataTable();
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to load data.';
                $('#pendingMeterReadingTable tbody').empty().append('<tr><td colspan="6" class="text-center text-danger">' + msg + '</td></tr>');
            }
        });
    }

    $('#btnLoadPending').on('click', loadPendingMeterReading);
    $('#bill_month').on('change', function() {
        destroyDataTable();
        $('#pendingMeterReadingTable tbody').empty().append('<tr id="noDataRow"><td colspan="6" class="text-center text-muted">Select Bill Month and click Load to view pending meter readings.</td></tr>');
    });
});
</script>
@endpush
