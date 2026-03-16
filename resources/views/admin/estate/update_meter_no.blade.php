@extends('admin.layouts.master')

@section('title', 'Update Meter No. - Sargam')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.request-for-others') }}">Estate Management</a></li>
            <li class="breadcrumb-item active" aria-current="page">Update Meter No.</li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row flex-md-nowrap justify-content-between align-items-start align-items-md-center gap-3 mb-4 no-print">
        <h2 class="mb-0">Update Meter No.</h2>
        <div class="d-flex flex-wrap gap-2 flex-shrink-0">
            @php
                $canUpdateReadingAndMeterNo = hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin');
            @endphp
            @if($canUpdateReadingAndMeterNo)
            <a href="{{ route('admin.estate.update-meter-reading') }}" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2">
                Update Reading & Meter No.
            </a>
            @endif
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Show / hide columns">
                    <i class="bi bi-columns-gap"></i>
                    <span class="d-none d-md-inline ms-1">Show / hide columns</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" id="updateMeterNoColumnToggleMenu"></ul>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" id="btnUpdateMeterNoPrint" title="Print">
                <i class="material-icons material-symbols-rounded">print</i>
                <span class="d-none d-md-inline">Print</span>
            </button>
        </div>
    </div>

    <!-- Filters + Data Table Card -->
    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Simple filter row: require bill month & year before loading -->
            <div class="row g-3 mb-3 no-print align-items-end">
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <label for="filterBillYear" class="form-label mb-1">Bill Year<span class="text-danger">*</span></label>
                    <input type="number" min="2000" max="2100" class="form-control form-control-sm" id="filterBillYear" placeholder="e.g. 2025" value="{{ now()->year }}">
                </div>
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <label for="filterBillMonth" class="form-label mb-1">Bill Month<span class="text-danger">*</span></label>
                    <select id="filterBillMonth" class="form-select form-select-sm">
                        <option value="">Select month</option>
                        <option value="1" @if(now()->month === 1) selected @endif>January</option>
                        <option value="2" @if(now()->month === 2) selected @endif>February</option>
                        <option value="3" @if(now()->month === 3) selected @endif>March</option>
                        <option value="4" @if(now()->month === 4) selected @endif>April</option>
                        <option value="5" @if(now()->month === 5) selected @endif>May</option>
                        <option value="6" @if(now()->month === 6) selected @endif>June</option>
                        <option value="7" @if(now()->month === 7) selected @endif>July</option>
                        <option value="8" @if(now()->month === 8) selected @endif>August</option>
                        <option value="9" @if(now()->month === 9) selected @endif>September</option>
                        <option value="10" @if(now()->month === 10) selected @endif>October</option>
                        <option value="11" @if(now()->month === 11) selected @endif>November</option>
                        <option value="12" @if(now()->month === 12) selected @endif>December</option>
                    </select>
                </div>
                <div class="col-sm-12 col-md-4 col-lg-3 d-flex gap-2">
                    <button type="button" id="btnUpdateMeterNoApplyFilter" class="btn btn-primary btn-sm flex-grow-1">
                        Apply Filter
                    </button>
                    <button type="button" id="btnUpdateMeterNoClearFilter" class="btn btn-outline-secondary btn-sm flex-grow-1">
                        Clear
                    </button>
                </div>
                <div class="col-12">
                    <div id="updateMeterNoFilterHint" class="small text-muted"></div>
                </div>
            </div>

            <div class="update-meter-no-table-wrapper table-responsive">
                <table class="table text-nowrap" id="updateMeterNoTable">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Name</th>
                            <th>Employee Type</th>
                            <th>Unit Type</th>
                            <th>Unit Sub Type</th>
                            <th>Building Name</th>
                            <th>House No.</th>
                            <th>Old Meter1 No.</th>
                            <th>New Meter1 No.</th>
                            <th>Old Meter2 No.</th>
                            <th>New Meter2 No.</th>
                            <th>Old Meter1 Reading</th>
                            <th>New Meter1 Reading</th>
                            <th>Old Meter2 Reading</th>
                            <th>New Meter2 Reading</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
