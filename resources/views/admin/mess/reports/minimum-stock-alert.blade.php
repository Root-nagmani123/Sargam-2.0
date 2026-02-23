@extends('admin.layouts.master')
@section('title', 'Minimum Stock Alert - Sargam | Lal Bahadur')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Minimum Stock Alert" />
    <div class="card" style="border-left: 4px solid #af2910;">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">
                <iconify-icon icon="solar:danger-triangle-bold" class="me-2 text-danger"></iconify-icon>
                Minimum Stock Alert
            </h5>
            <span class="badge bg-danger fs-6">{{ $items->total() }} item(s) below minimum stock</span>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form method="GET" action="{{ route('admin.mess.reports.minimum-stock-alert') }}" class="mb-4">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small">Category</label>
                        <select name="category_id" class="form-select form-select-sm">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->category_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if($stores->isNotEmpty())
                    <div class="col-md-3">
                        <label class="form-label small">Store</label>
                        <select name="store_id" class="form-select form-select-sm">
                            <option value="">All Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ request('store_id') == $store->id ? 'selected' : '' }}>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-4">
                        <label class="form-label small">Search</label>
                        <input type="text" name="search" class="form-control form-control-sm"
                               placeholder="Item name or code..."
                               value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-1">
                        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">
                            <iconify-icon icon="solar:magnifer-bold"></iconify-icon> Filter
                        </button>
                        <a href="{{ route('admin.mess.reports.minimum-stock-alert') }}" class="btn btn-sm btn-outline-secondary" title="Reset">Reset</a>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Item Code</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Sub-Category</th>
                            <th>Unit</th>
                            <th class="text-end">Current Stock</th>
                            <th class="text-end">Min Stock</th>
                            <th class="text-end">Shortfall</th>
                            <th class="text-end">Unit Price</th>
                            @if($items->isNotEmpty() && $items->first()->relationLoaded('store') && $items->first()->store_id)
                            <th>Store</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            @php
                                $shortfall = max(0, ($item->minimum_stock ?? 0) - ($item->current_stock ?? 0));
                            @endphp
                            <tr class="table-warning">
                                <td>{{ $item->item_code ?? 'N/A' }}</td>
                                <td>{{ $item->item_name }}</td>
                                <td>{{ $item->category->category_name ?? 'N/A' }}</td>
                                <td>{{ $item->subcategory->subcategory_name ?? $item->subcategory->item_name ?? 'N/A' }}</td>
                                <td>{{ $item->unit_of_measurement ?? $item->unit ?? 'N/A' }}</td>
                                <td class="text-end">
                                    <span class="badge bg-danger">{{ number_format($item->current_stock ?? 0, 2) }}</span>
                                </td>
                                <td class="text-end">{{ number_format($item->minimum_stock ?? 0, 2) }}</td>
                                <td class="text-end fw-bold text-danger">−{{ number_format($shortfall, 2) }}</td>
                                <td class="text-end">₹{{ number_format($item->unit_price ?? 0, 2) }}</td>
                                @if($item->relationLoaded('store') && isset($item->store_id))
                                <td>{{ $item->store->store_name ?? '—' }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ ($items->isNotEmpty() && $items->first()->relationLoaded('store') && $items->first()->store_id) ? 10 : 9 }}" class="text-center text-muted py-5">
                                    <iconify-icon icon="solar:check-circle-bold" style="font-size: 48px;" class="text-success"></iconify-icon>
                                    <p class="mt-2 mb-0">No items below minimum stock</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($items->hasPages())
            <div class="d-flex justify-content-center mt-3">
                {{ $items->withQueryString()->links() }}
            </div>
            @endif

            <div class="mt-3 no-print">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                    <iconify-icon icon="solar:printer-bold" class="me-1"></iconify-icon>
                    Print
                </button>
            </div>
        </div>
    </div>
</div>
<style>
@media print {
    .no-print, .card-header .badge, form, nav, .btn { display: none !important; }
    .table { font-size: 12px; }
}
</style>
@endsection
