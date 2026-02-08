@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <iconify-icon icon="solar:box-bold" class="me-2"></iconify-icon>
            Approved Inbound Transactions
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-3">
            <div class="row g-2">
                <div class="col-md-3">
                    <input type="date" name="from_date" class="form-control form-control-sm" 
                           value="{{ request('from_date') }}" placeholder="From Date">
                </div>
                <div class="col-md-3">
                    <input type="date" name="to_date" class="form-control form-control-sm" 
                           value="{{ request('to_date') }}" placeholder="To Date">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>GRN Number</th>
                        <th>Receipt Date</th>
                        <th>Vendor</th>
                        <th>Store</th>
                        <th>Total Quantity</th>
                        <th>Received By</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->grn_number }}</td>
                            <td>{{ $transaction->receipt_date ? date('d-M-Y', strtotime($transaction->receipt_date)) : 'N/A' }}</td>
                            <td>{{ $transaction->vendor->vendor_name ?? 'N/A' }}</td>
                            <td>{{ $transaction->store->store_name ?? 'N/A' }}</td>
                            <td>{{ $transaction->items->sum('quantity') ?? 0 }}</td>
                            <td>{{ $transaction->receivedBy->name ?? 'N/A' }}</td>
                            <td><span class="badge bg-success">{{ ucfirst($transaction->status ?? 'approved') }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No approved inbound transactions found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $transactions->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
