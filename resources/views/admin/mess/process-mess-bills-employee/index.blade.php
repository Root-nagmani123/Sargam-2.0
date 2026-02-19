@extends('admin.layouts.master')
@section('title', 'Process Mess Bills')
@section('setup_content')
<div class="container-fluid">
    {{-- Page header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="mb-1 fw-bold">Process Mess Bills</h4>
            <p class="text-muted small mb-0">View mess bills for Employee, OT, Course & Other, generate invoices, and mark payments. Filter by date to see bills from Selling Voucher and Date Range reports.</p>
        </div>
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addProcessMessBillsModal">
            <i class="material-symbols-rounded" style="font-size: 1.25rem;">receipt_long</i>
            Generate Invoice
        </button>
    </div>

    {{-- Summary cards --}}
    @php $stats = $stats ?? ['total_bills' => 0, 'paid_count' => 0, 'unpaid_count' => 0, 'total_amount' => 0]; @endphp
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 p-2">
                        <i class="material-symbols-rounded text-primary" style="font-size: 1.75rem;">description</i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Total Bills</div>
                        <div class="fs-4 fw-bold">{{ number_format($stats['total_bills']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning bg-opacity-10 p-2">
                        <i class="material-symbols-rounded text-warning" style="font-size: 1.75rem;">schedule</i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Unpaid</div>
                        <div class="fs-4 fw-bold">{{ number_format($stats['unpaid_count']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success bg-opacity-10 p-2">
                        <i class="material-symbols-rounded text-success" style="font-size: 1.75rem;">check_circle</i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Paid</div>
                        <div class="fs-4 fw-bold">{{ number_format($stats['paid_count']) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info bg-opacity-10 p-2">
                        <i class="material-symbols-rounded text-info" style="font-size: 1.75rem;">payments</i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold">Total Amount</div>
                        <div class="fs-4 fw-bold">₹ {{ number_format($stats['total_amount'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters card --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mess.process-mess-bills-employee.index') }}" id="mainFilterForm">
                <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Date From <span class="text-danger">*</span></label>
                        <input type="text" name="date_from" id="date_from" class="form-control form-control-sm"
                               value="{{ $effectiveDateFrom ?? request('date_from', now()->startOfMonth()->format('d-m-Y')) }}"
                               placeholder="dd-mm-yyyy" autocomplete="off">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Date To <span class="text-danger">*</span></label>
                        <input type="text" name="date_to" id="date_to" class="form-control form-control-sm"
                               value="{{ $effectiveDateTo ?? request('date_to', now()->endOfMonth()->format('d-m-Y')) }}"
                               placeholder="dd-mm-yyyy" autocomplete="off">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="material-symbols-rounded align-middle" style="font-size: 1rem;">filter_list</i>
                            Apply
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Table card --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mess.process-mess-bills-employee.index') }}" id="filterForm">
                <input type="hidden" name="date_from" value="{{ $effectiveDateFrom ?? request('date_from') }}">
                <input type="hidden" name="date_to" value="{{ $effectiveDateTo ?? request('date_to') }}">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted">Show</span>
                        <select name="per_page" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit();">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        </select>
                        <span class="small text-muted">entries</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-link btn-sm p-0 text-dark" title="Export">
                            <i class="material-symbols-rounded" style="font-size: 1.35rem;">file_download</i>
                        </button>
                        <button type="button" class="btn btn-link btn-sm p-0 text-dark" title="Print" onclick="window.print()">
                            <i class="material-symbols-rounded" style="font-size: 1.35rem;">print</i>
                        </button>
                        <label class="small text-muted mb-0">Search:</label>
                        <input type="text" name="search" class="form-control form-control-sm" style="width: 180px;"
                               value="{{ request('search') }}" placeholder="Name or invoice...">
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0" id="processMessBillsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap py-2">S.No.</th>
                            <th class="text-nowrap py-2">Buyer Name</th>
                            <th class="text-nowrap py-2">Invoice No.</th>
                            <th class="text-nowrap py-2">Invoice Date</th>
                            <th class="text-nowrap py-2">Client Type</th>
                            <th class="text-nowrap py-2 text-end">Total</th>
                            <th class="text-nowrap py-2">Payment Type</th>
                            <th class="text-nowrap py-2">Status</th>
                            <th class="text-nowrap py-2 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $paymentTypeMap = [0 => 'Cash', 1 => 'Deduct From Salary', 2 => 'Online', 5 => 'Deduct From Salary'];
                        @endphp
                        @forelse($bills as $index => $bill)
                            @php
                                $billId = $bill->id ?? $bill->pk ?? 0;
                            @endphp
                            <tr class="{{ ($bill->status ?? 0) == 2 ? '' : 'table-warning table-warning-subtle' }}">
                                <td>{{ $bills->firstItem() + $index }}</td>
                                <td>{{ $bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '—') }}</td>
                                <td>{{ $billId }}</td>
                                <td>{{ $bill->issue_date ? $bill->issue_date->format('d-m-Y') : (isset($bill->date_from) && $bill->date_from ? $bill->date_from->format('d-m-Y') : '—') }}</td>
                                <td>{{ $bill->client_type_label ?? ($bill->clientTypeCategory ? ucfirst($bill->clientTypeCategory->client_type ?? '') : ucfirst($bill->client_type_slug ?? '—')) }}</td>
                                <td class="text-end fw-semibold">₹ {{ number_format($bill->total_amount ?? $bill->items->sum('amount'), 2) }}</td>
                                <td>{{ $paymentTypeMap[$bill->payment_type ?? 1] ?? '—' }}</td>
                                <td>
                                    @if(($bill->status ?? 0) == 2)
                                        <span class="badge bg-success">Paid</span>
                                    @elseif(($bill->status ?? 0) == 1)
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @else
                                        <span class="badge bg-secondary">Unpaid</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.mess.process-mess-bills-employee.print-receipt', $billId) }}" target="_blank"
                                       class="btn btn-sm btn-outline-primary" title="Print receipt">
                                        <i class="material-symbols-rounded" style="font-size: 1.1rem;">receipt</i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="material-symbols-rounded d-block mb-2" style="font-size: 2.5rem;">inbox</i>
                                    No bills found for the selected date range.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($bills->hasPages())
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top">
                    <div class="small text-muted">
                        Showing {{ $bills->firstItem() ?? 0 }} to {{ $bills->lastItem() ?? 0 }} of {{ $bills->total() }} entries
                    </div>
                    <div>
                        {{ $bills->withQueryString()->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Toast container for feedback --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="processBillsToastContainer"></div>

{{-- Generate Invoice & Payment Modal --}}
<style>
#addProcessMessBillsModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#addProcessMessBillsModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; border-radius: 0.5rem; }
#addProcessMessBillsModal .modal-header { border-radius: 0.5rem 0.5rem 0 0; }
#addProcessMessBillsModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); }
#addProcessMessBillsModal .modal-footer { border-top: 1px solid var(--bs-border-color); }
</style>
<div class="modal fade" id="addProcessMessBillsModal" tabindex="-1" aria-labelledby="addProcessMessBillsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-semibold d-flex align-items-center gap-2" id="addProcessMessBillsModalLabel">
                    <i class="material-symbols-rounded">receipt_long</i>
                    Generate Invoice & Process Payment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addModalFilterForm">
                    @csrf
                    <div class="row g-3 mb-4">
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Date From <span class="text-danger">*</span></label>
                            <input type="text" name="modal_date_from" id="modal_date_from" class="form-control form-control-sm"
                                   value="{{ now()->startOfMonth()->format('d-m-Y') }}" placeholder="dd-mm-yyyy" autocomplete="off" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Date To <span class="text-danger">*</span></label>
                            <input type="text" name="modal_date_to" id="modal_date_to" class="form-control form-control-sm"
                                   value="{{ now()->endOfMonth()->format('d-m-Y') }}" placeholder="dd-mm-yyyy" autocomplete="off" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Invoice Date</label>
                            <input type="text" name="modal_invoice_date" id="modal_invoice_date" class="form-control form-control-sm"
                                   value="{{ now()->format('d-m-Y') }}" placeholder="dd-mm-yyyy" autocomplete="off">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Mode of Payment</label>
                            <select name="mode_of_payment" class="form-select form-select-sm">
                                <option value="deduct_from_salary" selected>Deduct From Salary</option>
                                <option value="cash">Cash</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-primary btn-sm w-100" id="modalLoadBillsBtn">
                                <i class="material-symbols-rounded align-middle" style="font-size: 1rem;">search</i> Load Bills
                            </button>
                        </div>
                    </div>
                </form>

                {{-- Bulk actions (shown when rows selected) --}}
                <div class="d-none align-items-center gap-2 mb-3 p-2 rounded bg-light" id="modalBulkActionsBar">
                    <span class="small fw-semibold" id="modalSelectedCount">0 selected</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="modalBulkInvoiceBtn">Generate Invoice (selected)</button>
                    <button type="button" class="btn btn-sm btn-outline-success" id="modalBulkPaymentBtn">Mark as Paid (selected)</button>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted">Show</span>
                        <select id="modalPerPage" class="form-select form-select-sm" style="width: auto;">
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span class="small text-muted">entries</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" id="modalSearch" class="form-control form-control-sm" style="width: 200px;" placeholder="Search bills...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-nowrap py-2" style="width: 40px;"><input type="checkbox" id="modalSelectAll" class="form-check-input" title="Select all"></th>
                                <th class="text-nowrap py-2">S.No.</th>
                                <th class="text-nowrap py-2">Buyer Name</th>
                                <th class="text-nowrap py-2">Invoice No.</th>
                                <th class="text-nowrap py-2">Payment Type</th>
                                <th class="text-nowrap py-2 text-end">Total</th>
                                <th class="text-nowrap py-2 text-end">Paid</th>
                                <th class="text-nowrap py-2 text-center">Actions</th>
                                <th class="text-nowrap py-2 text-center">Receipt</th>
                            </tr>
                        </thead>
                        <tbody id="modalBillsTableBody">
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">Select date range and click <strong>Load Bills</strong> to load unpaid bills.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
                    <div class="small text-muted" id="modalPaginationInfo">Showing 0 to 0 of 0 entries</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof flatpickr !== 'undefined') {
        flatpickr('#date_from', { dateFormat: 'd-m-Y', allowInput: true });
        flatpickr('#date_to', { dateFormat: 'd-m-Y', allowInput: true });
        flatpickr('#modal_date_from', { dateFormat: 'd-m-Y', allowInput: true });
        flatpickr('#modal_date_to', { dateFormat: 'd-m-Y', allowInput: true });
        flatpickr('#modal_invoice_date', { dateFormat: 'd-m-Y', allowInput: true });
    }

    var modalBillsData = [];
    var printReceiptBaseUrl = '{{ route("admin.mess.process-mess-bills-employee.print-receipt", ["id" => "__ID__"]) }}';
    var generateInvoiceBaseUrl = '{{ url("admin/mess/process-mess-bills-employee") }}';
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';

    function toYmd(val) {
        if (!val || !String(val).match(/^\d{1,2}-\d{1,2}-\d{4}$/)) return val;
        var p = String(val).split('-');
        return p[2] + '-' + p[1] + '-' + p[0];
    }

    function showToast(message, type) {
        type = type || 'success';
        var container = document.getElementById('processBillsToastContainer');
        var toastEl = document.createElement('div');
        toastEl.className = 'toast align-items-center text-bg-' + (type === 'error' ? 'danger' : 'success') + ' border-0 show';
        toastEl.setAttribute('role', 'alert');
        toastEl.innerHTML = '<div class="d-flex"><div class="toast-body">' + (message || 'Done') + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        container.appendChild(toastEl);
        var t = new bootstrap.Toast(toastEl, { delay: 4000 });
        t.show();
        toastEl.addEventListener('hidden.bs.toast', function() { toastEl.remove(); });
    }

    function loadModalBills() {
        var df = document.getElementById('modal_date_from');
        var dt = document.getElementById('modal_date_to');
        var dateFrom = (df && df.value) ? toYmd(df.value) : '';
        var dateTo = (dt && dt.value) ? toYmd(dt.value) : '';
        var url = '{{ route("admin.mess.process-mess-bills-employee.modal-data") }}?date_from=' + encodeURIComponent(dateFrom) + '&date_to=' + encodeURIComponent(dateTo);
        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                modalBillsData = data.bills || [];
                renderModalTable();
            })
            .catch(function() {
                modalBillsData = [];
                renderModalTable();
                showToast('Failed to load bills.', 'error');
            });
    }

    function getFilteredModalBills() {
        var search = (document.getElementById('modalSearch') || {}).value || '';
        search = String(search).toLowerCase().trim();
        return modalBillsData.filter(function(b) {
            if (!search) return true;
            return (b.buyer_name || '').toLowerCase().indexOf(search) >= 0 ||
                   String(b.invoice_no || '').indexOf(search) >= 0 ||
                   (b.payment_type || '').toLowerCase().indexOf(search) >= 0 ||
                   String(b.total || '').indexOf(search) >= 0;
        });
    }

    function renderModalTable() {
        var tbody = document.getElementById('modalBillsTableBody');
        var modalSelectAllEl = document.getElementById('modalSelectAll');
        if (modalSelectAllEl) modalSelectAllEl.checked = false;
        var filtered = getFilteredModalBills();
        var perPage = parseInt((document.getElementById('modalPerPage') || {}).value || 10, 10);
        var page = 1;
        var start = (page - 1) * perPage;
        var pageData = filtered.slice(start, start + perPage);

        if (pageData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-4 text-muted">No unpaid bills found. Adjust date range and click Load Bills.</td></tr>';
        } else {
            tbody.innerHTML = pageData.map(function(b, i) {
                var sn = start + i + 1;
                var printUrl = printReceiptBaseUrl.replace('__ID__', b.id);
                return '<tr class="' + (i % 2 === 0 ? 'table-light' : '') + '">' +
                    '<td><input type="checkbox" class="form-check-input modal-bill-check" data-id="' + b.id + '" data-name="' + (b.buyer_name || '').replace(/"/g, '&quot;') + '"></td>' +
                    '<td>' + sn + '</td>' +
                    '<td>' + (b.buyer_name || '—') + '</td>' +
                    '<td>' + (b.invoice_no || '—') + '</td>' +
                    '<td>' + (b.payment_type || '—') + '</td>' +
                    '<td class="text-end">' + (b.total || '0') + '</td>' +
                    '<td class="text-end">' + (b.paid_amount || '0') + '</td>' +
                    '<td class="text-center"><div class="btn-group btn-group-sm">' +
                    '<button type="button" class="btn btn-outline-primary generate-invoice-btn" data-bill-id="' + b.id + '" data-buyer-name="' + (b.buyer_name || '').replace(/"/g, '&quot;') + '" title="Generate Invoice">Invoice</button>' +
                    '<button type="button" class="btn btn-outline-success generate-payment-btn" data-bill-id="' + b.id + '" data-buyer-name="' + (b.buyer_name || '').replace(/"/g, '&quot;') + '" title="Mark as Paid">Payment</button>' +
                    '</div></td>' +
                    '<td class="text-center"><a href="' + printUrl + '" target="_blank" class="btn btn-sm btn-outline-secondary" title="Print receipt"><i class="material-symbols-rounded" style="font-size:1.1rem;">receipt</i></a></td>' +
                    '</tr>';
            }).join('');
        }

        document.getElementById('modalPaginationInfo').textContent = 'Showing ' + (filtered.length ? start + 1 : 0) + ' to ' + Math.min(start + perPage, filtered.length) + ' of ' + filtered.length + ' entries';
        updateBulkActionsBar();
    }

    function updateBulkActionsBar() {
        var checked = document.querySelectorAll('#addProcessMessBillsModal .modal-bill-check:checked');
        var bar = document.getElementById('modalBulkActionsBar');
        var countEl = document.getElementById('modalSelectedCount');
        if (!bar || !countEl) return;
        if (checked.length === 0) {
            bar.classList.add('d-none');
        } else {
            bar.classList.remove('d-none');
            countEl.textContent = checked.length + ' selected';
        }
    }

    document.getElementById('addProcessMessBillsModal').addEventListener('show.bs.modal', function() { loadModalBills(); });
    document.getElementById('modalLoadBillsBtn').addEventListener('click', loadModalBills);
    document.getElementById('modalSearch').addEventListener('input', renderModalTable);
    document.getElementById('modalPerPage').addEventListener('change', renderModalTable);

    document.getElementById('modalSelectAll').addEventListener('change', function() {
        document.querySelectorAll('#addProcessMessBillsModal .modal-bill-check').forEach(function(cb) {
            cb.checked = this.checked;
        }.bind(this));
        updateBulkActionsBar();
    });

    document.getElementById('addProcessMessBillsModal').addEventListener('click', function(e) {
        var target = e.target.closest('.modal-bill-check');
        if (target && target.classList.contains('modal-bill-check')) {
            updateBulkActionsBar();
        }
    });

    function doGenerateInvoice(billId, buyerName, btnEl) {
        if (!billId) { showToast('Bill ID not found.', 'error'); return; }
        if (btnEl) { btnEl.disabled = true; btnEl.textContent = '…'; }
        fetch(generateInvoiceBaseUrl + '/' + billId + '/generate-invoice', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({})
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showToast(data.message || 'Invoice generated.');
                loadModalBills();
            } else {
                showToast(data.message || 'Failed to generate invoice.', 'error');
            }
        })
        .catch(function() {
            showToast('Request failed. Try again.', 'error');
        })
        .finally(function() {
            if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Invoice'; }
        });
    }

    function doGeneratePayment(billId, buyerName, btnEl) {
        if (!billId) { showToast('Bill ID not found.', 'error'); return; }
        if (btnEl) { btnEl.disabled = true; btnEl.textContent = '…'; }
        fetch(generateInvoiceBaseUrl + '/' + billId + '/generate-payment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({})
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Payment completed for ' + (buyerName || data.client_name) + '.');
                var modalEl = document.getElementById('addProcessMessBillsModal');
                if (bootstrap.Modal.getInstance(modalEl)) bootstrap.Modal.getInstance(modalEl).hide();
                window.location.reload();
            } else {
                showToast(data.message || 'Failed to process payment.', 'error');
                if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Payment'; }
            }
        })
        .catch(function() {
            showToast('Request failed. Try again.', 'error');
            if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Payment'; }
        });
    }

    document.addEventListener('click', function(e) {
        var invoiceBtn = e.target.closest('.generate-invoice-btn');
        if (invoiceBtn) {
            e.preventDefault();
            var billId = invoiceBtn.getAttribute('data-bill-id');
            var buyerName = invoiceBtn.getAttribute('data-buyer-name') || '';
            if (confirm('Generate invoice and send notification to ' + (buyerName || 'this employee') + '?')) {
                doGenerateInvoice(billId, buyerName, invoiceBtn);
            }
            return;
        }
        var paymentBtn = e.target.closest('.generate-payment-btn');
        if (paymentBtn) {
            e.preventDefault();
            var billId = paymentBtn.getAttribute('data-bill-id');
            var buyerName = paymentBtn.getAttribute('data-buyer-name') || '';
            if (confirm('Mark as paid and send notification to ' + (buyerName || 'this employee') + '?')) {
                doGeneratePayment(billId, buyerName, paymentBtn);
            }
            return;
        }
    });

    // Bulk actions
    document.getElementById('modalBulkInvoiceBtn').addEventListener('click', function() {
        var ids = Array.from(document.querySelectorAll('#addProcessMessBillsModal .modal-bill-check:checked')).map(function(c) { return c.getAttribute('data-id'); });
        if (ids.length === 0) { showToast('Select at least one bill.', 'error'); return; }
        if (!confirm('Generate invoice for ' + ids.length + ' selected bill(s)?')) return;
        var done = 0;
        ids.forEach(function(id) {
            doGenerateInvoice(id, '', null);
            done++;
        });
        showToast('Processing ' + ids.length + ' invoice(s)...');
    });

    document.getElementById('modalBulkPaymentBtn').addEventListener('click', function() {
        var checked = document.querySelectorAll('#addProcessMessBillsModal .modal-bill-check:checked');
        if (checked.length === 0) { showToast('Select at least one bill.', 'error'); return; }
        if (!confirm('Mark ' + checked.length + ' selected bill(s) as paid?')) return;
        checked.forEach(function(cb) {
            doGeneratePayment(cb.getAttribute('data-id'), cb.getAttribute('data-name'), null);
        });
    });
});
</script>
@endpush

<script>
document.addEventListener('DOMContentLoaded', function() {
    var df = document.getElementById('date_from');
    var dt = document.getElementById('date_to');
    function toYmd(val) {
        if (!val || !String(val).match(/^\d{1,2}-\d{1,2}-\d{4}$/)) return val;
        var p = String(val).split('-');
        return p[2] + '-' + p[1] + '-' + p[0];
    }
    document.querySelectorAll('form[action="{{ route('admin.mess.process-mess-bills-employee.index') }}"]').forEach(function(form) {
        form.addEventListener('submit', function() {
            var hFrom = form.querySelector('input[name="date_from"]');
            var hTo = form.querySelector('input[name="date_to"]');
            var valFrom = (df && df.value) ? (toYmd(df.value) || df.value) : (hFrom ? hFrom.value : '');
            var valTo = (dt && dt.value) ? (toYmd(dt.value) || dt.value) : (hTo ? hTo.value : '');
            if (hFrom && valFrom) hFrom.value = valFrom;
            if (hTo && valTo) hTo.value = valTo;
        });
    });
});
</script>
@endsection
