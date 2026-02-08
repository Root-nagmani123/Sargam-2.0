@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:danger-triangle-bold" class="me-2"></iconify-icon>
            Payment Overdue Report
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
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($overdueInvoices as $invoice)
                        @php
                            $daysOverdue = now()->diffInDays($invoice->due_date);
                        @endphp
                        <tr class="table-danger">
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->invoice_date ? date('d-M-Y', strtotime($invoice->invoice_date)) : 'N/A' }}</td>
                            <td>{{ $invoice->vendor->vendor_name ?? 'N/A' }}</td>
                            <td>{{ $invoice->store->store_name ?? 'N/A' }}</td>
                            <td>₹{{ number_format($invoice->total_amount ?? 0, 2) }}</td>
                            <td>₹{{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                            <td>₹{{ number_format(($invoice->total_amount ?? 0) - ($invoice->paid_amount ?? 0), 2) }}</td>
                            <td>{{ $invoice->due_date ? date('d-M-Y', strtotime($invoice->due_date)) : 'N/A' }}</td>
                            <td><span class="badge bg-danger">{{ $daysOverdue }} days</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <iconify-icon icon="solar:check-circle-bold" style="font-size: 48px; color: green;"></iconify-icon>
                                <p class="mt-2">No overdue payments</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $overdueInvoices->links() }}
        </div>
    </div>
</div>
@endsection
