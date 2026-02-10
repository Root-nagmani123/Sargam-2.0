@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:clipboard-list-bold" class="me-2"></iconify-icon>
            Stock Summary Report
        </h5>
    </div>
    <div class="card-body">
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Items</h6>
                        <h3 class="mb-0">{{ $items->count() }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Stock Value</h6>
                        <h3 class="mb-0">₹{{ number_format($totalValue, 2) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title">Low Stock Items</h6>
                        <h3 class="mb-0">{{ $lowStockCount }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h6 class="card-title">Out of Stock</h6>
                        <h3 class="mb-0">{{ $outOfStockCount }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" action="{{ route('admin.mess.reports.stock-summary') }}" class="mb-4">
            <div class="row g-3">
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
                <div class="col-md-3">
                    <label class="form-label">Stock Status</label>
                    <select name="stock_status" class="form-select">
                        <option value="">All</option>
                        <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Low Stock</option>
                        <option value="out" {{ request('stock_status') == 'out' ? 'selected' : '' }}>Out of Stock</option>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="solar:filter-bold"></iconify-icon> Apply Filters
                </button>
                <a href="{{ route('admin.mess.reports.stock-summary') }}" class="btn btn-secondary">
                    <iconify-icon icon="solar:restart-bold"></iconify-icon> Reset
                </a>
                <button type="button" class="btn btn-success" onclick="window.print()">
                    <iconify-icon icon="solar:printer-bold"></iconify-icon> Print
                </button>
            </div>
        </form>

        <!-- Data Table -->
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>Store</th>
                        <th>Category</th>
                        <th>Sub-Category</th>
                        <th class="text-end">Current Stock</th>
                        <th class="text-end">Min Stock</th>
                        <th>Unit</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Stock Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $stockValue = ($item->current_stock ?? 0) * ($item->unit_price ?? 0);
                            $isLowStock = ($item->current_stock ?? 0) < ($item->minimum_stock ?? 0);
                            $isOutOfStock = ($item->current_stock ?? 0) <= 0;
                        @endphp
                        <tr class="{{ $isOutOfStock ? 'table-danger' : ($isLowStock ? 'table-warning' : '') }}">
                            <td>{{ $item->item_code ?? 'N/A' }}</td>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ $item->store->store_name ?? 'N/A' }}</td>
                            <td>{{ $item->category->category_name ?? 'N/A' }}</td>
                            <td>{{ $item->subcategory->subcategory_name ?? 'N/A' }}</td>
                            <td class="text-end">{{ number_format($item->current_stock ?? 0, 2) }}</td>
                            <td class="text-end">{{ number_format($item->minimum_stock ?? 0, 2) }}</td>
                            <td>{{ $item->unit ?? 'N/A' }}</td>
                            <td class="text-end">₹{{ number_format($item->unit_price ?? 0, 2) }}</td>
                            <td class="text-end">₹{{ number_format($stockValue, 2) }}</td>
                            <td>
                                @if($isOutOfStock)
                                    <span class="badge bg-danger">Out of Stock</span>
                                @elseif($isLowStock)
                                    <span class="badge bg-warning">Low Stock</span>
                                @else
                                    <span class="badge bg-success">In Stock</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">No items found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    @media print {
        .btn, form, .row.mb-4 { display: none !important; }
        .card { border: none !important; }
    }
</style>
@endsection
