@extends('admin.layouts.master')
@section('title', 'Process Mess Bills Employee')
@section('setup_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 fw-bold">Process Mess Bills Employee</h4>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProcessMessBillsModal">
            ADD
        </button>
    </div>

    {{-- Description --}}
    <p class="text-muted small mb-4">
        This page displays all kitchen issue added in the system, and provide options to manage records such as add, excel download, print etc.
    </p>

    {{-- Date Range Filter --}}
    <form method="GET" action="{{ route('admin.mess.process-mess-bills-employee.index') }}" id="mainFilterForm" class="mb-4">
        <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
        <input type="hidden" name="search" value="{{ request('search') }}">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Date From <span class="text-danger">*</span></label>
                <input type="text" name="date_from" id="date_from" class="form-control form-control-sm" 
                       value="{{ $effectiveDateFrom ?? request('date_from', now()->startOfMonth()->format('d-m-Y')) }}" 
                       placeholder="Select Starting Date" autocomplete="off">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Date To <span class="text-danger">*</span></label>
                <input type="text" name="date_to" id="date_to" class="form-control form-control-sm" 
                       value="{{ $effectiveDateTo ?? request('date_to', now()->endOfMonth()->format('d-m-Y')) }}" 
                       placeholder="Select Ending Date" autocomplete="off">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm">Show</button>
            </div>
        </div>
    </form>

    {{-- Table Controls: Show entries & Search --}}
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
                    <i class="material-icons material-symbols-rounded" style="font-size: 22px;">extension</i>
                </button>
                <button type="button" class="btn btn-link btn-sm p-0 text-dark" title="Print" onclick="window.print()">
                    <i class="material-icons material-symbols-rounded" style="font-size: 22px;">print</i>
                </button>
                <label class="small text-muted mb-0">Search with in table:</label>
                <input type="text" name="search" class="form-control form-control-sm" style="width: 180px;" 
                       value="{{ request('search') }}" placeholder="Search...">
            </div>
        </div>
    </form>

    {{-- Data Table --}}
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0" id="processMessBillsTable">
            <thead style="background-color: #004a93; color: #fff;">
                <tr>
                    <th class="text-nowrap py-2">S.NO.</th>
                    <th class="text-nowrap py-2">BUYER NAME</th>
                    <th class="text-nowrap py-2">INVOICE NO.</th>
                    <th class="text-nowrap py-2">INVOICE DATE</th>
                    <th class="text-nowrap py-2">CLIENT TYPE</th>
                    <th class="text-nowrap py-2">TOTAL</th>
                    <th class="text-nowrap py-2">PAYMENT TYPE</th>
                    <th class="text-nowrap py-2">PAYMENT STATUS</th>
                    <th class="text-nowrap py-2">PRINT RECEIPT</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $paymentTypeMap = [0 => 'Cash', 1 => 'My Self', 2 => 'Online', 5 => 'My Self'];
                @endphp
                @forelse($bills as $index => $bill)
                    <tr class="{{ $index % 2 === 0 ? 'table-light' : '' }}">
                        <td>{{ $bills->firstItem() + $index }}</td>
                        <td>{{ $bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '—') }}</td>
                        <td>{{ $bill->id }}</td>
                        <td>{{ $bill->issue_date ? $bill->issue_date->format('d-m-Y') : ($bill->date_from ? $bill->date_from->format('d-m-Y') : '—') }}</td>
                        <td>{{ $bill->clientTypeCategory ? ucfirst($bill->clientTypeCategory->client_type ?? '') : ucfirst($bill->client_type_slug ?? '—') }}</td>
                        <td>{{ number_format($bill->total_amount ?? $bill->items->sum('amount'), 2) }}</td>
                        <td>{{ $paymentTypeMap[$bill->payment_type ?? 1] ?? '—' }}</td>
                        <td>
                            @if(($bill->status ?? 0) == 2)
                                <span class="badge bg-success">Paid</span>
                            @elseif(($bill->status ?? 0) == 1)
                                <span class="badge bg-warning">Pending</span>
                            @else
                                <span class="badge bg-secondary">Unpaid</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.mess.process-mess-bills-employee.print-receipt', $bill->id) }}" target="_blank" class="text-primary text-decoration-none small">
                                Print Reciept
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($bills->hasPages())
        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
            <div class="small text-muted">
                Showing {{ $bills->firstItem() ?? 0 }} to {{ $bills->lastItem() ?? 0 }} of {{ $bills->total() }} entries
            </div>
            <div>
                {{ $bills->withQueryString()->links() }}
            </div>
        </div>
    @endif
