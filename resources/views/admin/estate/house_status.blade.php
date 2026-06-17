@extends('admin.layouts.master')

@section('title', 'House Status - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
<x-breadcrum title="House Status"></x-breadcrum>

    <!-- Data Table Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-body-secondary small mb-3">House-wise status: Occupied, Vacant, or Under Renovation.</p>
            <div class="table-responsive">
                <table class="table text-nowrap" id="houseStatusTable">
                    <thead>
                        <tr>
                            <th>Sno.</th>
                            <th>Qtr No</th>
                            <th>Building Name</th>
                            <th>Type</th>
                            <th>Allottee Name (Ms/Mr/Mrs.)</th>
                            <th>Section/Designation</th>
                            <th>Mobile Number</th>
                            <th>Alloted Date</th>
                            <th>Occupied Date</th>
                            <th>Vacated Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="noDataRow">
                            <td colspan="11" class="text-center text-muted">Loading...</td>
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

    function destroyDataTable() {
        if (dataTableInstance && $.fn.DataTable.isDataTable('#houseStatusTable')) {
            dataTableInstance.destroy();
            $('#houseStatusTable tbody').empty();
            dataTableInstance = null;
        }
    }

    function escapeHtml(str) {
        if (str == null || str === '') return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function loadHouseStatus() {
        $('#noDataRow').remove();
        $('#houseStatusTable tbody').html('<tr><td colspan="11" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: '{{ route("admin.estate.reports.house-status.data") }}',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                destroyDataTable();
                var tbody = $('#houseStatusTable tbody');
                tbody.empty();
                if (res.status && res.data && res.data.length > 0) {
                    $.each(res.data, function(i, row) {
                        tbody.append(
                            '<tr>' +
                                '<td>' + (row.sno != null ? row.sno : (i + 1)) + '</td>' +
                                '<td>' + escapeHtml(row.qtr_no || '—') + '</td>' +
                                '<td>' + escapeHtml(row.building_name || '—') + '</td>' +
                                '<td>' + escapeHtml(row.type || '—') + '</td>' +
                                '<td>' + escapeHtml(row.allottee_name || 'VACANT') + '</td>' +
                                '<td>' + escapeHtml(row.section_designation || '') + '</td>' +
                                '<td>' + escapeHtml(row.mobile_number || '') + '</td>' +
                                '<td>' + escapeHtml(row.alloted_date || '') + '</td>' +
                                '<td>' + escapeHtml(row.occupied_date || '') + '</td>' +
                                '<td>' + escapeHtml(row.vacated_date || '') + '</td>' +
                                '<td>' + escapeHtml(row.status || '') + '</td>' +
                            '</tr>'
                        );
                    });
                } else {
                    tbody.append('<tr id="noDataRow"><td colspan="11" class="text-center text-muted">No data available.</td></tr>');
                }
                dataTableInstance = $('#houseStatusTable').DataTable({
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
                // Print button next to search (same row as DataTable filter)
                var $filter = $('#houseStatusTable_wrapper .dataTables_filter');
                if ($filter.length && !$('#btnPrintHouseStatus').length) {
                    $filter.append('<label class="d-inline-flex align-items-center ms-2 mb-0"><button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" id="btnPrintHouseStatus" title="Print"><i class="material-icons material-symbols-rounded">print</i></button></label>');
                }
            },
            error: function(xhr) {
                destroyDataTable();
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to load data.';
                $('#houseStatusTable tbody').empty().append('<tr><td colspan="11" class="text-center text-danger">' + msg + '</td></tr>');
            }
        });
    }

    loadHouseStatus();

    // Print: same pattern as other estate module DataTable print
    function buildPrintableTableHtml(tableElement) {
        var clone = tableElement.cloneNode(true);
        clone.classList.remove('dataTable');
        clone.removeAttribute('style');
        clone.removeAttribute('width');

        clone.querySelectorAll('colgroup').forEach(function(colgroup) {
            colgroup.remove();
        });

        clone.querySelectorAll('[style]').forEach(function(el) {
            el.removeAttribute('style');
        });

        clone.querySelectorAll('[width]').forEach(function(el) {
            el.removeAttribute('width');
        });

        clone.querySelectorAll('th, td').forEach(function(cell) {
            cell.style.whiteSpace = 'normal';
            cell.style.wordBreak = 'break-word';
        });

        return clone.outerHTML;
    }

    function openPrintWindow(tableHtml) {
        var win = window.open('', '_blank', 'width=1200,height=900');
        if (!win) {
            alert('Please allow popups to print this list.');
            return;
        }
        win.document.open();
        win.document.write(
            '<!doctype html>' +
            '<html><head><title>House Status - Sargam</title>' +
            '<style>' +
            '@page{size:A4 landscape;margin:10mm;}' +
            'html,body{margin:0;padding:0;background:#fff;}' +
            'body{font-family:Arial,sans-serif;color:#111827;}' +
            '.print-wrap{padding:0;}' +
            'h2{margin:0 0 10px 0;font-size:18px;text-align:center;}' +
            'table{width:100%;border-collapse:collapse;table-layout:fixed;font-size:10px;}' +
            'th,td{border:1px solid #d1d5db;padding:4px 5px;vertical-align:top;text-align:left;white-space:normal;word-break:break-word;overflow-wrap:anywhere;line-height:1.25;}' +
            'th{background:#f3f4f6;font-weight:700;}' +
            'thead{display:table-header-group;}' +
            'tr{page-break-inside:avoid;}' +
            '@media print{body{-webkit-print-color-adjust:exact;print-color-adjust:exact;}}' +
            '</style></head><body><div class="print-wrap">' +
            '<h2>House Status</h2>' +
            tableHtml +
            '</div>' +
            '</body></html>'
        );
        win.document.close();
        win.onafterprint = function() { win.close(); };
        setTimeout(function() { win.focus(); win.print(); }, 250);
    }

    $(document).on('click', '#btnPrintHouseStatus', function() {
        var $table = $('#houseStatusTable');
        if (!$table.length) {
            alert('Table not found.');
            return;
        }
        var tableEl = $table[0];
        var dt = $table.DataTable();
        var prevPageLen = dt.settings()[0]._iDisplayLength;
        if (prevPageLen !== -1) {
            dt.page.len(-1).draw(false);
        }
        openPrintWindow(buildPrintableTableHtml(tableEl));
        if (prevPageLen !== -1) {
            dt.page.len(prevPageLen).draw(false);
        }
    });
});
</script>
@endpush
