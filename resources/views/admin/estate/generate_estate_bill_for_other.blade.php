@extends('admin.layouts.master')

@section('title', 'Generate Estate Bill for Other - Sargam')

@section('content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Generate Estate Bill for Other"></x-breadcrum>
    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row flex-wrap align-items-start align-items-md-center justify-content-between gap-3 mb-3 no-print">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Generate Estate Bill for Other</h1>
                    <p class="text-muted small mb-0">Contract employees. Select Bill Month and click Show to list bills.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Show / hide columns">
                            <i class="bi bi-columns-gap"></i>
                            <span class="d-none d-md-inline ms-1">Show / hide columns</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" id="columnToggleMenu"></ul>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" id="btnPrint" title="Print">
                        <i class="material-icons material-symbols-rounded">print</i>
                        <span class="d-none d-md-inline">Print</span>
                    </button>
                </div>
            </div>

            <form id="billForOtherFilterForm" class="row g-3 align-items-start">
                <div class="col-12 col-md-4">
                    <label for="bill_month" class="form-label">Bill Month <span class="text-danger">*</span></label>
                    <input type="month" class="form-control" id="bill_month" name="bill_month" value="{{ date('Y-m') }}" max="{{ date('Y-m') }}" required>
                    <small class="text-muted d-block">Select Bill Month</small>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label d-none d-md-block mb-2">&nbsp;</label>
                    <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="check_all_bills" name="check_all">
                        <label class="form-check-label" for="check_all_bills">Check All</label>
                    </div>
                    <button type="submit" class="btn btn-primary" id="btnShow">
                        <i class="bi bi-search me-1"></i> Show
                    </button>
                    </div>
                </div>
                <div class="col-12 col-md-4 d-flex flex-wrap gap-2 align-items-center justify-content-md-end">
                    <!-- <button type="button" class="btn btn-outline-success btn-sm" id="btnVerifySelected" title="Mark selected bills as verified (notify employee)">
                        <i class="bi bi-check2-circle me-1"></i> Verify Selected Bills
                    </button> -->
                    <!-- <button type="button" class="btn btn-outline-secondary btn-sm" id="btnSaveAsDraft" title="Save selected bills as draft">
                        <i class="bi bi-save me-1"></i> Save As Draft
                    </button> -->
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4">
            <div class="bill-for-other-table-wrapper table-responsive">
                <table class="table text-nowrap mb-0" id="billForOtherTable">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 40px;"><input type="checkbox" id="billForOtherCheckAll" class="form-check-input" title="Select all"></th>
                            <th>S.NO.</th>
                            <th>NAME</th>
                            <th>SECTION</th>
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
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="noDataRow">
                            <td colspan="15" class="text-center text-muted py-4">Select Bill Month and click Show to load data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
#billForOtherTable .dtr-control,
#billForOtherTable th.dtr-control,
#billForOtherTable td.dtr-control { display: none !important; }
@media (max-width: 767.98px) {
    .table-scroll-vertical-sm { max-height: 65vh; overflow-y: auto; overflow-x: auto; -webkit-overflow-scrolling: touch; }
}
@media print {
    @page {
        size: A4 landscape;
        margin: 8mm;
    }
    .no-print { display: none !important; }
    #billForOtherTable_wrapper .dataTables_length,
    #billForOtherTable_wrapper .dataTables_filter,
    #billForOtherTable_wrapper .dataTables_paginate { display: none !important; }

    .bill-for-other-table-wrapper,
    #billForOtherTable_wrapper .dataTables_scroll,
    #billForOtherTable_wrapper .dataTables_scrollBody,
    #billForOtherTable_wrapper .dataTables_scrollHead {
        overflow: visible !important;
    }
    #billForOtherTable_wrapper .dataTables_scrollBody {
        height: auto !important;
        max-height: none !important;
    }
    #billForOtherTable_wrapper .dataTables_scrollHead {
        display: none !important;
    }
    #billForOtherTable_wrapper table,
    #billForOtherTable_wrapper table.dataTable {
        width: 100% !important;
    }
    body {
        zoom: 0.78;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    #billForOtherTable_wrapper th,
    #billForOtherTable_wrapper td {
        white-space: normal !important;
        word-break: break-word;
        font-size: 11px;
        padding: 0.35rem 0.4rem !important;
    }
    #billForOtherTable_wrapper thead { display: table-header-group; }
}
.bill-for-other-table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    var dataTableInstance = null;
    var dataUrl = "{{ route('admin.estate.generate-estate-bill-for-other.data') }}";

    function formatMoney(n) {
        if (n == null || n === '' || isNaN(n)) return '—';
        return '₹ ' + parseFloat(n).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    function escapeHtml(str) {
        if (str == null || str === '') return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str)));
        return div.innerHTML;
    }

    function buildColumnToggle() {
        if (!dataTableInstance) return;
        var table = dataTableInstance;
        var menu = $('#columnToggleMenu');
        menu.empty();
        table.columns().every(function(i) {
            var col = this;
            var header = $(col.header()).text().trim();
            if (!header || header === 'S.NO.') return;
            var $li = $('<li><label class="dropdown-item d-flex align-items-center gap-2 cursor-pointer mb-0"><input type="checkbox" class="form-check-input column-toggle" data-column="' + i + '"> ' + header + '</label></li>');
            $li.find('input').prop('checked', col.visible());
            menu.append($li);
        });
    }

    $(document).on('change', '.column-toggle', function() {
        if (!dataTableInstance) return;
        var table = dataTableInstance;
        var colIdx = $(this).data('column');
        table.column(colIdx).visible($(this).prop('checked'));
    });

    function loadBillForOther() {
        var billMonth = $('#bill_month').val();
        if (!billMonth) return;

        $('#noDataRow').remove();
        $('#billForOtherTable tbody').html('<tr><td colspan="15" class="text-center">Loading...</td></tr>');
        // Reset all selection checkboxes when (re)loading data.
        $('#billForOtherCheckAll').prop('checked', false);
        $('#check_all_bills').prop('checked', false);

        $.ajax({
            url: dataUrl,
            type: 'GET',
            data: { bill_month: billMonth },
            dataType: 'json',
            success: function(res) {
                if (dataTableInstance && $.fn.DataTable.isDataTable('#billForOtherTable')) {
                    dataTableInstance.destroy();
                    dataTableInstance = null;
                }
                var tbody = $('#billForOtherTable tbody');
                tbody.empty();

                var data = (res && res.data) ? res.data : [];
                if (data.length === 0) {
                    tbody.append('<tr id="noDataRow"><td colspan="15" class="text-center text-muted py-4">No data available for the selected month.</td></tr>');
                } else {
                    data.forEach(function(row) {
                        var pk = row.pk != null ? row.pk : '';
                        tbody.append(
                            '<tr>' +
                            '<td class="text-center"><input type="checkbox" class="form-check-input bill-row-check" value="' + escapeHtml(pk) + '" data-pk="' + escapeHtml(pk) + '" data-bill-no="' + escapeHtml(row.bill_no || '') + '" data-bill-month="' + escapeHtml(row.bill_month || '') + '" data-bill-year="' + escapeHtml(row.bill_year || '') + '"></td>' +
                            '<td>' + escapeHtml(row.sno || '') + '</td>' +
                            '<td>' + escapeHtml(row.name || '—') + '</td>' +
                            '<td>' + escapeHtml(row.section || '—') + '</td>' +
                            '<td>' + escapeHtml(row.house_no || '—') + '</td>' +
                            '<td>' + escapeHtml(row.from_date || '—') + '</td>' +
                            '<td>' + escapeHtml(row.to_date || '—') + '</td>' +
                            '<td>' + (String(row.meter_no || '').replace(/\n/g, '<br>') || '—') + '</td>' +
                            '<td>' + (String(row.prev_reading || '').replace(/\n/g, '<br>') || '—') + '</td>' +
                            '<td>' + (String(row.curr_reading || '').replace(/\n/g, '<br>') || '—') + '</td>' +
                            '<td>' + escapeHtml(row.unit_consumed ?? '—') + '</td>' +
                            '<td>' + formatMoney(row.total_charge) + '</td>' +
                            '<td>' + formatMoney(row.licence_fee) + '</td>' +
                            '<td>' + formatMoney(row.water_charges) + '</td>' +
                            '<td class="fw-semibold">' + formatMoney(row.grand_total) + '</td>' +
                            '</tr>'
                        );
                    });
                }

                // Do not initialise DataTables when there is only the colspan row, it causes incorrect column count warnings.
                if (data.length > 0) {
                    dataTableInstance = $('#billForOtherTable').DataTable({
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
                    buildColumnToggle();
                }
            },
            error: function() {
                if (dataTableInstance && $.fn.DataTable.isDataTable('#billForOtherTable')) {
                    dataTableInstance.destroy();
                    dataTableInstance = null;
                }
                $('#billForOtherTable tbody').empty().append(
                    '<tr id="noDataRow"><td colspan="15" class="text-center text-danger py-4">Failed to load data. Please try again.</td></tr>'
                );
                $('#billForOtherCheckAll').prop('checked', false);
                $('#check_all_bills').prop('checked', false);
            }
        });
    }

    function buildPrintableTableHtml() {
        if (!dataTableInstance) return '';
        var table = dataTableInstance;
        var visibleIndexes = [];
        table.columns().every(function(i) {
            var header = ($(this.header()).text() || '').trim();
            if (!header) return;
            if (this.visible()) visibleIndexes.push(i);
        });

        var html = '<table class="table table-bordered table-striped">';
        html += '<thead><tr>';
        visibleIndexes.forEach(function(colIdx) {
            var h = ($(table.column(colIdx).header()).text() || '').trim();
            html += '<th>' + h + '</th>';
        });
        html += '</tr></thead><tbody>';

        table.rows({ search: 'applied' }).nodes().each(function(rowNode) {
            var $row = $(rowNode);
            if ($row.hasClass('child')) return;
            html += '<tr>';
            visibleIndexes.forEach(function(colIdx) {
                var cellNode = table.cell(rowNode, colIdx).node();
                var cellHtml = '';
                if (cellNode) {
                    var $cell = $(cellNode).clone();
                    $cell.find('input, button, select, textarea').remove();
                    $cell.find('a.btn, .btn, .form-check-input').remove();
                    cellHtml = ($cell.html() || '').trim();
                }
                html += '<td>' + cellHtml + '</td>';
            });
            html += '</tr>';
        });

        html += '</tbody></table>';
        return html;
    }

    function openPrintWindow(tableHtml) {
        var title = 'Generate Estate Bill for Other';
        var win = window.open('', '_blank');
        if (!win) {
            window.print();
            return;
        }

        win.document.open();
        win.document.write(
            '<!doctype html><html><head><meta charset="utf-8">' +
            '<title>' + title + '</title>' +
            '<style>' +
            '@page{size:A4 landscape;margin:8mm;}' +
            'body{font-family:Arial, sans-serif;font-size:11px;color:#111;}' +
            'h2{margin:0 0 8px 0;font-size:14px;}' +
            'table{width:100%;border-collapse:collapse;}' +
            'th,td{border:1px solid #333;padding:4px 6px;vertical-align:top;word-break:break-word;white-space:normal;}' +
            'thead{display:table-header-group;}' +
            'tr{page-break-inside:avoid;}' +
            '</style></head><body>' +
            '<h2>' + title + '</h2>' +
            tableHtml +
            '</body></html>'
        );
        win.document.close();

        setTimeout(function() {
            win.focus();
            win.print();
            win.close();
        }, 250);
    }

    function getSelectedBillPks() {
        var pks = [];
        $('#billForOtherTable .bill-row-check:checked').each(function() {
            var pk = $(this).data('pk');
            if (pk && parseInt(pk, 10) > 0) pks.push(parseInt(pk, 10));
        });
        return pks;
    }

    function showStatusMessage(msg, type) {
        type = type || 'success';
        var alertClass = type === 'success' ? 'alert-success' : (type === 'error' ? 'alert-danger' : 'alert-warning');
        var icon = type === 'success' ? 'check_circle' : (type === 'error' ? 'error' : 'info');
        $('#status-msg').html(
            '<div class="alert ' + alertClass + ' alert-dismissible fade show shadow-sm" role="alert">' +
            '<i class="material-icons material-symbols-rounded me-2">' + icon + '</i> ' + msg +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'
        ).show();
        setTimeout(function() {
            $('#status-msg').fadeOut();
        }, 4000);
    }

    $('#billForOtherFilterForm').on('submit', function(e) {
        e.preventDefault();
        loadBillForOther();
    });

    function syncCheckAll() {
        var total = $('#billForOtherTable .bill-row-check').length;
        var checked = $('#billForOtherTable .bill-row-check:checked').length;
        var allChecked = total > 0 && total === checked;
        $('#billForOtherCheckAll').prop('checked', allChecked);
        $('#check_all_bills').prop('checked', allChecked);
    }
    $('#billForOtherCheckAll').on('change', function() {
        var checked = this.checked;
        $('#billForOtherTable .bill-row-check').each(function() { this.checked = checked; });
        $('#check_all_bills').prop('checked', checked);
    });
    $('#check_all_bills').on('change', function() {
        var checked = this.checked;
        $('#billForOtherTable .bill-row-check').each(function() { this.checked = checked; });
        $('#billForOtherCheckAll').prop('checked', checked);
    });
    $(document).on('change', '#billForOtherTable .bill-row-check', function() {
        syncCheckAll();
    });

    $('#btnPrint').on('click', function() {
        var printAllUrl = "{{ route('admin.estate.reports.bill-report-print-all') }}";
        var selectedRows = $('#billForOtherTable .bill-row-check:checked');
        var selectedMonthValue = ($('#bill_month').val() || '').toString().trim();

        if (!selectedRows.length) {
            alert('Please select at least one bill to print.');
            return;
        }

        var selectedPks = [];
        selectedRows.each(function() {
            var pk = ($(this).data('pk') || '').toString().trim();
            if (pk) selectedPks.push(pk);
        });

        if (!selectedPks.length) {
            alert('No printable bill found for selected row(s).');
            return;
        }

        var combinedUrl = printAllUrl +
            '?bill_month=' + encodeURIComponent(selectedMonthValue) +
            '&selected_pks=' + encodeURIComponent(selectedPks.join(',')) +
            '&is_other=1';
        window.open(combinedUrl, '_blank', 'noopener');
    });

    $('#btnVerifySelected').on('click', function() {
        var pks = getSelectedBillPks();
        if (pks.length === 0) {
            showStatusMessage('Please select at least one bill to verify.', 'warning');
            return;
        }
        var btn = $(this);
        btn.prop('disabled', true);
        $.ajax({
            url: "{{ route('admin.estate.generate-estate-bill-for-other.verify-selected') }}",
            type: 'POST',
            data: { pks: pks, _token: '{{ csrf_token() }}' },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false);
                if (res.status && res.message) {
                    showStatusMessage(res.message, 'success');
                    loadBillForOther();
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false);
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : (xhr.responseJSON && xhr.responseJSON.errors) ? JSON.stringify(xhr.responseJSON.errors) : 'Failed to verify bills.';
                showStatusMessage(msg, 'error');
            }
        });
    });

    $('#btnSaveAsDraft').on('click', function() {
        var pks = getSelectedBillPks();
        if (pks.length === 0) {
            showStatusMessage('Please select at least one bill to save as draft.', 'warning');
            return;
        }
        var btn = $(this);
        btn.prop('disabled', true);
        $.ajax({
            url: "{{ route('admin.estate.generate-estate-bill-for-other.save-as-draft') }}",
            type: 'POST',
            data: { pks: pks, _token: '{{ csrf_token() }}' },
            dataType: 'json',
            success: function(res) {
                btn.prop('disabled', false);
                if (res.status && res.message) {
                    showStatusMessage(res.message, 'success');
                    loadBillForOther();
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false);
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : (xhr.responseJSON && xhr.responseJSON.errors) ? JSON.stringify(xhr.responseJSON.errors) : 'Failed to save as draft.';
                showStatusMessage(msg, 'error');
            }
        });
    });
});
</script>
@endpush
