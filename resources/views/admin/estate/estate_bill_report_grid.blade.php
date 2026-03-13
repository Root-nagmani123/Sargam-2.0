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
            <p class="text-muted small mb-4">Only verified/notified bills are listed for the selected month. Select Bill Month and click Show. If you expect more records, ensure those bills are verified (List Bill / Verify).</p>
            <form id="billReportGridFilterForm" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label for="bill_month" class="form-label">Bill Month <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="month" class="form-control" id="bill_month" name="bill_month" value="{{ date('Y-m') }}" max="{{ date('Y-m') }}" required>
                    </div>
                    <small class="text-muted d-block">Select Bill Month (current month or earlier)</small>
                </div>
                <div class="col-12 col-md-2">
                    <button type="submit" class="btn btn-primary rounded-1 px-3 w-100" id="btnShow">
                        <i class="bi bi-search me-1"></i> Show
                    </button>
                </div>
            </form>
            <div class="d-none" id="billReportToolbarPlaceholder">
                <div class="dropdown d-inline-block ms-2" data-bs-auto-close="outside">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-1 dropdown-toggle d-inline-flex align-items-center" data-bs-toggle="dropdown" aria-expanded="false" id="btnBillReportColumns" title="Show / hide columns" disabled>
                        <i class="material-icons material-symbols-rounded" style="font-size:18px">view_column</i>
                        <span class="ms-1">Columns</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end py-2" id="billReportColumnToggleMenu"></ul>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-1 d-inline-flex align-items-center ms-1" id="btnBillReportPrint" title="Print" disabled>
                    <i class="material-icons material-symbols-rounded" style="font-size:18px">print</i>
                </button>
            </div>
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
    var columnStorageKey = 'estateBillReportGrid:columns:v1';

    function formatMoney(n) {
        if (n == null || n === '' || isNaN(n)) return '—';
        return '₹ ' + parseFloat(n).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    function buildColumnToggle() {
        if (!billReportDt) return;
        var menu = $('#billReportColumnToggleMenu');
        menu.empty();
        billReportDt.columns().every(function(i) {
            var col = this;
            var header = ($(col.header()).text() || '').trim();
            if (!header) return;
            var $li = $('<li><label class="dropdown-item d-flex align-items-center gap-2 cursor-pointer mb-0"><input type="checkbox" class="form-check-input bill-report-column-toggle" data-column="' + i + '"> ' + header + '</label></li>');
            $li.find('input').prop('checked', col.visible());
            menu.append($li);
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

    $(document).on('change', '.bill-report-column-toggle', function() {
        if (!billReportDt) return;
        var colIdx = $(this).data('column');
        billReportDt.column(colIdx).visible($(this).prop('checked'));
        persistColumnVisibility();
    });

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

    function openPrintWindow(tableHtml) {
        var billMonth = ($('#bill_month').val() || '').trim();
        var title = 'Estate Bill Report - Grid View' + (billMonth ? (' (' + billMonth + ')') : '');
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

            billReportDt.on('draw', function() { buildColumnToggle(); });
            restoreColumnVisibility();
            buildColumnToggle();

            $('#btnBillReportColumns').prop('disabled', false);
            $('#btnBillReportPrint').prop('disabled', false);

            // Move Columns + Print into table toolbar (search row) so buttons appear above table
            var $wrapper = $('#estateBillReportTable').closest('.dataTables_wrapper');
            var $filter = $wrapper.find('.dataTables_filter');
            var $toolbar = $('#billReportToolbarPlaceholder').children().detach();
            if ($filter.length && $toolbar.length) {
                $filter.addClass('d-flex align-items-center justify-content-end flex-wrap gap-2');
                $filter.append($toolbar);
            }
        } else {
            billReportDt.ajax.reload(null, true);
        }
    }

    $('#billReportGridFilterForm').on('submit', function(e) {
        e.preventDefault();
        initOrReloadBillReportGrid();
    });

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
