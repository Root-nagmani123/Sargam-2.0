@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:box-bold" class="me-2"></iconify-icon>
            Stock Purchase Details
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Unit Price</th>
                        <th>Total Value</th>
                        <th>Last Purchase Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->item_code ?? 'N/A' }}</td>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ $item->category->category_name ?? 'N/A' }}</td>
                            <td>{{ number_format($item->current_stock ?? 0, 2) }}</td>
                            <td>₹{{ number_format($item->unit_price ?? 0, 2) }}</td>
                            <td>₹{{ number_format(($item->current_stock ?? 0) * ($item->unit_price ?? 0), 2) }}</td>
                            <td>{{ $item->last_purchase_date ? date('d-M-Y', strtotime($item->last_purchase_date)) : 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No stock purchase details found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
