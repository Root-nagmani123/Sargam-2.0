@extends('admin.layouts.master')

@section('setup_content')
<div class="card" >
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:document-text-bold" class="me-2"></iconify-icon>
            Mess Invoice Report
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width:4rem;">S. No.</th>
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
                    @forelse($invoices as $index => $invoice)
                        <tr>
                            <td class="text-center text-muted">@include('admin.mess.reports.partials.report-serial-number', ['paginator' => $invoices, 'index' => $index])</td>
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
                            <td colspan="10" class="text-center text-muted py-4">No invoices found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $invoices->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
