@extends('admin.layouts.master')

@section('setup_content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">Process Mess Bills Employee</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="{{ route('admin.dashboard') }}">Home</a></li>
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="#">Mess Management</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Process Mess Bills Employee</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mess.process-bills.employee-bills') }}" id="filterForm">
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Date From <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="{{ $startDate }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="end_date" class="form-label">Date To <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="{{ $endDate }}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="payment_mode" class="form-label">Mode Of Payment <span class="text-danger">*</span></label>
                            <select class="form-select" id="payment_mode" name="payment_mode" required>
                                <option value="salary_deduction" {{ $paymentMode == 'salary_deduction' ? 'selected' : '' }}>Deduction From Salary</option>
                                <option value="cash" {{ $paymentMode == 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="online" {{ $paymentMode == 'online' ? 'selected' : '' }}>Online</option>
                                <option value="card" {{ $paymentMode == 'card' ? 'selected' : '' }}>Card</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="action" class="form-label">Action <span class="text-danger">*</span></label>
                            <select class="form-select" id="action" name="action" required>
                                <option value="view" {{ $action == 'view' ? 'selected' : '' }}>View</option>
                                <option value="generate" {{ $action == 'generate' ? 'selected' : '' }}>Generate Invoice</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="invoice_date" class="form-label">Invoice Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="invoice_date" name="invoice_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mb-3">
                            <i class="ti ti-search"></i> Show
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Card -->
    <div class="card mt-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title">Employee Bills</h5>
                @if($action == 'generate' && $bills->count() > 0)
                    <button type="button" class="btn btn-success" onclick="generateBulkInvoice()">
                        <i class="ti ti-file-invoice"></i> Generate Bulk Invoice
                    </button>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered" id="billsTable">
                    <thead class="table-primary">
                        <tr>
                            @if($action == 'generate')
                            <th width="50">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            @endif
                            <th>S. NO.</th>
                            <th>BUYER NAME</th>
                            <th>INVOICE NO.</th>
                            <th>PAYMENT TYPE</th>
                            <th>TOTAL</th>
                            <th>PAID AMOUNT</th>
                            <th>ACTION</th>
                            <th>PRINT RECEIPT</th>
                            <th>BILL NO.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bills as $index => $bill)
                        <tr>
                            @if($action == 'generate')
                            <td>
                                <input type="checkbox" name="selected_bills[]" value="{{ $bill->buyer_id }}" class="form-check-input bill-checkbox">
                            </td>
                            @endif
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $bill->buyer_name }}</td>
                            <td>{{ $bill->invoice_no }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $bill->payment_type)) }}</td>
                            <td>{{ number_format($bill->total, 2) }}</td>
                            <td>{{ number_format($bill->paid_amount, 2) }}</td>
                            <td>
                                @if($bill->invoice_no != 'N/A')
                                    <a href="#" class="btn btn-sm btn-info" onclick="generateInvoice('{{ $bill->buyer_id }}')">
                                        <i class="ti ti-file-invoice"></i> Generate Invoice
                                    </a>
                                @else
                                    <span class="badge bg-secondary">No Invoice</span>
                                @endif
                            </td>
                            <td>
                                @if($bill->invoice_no != 'N/A')
                                    <a href="#" class="btn btn-sm btn-primary" onclick="printReceipt('{{ $bill->invoice_no }}')">
                                        <i class="ti ti-printer"></i> Print
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $bill->bill_numbers }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $action == 'generate' ? '10' : '9' }}" class="text-center">No bills found for the selected criteria.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($bills->count() > 0)
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="{{ $action == 'generate' ? '5' : '4' }}" class="text-end">Total Amount</td>
                            <td>{{ number_format($bills->sum('total'), 2) }}</td>
                            <td colspan="{{ $action == 'generate' ? '4' : '3' }}">0.0</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

            <div class="mt-3">
                <p class="text-muted small">Showing {{ $bills->count() }} of {{ $bills->count() }} entries</p>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for bulk invoice generation -->
<form id="bulkInvoiceForm" method="POST" action="{{ route('admin.mess.process-bills.generate-bulk') }}" style="display: none;">
    @csrf
    <input type="hidden" name="start_date" value="{{ $startDate }}">
    <input type="hidden" name="end_date" value="{{ $endDate }}">
    <input type="hidden" name="payment_mode" value="{{ $paymentMode }}">
    <input type="hidden" name="invoice_date" id="bulk_invoice_date" value="{{ date('Y-m-d') }}">
    <div id="selectedBillsContainer"></div>
</form>

@endsection

@push('scripts')
<script>
    // Select All functionality
    document.getElementById('selectAll')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.bill-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // Generate bulk invoice
    function generateBulkInvoice() {
        const checkedBoxes = document.querySelectorAll('.bill-checkbox:checked');
        
        if (checkedBoxes.length === 0) {
            alert('Please select at least one bill to generate invoice.');
            return;
        }

        if (!confirm('Are you sure you want to generate bulk invoices for the selected employees?')) {
            return;
        }

        // Get invoice date from the form
        const invoiceDate = document.getElementById('invoice_date').value;
        document.getElementById('bulk_invoice_date').value = invoiceDate;

        // Clear previous selections
        document.getElementById('selectedBillsContainer').innerHTML = '';

        // Add selected bills to the form
        checkedBoxes.forEach((checkbox, index) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_bills[]';
            input.value = checkbox.value;
            document.getElementById('selectedBillsContainer').appendChild(input);
        });

        // Submit the form
        document.getElementById('bulkInvoiceForm').submit();
    }

    // Generate single invoice
    function generateInvoice(buyerId) {
        // This would open a modal or redirect to generate single invoice
        alert('Generate invoice for buyer ID: ' + buyerId);
    }

    // Print receipt
    function printReceipt(invoiceNo) {
        // This would open a print dialog or PDF
        alert('Print receipt for invoice: ' + invoiceNo);
    }
</script>
@endpush
