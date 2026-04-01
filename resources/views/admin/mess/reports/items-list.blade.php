@extends('admin.layouts.master')

@section('title', 'Items List Report')

@section('setup_content')
@php
    use App\Models\Mess\ItemSubcategory;
@endphp
<div class="card items-list-report" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:clipboard-list-bold" class="me-2"></iconify-icon>
            Items List Report
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.mess.reports.items-list') }}" class="mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">Till date</label>
                    <input type="date" name="till_date" class="form-control form-control-sm" value="{{ $tillDate ?? request('till_date', now()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-0">Categories</label>
                    <select name="category_id[]" class="form-select form-select-sm choices-select" data-placeholder="All categories" multiple>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(in_array((int) $category->id, $categoryIds ?? [], true))>
                                {{ $category->category_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small text-muted mb-0">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Item name or code..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
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
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Sub-Category</th>
                        <th>Unit</th>
                        <th>Current Stock</th>
                        <th>Min Stock</th>
                        <th>Unit Price</th>
                        <th>Stock Value</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php
                            $isActive = ($item->status === ItemSubcategory::STATUS_ACTIVE);
                        @endphp
                        <tr class="{{ $item->current_stock < $item->minimum_stock ? 'table-warning' : '' }}">
                            <td>{{ $item->item_code ?? 'N/A' }}</td>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ $item->category->category_name ?? 'N/A' }}</td>
                            <td class="text-muted">—</td>
                            <td>{{ $item->unit_measurement ?? 'N/A' }}</td>
                            <td>
                                <span class="badge {{ $item->current_stock < $item->minimum_stock ? 'bg-danger' : 'bg-success' }}">
                                    {{ number_format($item->current_stock ?? 0, 2) }}
                                </span>
                            </td>
                            <td>{{ number_format($item->minimum_stock ?? 0, 2) }}</td>
                            <td>₹{{ number_format($item->unit_price ?? 0, 2) }}</td>
                            <td>₹{{ number_format(($item->current_stock ?? 0) * ($item->unit_price ?? 0), 2) }}</td>
                            <td>
                                @if($isActive)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <iconify-icon icon="solar:clipboard-list-bold" style="font-size: 48px;"></iconify-icon>
                                <p class="mt-2">No items found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($items->count() > 0)
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="8" class="text-end">Total Stock Value (this page):</th>
                            <th colspan="2">₹{{ number_format($items->sum(function($item) { return ($item->current_stock ?? 0) * ($item->unit_price ?? 0); }), 2) }}</th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Items</h6>
                        <h3 class="mb-0">{{ $items->total() }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Active (this page)</h6>
                        <h3 class="mb-0">{{ $items->filter(fn ($i) => $i->status === ItemSubcategory::STATUS_ACTIVE)->count() }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title">Low Stock (this page)</h6>
                        <h3 class="mb-0">{{ $items->filter(function($item) { return $item->current_stock < $item->minimum_stock; })->count() }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Page value</h6>
                        <h4 class="mb-0">₹{{ number_format($items->sum(function($item) { return ($item->current_stock ?? 0) * ($item->unit_price ?? 0); }), 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $items->withQueryString()->links() }}
        </div>

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

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.TomSelect === 'undefined') return;
    document.querySelectorAll('.items-list-report select.choices-select').forEach(function (el) {
        if (el.tomselect) return;
        var placeholder = el.getAttribute('data-placeholder') || 'Select';
        new TomSelect(el, {
            create: false,
            allowEmptyOption: true,
            placeholder: placeholder,
            plugins: ['remove_button', 'dropdown_input'],
            sortField: { field: 'text', direction: 'asc' }
        });
    });
});
</script>
@endsection
