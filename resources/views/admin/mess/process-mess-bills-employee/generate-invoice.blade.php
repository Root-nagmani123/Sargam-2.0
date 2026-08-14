@extends('admin.layouts.master')
@section('title', 'Generate Invoice & Process Payment')
@section('content')
{{-- MessColumnManager's assets. The layout also includes these, but from below
     <head>, so its @push('styles') lands after @stack('styles') has rendered and
     the .mess-col-hidden rule is lost — hiding a column would set the class and
     nothing would happen. Pulled in from the child view (it is @once, so the
     layout's later include is skipped) the styles arrive in time. --}}
@include('components.mess-column-manager-assets')
<div class="container-fluid py-3 py-md-4 process-mess-bills-employee-report pmbe-page">
    <x-breadcrum title="Generate Invoice & Process Payment" :showBack="true"
        :backUrl="route('admin.mess.process-mess-bills-employee.index')" />

    {{-- Download / Print bar — same placement and styling as the index. The
         href is rewritten on click to carry the filters currently applied. --}}
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        <a href="{{ route('admin.mess.process-mess-bills-employee.generate-invoice-export') }}"
           class="btn pmbe-export-btn text-primary" id="generateInvoiceDownloadBtn" title="Download (Excel)">
            <i class="material-symbols-rounded">download</i><span>Download</span>
        </a>
        <button type="button" class="btn pmbe-export-btn text-primary" title="Print"
                onclick="printProcessMessBillsTable()">
            <i class="material-symbols-rounded">print</i><span>Print</span>
        </button>
    </div>

    {{-- id: the page JS delegates row clicks from this root, the same way it
         used to from the modal. --}}
    <div class="card border-0 shadow" id="pmbeBillsPanel">
        <div class="card-body p-3 p-lg-4">
            @include('admin.mess.process-mess-bills-employee.partials.generate-invoice-panel')
        </div>
    </div>
</div>

{{-- Column visibility, driven by MessColumnManager (same bridge as the index). --}}
<div class="modal fade" id="modalBillsColumnVisibilityModal" tabindex="-1" aria-labelledby="modalBillsColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="modalBillsColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0"><hr class="mt-0"><div class="row g-3" id="modalBillsColumnToggleGrid"></div></div>
            <div class="modal-footer border-0"><button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

@include('admin.mess.process-mess-bills-employee.partials.payment-modals')
@include('admin.mess.reports.partials.report-styles')

{{-- Toast container for feedback --}}
<div class="toast-container position-fixed top-0 end-0 p-3" id="pmbeToastContainer" style="z-index: 1100;"></div>
@endsection

@push('styles')
{{-- Select2 powers the filter pills; base stylesheet only, as on the index. --}}
<link rel="stylesheet" href="{{ asset('admin_assets/libs/select2/dist/css/select2.min.css') }}" />
<link rel="stylesheet" href="{{ asset('css/process-mess-bills-employee.css') }}?v={{ @filemtime(public_path('css/process-mess-bills-employee.css')) }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
@include('admin.mess.process-mess-bills-employee.partials.config-script')
<script src="{{ asset('js/process-mess-bills-employee.js') }}?v={{ @filemtime(public_path('js/process-mess-bills-employee.js')) }}"></script>

{{-- Stays inline: it writes a literal </script> into the print window, which
     only parses correctly inside an inline script block. --}}
<script>
function printProcessMessBillsTable() {
    var table = document.getElementById('modalBillsTable');
    if (!table) {
        window.print();
        return;
    }

    function openModalPrintWithBills(bills) {
        var dateFrom = document.getElementById('modal_date_from')?.value || '';
        var dateTo   = document.getElementById('modal_date_to')?.value || '';

        var title = 'Generate Invoice & Process Payment';
        var periodText = dateFrom || dateTo
            ? ('From ' + (dateFrom || 'Start') + ' To ' + (dateTo || 'End'))
            : 'All Dates';

        // One entry per printable column, keyed by the header's
        // data-mess-col-original. Action has no entry, so it is never printed.
        var VALUE_BY_COLUMN = {
            'S.No.': function (b, sn) { return String(sn); },
            'Buyer Name': function (b) { return b.buyer_name || '—'; },
            'Slip Number': function (b) { return b.invoice_no || '—'; },
            'Payment Type': function (b) { return b.payment_type || '—'; },
            'Total': function (b) { return b.total || '0'; },
            'Total Due Amount': function (b) { return b.total_due_amount || '0.00'; },
            'Status': function (b) {
                var due = parseFloat(String(b.total_due_amount || 0).replace(/[^0-9.-]/g, '')) || 0;
                var paid = parseFloat(String(b.paid_amount || 0).replace(/[^0-9.-]/g, '')) || 0;
                var text = due <= 0 ? 'Paid' : (paid > 0 ? 'Partial' : 'Unpaid');
                if (b.invoice_notification_sent) {
                    text += b.invoice_notification_fully_sent
                        ? ' · Invoice Sent'
                        : ' · Invoice Sent (partial)';
                }
                return text;
            }
        };

        // Columns are read off the live header, so the printout matches the
        // screen — a column hidden via Columns stays out of the print too.
        var headerRow = table.querySelector('thead tr');
        var printCols = Array.prototype.filter.call(headerRow ? headerRow.children : [], function (th) {
            if (th.classList.contains('mess-col-hidden')) return false;
            return !!VALUE_BY_COLUMN[(th.getAttribute('data-mess-col-original') || '').trim()];
        }).map(function (th) { return (th.getAttribute('data-mess-col-original') || '').trim(); });

        var columnsCount = printCols.length || 1;
        var columnHeadHtml = '<tr>' + printCols.map(function (c) { return '<th>' + c + '</th>'; }).join('') + '</tr>';

        var bodyHtml = (bills || []).map(function (b, i) {
            var sn = b.sno || (i + 1);
            return '<tr>' + printCols.map(function (c) {
                var cls = (c === 'Total' || c === 'Total Due Amount')
                    ? ' class="text-end"'
                    : (c === 'Status' ? ' class="text-center"' : '');
                return '<td' + cls + '>' + VALUE_BY_COLUMN[c](b, sn) + '</td>';
            }).join('') + '</tr>';
        }).join('');

        if (!bodyHtml) {
            if (window.alert) {
                window.alert('No bills to print. Adjust your filters and try again.');
            }
            return;
        }

        var printWindow = window.open('', '_blank');
        if (!printWindow) {
            window.print();
            return;
        }

        var printableTable = `
      <table class="table table-sm table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th colspan="${columnsCount}">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                  <img src="https://upload.wikimedia.org/wikipedia/commons/5/55/Emblem_of_India.svg" alt="India Emblem" height="40">
                  <div>
                    <div class="brand-line-1">Government of India</div>
                    <div class="brand-line-2">OFFICER'S MESS LBSNAA MUSSOORIE</div>
                    <div class="brand-line-3">Lal Bahadur Shastri National Academy of Administration</div>
                  </div>
                </div>
                <div class="d-none d-print-block">
                  <img src="https://www.lbsnaa.gov.in/admin_assets/images/logo.png" alt="LBSNAA Logo" height="40">
                </div>
              </div>
              <div class="d-flex flex-wrap justify-content-between align-items-center report-meta">
                <span><strong>${title}</strong></span>
                <span>${periodText}</span>
                <span><strong>Printed on:</strong> ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</span>
              </div>
            </th>
          </tr>
          ${columnHeadHtml}
        </thead>
        <tbody>
          ${bodyHtml}
        </tbody>
      </table>`;

        printWindow.document.open();
        printWindow.document.write(`<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>${title} - OFFICER'S MESS LBSNAA MUSSOORIE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      font-size: 10px;
      margin: 0;
      padding: 0;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .lbsnaa-header {
      border-bottom: 2px solid #004a93;
      padding-bottom: .75rem;
      margin-bottom: 1rem;
    }
    .brand-line-1 { font-size: .85rem; text-transform: uppercase; letter-spacing: .06em; color: #004a93; }
    .brand-line-2 { font-size: 1.1rem; font-weight: 700; text-transform: uppercase; color: #222; }
    .brand-line-3 { font-size: .8rem; color: #555; }
    .report-meta { font-size: .8rem; margin-bottom: .75rem; }
    .report-meta span { display: inline-block; margin-right: 1.5rem; }
    .container-fluid { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 9px; }
    th, td {
      padding: 4px 6px;
      border: 1px solid #dee2e6;
      white-space: normal !important;
      word-break: break-word;
      overflow-wrap: anywhere;
      vertical-align: top;
    }
    thead th { background: #f8f9fa; font-weight: 600; }
    .table, .table * { white-space: normal !important; }
    .table-responsive { overflow: visible !important; }
    thead { display: table-header-group; }
    @page { size: A4 landscape; margin: 8mm; }
    @media print { body { margin: 0; } }
    ${(window.MessColumnManager && window.MessColumnManager.MESS_PRINT_SUPPRESS_ICON_CSS) || ''}
  </style>
</head>
<body>
  <div class="container-fluid">
    <div class="table-responsive">
      ${printableTable}
    </div>
  </div>

  <script>
    window.addEventListener('load', function() { window.print(); });
  <\/script>
</body>
</html>`);
        printWindow.document.close();
    }

    if (typeof window.buildModalBillsDataUrl === 'function') {
        fetch(window.buildModalBillsDataUrl({ forPrint: true }))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                openModalPrintWithBills(data.bills || []);
            })
            .catch(function () {
                var fallback = (typeof window.getFilteredModalBills === 'function') ? window.getFilteredModalBills() : [];
                openModalPrintWithBills(fallback);
            });
        return;
    }

    openModalPrintWithBills((typeof window.getFilteredModalBills === 'function') ? window.getFilteredModalBills() : []);
}
</script>
@endpush
