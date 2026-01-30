@extends('admin.layouts.master')
@section('title', 'Material Management Bill Report')
@section('setup_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Material Management Bill Report</h4>
        <a href="{{ route('admin.mess.material-management.index') }}" class="btn btn-secondary">Back to List</a>
    </div>
    
    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mess.material-management.bill-report') }}">
                <div class="row">
                    <div class="col-md-3">
                        <label>Store</label>
                        <select name="store" class="form-select">
                            <option value="">All Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ request('store') == $store->id ? 'selected' : '' }}>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label>Payment Status</label>
                        <select name="payment_status" class="form-select">
                            <option value="">All</option>
                            <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Invoice No</label>
                        <input type="text" name="invoice" class="form-control" value="{{ request('invoice') }}" placeholder="Invoice No">
                    </div>
                    <div class="col-md-1">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Total Issues</h6>
                    <h3>{{ $kitchenIssues->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Paid</h6>
                    <h3>{{ $kitchenIssues->where('payment_type', 1)->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Unpaid</h6>
                    <h3>{{ $kitchenIssues->where('payment_type', 0)->count() }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Total Amount</h6>
                    <h3>₹{{ number_format($kitchenIssues->sum(function($issue) { return $issue->unit_price * $issue->quantity; }), 2) }}</h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Report Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Issue No</th>
                    <th>Date</th>
                    <th>Store</th>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                    <th>Payment Status</th>
                    <th>Invoice</th>
                </tr>
            </thead>
            <tbody>
            @forelse($kitchenIssues as $issue)
                <tr>
                    <td>{{ $issue->pk }}</td>
                    <td>{{ $issue->request_date ? \Carbon\Carbon::parse($issue->request_date)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $issue->storeMaster->store_name ?? 'N/A' }}</td>
                    <td>{{ $issue->itemMaster->item_name ?? 'N/A' }}</td>
                    <td>{{ $issue->quantity }}</td>
                    <td>₹{{ number_format($issue->unit_price, 2) }}</td>
                    <td>₹{{ number_format($issue->unit_price * $issue->quantity, 2) }}</td>
                    <td>
                        @if($issue->payment_type == 1)
                            <span class="badge bg-success">Paid</span>
                        @else
                            <span class="badge bg-danger">Unpaid</span>
                        @endif
                    </td>
                    <td>
                        @if($issue->paymentDetails->count() > 0)
                            {{ $issue->paymentDetails->first()->invoice_no ?? '-' }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No records found</td>
                </tr>
            @endforelse
            </tbody>
            @if($kitchenIssues->count() > 0)
            <tfoot class="table-secondary">
                <tr>
                    <th colspan="6" class="text-end">Grand Total:</th>
                    <th>₹{{ number_format($kitchenIssues->sum(function($issue) { return $issue->unit_price * $issue->quantity; }), 2) }}</th>
                    <th colspan="2"></th>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    
    @if($kitchenIssues->count() > 0)
    <div class="text-end mt-3">
        <button onclick="window.print()" class="btn btn-success">Print Report</button>
    </div>
    @endif
</div>

<style>
@media print {
    .btn, .card-body form, nav { display: none !important; }
    .table { font-size: 12px; }
}
</style>
@endsection