</div>

{{-- ADD / Generate Invoice Modal --}}
<style>
#addProcessMessBillsModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#addProcessMessBillsModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; }
#addProcessMessBillsModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); }
</style>
<div class="modal fade" id="addProcessMessBillsModal" tabindex="-1" aria-labelledby="addProcessMessBillsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-semibold" id="addProcessMessBillsModalLabel">Generate Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addModalFilterForm">
                    @csrf
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Date From <span class="text-danger">*</span></label>
                            <input type="text" name="modal_date_from" id="modal_date_from" class="form-control form-control-sm" 
                                   value="{{ now()->startOfMonth()->format('d-m-Y') }}" placeholder="Select Starting Date" autocomplete="off" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To <span class="text-danger">*</span></label>
                            <input type="text" name="modal_date_to" id="modal_date_to" class="form-control form-control-sm" 
                                   value="{{ now()->endOfMonth()->format('d-m-Y') }}" placeholder="Select Ending Date" autocomplete="off" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Mode Of Payment <span class="text-danger">*</span></label>
                            <select name="mode_of_payment" class="form-select form-select-sm" required>
                                <option value="">Please Select Mode Of Payment</option>
                                <option value="deduct_from_salary" selected>Deduction From salary</option>
                                <option value="cash">Cash</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Action <span class="text-danger">*</span></label>
                            <div class="d-flex align-items-center gap-2">
                                <select name="action" class="form-select form-select-sm" required>
                                    <option value="">Please Select Action</option>
                                    <option value="generate_invoice" selected>Generate Invoice</option>
                                </select>
                                <a href="#" class="text-primary text-decoration-none small text-nowrap">Generate Bulk Invoice</a>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                            <input type="text" name="modal_invoice_date" id="modal_invoice_date" class="form-control form-control-sm" 
                                   value="{{ now()->format('d-m-Y') }}" placeholder="Select Invoice Date" autocomplete="off" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-primary btn-sm" id="modalLoadBillsBtn">Show</button>
                        </div>
                    </div>
                </form>

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
                        <button type="button" class="btn btn-link btn-sm p-0 text-dark" title="Export"><i class="material-icons material-symbols-rounded" style="font-size: 22px;">extension</i></button>
                        <button type="button" class="btn btn-link btn-sm p-0 text-dark" title="Print" onclick="window.print()"><i class="material-icons material-symbols-rounded" style="font-size: 22px;">print</i></button>
                        <label class="small text-muted mb-0">Search with in table:</label>
                        <input type="text" id="modalSearch" class="form-control form-control-sm" style="width: 180px;" placeholder="Search...">
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead style="background-color: #004a93; color: #fff;">
                            <tr>
                                <th class="text-nowrap py-2" style="width: 40px;"><input type="checkbox" id="modalSelectAll" class="form-check-input"></th>
                                <th class="text-nowrap py-2">S.NO.</th>
                                <th class="text-nowrap py-2">BUYER NAME</th>
                                <th class="text-nowrap py-2">INVOICE NO.</th>
                                <th class="text-nowrap py-2">PAYMENT TYPE</th>
                                <th class="text-nowrap py-2">TOTAL</th>
                                <th class="text-nowrap py-2">PAID AMOUNT</th>
                                <th class="text-nowrap py-2">ACTION</th>
                                <th class="text-nowrap py-2">PRINT RECEIPT</th>
                                <th class="text-nowrap py-2">BILL NO.</th>
                            </tr>
                        </thead>
                        <tbody id="modalBillsTableBody">
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">Select date range and click Show to load bills.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3">
                    <div class="small text-muted" id="modalPaginationInfo">Showing 0 to 0 of 0 entries</div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
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

        // Modal bills data (client-side)
        var modalBillsData = [];
        var printReceiptBaseUrl = '{{ route("admin.mess.process-mess-bills-employee.print-receipt", ["id" => "__ID__"]) }}';

        function toYmd(val) {
            if (!val || !String(val).match(/^\d{1,2}-\d{1,2}-\d{4}$/)) return val;
            var p = String(val).split('-');
            return p[2] + '-' + p[1] + '-' + p[0];
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
                });
        }

        function renderModalTable() {
            var tbody = document.getElementById('modalBillsTableBody');
            var modalSelectAllEl = document.getElementById('modalSelectAll');
            if (modalSelectAllEl) modalSelectAllEl.checked = false;
            var search = (document.getElementById('modalSearch') || {}).value || '';
            var perPage = parseInt((document.getElementById('modalPerPage') || {}).value || 10, 10);
            search = String(search).toLowerCase().trim();

            var filtered = modalBillsData.filter(function(b) {
                if (!search) return true;
                return (b.buyer_name || '').toLowerCase().indexOf(search) >= 0 ||
                       String(b.invoice_no || '').indexOf(search) >= 0 ||
                       (b.payment_type || '').toLowerCase().indexOf(search) >= 0 ||
                       String(b.total || '').indexOf(search) >= 0;
            });

            var page = 1;
            var start = (page - 1) * perPage;
            var pageData = filtered.slice(start, start + perPage);

            if (pageData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center py-4 text-muted">No records found.</td></tr>';
            } else {
                tbody.innerHTML = pageData.map(function(b, i) {
                    var sn = start + i + 1;
                    return '<tr class="' + (i % 2 === 0 ? 'table-light' : '') + '">' +
                        '<td><input type="checkbox" class="form-check-input modal-bill-check" data-id="' + b.id + '"></td>' +
                        '<td>' + sn + '</td>' +
                        '<td>' + (b.buyer_name || '—') + '</td>' +
                        '<td>' + (b.invoice_no || 'N/A') + '</td>' +
                        '<td>' + (b.payment_type || '—') + '</td>' +
                        '<td>' + (b.total || '0') + '</td>' +
                        '<td>' + (b.paid_amount || '0') + '</td>' +
                        '<td><a href="#" class="text-primary text-decoration-none small">Generate Invoice</a></td>' +
                        '<td><a href="' + printReceiptBaseUrl.replace('__ID__', b.id) + '" target="_blank" class="text-primary text-decoration-none small">Print Reciept</a></td>' +
                        '<td>' + (b.bill_no || '—') + '</td>' +
                        '</tr>';
                }).join('');
            }

            document.getElementById('modalPaginationInfo').textContent = 'Showing ' + (filtered.length ? start + 1 : 0) + ' to ' + Math.min(start + perPage, filtered.length) + ' of ' + filtered.length + ' entries';
        }

        document.getElementById('addProcessMessBillsModal').addEventListener('show.bs.modal', function() {
            loadModalBills();
        });

        var loadBtn = document.getElementById('modalLoadBillsBtn');
        if (loadBtn) loadBtn.addEventListener('click', loadModalBills);

        var modalSearchEl = document.getElementById('modalSearch');
        if (modalSearchEl) modalSearchEl.addEventListener('input', renderModalTable);

        var modalPerPageEl = document.getElementById('modalPerPage');
        if (modalPerPageEl) modalPerPageEl.addEventListener('change', renderModalTable);

        var modalSelectAll = document.getElementById('modalSelectAll');
        if (modalSelectAll) {
            modalSelectAll.addEventListener('change', function() {
                document.querySelectorAll('#addProcessMessBillsModal .modal-bill-check').forEach(function(cb) {
                    cb.checked = this.checked;
                }.bind(this));
            });
        }
    });
</script>
@endpush

{{-- Sync visible dates to hidden inputs and convert d-m-Y to Y-m-d for backend --}}
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