@media print {
    @page { size: A4 landscape; margin: 8mm; }
    .no-print { display: none !important; }
    #updateMeterNoTable_wrapper .dataTables_length,
    #updateMeterNoTable_wrapper .dataTables_filter,
    #updateMeterNoTable_wrapper .dataTables_paginate { display: none !important; }
    .update-meter-no-table-wrapper,
    #updateMeterNoTable_wrapper .dataTables_scroll,
    #updateMeterNoTable_wrapper .dataTables_scrollBody,
    #updateMeterNoTable_wrapper .dataTables_scrollHead { overflow: visible !important; }
    #updateMeterNoTable_wrapper .dataTables_scrollBody { height: auto !important; max-height: none !important; }
    #updateMeterNoTable_wrapper .dataTables_scrollHead { display: none !important; }
    #updateMeterNoTable_wrapper table, #updateMeterNoTable_wrapper table.dataTable { width: 100% !important; }
    body { zoom: 0.78; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    #updateMeterNoTable_wrapper th, #updateMeterNoTable_wrapper td { white-space: normal !important; word-break: break-word; font-size: 11px; padding: 0.35rem 0.4rem !important; }
    #updateMeterNoTable_wrapper thead { display: table-header-group; }
}
.update-meter-no-table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    var updateMeterNoFilters = {
        bill_year: ($('#filterBillYear').val() || '').trim(),
        bill_month: ($('#filterBillMonth').val() || '').trim(),
    };

    var table = $('#updateMeterNoTable').DataTable({
        processing: true,
        serverSide: true,
        deferLoading: 0,
        ajax: {
            url: "{{ route('admin.estate.update-meter-no.list') }}",
            data: function (d) {
                if (updateMeterNoFilters.bill_year && updateMeterNoFilters.bill_month) {
                    d.bill_year = updateMeterNoFilters.bill_year;
                    d.bill_month = updateMeterNoFilters.bill_month;
                }
            },
        },
        columns: [
            { data: 'sn', title: 'S.No.' },
            { data: 'name', title: 'Name' },
            { data: 'employee_type', title: 'Employee Type' },
            { data: 'unit_type', title: 'Unit Type' },
            { data: 'unit_sub_type', title: 'Unit Sub Type' },
            { data: 'building_name', title: 'Building Name' },
            { data: 'house_no', title: 'House No.' },
            { data: 'old_meter1_no', title: 'Old Meter1 No.', defaultContent: '—' },
            { data: 'new_meter1_no', title: 'New Meter1 No.', defaultContent: '—' },
            { data: 'old_meter2_no', title: 'Old Meter2 No.', defaultContent: '—' },
            { data: 'new_meter2_no', title: 'New Meter2 No.', defaultContent: '—' },
            { data: 'old_meter1_reading', title: 'Old Meter1 Reading', defaultContent: '—' },
            { data: 'new_meter1_reading', title: 'New Meter1 Reading', defaultContent: '—' },
            { data: 'old_meter2_reading', title: 'Old Meter2 Reading', defaultContent: '—' },
            { data: 'new_meter2_reading', title: 'New Meter2 Reading', defaultContent: '—' }
        ],
        order: [[0, 'desc']],
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
        responsive: false,
        autoWidth: true,
        scrollX: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
    });

    // On first load, if current month/year present, load that data by default
    if (updateMeterNoFilters.bill_year && updateMeterNoFilters.bill_month) {
        $('#updateMeterNoFilterHint').text(
            'Showing records for Bill Year ' + updateMeterNoFilters.bill_year + ' and Bill Month ' + $('#filterBillMonth option:selected').text() + '.'
        );
        table.ajax.reload();
    } else {
        $('#updateMeterNoFilterHint').text(
            'Please select Bill Year and Bill Month, then click "Apply Filter" to load records.'
        );
    }

    $('#btnUpdateMeterNoApplyFilter').on('click', function() {
        var year = ($('#filterBillYear').val() || '').trim();
        var month = ($('#filterBillMonth').val() || '').trim();

        if (!year || !month) {
            alert('Please select both Bill Year and Bill Month to load the list.');
            return;
        }

        updateMeterNoFilters.bill_year = year;
        updateMeterNoFilters.bill_month = month;

        $('#updateMeterNoFilterHint').text(
            'Showing records for Bill Year ' + year + ' and Bill Month ' + $('#filterBillMonth option:selected').text() + '.'
        );

        table.ajax.reload();
    });

    $('#btnUpdateMeterNoClearFilter').on('click', function() {
        $('#filterBillYear').val('');
        $('#filterBillMonth').val('');
        updateMeterNoFilters.bill_year = null;
        updateMeterNoFilters.bill_month = null;
        $('#updateMeterNoFilterHint').text(
            'Please select Bill Year and Bill Month, then click "Apply Filter" to load records.'
        );
        table.clear().draw();
    });

    function buildUpdateMeterNoColumnToggle() {
        var menu = $('#updateMeterNoColumnToggleMenu');
        menu.empty();
        table.columns().every(function(i) {
            var col = this;
            var header = $(col.header()).text().trim();
            if (!header) return;
            var $li = $('<li><label class="dropdown-item d-flex align-items-center gap-2 cursor-pointer mb-0"><input type="checkbox" class="form-check-input column-toggle-update-meter-no" data-column="' + i + '"> ' + header + '</label></li>');
            $li.find('input').prop('checked', col.visible());
            menu.append($li);
        });
    }
    $(document).on('change', '.column-toggle-update-meter-no', function() {
        var colIdx = $(this).data('column');
        table.column(colIdx).visible($(this).prop('checked'));
    });
    table.on('draw', function() { buildUpdateMeterNoColumnToggle(); });
    buildUpdateMeterNoColumnToggle();

    function buildUpdateMeterNoPrintableHtml() {
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
                    $cell.find('a.btn, .btn').remove();
                    cellHtml = ($cell.html() || '').trim();
                }
                html += '<td>' + cellHtml + '</td>';
            });
            html += '</tr>';
        });
        html += '</tbody></table>';
        return html;
    }
    function openUpdateMeterNoPrintWindow(tableHtml) {
        var title = 'Update Meter No.';
        var win = window.open('', '_blank');
        if (!win) { window.print(); return; }
        win.document.open();
        win.document.write(
            '<!doctype html><html><head><meta charset="utf-8">' +
            '<title>' + title + '</title>' +
            '<style>@page{size:A4 landscape;margin:8mm;}body{font-family:Arial,sans-serif;font-size:11px;}h2{margin:0 0 8px 0;font-size:14px;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #333;padding:4px 6px;}thead{display:table-header-group;}tr{page-break-inside:avoid;}</style></head><body><h2>' + title + '</h2>' + tableHtml + '</body></html>'
        );
        win.document.close();
        setTimeout(function() { win.focus(); win.print(); win.close(); }, 250);
    }
    $('#btnUpdateMeterNoPrint').on('click', function() {
        if (!$.fn.DataTable.isDataTable('#updateMeterNoTable')) { window.print(); return; }
        var originalLen = table.page.len();
        var originalPage = table.page();
        var restored = false;
        var safeRestore = function() {
            if (restored) return;
            restored = true;
            table.page.len(originalLen);
            table.page(originalPage);
            table.draw(false);
        };
        table.one('draw', function() {
            setTimeout(function() {
                var tableHtml = buildUpdateMeterNoPrintableHtml();
                openUpdateMeterNoPrintWindow(tableHtml);
                setTimeout(safeRestore, 800);
            }, 250);
        });
        table.page.len(-1).draw();
    });
});
</script>
@endpush
