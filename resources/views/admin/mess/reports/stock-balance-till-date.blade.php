@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:chart-2-bold" class="me-2"></iconify-icon>
            Stock Balance as of Till Date
        </h5>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.mess.reports.stock-balance-till-date') }}" class="mb-4 no-print">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Till Date <span class="text-danger">*</span></label>
                    <input type="date" name="till_date" class="form-control" value="{{ request('till_date', $tillDate) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Store</label>
                    <select name="store_id" class="form-select">
                        <option value="">All Stores</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                {{ $store->store_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->category_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sub-Category</label>
                    <select name="subcategory_id" class="form-select">
                        <option value="">All Sub-Categories</option>
                        @foreach($subcategories as $subcategory)
                            <option value="{{ $subcategory->id }}" {{ request('subcategory_id') == $subcategory->id ? 'selected' : '' }}>
                                {{ $subcategory->subcategory_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="solar:filter-bold"></iconify-icon> Apply Filters
                </button>
                <a href="{{ route('admin.mess.reports.stock-balance-till-date') }}" class="btn btn-secondary">
                    <iconify-icon icon="solar:restart-bold"></iconify-icon> Reset
                </a>
                <button type="button" class="btn btn-success" onclick="window.print()">
                    <iconify-icon icon="solar:printer-bold"></iconify-icon> Print
                </button>
            </div>
        </form>

        <!-- Print Header -->
        <div class="print-header text-center mb-4">
            <h4>Stock Balance Report</h4>
            <p>As on: {{ date('d-M-Y', strtotime($tillDate)) }}</p>
            @if(request('store_id'))
                <p>Store: {{ $stores->firstWhere('id', request('store_id'))->store_name ?? 'N/A' }}</p>
            @endif
        </div>

        <!-- Summary Card -->
        <div class="row mb-4 no-print">
            <div class="col-md-4 offset-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total Closing Stock Value</h6>
                        <h3 class="mb-0">₹{{ number_format($totalClosingValue, 2) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2">S.No</th>
                        <th rowspan="2">Item Code</th>
                        <th rowspan="2">Item Name</th>
                        <th rowspan="2">Store</th>
                        <th rowspan="2">Category</th>
                        <th rowspan="2">Unit</th>
                        <th colspan="4" class="text-center">Stock Movement</th>
                        <th rowspan="2" class="text-end">Unit Price</th>
                        <th rowspan="2" class="text-end">Closing Value</th>
                    </tr>
                    <tr>
                        <th class="text-end">Opening Balance</th>
                        <th class="text-end">Purchased</th>
                        <th class="text-end">Issued</th>
                        <th class="text-end">Closing Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalOpeningQty = 0;
                        $totalPurchasedQty = 0;
                        $totalIssuedQty = 0;
                        $totalClosingQty = 0;
                        $grandTotalValue = 0;
                    @endphp
                    @forelse($itemsWithBalance as $index => $item)
                        @php
                            $closingValue = ($item->closing_balance ?? 0) * ($item->unit_price ?? 0);
                            $totalOpeningQty += ($item->opening_balance ?? 0);
                            $totalPurchasedQty += ($item->total_purchased ?? 0);
                            $totalIssuedQty += ($item->total_issued ?? 0);
                            $totalClosingQty += ($item->closing_balance ?? 0);
                            $grandTotalValue += $closingValue;
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->item_code ?? 'N/A' }}</td>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ $item->store->store_name ?? 'N/A' }}</td>
                            <td>{{ $item->category->category_name ?? 'N/A' }}</td>
                            <td>{{ $item->unit ?? 'N/A' }}</td>
                            <td class="text-end">{{ number_format($item->opening_balance ?? 0, 2) }}</td>
                            <td class="text-end">{{ number_format($item->total_purchased ?? 0, 2) }}</td>
                            <td class="text-end">{{ number_format($item->total_issued ?? 0, 2) }}</td>
                            <td class="text-end fw-bold">{{ number_format($item->closing_balance ?? 0, 2) }}</td>
                            <td class="text-end">₹{{ number_format($item->unit_price ?? 0, 2) }}</td>
                            <td class="text-end">₹{{ number_format($closingValue, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">No stock data found</td>
                        </tr>
                    @endforelse
                    @if($itemsWithBalance->count() > 0)
                        <tr class="table-info fw-bold">
                            <td colspan="6" class="text-end">Grand Total:</td>
                            <td class="text-end">{{ number_format($totalOpeningQty, 2) }}</td>
                            <td class="text-end">{{ number_format($totalPurchasedQty, 2) }}</td>
                            <td class="text-end">{{ number_format($totalIssuedQty, 2) }}</td>
                            <td class="text-end">{{ number_format($totalClosingQty, 2) }}</td>
                            <td></td>
                            <td class="text-end">₹{{ number_format($grandTotalValue, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Print Footer -->
        <div class="print-footer mt-5">
            <div class="row">
                <div class="col-4 text-start">
                    <p>_____________________</p>
                    <p>Prepared By</p>
                    <p>Date: _______________</p>
                </div>
                <div class="col-4 text-center">
                    <p>_____________________</p>
                    <p>Verified By</p>
                    <p>Date: _______________</p>
                </div>
                <div class="col-4 text-end">
                    <p>_____________________</p>
                    <p>Approved By</p>
                    <p>Date: _______________</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print, .btn, .card-header { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .print-header { display: block !important; }
        .print-footer { 
            position: fixed; 
            bottom: 20px; 
            width: 100%;
            display: block !important;
        }
        body { font-size: 11px; }
        table { font-size: 10px; }
        th, td { padding: 4px !important; }
    }
    
    .table-bordered th,
    .table-bordered td {
        border: 1px solid #dee2e6;
    }
</style>
@endsection
