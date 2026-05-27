@extends('admin.layouts.master')

@section('title', 'Vacant House Monitoring - Sargam')

@section('content')
<div class="container-fluid py-2">
    <x-breadcrum :title="'Vacant House Monitoring'" :items="['Home', 'Estate Management', 'Vacant House Monitoring']" />

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="h4 fw-semibold text-body mb-1">Vacant House Monitoring</h2>
            <p class="text-body-secondary small mb-0">Vacant houses with meter and last allottee details</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" id="btnPrintVacant" title="Print">
                <i class="material-symbols-rounded">print</i>
                <span class="d-none d-md-inline">Print</span>
            </button>
            <a href="{{ route('admin.estate.vacant-house-monitoring.export') }}" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" title="Download Excel">
                <i class="material-symbols-rounded">download</i>
                <span class="d-none d-md-inline">Excel</span>
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2" id="btnPdfVacant" title="Download PDF (via print)">
                <i class="material-symbols-rounded">picture_as_pdf</i>
                <span class="d-none d-md-inline">PDF</span>
            </button>
        </div>
    </div>

    <x-session_message />

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-body-secondary bg-opacity-10 border-0 py-3 px-4">
            <h5 class="card-title fw-semibold mb-0">Vacant House List</h5>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                {!! $dataTable->table(['class' => 'table text-nowrap align-middle mb-0', 'aria-describedby' => 'vacant-house-caption']) !!}
            </div>
            <div id="vacant-house-caption" class="visually-hidden">Vacant house monitoring list</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{!! $dataTable->scripts() !!}
<script>
(function() {
    var tableId = '#vacantHouseMonitoringTable';
    var reportTitle = 'Vacant House Monitoring';

    function getTable() {
        if (!$.fn.DataTable.isDataTable(tableId)) return null;
        return $(tableId).DataTable();
    }

    function buildPrintableTableHtml(tableElement) {
        var clone = tableElement.cloneNode(true);
        clone.classList.remove('dataTable');
        clone.removeAttribute('style');
        clone.removeAttribute('width');
        clone.querySelectorAll('colgroup, [style], [width]').forEach(function(el) {
            if (el.tagName === 'COLGROUP') el.remove();
            else { el.removeAttribute('style'); el.removeAttribute('width'); }
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
        win.document.write(
            '<!doctype html><html><head><meta charset="utf-8"><title>' + reportTitle + '</title>' +
            '<style>@page{size:A4 landscape;margin:10mm;}body{font-family:Arial,sans-serif;font-size:11px;}' +
            'h2{text-align:center;font-size:16px;margin:0 0 12px;}' +
            'table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ccc;padding:5px;}' +
            'th{background:#f3f4f6;}</style></head><body>' +
            '<h2>' + reportTitle + '</h2>' + tableHtml + '</body></html>'
        );
        win.document.close();
        setTimeout(function() { win.focus(); win.print(); }, 250);
    }

    function printAllRows() {
        var dt = getTable();
        var el = document.querySelector(tableId);
        if (!dt || !el) return;
        var prevLen = dt.page.len();
        dt.page.len(-1).draw(false);
        setTimeout(function() {
            openPrintWindow(buildPrintableTableHtml(el));
            dt.page.len(prevLen).draw(false);
        }, 300);
    }

    $('#btnPrintVacant, #btnPdfVacant').on('click', printAllRows);
})();
</script>
@endpush
