@extends('admin.layouts.master')
@section('title', 'Process Mess Bills')
@section('setup_content')
<div class="container-fluid">
    {{-- Report Header (Print Only) --}}
    @php
        $dateFromDisplay = $effectiveDateFrom ?? now()->startOfMonth()->format('d-m-Y');
        $dateToDisplay = $effectiveDateTo ?? now()->endOfMonth()->format('d-m-Y');
        try {
            $dateFromDisplay = \Carbon\Carbon::parse($dateFromDisplay)->format('d-F-Y');
            $dateToDisplay = \Carbon\Carbon::parse($dateToDisplay)->format('d-F-Y');
        } catch (\Exception $e) {
            $dateFromDisplay = $effectiveDateFrom ?? '';
            $dateToDisplay = $effectiveDateTo ?? '';
        }
    @endphp
    <div class="report-header text-center mb-4">
        <h4 class="fw-bold">Process Mess Bills</h4>
        <p class="mb-1">Period: {{ $dateFromDisplay }} to {{ $dateToDisplay }}</p>
        <p class="text-muted mb-0 small">Generated on: {{ now()->format('d-m-Y H:i:s') }}</p>
    </div>

    {{-- Page header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 no-print">
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
    <div class="no-print">
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
    </div>

    {{-- Filters card --}}
    <div class="card border-0 shadow-sm mb-4 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mess.process-mess-bills-employee.index') }}" id="mainFilterForm">
                <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Date From <span class="text-danger">*</span></label>
                        <input type="text" name="date_from" id="date_from" class="form-control form-control-sm"
                               value="{{ $effectiveDateFrom ?? request('date_from', now()->startOfMonth()->format('d-m-Y')) }}"
                               data-default-ymd="{{ $effectiveDateFromYmd ?? now()->startOfMonth()->format('Y-m-d') }}"
                               placeholder="dd-mm-yyyy" autocomplete="off">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Date To <span class="text-danger">*</span></label>
                        <input type="text" name="date_to" id="date_to" class="form-control form-control-sm"
                               value="{{ $effectiveDateTo ?? request('date_to', now()->endOfMonth()->format('d-m-Y')) }}"
                               data-default-ymd="{{ $effectiveDateToYmd ?? now()->endOfMonth()->format('Y-m-d') }}"
                               placeholder="dd-mm-yyyy" autocomplete="off">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Client Type</label>
                        <select name="client_type" class="form-select form-select-sm select2">
                            <option value="">All</option>
                            <option value="employee" {{ ($clientType ?? '') === 'employee' ? 'selected' : '' }}>Employee</option>
                            <option value="ot" {{ ($clientType ?? '') === 'ot' ? 'selected' : '' }}>OT</option>
                            <option value="course" {{ ($clientType ?? '') === 'course' ? 'selected' : '' }}>Course</option>
                            <option value="other" {{ ($clientType ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Buyer Name</label>
                        <input type="text" name="buyer_name" class="form-control form-control-sm"
                               value="{{ $buyerName ?? request('buyer_name') }}" placeholder="Filter by buyer name...">
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                            <i class="material-symbols-rounded align-middle" style="font-size: 1rem;">filter_list</i>
                            Apply
                        </button>
                        @php
                            $clearFilterParams = array_filter([
                                'per_page' => request('per_page', 10),
                                'search' => request('search'),
                            ]);
                        @endphp
                        <a href="{{ route('admin.mess.process-mess-bills-employee.index', $clearFilterParams) }}" class="btn btn-outline-secondary btn-sm">Clear filters</a>
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
                <input type="hidden" name="client_type" value="{{ $clientType ?? request('client_type') }}">
                <input type="hidden" name="buyer_name" value="{{ $buyerName ?? request('buyer_name') }}">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2 no-print">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted">Show</span>
                        <select name="per_page" class="form-select form-select-sm select2" style="width: auto;" onchange="this.form.submit();">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                        </select>
                        <span class="small text-muted">entries</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('admin.mess.process-mess-bills-employee.export') }}?{{ http_build_query(request()->only(['date_from', 'date_to', 'client_type', 'buyer_name', 'search'])) }}" class="btn btn-link btn-sm p-0 text-dark" title="Export to Excel">
                            <i class="material-symbols-rounded" style="font-size: 1.35rem;">file_download</i>
                        </a>
                        <button type="button" class="btn btn-link btn-sm p-0 text-dark no-print" title="Print" onclick="window.print()">
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
                            <th class="text-nowrap py-2">Slip No.</th>
                            <th class="text-nowrap py-2">Invoice Date</th>
                            <th class="text-nowrap py-2">Client Type</th>
                            <th class="text-nowrap py-2 text-end">Total</th>
                            <th class="text-nowrap py-2">Payment Type</th>
                            <th class="text-nowrap py-2">Status</th>
                            <th class="text-nowrap py-2 text-center no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $paymentTypeMap = [0 => 'Cash', 1 => 'Deduct From Salary', 2 => 'Online', 5 => 'Deduct From Salary'];
                        @endphp
                        @forelse($bills as $index => $bill)
                            @php
                                $billId = $bill->id ?? $bill->pk ?? 0;
                                $isDateRange = ($bill->source_type ?? '') === 'date_range';
                                $slipNo = $isDateRange ? 'DR-' . str_pad($bill->id, 6, '0', STR_PAD_LEFT) : 'SV-' . str_pad($bill->pk ?? $bill->id ?? 0, 6, '0', STR_PAD_LEFT);
                                $receiptId = $isDateRange ? 'dr-' . $bill->id : 'ki-' . ($bill->pk ?? $bill->id);
                            @endphp
                            <tr class="{{ ($bill->status ?? 0) == 2 ? '' : 'table-warning table-warning-subtle' }}">
                                <td>{{ $bills->firstItem() + $index }}</td>
                                <td>{{ $bill->client_name ?? ($bill->clientTypeCategory->client_name ?? '—') }}</td>
                                <td>{{ $slipNo }}</td>
                                <td>{{ $bill->issue_date ? $bill->issue_date->format('d-m-Y') : (isset($bill->date_from) && $bill->date_from ? $bill->date_from->format('d-m-Y') : '—') }}</td>
                                <td>{{ $bill->client_type_display ?? ($bill->client_type_label ?? ($bill->clientTypeCategory ? ucfirst($bill->clientTypeCategory->client_type ?? '') : ucfirst($bill->client_type_slug ?? '—'))) }}</td>
                                <td class="text-end fw-semibold">₹ {{ number_format($bill->net_total, 2) }}</td>
                                <td>{{ $paymentTypeMap[$bill->payment_type ?? 1] ?? '—' }}</td>
                                <td>
                                    @if(($bill->status ?? 0) == 2)
                                        <span class="badge bg-success">Paid</span>
                                    @elseif(($bill->status ?? 0) == 1)
                                        <span class="badge bg-warning text-dark">Partial</span>
                                    @else
                                        <span class="badge bg-secondary">Unpaid</span>
                                    @endif
                                </td>
                                <td class="text-center no-print">
                                    <a href="{{ route('admin.mess.process-mess-bills-employee.print-receipt', $receiptId) }}" target="_blank"
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
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top no-print">
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
<div class="toast-container position-fixed bottom-0 end-0 p-3 no-print" id="processBillsToastContainer"></div>

{{-- Payment Details (Bill Receipt) Modal - shows when user clicks "Payment" --}}
<div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-labelledby="paymentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content bill-receipt-modal-content">
            <div class="modal-header border-0 py-2 align-items-start">
                <h5 class="modal-title fw-bold" id="paymentDetailsModalLabel">Bill Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bill-receipt-modal-body">
                <div id="paymentDetailsContent" class="bill-receipt-content">
                    <div class="text-center py-4 text-muted">Loading...</div>
                </div>
                <div class="bill-receipt-actions">
                    <button type="button" class="btn btn-receipt-pay" id="paymentDetailsPayNowBtn">
                        <i class="material-symbols-rounded align-middle" style="font-size: 1.1rem;">payments</i> Pay Now
                    </button>
                    <button type="button" class="btn btn-receipt-print" id="paymentDetailsPrintBtn">
                        <i class="material-symbols-rounded align-middle" style="font-size: 1.1rem;">print</i> Print
                    </button>
                    <button type="button" class="btn btn-receipt-cancel" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Pay Now (Payment Detail form) Modal - opens when user clicks "Pay Now" in Bill Receipt --}}
<div class="modal fade payment-detail-modal" id="payNowModal" tabindex="-1" aria-labelledby="payNowModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content payment-detail-modal-content">
            <div class="modal-header payment-detail-modal-header">
                <h5 class="modal-title payment-detail-modal-title" id="payNowModalLabel">Payment Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body payment-detail-modal-body">
                <form id="payNowForm">
                    @csrf
                    <div class="payment-detail-grid">
                        <div class="payment-detail-row">
                            <label class="payment-detail-label">Payment Mode</label>
                            <select name="payment_mode" id="payNowPaymentMode" class="payment-detail-input form-select form-select-sm">
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="deduct_from_salary">Deduct From Salary</option>
                                <option value="online">Online</option>
                            </select>
                            <div class="payment-detail-bank-wrap">
                                <label class="payment-detail-label">Bank Name</label>
                                <input type="text" name="bank_name" id="payNowBankName" class="payment-detail-input form-control form-control-sm" placeholder="Bank Name" autocomplete="off">
                            </div>
                        </div>
                        <div class="payment-detail-row payment-detail-cheque-row" id="payNowChequeRow">
                            <label class="payment-detail-label">Cheque Number</label>
                            <input type="text" name="cheque_number" id="payNowChequeNumber" class="payment-detail-input form-control form-control-sm" placeholder="Cheque Number" autocomplete="off">
                            <label class="payment-detail-label">Cheque Date</label>
                            <input type="text" name="cheque_date" id="payNowChequeDate" class="payment-detail-input form-control form-control-sm" value="{{ now()->format('d-m-Y') }}" placeholder="dd-mm-yyyy" autocomplete="off">
                        </div>
                        <div class="payment-detail-row">
                            <label class="payment-detail-label">Amount</label>
                            <input type="number" name="amount" id="payNowAmount" class="payment-detail-input form-control form-control-sm" step="0.01" min="0" required placeholder="0.00">
                            <label class="payment-detail-label">Payment Date</label>
                            <input type="text" name="payment_date" id="payNowPaymentDate" class="payment-detail-input form-control form-control-sm" value="{{ now()->format('d-m-Y') }}" placeholder="dd-mm-yyyy" autocomplete="off">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer payment-detail-modal-footer">
                <button type="button" class="btn payment-detail-save-btn" id="payNowSaveBtn">
                    <i class="material-symbols-rounded align-middle" style="font-size: 1.1rem;">save</i> Save
                </button>
                <button type="button" class="btn payment-detail-cancel-btn" data-bs-dismiss="modal">
                    <i class="material-symbols-rounded align-middle" style="font-size: 1.1rem;">close</i> Cancel
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Generate Invoice & Payment Modal --}}
<style>
/* Bill Receipt (Payment Details) modal – match reference design */
#paymentDetailsModal .modal-dialog { max-width: 720px; }
.bill-receipt-modal-content { border-radius: 8px; margin: 1rem auto; }
.bill-receipt-modal-body { padding: 1rem 1.5rem 1.5rem; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
.bill-receipt-content { color: #333; }
.bill-receipt-content .receipt-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
.bill-receipt-content .receipt-logo { display: inline-flex; align-items: center; gap: 0.35rem; }
.bill-receipt-content .receipt-logo-icon { width: 20px; height: 20px; background: linear-gradient(135deg, #c00 0%, #a00 50%, #800 100%); clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%); }
.bill-receipt-content .receipt-logo-text { font-size: 1.1rem; font-weight: 700; color: #0a3d6b; letter-spacing: 0.02em; }
.bill-receipt-content .receipt-date { font-size: 0.9rem; color: #555; }
.bill-receipt-content .receipt-center { text-align: center; margin: 1rem 0; padding: 0 0.5rem; }
.bill-receipt-content .receipt-title { font-size: 1.35rem; font-weight: 700; color: #0a3d6b; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 0.25rem; }
.bill-receipt-content .receipt-subtitle { font-size: 1rem; font-weight: 700; color: #c00; text-transform: uppercase; letter-spacing: 0.02em; margin-bottom: 0.5rem; }
.bill-receipt-content .receipt-period { font-size: 0.95rem; font-weight: 600; color: #0a3d6b; }
.bill-receipt-content hr { border: 0; border-top: 1px solid #333; margin: 0.75rem 0; opacity: 0.25; }
.bill-receipt-content .client-row { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; padding: 0.25rem 0; }
.bill-receipt-content .client-row .client-label { font-weight: 700; color: #333; }
.bill-receipt-content .client-row .client-value { font-weight: 500; }
.bill-receipt-content .bill-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; margin: 0.75rem 0; }
.bill-receipt-content .bill-table th, .bill-receipt-content .bill-table td { padding: 0.5rem 0.75rem; text-align: left; border-bottom: 1px solid #e0e0e0; }
.bill-receipt-content .bill-table th { font-weight: 700; color: #333; background: transparent; border-bottom: 1px solid #333; }
.bill-receipt-content .bill-table td { border-bottom: 1px solid #e8e8e8; }
.bill-receipt-content .bill-table .text-end { text-align: right; }
.bill-receipt-content .bill-table th.text-end { text-align: right; }
.bill-receipt-content .receipt-bottom { display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-top: 1rem; }
.bill-receipt-content .payment-summary { text-align: right; min-width: 200px; }
.bill-receipt-content .payment-summary .summary-row { display: flex; justify-content: flex-end; align-items: baseline; gap: 0.5rem; margin-bottom: 0.2rem; }
.bill-receipt-content .payment-summary .summary-label { font-weight: 600; color: #333; }
.bill-receipt-content .payment-summary .summary-value { font-weight: 500; min-width: 3rem; text-align: right; }
.bill-receipt-actions { margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #dee2e6; display: flex; gap: 0.75rem; flex-wrap: wrap; }
.bill-receipt-actions .btn { padding: 0.5rem 1.25rem; font-weight: 600; border-radius: 6px; border: none; font-size: 0.95rem; }
.bill-receipt-actions .btn-receipt-pay, .bill-receipt-actions .btn-receipt-print { background: linear-gradient(180deg, #0a6bb5 0%, #0a3d6b 100%); color: #fff; }
.bill-receipt-actions .btn-receipt-pay:hover, .bill-receipt-actions .btn-receipt-print:hover { background: linear-gradient(180deg, #0a5a9a 0%, #082d52 100%); color: #fff; }
.bill-receipt-actions .btn-receipt-cancel { background: #c00; color: #fff; }
.bill-receipt-actions .btn-receipt-cancel:hover { background: #a00; color: #fff; }

/* Payment Detail modal – match reference (two-column grid, Cheque fields, Save/Cancel style) */
.payment-detail-modal-content { border: 1px solid #333; border-radius: 8px; }
.payment-detail-modal-header { border-bottom: 1px solid #dee2e6; padding: 0.75rem 1rem; }
.payment-detail-modal-title { font-weight: 700; color: #0a3d6b; font-size: 1.1rem; text-transform: capitalize; }
.payment-detail-modal-body { padding: 1rem 1.25rem; }
.payment-detail-grid { display: flex; flex-direction: column; gap: 0.75rem; }
.payment-detail-row { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 0.5rem 0.75rem; align-items: center; }
.payment-detail-row .payment-detail-label { font-weight: 600; color: #333; font-size: 0.9rem; }
.payment-detail-row .payment-detail-input { background: #faf9f6; border: 1px solid #ccc; border-radius: 4px; padding: 0.4rem 0.5rem; font-size: 0.9rem; }
.payment-detail-row .payment-detail-input:focus { background: #fff; border-color: #0a3d6b; outline: none; }
.payment-detail-cheque-row { display: none; }
.payment-detail-modal.payment-mode-cheque .payment-detail-cheque-row { display: grid; }
.payment-detail-bank-wrap { display: none; grid-column: span 2; grid-template-columns: 1fr 1fr; gap: 0.5rem 0.75rem; align-items: center; }
.payment-detail-modal.payment-mode-cheque .payment-detail-bank-wrap { display: grid; }
.payment-detail-modal-footer { border-top: 1px solid #dee2e6; padding: 0.75rem 1rem; gap: 0.5rem; }
.payment-detail-save-btn { background: #198754; color: #fff; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 0.35rem; }
.payment-detail-save-btn:hover { background: #157347; color: #fff; }
.payment-detail-cancel-btn { background: #e9ecef; color: #495057; border: 1px solid #dee2e6; padding: 0.5rem 1rem; border-radius: 6px; font-weight: 600; display: inline-flex; align-items: center; gap: 0.35rem; }
.payment-detail-cancel-btn:hover { background: #dee2e6; color: #212529; }
.payment-detail-cancel-btn i { color: #c00; }

#addProcessMessBillsModal .modal-dialog { max-height: calc(100vh - 2rem); margin: 1rem auto; }
#addProcessMessBillsModal .modal-content { max-height: calc(100vh - 2rem); display: flex; flex-direction: column; border-radius: 0.5rem; }
#addProcessMessBillsModal .modal-header { border-radius: 0.5rem 0.5rem 0 0; }
#addProcessMessBillsModal .modal-body { overflow-y: auto; max-height: calc(100vh - 10rem); }
#addProcessMessBillsModal .modal-footer { border-top: 1px solid var(--bs-border-color); }

/* Print styles */
@media screen {
    .report-header { display: none; }
}
@media print {
    .no-print { display: none !important; }
    .topbar,
    .side-mini-panel,
    aside.side-mini-panel,
    #mainSidebar,
    .sidebar-google-hamburger,
    #mainNavbar,
    header.topbar {
        display: none !important;
    }
    .page-wrapper { margin-left: 0 !important; padding-left: 0 !important; }
    .body-wrapper { margin-left: 0 !important; }
    .report-header {
        display: block !important;
        margin-top: 0;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid #333;
    }
    .report-header h4 { margin-bottom: 8px; color: #000; font-weight: bold; }
    .report-header p { color: #333; font-size: 14px; margin: 4px 0; }
    body { font-size: 12px; }
    .table { font-size: 11px; }
    .table th, .table td { padding: 6px !important; }
    @page { margin: 1cm; size: A4; }
}
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
                    <div class="row g-3 mb-2">
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
                            <label class="form-label small fw-semibold">Client Type</label>
                            <select name="modal_client_type" id="modal_client_type" class="form-select form-select-sm select2">
                                <option value="">All</option>
                                <option value="employee">Employee</option>
                                <option value="ot">OT</option>
                                <option value="course">Course</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Buyer Name</label>
                            <input type="text" name="modal_buyer_name" id="modal_buyer_name" class="form-control form-control-sm"
                                   placeholder="Filter by buyer name...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Invoice Date</label>
                            <input type="text" name="modal_invoice_date" id="modal_invoice_date" class="form-control form-control-sm"
                                   value="{{ now()->format('d-m-Y') }}" placeholder="dd-mm-yyyy" autocomplete="off">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-semibold">Mode of Payment</label>
                            <select name="mode_of_payment" id="modal_mode_of_payment" class="form-select form-select-sm select2">
                                <option value="deduct_from_salary" selected>Deduct From Salary</option>
                                <option value="cash">Cash</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-4">
                        <div class="col-md-12 d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-primary btn-sm" id="modalLoadBillsBtn">
                                <i class="material-symbols-rounded align-middle" style="font-size: 1rem;">search</i> Load Bills
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="modalClearFiltersBtn">
                                <i class="material-symbols-rounded align-middle" style="font-size: 1rem;">filter_list_off</i> Clear Filters
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
                        <select id="modalPerPage" class="form-select form-select-sm select2" style="width: auto;">
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
                                <th class="text-nowrap py-2 text-center">Actions</th>
                                <th class="text-nowrap py-2 text-center">Receipt</th>
                            </tr>
                        </thead>
                        <tbody id="modalBillsTableBody">
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Select date range and click <strong>Load Bills</strong> to load unpaid bills.</td>
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
        var dateFromInput = document.getElementById('date_from');
        var dateToInput = document.getElementById('date_to');
        // Prefer data-default-ymd (Y-m-d from server) so nothing can overwrite before we read it.
        function ymdToDate(ymd) {
            if (!ymd || !/^\d{4}-\d{2}-\d{2}$/.test(String(ymd))) return null;
            var p = String(ymd).split('-');
            return new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
        }
        function dmyToDate(dmy) {
            var m = (dmy || '').match(/^(\d{1,2})-(\d{1,2})-(\d{4})$/);
            if (!m) return null;
            return new Date(parseInt(m[3], 10), parseInt(m[2], 10) - 1, parseInt(m[1], 10));
        }
        var defaultFrom = (dateFromInput && dateFromInput.getAttribute('data-default-ymd')) ? ymdToDate(dateFromInput.getAttribute('data-default-ymd')) : (dateFromInput && dateFromInput.value ? dmyToDate(dateFromInput.value) : null);
        var defaultTo = (dateToInput && dateToInput.getAttribute('data-default-ymd')) ? ymdToDate(dateToInput.getAttribute('data-default-ymd')) : (dateToInput && dateToInput.value ? dmyToDate(dateToInput.value) : null);
        var fpFrom = flatpickr('#date_from', {
            dateFormat: 'd-m-Y',
            allowInput: true,
            defaultDate: defaultFrom
        });
        var fpTo = flatpickr('#date_to', {
            dateFormat: 'd-m-Y',
            allowInput: true,
            defaultDate: defaultTo
        });
        if (defaultFrom && fpFrom) fpFrom.setDate(defaultFrom, false);
        if (defaultTo && fpTo) fpTo.setDate(defaultTo, false);
        flatpickr('#modal_date_from', { dateFormat: 'd-m-Y', allowInput: true });
        flatpickr('#modal_date_to', { dateFormat: 'd-m-Y', allowInput: true });
        flatpickr('#modal_invoice_date', { dateFormat: 'd-m-Y', allowInput: true });
        flatpickr('#payNowPaymentDate', { dateFormat: 'd-m-Y', allowInput: true });
        flatpickr('#payNowChequeDate', { dateFormat: 'd-m-Y', allowInput: true });
    }

    var addModalEl = document.getElementById('addProcessMessBillsModal');
    if (addModalEl && typeof $ !== 'undefined' && $.fn.select2) {
        addModalEl.addEventListener('shown.bs.modal', function() {
            $('#addProcessMessBillsModal .select2').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy');
                $(this).select2({ theme: 'bootstrap-5', width: '100%', dropdownParent: $('#addProcessMessBillsModal') });
            });
        });
    }

    var modalBillsData = [];
    var paymentDetailsBillId = null;
    var paymentDetailsUrl = '{{ route("admin.mess.process-mess-bills-employee.payment-details", ["id" => "__ID__"]) }}';
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
        var ct = document.getElementById('modal_client_type');
        var bn = document.getElementById('modal_buyer_name');
        var dateFrom = (df && df.value) ? toYmd(df.value) : '';
        var dateTo = (dt && dt.value) ? toYmd(dt.value) : '';
        var clientType = (ct && ct.value) ? ct.value : '';
        var buyerName = (bn && bn.value) ? bn.value.trim() : '';
        var url = '{{ route("admin.mess.process-mess-bills-employee.modal-data") }}?date_from=' + encodeURIComponent(dateFrom) + '&date_to=' + encodeURIComponent(dateTo);
        if (clientType) url += '&client_type=' + encodeURIComponent(clientType);
        if (buyerName) url += '&buyer_name=' + encodeURIComponent(buyerName);
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
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-4 text-muted">No unpaid bills found. Adjust date range and click Load Bills.</td></tr>';
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

    function clearModalFilters() {
        // Reset all filter inputs to defaults, then reload bills so table shows unfiltered (default) data
        var defaultDateFrom = '{{ now()->startOfMonth()->format("d-m-Y") }}';
        var defaultDateTo = '{{ now()->endOfMonth()->format("d-m-Y") }}';
        var defaultInvoiceDate = '{{ now()->format("d-m-Y") }}';

        function setDateInput(id, value) {
            var el = document.getElementById(id);
            if (!el) return;
            el.value = value;
            if (el._flatpickr) el._flatpickr.setDate(value, false);
        }
        setDateInput('modal_date_from', defaultDateFrom);
        setDateInput('modal_date_to', defaultDateTo);
        setDateInput('modal_invoice_date', defaultInvoiceDate);

        var ct = document.getElementById('modal_client_type');
        if (ct) ct.value = '';
        var bn = document.getElementById('modal_buyer_name');
        if (bn) bn.value = '';
        var mp = document.getElementById('modal_mode_of_payment');
        if (mp) mp.value = 'deduct_from_salary';
        var ms = document.getElementById('modalSearch');
        if (ms) ms.value = '';

        loadModalBills();
    }

    document.getElementById('addProcessMessBillsModal').addEventListener('show.bs.modal', function() { loadModalBills(); });
    document.getElementById('modalLoadBillsBtn').addEventListener('click', loadModalBills);
    document.getElementById('modalClearFiltersBtn').addEventListener('click', clearModalFilters);
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

    function doGeneratePayment(billId, buyerName, btnEl, paymentPayload) {
        if (!billId) { showToast('Bill ID not found.', 'error'); return; }
        if (btnEl) { btnEl.disabled = true; btnEl.textContent = '…'; }
        var body = paymentPayload && (paymentPayload.amount || paymentPayload.payment_mode || paymentPayload.payment_date)
            ? JSON.stringify(paymentPayload)
            : JSON.stringify({});
        fetch(generateInvoiceBaseUrl + '/' + billId + '/generate-payment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: body
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Payment completed for ' + (buyerName || data.client_name) + '.');
                var payNowModalEl = document.getElementById('payNowModal');
                if (payNowModalEl && bootstrap.Modal.getInstance(payNowModalEl)) bootstrap.Modal.getInstance(payNowModalEl).hide();
                var addModalEl = document.getElementById('addProcessMessBillsModal');
                if (addModalEl && bootstrap.Modal.getInstance(addModalEl)) bootstrap.Modal.getInstance(addModalEl).hide();
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

    function renderPaymentDetailsContent(data) {
        var dateStr = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '-');
        var timeStr = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', hour12: false });
        var rows = (data.items || []).map(function(item) {
            return '<tr><td>' + (item.store_name || '—') + '</td><td>' + (item.item_name || '—') + '</td><td>' + (item.purchase_date || '—') + '</td><td class="text-end">' + (item.price || '0') + '</td><td class="text-end">' + (item.quantity || '0') + '</td><td class="text-end">' + (item.amount || '0') + '</td></tr>';
        }).join('');
        var html = '<div class="receipt-top">' +
            '<div class="receipt-logo"><span class="receipt-logo-icon"></span><span class="receipt-logo-text">Sargam</span></div>' +
            '<span class="receipt-date">Date ' + dateStr + ' ' + timeStr + '</span>' +
            '</div>' +
            '<div class="receipt-center">' +
            '<div class="receipt-title">OFFICER\'S MESS LBSNAA MUSSOORIE</div>' +
            '<div class="receipt-subtitle">MESS BILLS</div>' +
            '<div class="receipt-period">Client Bill From Period ' + (data.date_from || '') + ' To ' + (data.date_to || '') + '</div>' +
            '</div>' +
            '<hr/>' +
            '<div class="client-row">' +
            '<span><span class="client-label">Client Name</span>: <span class="client-value">' + (data.client_name || '—') + '</span></span>' +
            '<span><span class="client-label">Client Type</span>: <span class="client-value">' + (data.client_type || '—') + '</span></span>' +
            '</div>' +
            '<hr/>' +
            '<table class="bill-table"><thead><tr><th>Store Name</th><th>Item Name</th><th>Purchase Date</th><th class="text-end">Price</th><th class="text-end">Quantity</th><th class="text-end">Amount</th></tr></thead><tbody>' + rows + '</tbody></table>' +
            '<div class="receipt-bottom">' +
            '<div></div>' +
            '<div class="payment-summary">' +
            '<div class="summary-row"><span class="summary-label">Paid Amount</span><span class="summary-value">' + (data.paid_amount || '0.0') + '</span></div>' +
            '<div class="summary-row"><span class="summary-label">Total Amount</span><span class="summary-value">' + (data.total_amount || '0.0') + '</span></div>' +
            '<div class="summary-row"><span class="summary-label">Due Amount</span><span class="summary-value">' + (data.due_amount || '0.0') + '</span></div>' +
            '</div>' +
            '</div>';
        return html;
    }

    function openPaymentDetailsModal(billId) {
        paymentDetailsBillId = billId;
        var content = document.getElementById('paymentDetailsContent');
        if (content) content.innerHTML = '<div class="text-center py-4 text-muted">Loading...</div>';
        var url = paymentDetailsUrl.replace('__ID__', billId);
        fetch(url).then(function(r) { return r.json(); })
            .then(function(data) {
                content.innerHTML = renderPaymentDetailsContent(data);
                content.setAttribute('data-due-amount-raw', data.due_amount_raw != null ? data.due_amount_raw : data.due_amount || 0);
                var pdModal = document.getElementById('paymentDetailsModal');
                var addModalEl = document.getElementById('addProcessMessBillsModal');
                var addModalInstance = addModalEl && typeof bootstrap !== 'undefined' ? bootstrap.Modal.getInstance(addModalEl) : null;
                function showPaymentDetailsModal() {
                    if (pdModal && typeof bootstrap !== 'undefined') {
                        var m = bootstrap.Modal.getOrCreateInstance(pdModal);
                        m.show();
                    }
                }
                if (addModalInstance) {
                    addModalEl.addEventListener('hidden.bs.modal', function once() {
                        addModalEl.removeEventListener('hidden.bs.modal', once);
                        showPaymentDetailsModal();
                    });
                    addModalInstance.hide();
                } else {
                    showPaymentDetailsModal();
                }
            })
            .catch(function() {
                if (content) content.innerHTML = '<div class="text-danger py-4 text-center">Failed to load payment details.</div>';
                showToast('Failed to load payment details.', 'error');
            });
    }

    document.getElementById('paymentDetailsPayNowBtn').addEventListener('click', function() {
        var content = document.getElementById('paymentDetailsContent');
        var dueRaw = content && content.getAttribute('data-due-amount-raw');
        var due = dueRaw !== null && dueRaw !== '' ? parseFloat(dueRaw) : 0;
        document.getElementById('payNowAmount').value = isNaN(due) ? '' : due;
        var pdModal = document.getElementById('paymentDetailsModal');
        if (pdModal && bootstrap.Modal.getInstance(pdModal)) bootstrap.Modal.getInstance(pdModal).hide();
        var payNowModal = document.getElementById('payNowModal');
        if (payNowModal && typeof bootstrap !== 'undefined') {
            payNowModal.classList.toggle('payment-mode-cheque', document.getElementById('payNowPaymentMode').value === 'cheque');
            var m = bootstrap.Modal.getOrCreateInstance(payNowModal);
            m.show();
        }
    });

    document.getElementById('paymentDetailsPrintBtn').addEventListener('click', function() {
        if (paymentDetailsBillId) {
            var printUrl = printReceiptBaseUrl.replace('__ID__', paymentDetailsBillId);
            window.open(printUrl, '_blank');
        }
    });

    document.getElementById('payNowPaymentMode').addEventListener('change', function() {
        var modal = document.getElementById('payNowModal');
        if (modal) modal.classList.toggle('payment-mode-cheque', this.value === 'cheque');
    });

    document.getElementById('payNowSaveBtn').addEventListener('click', function() {
        var billId = paymentDetailsBillId;
        if (!billId) { showToast('No bill selected.', 'error'); return; }
        var amountEl = document.getElementById('payNowAmount');
        var modeEl = document.getElementById('payNowPaymentMode');
        var dateEl = document.getElementById('payNowPaymentDate');
        var amount = amountEl && amountEl.value ? amountEl.value : '';
        var paymentMode = modeEl && modeEl.value ? modeEl.value : 'cash';
        var paymentDate = dateEl && dateEl.value ? dateEl.value : '';
        if (!amount) { showToast('Please enter amount.', 'error'); return; }
        var payload = { amount: amount, payment_mode: paymentMode, payment_date: paymentDate };
        if (paymentMode === 'cheque') {
            payload.bank_name = (document.getElementById('payNowBankName') || {}).value || '';
            payload.cheque_number = (document.getElementById('payNowChequeNumber') || {}).value || '';
            payload.cheque_date = (document.getElementById('payNowChequeDate') || {}).value || '';
        }
        var btn = this;
        btn.disabled = true;
        doGeneratePayment(billId, '', btn, payload);
        btn.disabled = false;
    });

    document.addEventListener('mousedown', function(e) {
        var invoiceBtn = e.target.closest('.generate-invoice-btn');
        if (invoiceBtn) {
            e.preventDefault();
            e.stopPropagation();
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
            e.stopPropagation();
            var billId = paymentBtn.getAttribute('data-bill-id');
            openPaymentDetailsModal(billId);
            return;
        }
    }, true);

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
