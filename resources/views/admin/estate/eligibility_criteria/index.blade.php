@extends('admin.layouts.master')

@section('title', 'Eligibility - Criteria - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
<x-breadcrum title="Eligibility - Criteria" />

    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden" style="border-left: 4px solid #0d6efd;">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div class="flex-grow-1 min-w-0">
                    <h1 class="h4 fw-bold text-body-emphasis mb-2">Eligibility - Criteria</h1>
                    <p class="text-body-secondary small mb-0 lh-sm">This page displays all the Estate Eligibility Block Mapping added in the system and provides options such as add, edit, delete, excel upload, print etc.</p>
                </div>
                <div class="flex-shrink-0 d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.eligibility-criteria.create') }}" class="btn btn-success px-3" title="Add New"><i class="bi bi-plus-lg me-1"></i> Add New</a>
                    <button type="button" class="btn btn-outline-secondary px-3" id="btnPrintEligibilityCriteria" title="Print"><i class="bi bi-printer"></i></button>
                </div>
            </div>

            <div class="table-responsive">
                {!! $dataTable->table([
                    'class' => 'table text-nowrap align-middle mb-0',
                    'aria-describedby' => 'eligibility-criteria-caption'
                ]) !!}
            </div>
            <div id="eligibility-criteria-caption" class="visually-hidden">Eligibility Criteria list</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @include('admin.estate.partials.lbsnaa_print_layout')
    {!! $dataTable->scripts(attributes: ['type' => 'module']) !!}
    <script>
        (function() {
            function buildPrintableTableHtml(tableElement) {
                var clone = tableElement.cloneNode(true);

                // Drop action column from print output.
                Array.from(clone.querySelectorAll('tr')).forEach(function(tr) {
                    if (tr.lastElementChild) {
                        tr.removeChild(tr.lastElementChild);
                    }
                });

                return clone.outerHTML;
            }

            function openPrintWindow(tableHtml) {
                var win = window.open('', '_blank', 'width=1200,height=900');
                if (!win) {
                    alert('Please allow popups to print this list.');
                    return;
                }
                var docHtml = (window.LBSNAAPrint && window.LBSNAAPrint.getDocumentHtml)
                    ? window.LBSNAAPrint.getDocumentHtml('Eligibility - Criteria', tableHtml)
                    : '<!doctype html><html><head><title>Eligibility - Criteria</title></head><body><h2>Eligibility - Criteria</h2>' + tableHtml + '</body></html>';
                win.document.open();
                win.document.write(docHtml);
                win.document.close();
                win.onafterprint = function() { win.close(); };
                setTimeout(function() { win.focus(); win.print(); }, 250);
            }

            document.addEventListener('click', function(e) {
                var btn = e.target.closest('#btnPrintEligibilityCriteria');
                if (!btn) {
                    return;
                }

                var table = document.getElementById('eligibilityCriteriaTable');
                if (!table) {
                    alert('Table not found.');
                    return;
                }

                var dt = window.jQuery && $.fn && $.fn.dataTable && $.fn.DataTable
                    ? $('#eligibilityCriteriaTable').DataTable()
                    : null;

                // If DataTable is not available for some reason, fall back to current page only.
                if (!dt) {
                    openPrintWindow(buildPrintableTableHtml(table));
                    return;
                }

                var originalLen = dt.page.len();
                var originalPage = dt.page();

                var restore = function() {
                    dt.page.len(originalLen);
                    dt.page(originalPage);
                    dt.draw(false);
                };

                var restored = false;
                var safeRestore = function() {
                    if (restored) return;
                    restored = true;
                    restore();
                };

                dt.one('draw', function() {
                    setTimeout(function() {
                        var refreshedTable = document.getElementById('eligibilityCriteriaTable');
                        openPrintWindow(buildPrintableTableHtml(refreshedTable));
                        setTimeout(safeRestore, 800);
                    }, 150);
                });

                // Load all rows for printing (DataTables "All")
                dt.page.len(-1).draw();
            });
        })();
    </script>
@endpush
