@extends('admin.layouts.master')

@section('title', 'Pending Meter Reading - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Pending Meter Reading"></x-breadcrum>
    <x-session_message />

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4">
                    <label for="bill_month" class="form-label">Select Bill Month <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="month" class="form-control" id="bill_month" name="bill_month" value="{{ date('Y-m') }}" max="{{ date('Y-m') }}" required>
                    </div>
                    <small class="text-muted">
                        <i class="bi bi-info-circle"></i> Select Bill Month and click Show to load data.
                    </small>
                </div>
                <div class="col-md-2 mt-3 mt-md-0">
                    <button type="button" id="showPendingBtn" class="btn btn-primary rounded-1 px-3 w-100">Show</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="pendingMeterReadingTable">
                    <thead>
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
                        <tr id="initialInfoRow">
                            <td colspan="6" class="text-center text-muted">Select Bill Month and click Show to load pending meter readings.</td>
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
    var dataTableInstance = null;
    var tableSelector = '#pendingMeterReadingTable';

    function destroyDataTable() {
        if ($.fn.DataTable.isDataTable(tableSelector)) {
            $(tableSelector).DataTable().destroy();
        }
        dataTableInstance = null;
        $(tableSelector + ' tbody').empty();
    }

    function loadPendingMeterReading() {
        var billMonth = $('#bill_month').val();
        if (!billMonth) {
            alert('Please select Bill Month.');
            return;
        }

        var parts = billMonth.split('-');
        var billYear = parts.length >= 1 ? parts[0] : '';
        destroyDataTable();
        $(tableSelector + ' tbody').html('<tr><td colspan="6" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: '{{ route("admin.estate.reports.pending-meter-reading.data") }}',
            type: 'GET',
            data: { bill_month: billMonth, bill_year: billYear },
            dataType: 'json',
            success: function(res) {
                var tbody = $(tableSelector + ' tbody');
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

                    dataTableInstance = $(tableSelector).DataTable({
                        order: [[0, 'asc']],
                        pageLength: 10,
                        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                        searching: true,
                        language: {
                            search: 'Search:',
                            lengthMenu: 'Show _MENU_ entries',
                            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                            infoEmpty: 'Showing 0 to 0 of 0 entries',
                            infoFiltered: '(filtered from _MAX_ total entries)',
                            paginate: {
                                first: 'First',
                                last: 'Last',
                                next: 'Next',
                                previous: 'Previous'
                            }
                        },
                        responsive: true,
                        autoWidth: false,
                        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
                    });

                    // Toolbar: right-align filter, add Show/Hide columns + Print
                    var $wrapper = $(tableSelector).closest('.dataTables_wrapper');
                    var $filter = $wrapper.find('.dataTables_filter');
                    $filter.addClass('d-flex align-items-center justify-content-end flex-wrap gap-2');

                    var colLabels = ['S.No.', 'Employee Type', 'Name', 'House No.', 'Meter Reading Date', 'Last Meter Reading'];
                    var $colDropdown = $('<div class="dropdown d-inline-block" data-bs-auto-close="outside">' +
                        '<button class="btn btn-outline-secondary btn-sm rounded-1 dropdown-toggle" type="button" id="pendingMeterColDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Show/Hide columns"><i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle">view_column</i> Columns</button>' +
                        '<ul class="dropdown-menu dropdown-menu-end py-2" aria-labelledby="pendingMeterColDropdown" id="pendingMeterColMenu"></ul></div>');
                    var $colMenu = $colDropdown.find('#pendingMeterColMenu');
                    colLabels.forEach(function(label, idx) {
                        var $li = $('<li><label class="dropdown-item d-flex align-items-center gap-2 mb-0 cursor-pointer"><input type="checkbox" class="form-check-input column-toggle" data-column="' + idx + '" checked> ' + label + '</label></li>');
                        $li.find('input').on('change', function() {
                            dataTableInstance.column($(this).data('column')).visible(this.checked);
                        });
                        $colMenu.append($li);
                    });
                    $colDropdown.find('.dropdown-item').on('click', function(e) { e.stopPropagation(); });

                    var $printBtn = $('<button type="button" class="btn btn-outline-secondary btn-sm rounded-1 d-inline-flex align-items-center" id="btnPrintPendingMeter" title="Print"><i class="material-icons material-symbols-rounded" style="font-size:18px">print</i></button>');
                    $filter.append($colDropdown).append($printBtn);

                    $('#btnPrintPendingMeter').on('click', function() {
                        var dt = $(tableSelector).DataTable();
                        var visibleIndexes = [];
                        dt.columns().every(function(i) {
                            if (this.visible()) visibleIndexes.push(i);
                        });
                        if (visibleIndexes.length === 0) {
                            alert('At least one column must be visible to print.');
                            return;
                        }
                        var tableHtml = '<table class="table align-middle mb-0" style="width:100%;border-collapse:collapse;font-size:12px;"><thead><tr>';
                        visibleIndexes.forEach(function(colIdx) {
                            var h = ($(dt.column(colIdx).header()).text() || '').trim();
                            tableHtml += '<th style="border:1px solid #ddd;padding:8px;background:#f5f5f5;">' + h + '</th>';
                        });
                        tableHtml += '</tr></thead><tbody>';
                        dt.rows({ search: 'applied' }).nodes().each(function(rowNode) {
                            var $row = $(rowNode);
                            if ($row.find('td').length === 0) return;
                            tableHtml += '<tr>';
                            visibleIndexes.forEach(function(colIdx) {
                                var cellNode = dt.cell(rowNode, colIdx).node();
                                var cellHtml = (cellNode && cellNode.innerHTML) ? $(cellNode).text().trim() : '';
                                tableHtml += '<td style="border:1px solid #ddd;padding:8px;">' + (cellHtml || '') + '</td>';
                            });
                            tableHtml += '</tr>';
                        });
                        tableHtml += '</tbody></table>';
                        var win = window.open('', '_blank', 'width=1000,height=700');
                        if (!win) { alert('Please allow popups to print.'); return; }
                        win.document.write('<!doctype html><html><head><title>Pending Meter Reading</title><style>body{font-family:Arial,sans-serif;padding:16px;} table{width:100%;border-collapse:collapse;font-size:12px;} th,td{border:1px solid #ddd;padding:8px;} th{background:#f5f5f5;}</style></head><body><h2>Pending Meter Reading</h2>' + tableHtml + '</body></html>');
                        win.document.close();
                        win.onafterprint = function() { win.close(); };
                        setTimeout(function() { win.focus(); win.print(); }, 250);
                    });
                } else {
                    tbody.append('<tr id="noDataRow"><td colspan="6" class="text-center text-muted">' + (res.message || 'No pending meter readings for the selected month.') + '</td></tr>');
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to load data.';
                $(tableSelector + ' tbody').empty().append('<tr><td colspan="6" class="text-center text-danger">' + msg + '</td></tr>');
            }
        });
    }

    $('#showPendingBtn').on('click', function() {
        loadPendingMeterReading();
    });

    $('#bill_month').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            loadPendingMeterReading();
        }
    });
});
</script>
@endpush
