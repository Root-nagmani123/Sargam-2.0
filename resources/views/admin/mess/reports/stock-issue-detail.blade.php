@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:box-minimalistic-bold" class="me-2"></iconify-icon>
            Stock Issue Details (Kitchen Issues)
        </h5>
    </div>
    <div class="card-body">
        <!-- Date Filters -->
        <form method="GET" action="{{ route('admin.mess.reports.stock-issue-detail') }}" class="mb-3">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" 
                           value="{{ request('from_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" 
                           value="{{ request('to_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <iconify-icon icon="solar:magnifer-bold"></iconify-icon> Filter
                    </button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Issue Number</th>
                        <th>Issue Date</th>
                        <th>Store</th>
                        <th>Issued To</th>
                        <th>Total Items</th>
                        <th>Total Quantity</th>
                        <th>Total Amount</th>
                        <th>Payment Status</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($issues as $issue)
                        <tr>
                            <td>{{ $issue->issue_number ?? 'N/A' }}</td>
                            <td>{{ $issue->issue_date ? date('d-M-Y', strtotime($issue->issue_date)) : 'N/A' }}</td>
                            <td>{{ $issue->store->store_name ?? 'N/A' }}</td>
                            <td>{{ $issue->user->name ?? 'N/A' }}</td>
                            <td>{{ $issue->items->count() }}</td>
                            <td>{{ number_format($issue->items->sum('quantity') ?? 0, 2) }}</td>
                            <td>₹{{ number_format($issue->total_amount ?? 0, 2) }}</td>
                            <td>
                                <span class="badge {{ ($issue->paymentDetails->payment_status ?? 'pending') == 'paid' ? 'bg-success' : 'bg-warning' }}">
                                    {{ ucfirst($issue->paymentDetails->payment_status ?? 'pending') }}
                                </span>
                            </td>
                            <td>
                                @if($issue->approval && $issue->approval->is_approved)
                                    <span class="badge bg-success">Approved</span>
                                @elseif($issue->approval && !$issue->approval->is_approved)
                                    <span class="badge bg-danger">Rejected</span>
                                @else
                                    <span class="badge bg-info">Pending</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <iconify-icon icon="solar:box-minimalistic-bold" style="font-size: 48px;"></iconify-icon>
                                <p class="mt-2">No stock issues found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($issues->count() > 0)
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="6" class="text-end">Total Amount (Page):</th>
                            <th colspan="3">₹{{ number_format($issues->sum('total_amount'), 2) }}</th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <!-- Summary Cards -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Issues</h6>
                        <h3 class="mb-0">{{ $issues->total() }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Approved Issues</h6>
                        <h3 class="mb-0">{{ $issues->filter(function($i) { return $i->approval && $i->approval->is_approved; })->count() }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title">Pending Payment</h6>
                        <h3 class="mb-0">{{ $issues->filter(function($i) { return ($i->paymentDetails->payment_status ?? 'pending') != 'paid'; })->count() }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Value (Page)</h6>
                        <h4 class="mb-0">₹{{ number_format($issues->sum('total_amount'), 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-3">
            {{ $issues->withQueryString()->links() }}
        </div>

        <!-- Export Button -->
        <div class="text-center mt-3">
            <button class="btn btn-success" onclick="window.print()">
                <iconify-icon icon="solar:printer-bold" class="me-1"></iconify-icon>
                Print Report
            </button>
        </div>
    </div>
</div>

<style>
@media print {
    .card-header, .btn, form, nav { display: none !important; }
    .table { font-size: 12px; }
}
</style>
@endsection
