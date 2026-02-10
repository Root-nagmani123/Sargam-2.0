@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:document-text-bold" class="me-2"></iconify-icon>
            Category-wise Print Slip
        </h5>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" action="{{ route('admin.mess.reports.category-wise-print-slip') }}" class="mb-4 no-print">
            <div class="row g-3">
                <div class="col-md-4">
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
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($allCategories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->category_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Show Stock</label>
                    <select name="show_stock" class="form-select">
                        <option value="">All Items</option>
                        <option value="available" {{ request('show_stock') == 'available' ? 'selected' : '' }}>Available Stock Only</option>
                        <option value="low" {{ request('show_stock') == 'low' ? 'selected' : '' }}>Low Stock Only</option>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <iconify-icon icon="solar:filter-bold"></iconify-icon> Apply Filters
                </button>
                <a href="{{ route('admin.mess.reports.category-wise-print-slip') }}" class="btn btn-secondary">
                    <iconify-icon icon="solar:restart-bold"></iconify-icon> Reset
                </a>
                <button type="button" class="btn btn-success" onclick="window.print()">
                    <iconify-icon icon="solar:printer-bold"></iconify-icon> Print
                </button>
            </div>
        </form>

        <!-- Print Header -->
        <div class="print-header text-center mb-4">
            <h4>Category-wise Material List</h4>
            <p>Generated on: {{ now()->format('d-M-Y h:i A') }}</p>
            @if(request('store_id'))
                <p>Store: {{ $stores->firstWhere('id', request('store_id'))->store_name ?? 'N/A' }}</p>
            @endif
        </div>

        <!-- Category-wise Items -->
        @forelse($categories as $category)
            @if($category->items->count() > 0)
                <div class="category-section mb-4">
                    <div class="category-header bg-primary text-white p-2 mb-2">
                        <h5 class="mb-0">
                            <iconify-icon icon="solar:folder-bold" class="me-2"></iconify-icon>
                            {{ $category->category_name }}
                        </h5>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">S.No</th>
                                    <th width="15%">Item Code</th>
                                    <th width="30%">Item Name</th>
                                    <th width="15%">Sub-Category</th>
                                    <th width="10%" class="text-end">Current Stock</th>
                                    <th width="10%">Unit</th>
                                    <th width="15%" class="text-end">Unit Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($category->items as $index => $item)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $item->item_code ?? 'N/A' }}</td>
                                        <td>{{ $item->item_name }}</td>
                                        <td>{{ $item->subcategory->subcategory_name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ number_format($item->current_stock ?? 0, 2) }}</td>
                                        <td>{{ $item->unit ?? 'N/A' }}</td>
                                        <td class="text-end">₹{{ number_format($item->unit_price ?? 0, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="table-info fw-bold">
                                    <td colspan="4" class="text-end">Total Items:</td>
                                    <td class="text-end">{{ $category->items->count() }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Page break for printing -->
                <div class="page-break"></div>
            @endif
        @empty
            <div class="alert alert-info text-center">
                <iconify-icon icon="solar:info-circle-bold" class="me-2"></iconify-icon>
                No categories found with items
            </div>
        @endforelse

        <!-- Print Footer -->
        <div class="print-footer mt-5">
            <div class="row">
                <div class="col-4 text-start">
                    <p>_____________________</p>
                    <p>Prepared By</p>
                </div>
                <div class="col-4 text-center">
                    <p>_____________________</p>
                    <p>Verified By</p>
                </div>
                <div class="col-4 text-end">
                    <p>_____________________</p>
                    <p>Approved By</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .no-print, .btn, .card-header { display: none !important; }
        .card { border: none !important; box-shadow: none !important; }
        .page-break { page-break-after: always; }
        .print-header { display: block !important; }
        .print-footer { 
            position: fixed; 
            bottom: 0; 
            width: 100%;
            display: block !important;
        }
        body { font-size: 12px; }
        table { font-size: 11px; }
    }
    
    @media screen {
        .page-break { display: none; }
    }
    
    .category-section {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 10px;
        margin-bottom: 20px;
    }
    
    .category-header {
        border-radius: 4px;
    }
</style>
@endsection
