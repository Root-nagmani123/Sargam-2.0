@extends('admin.layouts.master')
@section('title', 'Item Report')
@section('setup_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h4>Item Report</h4>
    </div>

    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mess.reports.purchase-sale-quantity') }}">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">View</label>
                        <select name="view_type" id="viewType" class="form-select">
                            <option value="item_wise" {{ $viewType === 'item_wise' ? 'selected' : '' }}>Item-wise</option>
                            <option value="subcategory_wise" {{ $viewType === 'subcategory_wise' ? 'selected' : '' }}>Subcategory-wise</option>
                            <option value="category_wise" {{ $viewType === 'category_wise' ? 'selected' : '' }}>Category-wise</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="categoryIdWrap" style="display: {{ $viewType === 'category_wise' ? 'block' : 'none' }};">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->category_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Item</label>
                        <select name="item_id" class="form-select">
                            <option value="">All Items</option>
                            @foreach($allItems as $it)
                                <option value="{{ $it->id }}" {{ ($itemId ?? '') == $it->id ? 'selected' : '' }}>{{ $it->item_name ?? $it->subcategory_name ?? $it->name ?? '—' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter"></i> Apply Filters
                    </button>
                    <a href="{{ route('admin.mess.reports.purchase-sale-quantity') }}" class="btn btn-secondary">
                        <i class="ti ti-refresh"></i> Reset
                    </a>
                    <button type="button" class="btn btn-outline-primary" onclick="window.print()" title="Print or Save as PDF">
                        <i class="ti ti-printer"></i> Print
                    </button>
                    <a href="{{ route('admin.mess.reports.purchase-sale-quantity.excel', request()->query()) }}" class="btn btn-success" title="Export to Excel">
                        <i class="ti ti-file-spreadsheet"></i> Export Excel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="report-header text-center mb-4">
        <h4 class="fw-bold">Item Report</h4>
        <p class="mb-0">From {{ date('d-M-Y', strtotime($fromDate)) }} to {{ date('d-M-Y', strtotime($toDate)) }}</p>
    </div>

    @if($viewType === 'item_wise')
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead style="background-color: #af2910;">
                    <tr>
                        <th style="color: #fff; border-color: #af2910; width: 60px;">S. No.</th>
                        <th style="color: #fff; border-color: #af2910;">Item Name</th>
                        <th style="color: #fff; border-color: #af2910;">Unit</th>
                        <th style="color: #fff; border-color: #af2910; text-align: right;">Total Purchase Qty</th>
                        <th style="color: #fff; border-color: #af2910; text-align: right;">Avg Purchase Price</th>
                        <th style="color: #fff; border-color: #af2910; text-align: right;">Total Sale Qty</th>
                        <th style="color: #fff; border-color: #af2910; text-align: right;">Avg Sale Price</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData as $index => $row)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $row['item_name'] }}</td>
                            <td>{{ $row['unit'] }}</td>
                            <td class="text-end">{{ number_format($row['purchase_qty'], 2) }}</td>
                            <td class="text-end">{{ $row['avg_purchase_price'] !== null ? '₹' . number_format($row['avg_purchase_price'], 2) : '—' }}</td>
                            <td class="text-end">{{ number_format($row['sale_qty'], 2) }}</td>
                            <td class="text-end">{{ $row['avg_sale_price'] !== null ? '₹' . number_format($row['avg_sale_price'], 2) : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No data found for the selected date range</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        @php $groupedData = $groupedData ?? []; @endphp
        @forelse($groupedData as $group)
            <div class="mb-4">
                <h5 class="text-primary mb-2">{{ $group['category_name'] }}</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead style="background-color: #af2910;">
                            <tr>
                                <th style="color: #fff; border-color: #af2910; width: 60px;">S. No.</th>
                                <th style="color: #fff; border-color: #af2910;">Item Name</th>
                                <th style="color: #fff; border-color: #af2910;">Unit</th>
                                <th style="color: #fff; border-color: #af2910; text-align: right;">Total Purchase Qty</th>
                                <th style="color: #fff; border-color: #af2910; text-align: right;">Avg Purchase Price</th>
                                <th style="color: #fff; border-color: #af2910; text-align: right;">Total Sale Qty</th>
                                <th style="color: #fff; border-color: #af2910; text-align: right;">Avg Sale Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group['items'] as $idx => $row)
                                <tr>
                                    <td class="text-center">{{ $idx + 1 }}</td>
                                    <td>{{ $row['item_name'] }}</td>
                                    <td>{{ $row['unit'] }}</td>
                                    <td class="text-end">{{ number_format($row['purchase_qty'], 2) }}</td>
                                    <td class="text-end">{{ isset($row['avg_purchase_price']) && $row['avg_purchase_price'] !== null ? '₹' . number_format($row['avg_purchase_price'], 2) : '—' }}</td>
                                    <td class="text-end">{{ number_format($row['sale_qty'], 2) }}</td>
                                    <td class="text-end">{{ isset($row['avg_sale_price']) && $row['avg_sale_price'] !== null ? '₹' . number_format($row['avg_sale_price'], 2) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="alert alert-info">No data found for the selected filters.</div>
        @endforelse
    @endif
</div>

<style>
    @media print {
        .no-print { display: none !important; }
        .report-header { display: block !important; }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var viewType = document.getElementById('viewType');
    var categoryIdWrap = document.getElementById('categoryIdWrap');
    if (viewType && categoryIdWrap) {
        function toggleCategory() {
            categoryIdWrap.style.display = viewType.value === 'category_wise' ? 'block' : 'none';
        }
        viewType.addEventListener('change', toggleCategory);
    }
});
</script>
@endsection
