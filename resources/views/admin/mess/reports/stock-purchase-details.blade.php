@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header no-print">
        <h5 class="mb-0">
            <iconify-icon icon="solar:box-bold" class="me-2"></iconify-icon>
            Stock Purchase Details Report
        </h5>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.mess.reports.stock-purchase-details') }}" class="mb-4 no-print">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Select Vendor Name</label>
                    <select name="vendor_id" class="form-select">
                        <option value="">All Vendors</option>
                        @foreach($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->vendor_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Select Store Name (Main Store)</label>
                    <select name="store_id" class="form-select">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                {{ $store->store_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="solar:filter-bold"></iconify-icon> Apply Filters
                </button>
                <a href="{{ route('admin.mess.reports.stock-purchase-details') }}" class="btn btn-secondary">
                    <iconify-icon icon="solar:restart-bold"></iconify-icon> Reset
                </a>
                <button type="button" class="btn btn-success" onclick="window.print()">
                    <iconify-icon icon="solar:printer-bold"></iconify-icon> Print
                </button>
            </div>
        </form>

        <!-- Report Heading -->
        <div class="report-header text-center mb-4">
            <h4 class="fw-bold">Stock Purchase Details</h4>
            @if($selectedVendor)
                <h5 class="text-primary">Vendor Name: {{ $selectedVendor->vendor_name }}</h5>
            @endif
        </div>

        <!-- Report Table -->
        <div class="table-responsive">
            <table class="table table-bordered table-sm">
                <thead class="table-light">
                    <tr>
                        <th width="8%">S. No.</th>
                        <th width="40%">Item Name</th>
                        <th width="15%" class="text-end">Quantity</th>
                        <th width="17%" class="text-end">Purchase (Unit Price)</th>
                        <th width="20%" class="text-end">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalAmount = 0;
                    @endphp
                    @forelse($purchaseItems as $index => $item)
                        @php
                            $amount = ($item->quantity ?? 0) * ($item->rate ?? 0);
                            $totalAmount += $amount;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $item->inventory->item_name ?? 'N/A' }}</td>
                            <td class="text-end">{{ number_format($item->quantity ?? 0, 2) }}</td>
                            <td class="text-end">₹{{ number_format($item->rate ?? 0, 2) }}</td>
                            <td class="text-end">₹{{ number_format($amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No purchase details found</td>
                        </tr>
                    @endforelse
                    @if($purchaseItems->count() > 0)
                        <tr class="table-info fw-bold">
                            <td colspan="4" class="text-end">Total Amount:</td>
                            <td class="text-end">₹{{ number_format($totalAmount, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .report-header { margin-top: 20px; }
        body { font-size: 12px; }
        table { font-size: 11px; }
        th, td { padding: 8px !important; }
    }
    
    .report-header h4 {
        margin-bottom: 10px;
        color: #004a93;
    }
    
    .report-header h5 {
        margin-bottom: 20px;
    }
    
    .table-bordered th,
    .table-bordered td {
        border: 1px solid #dee2e6;
    }
</style>
@endsection
