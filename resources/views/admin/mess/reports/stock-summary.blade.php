@extends('admin.layouts.master')

@section('setup_content')
<div class="container-fluid">
    <!-- Filters Section (Hide on Print) -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h4>Stock Summary Report</h4>
    </div>

    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mess.reports.stock-summary') }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control" 
                               value="{{ $fromDate }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control" 
                               value="{{ $toDate }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Store Type</label>
                        <select name="store_type" id="store_type" class="form-select">
                            <option value="main" {{ $storeType == 'main' ? 'selected' : '' }}>Main Store</option>
                            <option value="sub" {{ $storeType == 'sub' ? 'selected' : '' }}>Sub Store</option>
                        </select>
                    </div>
                    <div class="col-md-4" id="main_store_div" style="display: {{ $storeType == 'main' ? 'block' : 'none' }};">
                        <label class="form-label">Main Store</label>
                        <select name="store_id" class="form-select">
                            <option value="">All Main Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ $storeId == $store->id && $storeType == 'main' ? 'selected' : '' }}>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4" id="sub_store_div" style="display: {{ $storeType == 'sub' ? 'block' : 'none' }};">
                        <label class="form-label">Sub Store</label>
                        <select name="store_id" class="form-select">
                            <option value="">All Sub Stores</option>
                            @foreach($subStores as $subStore)
                                <option value="{{ $subStore->id }}" {{ $storeId == $subStore->id && $storeType == 'sub' ? 'selected' : '' }}>
                                    {{ $subStore->sub_store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter"></i> Generate Report
                    </button>
                    <a href="{{ route('admin.mess.reports.stock-summary') }}" class="btn btn-secondary">
                        <i class="ti ti-refresh"></i> Reset
                    </a>
                    <button type="button" class="btn btn-success" onclick="window.print()">
                        <i class="ti ti-printer"></i> Print
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Header (Print Only) -->
    <div class="report-header text-center mb-4">
        <h4 class="fw-bold">Stock Summary Report</h4>
        <p class="mb-1">Period: {{ date('d-F-Y', strtotime($fromDate)) }} to {{ date('d-F-Y', strtotime($toDate)) }}</p>
        <p class="text-primary mb-0">
            <strong>Store:</strong> {{ $selectedStoreName ?? ($storeType == 'main' ? "Officer's Main Mess(Primary)" : 'All Sub Stores') }}
        </p>
    </div>

    <!-- Report Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead>
                <tr style="background-color: #cbd5e0;">
                    <th rowspan="2" class="text-center align-middle" style="width: 60px;">SR.<br>No</th>
                    <th rowspan="2" class="text-center align-middle" style="min-width: 150px;">Item Name</th>
                    <th colspan="3" class="text-center" style="background-color: #bfdbfe;">Opening</th>
                    <th colspan="3" class="text-center" style="background-color: #fde68a;">Purchase</th>
                    <th colspan="3" class="text-center" style="background-color: #fed7aa;">Sale</th>
                    <th colspan="3" class="text-center" style="background-color: #bbf7d0;">Closing</th>
                </tr>
                <tr style="background-color: #cbd5e0;">
                    <!-- Opening -->
                    <th class="text-center" style="background-color: #bfdbfe;">Qty</th>
                    <th class="text-center" style="background-color: #bfdbfe;">Rate</th>
                    <th class="text-center" style="background-color: #bfdbfe;">Amount</th>
                    <!-- Purchase -->
                    <th class="text-center" style="background-color: #fde68a;">Qty</th>
                    <th class="text-center" style="background-color: #fde68a;">Rate</th>
                    <th class="text-center" style="background-color: #fde68a;">Amount</th>
                    <!-- Sale -->
                    <th class="text-center" style="background-color: #fed7aa;">Qty</th>
                    <th class="text-center" style="background-color: #fed7aa;">Rate</th>
                    <th class="text-center" style="background-color: #fed7aa;">Amount</th>
                    <!-- Closing -->
                    <th class="text-center" style="background-color: #bbf7d0;">Qty</th>
                    <th class="text-center" style="background-color: #bbf7d0;">Rate</th>
                    <th class="text-center" style="background-color: #bbf7d0;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reportData as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $item['item_name'] }}</td>
                        <!-- Opening -->
                        <td class="text-end">{{ number_format($item['opening_qty'], 2) }}</td>
                        <td class="text-end">{{ number_format($item['opening_rate'], 2) }}</td>
                        <td class="text-end">{{ number_format($item['opening_amount'], 2) }}</td>
                        <!-- Purchase -->
                        <td class="text-end">{{ number_format($item['purchase_qty'], 2) }}</td>
                        <td class="text-end">{{ number_format($item['purchase_rate'], 2) }}</td>
                        <td class="text-end">{{ number_format($item['purchase_amount'], 2) }}</td>
                        <!-- Sale -->
                        <td class="text-end">{{ number_format($item['sale_qty'], 2) }}</td>
                        <td class="text-end">{{ number_format($item['sale_rate'], 2) }}</td>
                        <td class="text-end">{{ number_format($item['sale_amount'], 2) }}</td>
                        <!-- Closing -->
                        <td class="text-end">{{ number_format($item['closing_qty'], 2) }}</td>
                        <td class="text-end">{{ number_format($item['closing_rate'], 2) }}</td>
                        <td class="text-end">{{ number_format($item['closing_amount'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="14" class="text-center text-muted py-4">
                            No stock movement found for the selected period
                        </td>
                    </tr>
                @endforelse
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
            page-break-inside: auto;
        }
        table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        table thead {
            display: table-header-group;
        }
        th, td { 
            padding: 6px !important; 
        }
        @page {
            margin: 1cm;
            size: A4 landscape;
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
        font-weight: bold;
    }
    
    .report-header p {
        color: #333;
        font-size: 14px;
    }

    /* Table styling for better visibility */
    .table th {
        font-weight: 600;
        white-space: nowrap;
    }

    .table td {
        white-space: nowrap;
    }

    /* Error highlighting */
    .table-danger {
        background-color: #f8d7da !important;
    }

    .table-danger:hover {
        background-color: #f5c2c7 !important;
    }

    .text-danger {
        color: #dc3545 !important;
    }

    .fw-bold {
        font-weight: 700 !important;
    }

    /* Alert styling */
    .alert-danger {
        border-left: 4px solid #dc3545;
    }

    @media print {
        .table-danger {
            background-color: #ffcccc !important;
            border: 2px solid #ff0000 !important;
        }
    }
</style>

<script>
    // Store Type Selection Handler
    document.addEventListener('DOMContentLoaded', function() {
        const storeTypeSelect = document.getElementById('store_type');
        const mainStoreDiv = document.getElementById('main_store_div');
        const subStoreDiv = document.getElementById('sub_store_div');

        if (storeTypeSelect) {
            storeTypeSelect.addEventListener('change', function() {
                if (this.value === 'main') {
                    mainStoreDiv.style.display = 'block';
                    subStoreDiv.style.display = 'none';
                } else {
                    mainStoreDiv.style.display = 'none';
                    subStoreDiv.style.display = 'block';
                }
            });
        }
    });
</script>
@endsection
