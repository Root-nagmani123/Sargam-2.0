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
                </div>
                <div class="col-md-3 mt-3 mt-md-0">
                    <label for="employee_type_filter" class="form-label">Employee Type</label>
                    <select class="form-select rounded-1" id="employee_type_filter" name="employee_type_filter">
                        <option value="all" selected>All</option>
                        <option value="lbsnaa">LBSNAA</option>
                        <option value="other">OTHER</option>
                    </select>
                </div>
                <div class="col-md-2 mt-3 mt-md-0">
                    <button type="button" id="showPendingBtn" class="btn btn-primary rounded-1 px-3 w-100">Show</button>
                </div>
            </div>
            <small class="text-muted d-block mt-2">
                <i class="bi bi-info-circle"></i> Select Bill Month and click Show to load data.
            </small>
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
                            <th>Designation</th>
                            <th>House No.</th>
                            <th>Meter Reading Date</th>
                            <th>Last Meter Reading</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    var tableSelector = '#pendingMeterReadingTable';
    var dataUrl = '{{ route("admin.estate.reports.pending-meter-reading.data") }}';
    var colLabels = ['S.No.', 'Employee Type', 'Name', 'Designation', 'House No.', 'Meter Reading Date', 'Last Meter Reading'];

    function escapeHtml(str) {
        if (str == null || str === '') return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Filters are sent with every server-side request (the Show button just reloads the table).
    function currentFilters() {
        var billMonth = $('#bill_month').val() || '';
        var parts = billMonth.split('-');

        return {
            bill_month: billMonth,
            bill_year: parts.length >= 1 ? parts[0] : '',
            employee_type: $('#employee_type_filter').val() || 'all'
        };
    }

    var dataTableInstance = $(tableSelector).DataTable({
        processing: true,
        serverSide: true,
        deferLoading: 0, // wait for the user to press Show before hitting the server
        ajax: {
            url: dataUrl,
            type: 'GET',
            data: function(d) {
                return $.extend({}, d, currentFilters());
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'employee_type', name: 'employee_type', defaultContent: 'N/A' },
            { data: 'name', name: 'name', defaultContent: 'N/A' },
            { data: 'designation', name: 'designation', defaultContent: 'N/A' },
            { data: 'house_no', name: 'house_no', defaultContent: 'N/A' },
            { data: 'meter_reading_date', name: 'meter_reading_date', defaultContent: '-' },
            { data: 'last_meter_reading', name: 'last_meter_reading', defaultContent: 'N/A' }
        ],
        order: [],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        searching: true,
        language: {
            processing: 'Loading data…',
            emptyTable: 'Select Bill Month and click Show to load pending meter readings.',
            zeroRecords: 'No pending meter readings for the selected month.',
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
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        initComplete: function() {
            // Toolbar: right-align filter, add Show/Hide columns + Print
            var $wrapper = $(tableSelector).closest('.dataTables_wrapper');
            var $filter = $wrapper.find('.dataTables_filter');
            $filter.addClass('d-flex align-items-center justify-content-end flex-wrap gap-2');

            var $colDropdown = $('<div class="dropdown d-inline-block" data-bs-auto-close="outside">' +
                '<button class="btn btn-outline-secondary btn-sm rounded-1 dropdown-toggle" type="button" id="pendingMeterColDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Show/Hide columns"><i class="material-icons material-symbols-rounded" style="font-size:18px;vertical-align:middle">view_column</i> Columns</button>' +
                '<ul class="dropdown-menu dropdown-menu-end py-2" aria-labelledby="pendingMeterColDropdown" id="pendingMeterColMenu"></ul></div>');
            var $colMenu = $colDropdown.find('#pendingMeterColMenu');
            colLabels.forEach(function(label, idx) {
                var $li = $('<li>' +
                    '<div class="dropdown-item px-3 py-1">' +
                        '<div class="form-check d-flex align-items-center mb-0">' +
                            '<input type="checkbox" class="form-check-input me-2 column-toggle" data-column="' + idx + '" checked>' +
                            '<label class="form-check-label cursor-pointer">' + label + '</label>' +
                        '</div>' +
                    '</div>' +
                '</li>');
                $li.find('input.column-toggle').on('change', function(e) {
                    e.stopPropagation();
                    dataTableInstance.column($(this).data('column')).visible(this.checked);
                });
                $li.find('label.form-check-label').on('click', function(e) {
                    e.preventDefault();
                    var $checkbox = $(this).closest('.form-check').find('input.column-toggle');
                    $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
                });
                $colMenu.append($li);
            });
            $colDropdown.find('.dropdown-item').on('click', function(e) { e.stopPropagation(); });

            var $printBtn = $('<button type="button" class="btn btn-outline-secondary btn-sm rounded-1 d-inline-flex align-items-center" id="btnPrintPendingMeter" title="Print"><i class="material-icons material-symbols-rounded" style="font-size:18px">print</i></button>');
            $filter.append($colDropdown).append($printBtn);
        },
        drawCallback: function(settings) {
            // Surface server messages (e.g. "No meter reading entries found for selected month/year.")
            var message = settings.json && settings.json.message;
            if (message && settings.json.recordsTotal === 0) {
                $(tableSelector + ' tbody .dataTables_empty').text(message);
            }
        }
    });

    function loadPendingMeterReading() {
        if (!$('#bill_month').val()) {
            alert('Please select Bill Month.');
            return;
        }
        dataTableInstance.ajax.reload();
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

    // Print: pull every row for the current filters/search instead of just the visible page.
    $(document).on('click', '#btnPrintPendingMeter', function() {
        var $btn = $(this);
        var visibleIndexes = [];
        dataTableInstance.columns().every(function(i) {
            if (this.visible()) visibleIndexes.push(i);
        });
        if (visibleIndexes.length === 0) {
            alert('At least one column must be visible to print.');
            return;
        }

        var keys = ['DT_RowIndex', 'employee_type', 'name', 'designation', 'house_no', 'meter_reading_date', 'last_meter_reading'];
        var params = $.extend({}, dataTableInstance.ajax.params(), { start: 0, length: -1 });
        $btn.prop('disabled', true);

        $.ajax({
            url: dataUrl,
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(res) {
                var rows = (res && res.data) || [];
                var tableHtml = '<table class="table align-middle mb-0" style="width:100%;border-collapse:collapse;font-size:12px;"><thead><tr>';
                visibleIndexes.forEach(function(colIdx) {
                    tableHtml += '<th style="border:1px solid #ddd;padding:8px;background:#f5f5f5;">' + escapeHtml(colLabels[colIdx]) + '</th>';
                });
                tableHtml += '</tr></thead><tbody>';
                rows.forEach(function(row) {
                    tableHtml += '<tr>';
                    visibleIndexes.forEach(function(colIdx) {
                        tableHtml += '<td style="border:1px solid #ddd;padding:8px;">' + escapeHtml(row[keys[colIdx]]) + '</td>';
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
            },
            error: function() {
                alert('Failed to load data for printing.');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
