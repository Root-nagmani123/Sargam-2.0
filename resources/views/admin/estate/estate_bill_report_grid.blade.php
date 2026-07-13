@extends('admin.layouts.master')

@section('title', 'Estate Bill Report - Grid View - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <!-- Breadcrumb -->
    <x-breadcrum title="Estate Bill Report - Grid View" :showBack="false"></x-breadcrum>

    <!-- Filter: Bill Month + Show -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body p-4">
            <h1 class="h4 fw-bold text-dark mb-1">List Bill For Other And Lbsnaa</h1>
            <p class="text-muted small mb-4">Only notified bills are listed for the selected month except for other employee. Select Bill Month and click Show. If you expect more records, ensure those bills are notified except other employee.</p>
            <form id="billReportGridFilterForm" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="bill_month" class="form-label">Bill Month <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="month" class="form-control" id="bill_month" name="bill_month" value="{{ request('bill_month', date('Y-m')) }}" max="{{ date('Y-m') }}" required>
                    </div>
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-primary rounded-1 px-3 w-100" id="btnShow">
                        <i class="bi bi-search me-1"></i> Show
                    </button>
                </div>
            </form>
            <small class="text-muted d-block mt-2">Select Bill Month (current month or earlier)</small>
        </div>
    </div>

    <!-- Column Visibility Modal -->
    <div class="modal fade" id="estateBillReportColumnModal" tabindex="-1" aria-labelledby="estateBillReportColumnLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="estateBillReportColumnLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="estateBillReportColumnGrid"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center justify-content-end gap-3 mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnBillReportColumns"
                        data-bs-toggle="modal" data-bs-target="#estateBillReportColumnModal"
                        title="Show / hide columns" disabled>
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="btn programme-dt-btn-columns" id="btnBillReportPrint" title="Print" disabled>
                        <span>Print</span>
                        <i class="bi bi-printer" aria-hidden="true"></i>
                    </button>
                    <div id="billReportDtSearch" class="programme-dt-search" data-dt-search-for="estateBillReportTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table class="table text-nowrap mb-0 w-100 programme-dt-table" id="estateBillReportTable">
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
                <div id="billReportDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="estateBillReportTable"></div>
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
    var columnStorageKey = 'estateBillReportGrid:columns:v1';

    function formatMoney(n) {
        if (n == null || n === '' || isNaN(n)) return '—';
        return '₹ ' + parseFloat(n).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    function buildColumnToggle() {
        if (!billReportDt) return;
        var $grid = $('#estateBillReportColumnGrid');
        if (!$grid.length) return;
        $grid.empty();
        billReportDt.columns().every(function(i) {
            var col = this;
            var header = ($(col.header()).text() || '').trim();
            if (!header) return;

            var inputId = 'billReportColVis_' + i;
            var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
            var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                .attr('for', inputId);
            var $cb = $('<input type="checkbox" class="form-check-input m-0 bill-report-column-toggle">')
                .attr('id', inputId)
                .attr('data-column', i)
                .prop('checked', col.visible());

            $cb.on('change', function() {
                if (!billReportDt) return;
                var colIdx = $(this).data('column');
                billReportDt.column(colIdx).visible($(this).prop('checked'));
                persistColumnVisibility();
            });

            $label.append($cb).append($('<span></span>').text(header));
            $cell.append($label);
            $grid.append($cell);
        });
    }

    function persistColumnVisibility() {
        if (!billReportDt) return;
        var state = {};
        billReportDt.columns().every(function(i) {
            state[i] = this.visible();
        });
        try { localStorage.setItem(columnStorageKey, JSON.stringify(state)); } catch (e) {}
    }

    function restoreColumnVisibility() {
        if (!billReportDt) return;
        var raw = null;
        try { raw = localStorage.getItem(columnStorageKey); } catch (e) { raw = null; }
        if (!raw) return;
        var state = null;
        try { state = JSON.parse(raw); } catch (e) { state = null; }
        if (!state || typeof state !== 'object') return;
        Object.keys(state).forEach(function(k) {
            var idx = parseInt(k, 10);
            if (isNaN(idx)) return;
            billReportDt.column(idx).visible(!!state[k], false);
        });
        billReportDt.columns.adjust().draw(false);
    }

    // Change handler is now attached inside buildColumnToggle per-checkbox

    function buildPrintableTableHtml() {
        if (!billReportDt) return '';
        var visibleIndexes = [];
        billReportDt.columns().every(function(i) {
            if (this.visible()) visibleIndexes.push(i);
        });

        var html = '<table class="table table-bordered table-striped">';
        html += '<thead><tr>';
        visibleIndexes.forEach(function(colIdx) {
            var h = ($(billReportDt.column(colIdx).header()).text() || '').trim();
            html += '<th>' + h + '</th>';
        });
        html += '</tr></thead><tbody>';

        billReportDt.rows({ search: 'applied' }).nodes().each(function(rowNode) {
            var $row = $(rowNode);
            if ($row.hasClass('child')) return;
            html += '<tr>';
            visibleIndexes.forEach(function(colIdx) {
                var cellNode = billReportDt.cell(rowNode, colIdx).node();
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

    // Branded LBSNAA header assets (same layout as the official report PDF).
    var printLogoLeft   = @json(asset('admin_assets/images/logos/logo_new.png'));
    var printLogoRight  = @json(file_exists(public_path('admin_assets/images/logos/constitution-75.png'))
        ? asset('admin_assets/images/logos/constitution-75.png')
        : asset('admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png'));
    var printTitleHindi = @json(asset('admin_assets/images/logos/lbsnaa-title-hi.png'));

    function openPrintWindow(tableHtml) {
        var billMonth = ($('#bill_month').val() || '').trim();
        var win = window.open('', '_blank');
        if (!win) {
            window.print();
            return;
        }

        var today = new Date();
        var dateStr = today.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });

        win.document.open();
        win.document.write(
            '<!doctype html><html><head><meta charset="utf-8">' +
            '<title>Estate Bill Report - Grid View</title>' +
            '<style>' +
            '@page{size:A4 landscape;margin:10mm;}' +
            'body{font-family:Arial, sans-serif;font-size:11px;color:#1f2937;margin:16px;}' +
            '.pdf-hdr{width:100%;border-collapse:collapse;margin-bottom:4px;}' +
            '.pdf-hdr td{vertical-align:middle;}' +
            '.pdf-hdr .logo{width:90px;text-align:center;}' +
            '.pdf-hdr .logo img{max-height:64px;max-width:84px;}' +
            '.pdf-hdr .center{text-align:center;padding:0 8px;}' +
            '.pdf-hdr .inst-hi-img{height:18px;width:auto;margin-bottom:2px;}' +
            '.pdf-hdr .inst-en{font-size:16px;font-weight:bold;color:#102a43;line-height:1.25;}' +
            '.pdf-hdr .course-line{font-size:12px;font-weight:bold;color:#243b53;margin-top:4px;}' +
            '.report-title{text-align:center;font-size:20px;font-weight:bold;color:#004a93;margin:8px 0 6px;padding-bottom:8px;border-bottom:2px solid #004a93;}' +
            '.print-info{margin-bottom:12px;font-size:11px;color:#666;text-align:center;}' +
            'table{width:100%;border-collapse:collapse;margin-top:10px;}' +
            'th,td{border:1px solid #8fa3bd;padding:6px 8px;text-align:left;font-size:11px;vertical-align:top;word-break:break-word;white-space:normal;}' +
            'thead{display:table-header-group;}' +
            'thead th{font-weight:bold;background-color:#004a93 !important;color:#fff !important;text-align:center;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            'tbody tr:nth-child(even){background-color:#eef2f8;-webkit-print-color-adjust:exact;print-color-adjust:exact;}' +
            'tr{page-break-inside:avoid;}' +
            '.print-footer{margin-top:18px;text-align:center;font-size:10px;color:#666;border-top:1px solid #ccc;padding-top:10px;}' +
            '@media print{body{margin:0;}}' +
            '</style></head><body>' +
            '<table class="pdf-hdr"><tr>' +
                '<td class="logo"><img src="' + printLogoLeft + '" alt=""></td>' +
                '<td class="center">' +
                    '<img class="inst-hi-img" src="' + printTitleHindi + '" alt="">' +
                    '<div class="inst-en">Lal Bahadur Shastri National Academy of Administration, Mussoorie</div>' +
                    (billMonth ? '<div class="course-line">Bill Month: ' + billMonth + '</div>' : '') +
                '</td>' +
                '<td class="logo"><img src="' + printLogoRight + '" alt=""></td>' +
            '</tr></table>' +
            '<div class="report-title">Estate Bill Report</div>' +
            '<div class="print-info"><div>Print Date: ' + dateStr + '</div></div>' +
            tableHtml +
            '<div class="print-footer"><p>Generated on ' + today.toLocaleString() + '</p></div>' +
            '</body></html>'
        );
        win.document.close();

        setTimeout(function() {
            win.focus();
            win.print();
            win.close();
        }, 400);
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
                        // Always read latest value (avoid stale closure on reload)
                        d.bill_month = $('#bill_month').val();
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
                    { data: 'meter_no', searchable: false, render: function(v){ return (v == null || v === '') ? '—' : v.toString().split(/\n+/).map(function(s){ return s.trim(); }).filter(Boolean).join(', '); } },
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
                autoWidth: false
                // scrollX removed: it cloned the header into .dataTables_scrollHead
                // (showing a duplicate header row). The wrapping .table-responsive
                // already provides horizontal scroll for the wide table.
                // No custom `dom` — the global DataTables enhancer
                // (datatable-global-ui.js) relocates search/length/pagination into
                // the .programme-dt-search / .programme-dt-footer slots below.
            });

            billReportDt.on('draw', function() { buildColumnToggle(); });
            restoreColumnVisibility();
            buildColumnToggle();

            $('#btnBillReportColumns').prop('disabled', false);
            $('#btnBillReportPrint').prop('disabled', false);
        } else {
            billReportDt.ajax.reload(null, true);
        }
    }

    $('#billReportGridFilterForm').on('submit', function(e) {
        e.preventDefault();
        initOrReloadBillReportGrid();
    });

    // Filter works independently — reload as soon as the month changes,
    // no need to press Show.
    $('#bill_month').on('change', function() {
        initOrReloadBillReportGrid();
    });

    // Auto-load on page open for the default/selected month (Show not required).
    if ($('#bill_month').val()) {
        initOrReloadBillReportGrid();
    }

    $('#btnBillReportPrint').on('click', function() {
        if (!billReportDt) {
            window.print();
            return;
        }

        // Try to print all rows (server-side must support length=-1 for "All")
        var originalLen = billReportDt.page.len();
        var originalPage = billReportDt.page();
        var restored = false;

        var restore = function() {
            if (restored) return;
            restored = true;
            billReportDt.page.len(originalLen);
            billReportDt.page(originalPage);
            billReportDt.draw(false);
        };

        billReportDt.one('draw', function() {
            setTimeout(function() {
                var tableHtml = buildPrintableTableHtml();
                openPrintWindow(tableHtml);
                setTimeout(restore, 800);
            }, 250);
        });

        billReportDt.page.len(-1).draw();
    });
});
</script>
@endpush
