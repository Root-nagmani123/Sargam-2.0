@extends('admin.layouts.master')
@section('title', 'Stock Balance as of Till Date')
@section('setup_content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h4>Stock Balance as of Till Date</h4>
    </div>

    <!-- Filters Section -->
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mess.reports.stock-balance-till-date') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Till Date</label>
                        <input type="date" name="till_date" class="form-control" value="{{ $tillDate }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Select Store Name</label>
                        <select name="store_id" class="form-select">
                            <option value="">All Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ $storeId == $store->id ? 'selected' : '' }}>
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
                    <a href="{{ route('admin.mess.reports.stock-balance-till-date') }}" class="btn btn-secondary">
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
        <h4 class="fw-bold">Stock Balance as of Till Date</h4>
        @if($selectedStoreName)
            <h5 class="text-primary">Store Name: {{ $selectedStoreName }}</h5>
        @endif
        <p class="mb-0">As on: {{ date('d-M-Y', strtotime($tillDate)) }}</p>
    </div>

    <!-- Report Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead style="background-color: #af2910;">
                <tr>
                    <th style="color: #fff; border-color: #af2910; width: 80px;">S. No.</th>
                    <th style="color: #fff; border-color: #af2910;">Item Name</th>
                    <th style="color: #fff; border-color: #af2910; text-align: right;">Remaining Quantity</th>
                    <th style="color: #fff; border-color: #af2910; text-align: right;">Rate</th>
                    <th style="color: #fff; border-color: #af2910; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalAmount = 0;
                @endphp
                @forelse($reportData as $index => $item)
                    @php
                        $totalAmount += $item['amount'];
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item['item_name'] }}</td>
                        <td class="text-end">{{ number_format($item['remaining_qty'], 2) }} {{ $item['unit'] }}</td>
                        <td class="text-end">₹{{ number_format($item['rate'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No stock balance found</td>
                    </tr>
                @endforelse
                @if(count($reportData) > 0)
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
