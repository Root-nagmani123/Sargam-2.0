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
    var dataUrl = '{{ route("admin.estate.reports.house-status.data") }}';

    // Server-side DataTable: searching, sorting and paging are done by the server.
    var dataTableInstance = $('#houseStatusTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: dataUrl,
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'qtr_no', name: 'qtr_no' },
            { data: 'building_name', name: 'building_name' },
            { data: 'type', name: 'type' },
            { data: 'allottee_name', name: 'allottee_name' },
            { data: 'section_designation', name: 'section_designation' },
            { data: 'mobile_number', name: 'mobile_number' },
            { data: 'alloted_date', name: 'alloted_date' },
            { data: 'occupied_date', name: 'occupied_date' },
            { data: 'vacated_date', name: 'vacated_date' },
            { data: 'status', name: 'status' }
        ],
        order: [],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: "Loading data…",
            emptyTable: "No data available.",
            zeroRecords: "No matching records found.",
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
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        initComplete: function() {
            // Print button next to search (same row as DataTable filter)
            var $filter = $('#houseStatusTable_wrapper .dataTables_filter');
            if ($filter.length && !$('#btnPrintHouseStatus').length) {
                $filter.append('<label class="d-inline-flex align-items-center ms-2 mb-0"><button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2" id="btnPrintHouseStatus" title="Print"><i class="material-icons material-symbols-rounded">print</i></button></label>');
            }
        }
    });

    function escapeHtml(str) {
        if (str == null || str === '') return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // Print: rows come from the server (all pages, current search/sort applied).
    function buildPrintableTableHtml(rows) {
        var headers = [];
        $('#houseStatusTable thead th').each(function() {
            headers.push($(this).text().trim());
        });

        var html = '<table><thead><tr>';
        headers.forEach(function(h) { html += '<th>' + escapeHtml(h) + '</th>'; });
        html += '</tr></thead><tbody>';

        rows.forEach(function(row, i) {
            html += '<tr>' +
                '<td>' + escapeHtml(String(row.DT_RowIndex != null ? row.DT_RowIndex : (i + 1))) + '</td>' +
                '<td>' + escapeHtml(row.qtr_no) + '</td>' +
                '<td>' + escapeHtml(row.building_name) + '</td>' +
                '<td>' + escapeHtml(row.type) + '</td>' +
                '<td>' + escapeHtml(row.allottee_name) + '</td>' +
                '<td>' + escapeHtml(row.section_designation) + '</td>' +
                '<td>' + escapeHtml(row.mobile_number) + '</td>' +
                '<td>' + escapeHtml(row.alloted_date) + '</td>' +
                '<td>' + escapeHtml(row.occupied_date) + '</td>' +
                '<td>' + escapeHtml(row.vacated_date) + '</td>' +
                '<td>' + escapeHtml(row.status) + '</td>' +
            '</tr>';
        });

        return html + '</tbody></table>';
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
        var $btn = $(this);
        // Pull every row for the current search/sort (length = -1) so print is not limited to one page.
        var params = $.extend({}, dataTableInstance.ajax.params(), { start: 0, length: -1 });
        $btn.prop('disabled', true);

        $.ajax({
            url: dataUrl,
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function(res) {
                openPrintWindow(buildPrintableTableHtml((res && res.data) || []));
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
