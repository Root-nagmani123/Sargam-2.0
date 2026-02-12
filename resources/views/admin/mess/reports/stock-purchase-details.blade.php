@extends('admin.layouts.master')
@section('title', 'Stock Purchase Details Report')
@section('setup_content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h4>Stock Purchase Details Report</h4>
    </div>

    <!-- Filters Section -->
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mess.reports.stock-purchase-details') }}">
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
                                    {{ $vendor->name }}
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
                        <i class="ti ti-filter"></i> Apply Filters
                    </button>
                    <a href="{{ route('admin.mess.reports.stock-purchase-details') }}" class="btn btn-secondary">
                        <i class="ti ti-refresh"></i> Reset
                    </a>
                    <button type="button" class="btn btn-success" onclick="window.print()">
                        <i class="ti ti-printer"></i> Print
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Heading (Print Only) -->
    <div class="report-header text-center mb-4">
        <h4 class="fw-bold">Stock Purchase Details</h4>
        @if($selectedVendor)
            <h5 class="text-primary">Vendor Name: {{ $selectedVendor->name }}</h5>
        @endif
    </div>

    <!-- Report Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead style="background-color: #af2910;">
                <tr>
                    <th style="color: #fff; border-color: #af2910; width: 80px;">S. No.</th>
                    <th style="color: #fff; border-color: #af2910;">Item Name</th>
                    <th style="color: #fff; border-color: #af2910; text-align: right;">Quantity</th>
                    <th style="color: #fff; border-color: #af2910; text-align: right;">Purchase (Unit Price)</th>
                    <th style="color: #fff; border-color: #af2910; text-align: right;">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalAmount = 0;
                @endphp
                @forelse($purchaseItems as $index => $item)
                    @php
                        $amount = ($item->quantity ?? 0) * ($item->unit_price ?? 0);
                        $totalAmount += $amount;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item->itemSubcategory->item_name ?? 'N/A' }}</td>
                        <td class="text-end">{{ number_format($item->quantity ?? 0, 2) }}</td>
                        <td class="text-end">₹{{ number_format($item->unit_price ?? 0, 2) }}</td>
                        <td class="text-end">₹{{ number_format($amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No purchase details found</td>
                    </tr>
                @endforelse
                @if($purchaseItems->count() > 0)
                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                        <td colspan="4" class="text-end">Total Amount:</td>
                        <td class="text-end">₹{{ number_format($totalAmount, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>

<style>
    @media print {
        .no-print { 
            display: none !important; 
        }
        .report-header { 
            display: block !important;
            margin-top: 20px;
            margin-bottom: 30px;
        }
        body { 
            font-size: 12px; 
        }
        table { 
            font-size: 11px; 
        }
        th, td { 
            padding: 8px !important; 
        }
    }
    
    @media screen {
        .report-header {
            display: none;
        }
    }
    
    .report-header h4 {
        margin-bottom: 10px;
        color: #000;
    }
    
    .report-header h5 {
        margin-bottom: 20px;
        color: #af2910;
    }
</style>
@endsection
