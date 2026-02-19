@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:document-text-bold" class="me-2"></iconify-icon>
            Mess Invoice Report
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Invoice Number</th>
                        <th>Invoice Date</th>
                        <th>Vendor</th>
                        <th>Store</th>
                        <th>Total Amount</th>
                        <th>Paid Amount</th>
                        <th>Balance</th>
                        <th>Payment Status</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->invoice_date ? date('d-M-Y', strtotime($invoice->invoice_date)) : 'N/A' }}</td>
                            <td>{{ $invoice->vendor->vendor_name ?? 'N/A' }}</td>
                            <td>{{ $invoice->store->store_name ?? 'N/A' }}</td>
                            <td>₹{{ number_format($invoice->total_amount ?? 0, 2) }}</td>
                            <td>₹{{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                            <td>₹{{ number_format(($invoice->total_amount ?? 0) - ($invoice->paid_amount ?? 0), 2) }}</td>
                            <td>
                                <span class="badge {{ $invoice->payment_status == 'paid' ? 'bg-success' : 'bg-warning' }}">
                                    {{ ucfirst($invoice->payment_status ?? 'pending') }}
                                </span>
                            </td>
                            <td>{{ $invoice->due_date ? date('d-M-Y', strtotime($invoice->due_date)) : 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No invoices found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $invoices->links() }}
        </div>
    </div>
</div>
@endsection
