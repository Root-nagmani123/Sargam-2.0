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
                <div class="flex-shrink-0 d-flex flex-wrap gap-2 eligibility-criteria-header-buttons">
                    <a href="{{ route('admin.estate.eligibility-criteria.create') }}" class="btn btn-primary rounded-1 px-3 d-inline-flex align-items-center gap-2 eligibility-criteria-add-btn" title="Add New"><i class="material-icons material-symbols-rounded">add</i> Add New</a>
                    <button type="button" class="btn btn-outline-secondary rounded-1 px-3 d-inline-flex align-items-center eligibility-criteria-print-btn" id="btnPrintEligibilityCriteria" title="Print"><i class="material-icons material-symbols-rounded">print</i></button>
                </div>
            </div>

            <div class="table-responsive">
                {!! $dataTable->table([
                    'class' => 'table table-bordered table-striped table-hover align-middle mb-0',
                    'aria-describedby' => 'eligibility-criteria-caption'
                ]) !!}
            </div>
            <div id="eligibility-criteria-caption" class="visually-hidden">Eligibility Criteria list</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts(attributes: ['type' => 'module']) !!}
    <script>
        (function() {
            function moveButtonsNextToSearch() {
                var wrapper = document.querySelector('#eligibilityCriteriaTable');
                if (!wrapper) return false;
                wrapper = wrapper.closest('.dataTables_wrapper');
                if (!wrapper) return false;
                var filter = wrapper.querySelector('.dataTables_filter');
                var addBtn = document.querySelector('.eligibility-criteria-add-btn');
                var printBtn = document.querySelector('.eligibility-criteria-print-btn');
                if (!filter || !addBtn) return false;
                addBtn.classList.add('ms-2');
                if (printBtn) {
                    printBtn.classList.add('ms-1');
                    filter.appendChild(printBtn);
                }
                filter.appendChild(addBtn);
                filter.classList.add('d-flex', 'align-items-center', 'justify-content-end', 'gap-2');
                return true;
            }
            var attempts = 0;
            var t = setInterval(function() {
                if (moveButtonsNextToSearch() || ++attempts > 50) clearInterval(t);
            }, 100);
        })();
    </script>
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

                win.document.open();
                win.document.write(
                    '<!doctype html>' +
                    '<html><head><title>Eligibility - Criteria</title>' +
                    '<style>' +
                    'body{font-family:Arial,sans-serif;padding:16px;color:#111827;}' +
                    'h2{margin:0 0 12px 0;font-size:20px;}' +
                    'table{width:100%;border-collapse:collapse;font-size:12px;}' +
                    'th,td{border:1px solid #d1d5db;padding:8px;vertical-align:top;text-align:left;}' +
                    'th{background:#f3f4f6;font-weight:600;}' +
                    '</style></head><body>' +
                    '<h2>Eligibility - Criteria</h2>' +
                    tableHtml +
                    '</body></html>'
                );
                win.document.close();

                // Close popup after print dialog closes (print or cancel),
                // so user stays on the listing page.
                win.onafterprint = function() {
                    win.close();
                };

                setTimeout(function() {
                    win.focus();
                    win.print();
                }, 250);
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

                openPrintWindow(buildPrintableTableHtml(table));
            });
        })();
    </script>
@endpush
