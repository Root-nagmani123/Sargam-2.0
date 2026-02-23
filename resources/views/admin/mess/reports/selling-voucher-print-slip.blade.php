@extends('admin.layouts.master')
@section('title', 'Print Slip - Category Wise')
@section('setup_content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h4>Print Slip – Category Wise</h4>
    </div>

    <!-- Filters Section -->
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mess.reports.selling-voucher-print-slip') }}" id="filterForm">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Select Employee / OT</label>
                        <select name="employee_ot_filter" id="employeeOtFilter" class="form-select select2">
                            <option value="">All</option>
                            <option value="employee_ot" {{ request('employee_ot_filter') == 'employee_ot' ? 'selected' : '' }}>Employee / OT</option>
                            <option value="employee" {{ request('employee_ot_filter') == 'employee' ? 'selected' : '' }}>Employee Only</option>
                            <option value="ot" {{ request('employee_ot_filter') == 'ot' ? 'selected' : '' }}>OT Only</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Select Client Type</label>
                        <select name="client_type_slug" id="clientTypeSlug" class="form-select select2">
                            <option value="">All Client Types</option>
                            @foreach($clientTypes as $key => $label)
                                <option value="{{ $key }}" {{ request('client_type_slug') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Select Buyer Name (Selling Voucher)</label>
                        <select name="client_type_pk" id="clientTypePk" class="form-select select2">
                            <option value="">All Buyers</option>
                            @if(request('client_type_slug') && isset($clientTypeCategories[request('client_type_slug')]))
                                @foreach($clientTypeCategories[request('client_type_slug')] as $category)
                                    <option value="{{ $category->id }}" {{ request('client_type_pk') == $category->id ? 'selected' : '' }}>
                                        {{ $category->client_name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>
                <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter"></i> Apply Filters
                    </button>
                    <a href="{{ route('admin.mess.reports.selling-voucher-print-slip') }}" class="btn btn-secondary">
                        <i class="ti ti-refresh"></i> Reset
                    </a>
                    <button type="button" class="btn btn-outline-primary" onclick="window.print()" title="Print or Save as PDF">
                        <i class="ti ti-printer"></i> Print
                    </button>
                    <a href="{{ route('admin.mess.reports.selling-voucher-print-slip.excel', request()->query()) }}" class="btn btn-success" title="Export to Excel">
                        <i class="ti ti-file-spreadsheet"></i> Export Excel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Heading (Print Only) -->
    <div class="report-header text-center mb-4">
        <h3 class="fw-bold mb-0">Office Mess, LBS Mussoorie</h3>
        <h5 class="mt-2 mb-3">Selling Voucher Print Slip - Category Wise</h5>
        @if(request('from_date') || request('to_date'))
            <p class="mb-1">
                <strong>Period:</strong> 
                {{ request('from_date') ? \Carbon\Carbon::parse(request('from_date'))->format('d/m/Y') : 'Start' }} 
                to 
                {{ request('to_date') ? \Carbon\Carbon::parse(request('to_date'))->format('d/m/Y') : 'End' }}
            </p>
        @endif
    </div>

    <!-- Details Section (Print Only) -->
    <div class="report-details mb-3">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-1">
                    <strong>Buyer Name:</strong> 
                    @if(request('employee_ot_filter'))
                        @if(request('employee_ot_filter') == 'employee_ot')
                            Employee / OT
                        @elseif(request('employee_ot_filter') == 'employee')
                            Employee
                        @elseif(request('employee_ot_filter') == 'ot')
                            OT
                        @else
                            All
                        @endif
                    @elseif(request('client_type_slug'))
                        {{ ucfirst(request('client_type_slug')) }}
                    @else
                        All Categories
                    @endif
                </p>
            </div>
            <div class="col-md-6">
                <p class="mb-1">
                    <strong>Client Type:</strong> 
                    @if(request('client_type_pk') && isset($clientTypeCategories[request('client_type_slug')]))
                        @php
                            $selectedClient = $clientTypeCategories[request('client_type_slug')]->firstWhere('id', request('client_type_pk'));
                        @endphp
                        {{ $selectedClient ? $selectedClient->client_name : 'All' }}
                    @else
                        All
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- Report Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead style="background-color: #af2910;">
                <tr>
                    <th style="color: #fff; border-color: #af2910; width: 60px;">S. No.</th>
                    <th style="color: #fff; border-color: #af2910;">Buyer Name</th>
                    <th style="color: #fff; border-color: #af2910; width: 90px;">Status</th>
                    <th style="color: #fff; border-color: #af2910;">Item Name</th>
                    <th style="color: #fff; border-color: #af2910; width: 120px;">Request No.</th>
                    <th style="color: #fff; border-color: #af2910; text-align: right; width: 100px;">Quantity</th>
                    <th style="color: #fff; border-color: #af2910; text-align: right; width: 100px;">Price</th>
                    <th style="color: #fff; border-color: #af2910; text-align: right; width: 120px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $serialNo = 0;
                    $totalAmount = 0;
                @endphp
                @forelse($vouchers as $voucher)
                    @php
                        // Get buyer name and client type
                        $buyerName = $voucher->client_name ?? ($voucher->clientTypeCategory->client_name ?? 'N/A');
                        $clientType = $voucher->clientTypeCategory 
                            ? ucfirst($voucher->clientTypeCategory->client_type) 
                            : ucfirst($voucher->client_type_slug ?? 'N/A');
                        
                        // Format request number (voucher ID)
                        $requestNo = 'SV-' . str_pad($voucher->id, 6, '0', STR_PAD_LEFT);
                    @endphp
                    
                    @foreach($voucher->items as $itemIndex => $item)
                        @php
                            $serialNo++;
                            $itemAmount = ($item->quantity ?? 0) * ($item->rate ?? 0);
                            $totalAmount += $itemAmount;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $serialNo }}</td>
                            @if($itemIndex === 0)
                                <td rowspan="{{ $voucher->items->count() }}">
                                    <strong>{{ $buyerName }}</strong>
                                    <br>
                                    <small class="text-muted">Type: {{ $clientType }}</small>
                                </td>
                                <td class="text-center align-middle" rowspan="{{ $voucher->items->count() }}">{{ $voucher->status_label ?? 'N/A' }}</td>
                            @endif
                            <td>{{ $item->item_name ?? ($item->itemSubcategory->item_name ?? $item->itemSubcategory->name ?? 'N/A') }}</td>
                            @if($itemIndex === 0)
                                <td rowspan="{{ $voucher->items->count() }}" class="text-center">
                                    {{ $requestNo }}
                                    <br>
                                    <small class="text-muted">{{ $voucher->issue_date ? $voucher->issue_date->format('d/m/Y') : 'N/A' }}</small>
                                </td>
                            @endif
                            <td class="text-end">{{ number_format($item->quantity ?? 0, 2) }} {{ $item->unit ?? '' }}</td>
                            <td class="text-end">₹{{ number_format($item->rate ?? 0, 2) }}</td>
                            <td class="text-end">₹{{ number_format($itemAmount, 2) }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No selling vouchers found</td>
                    </tr>
                @endforelse
                
                @if($vouchers->count() > 0)
                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                        <td colspan="7" class="text-end">Grand Total:</td>
                        <td class="text-end">₹{{ number_format($totalAmount, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    @if($vouchers->count() > 0)
        <!-- Summary Section (Print Only) -->
        <div class="report-summary mt-4">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Total Vouchers:</strong> {{ $vouchers->count() }}</p>
                    <p><strong>Total Items:</strong> {{ $vouchers->sum(function($v) { return $v->items->count(); }) }}</p>
                </div>
                <div class="col-md-6 text-end">
                    <p><strong>Grand Total Amount:</strong> ₹{{ number_format($totalAmount, 2) }}</p>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
    @media print {
        .no-print { 
            display: none !important; 
        }
        .report-header, .report-details, .report-summary { 
            display: block !important;
        }
        body { 
            font-size: 11px; 
        }
        table { 
            font-size: 10px; 
        }
        th, td { 
            padding: 6px !important; 
        }
        .report-header {
            margin-top: 15px;
            margin-bottom: 15px;
        }
        .report-details {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            padding: 10px 15px;
            background-color: #f9f9f9;
        }
        .report-summary {
            margin-top: 30px;
            border-top: 2px solid #000;
            padding-top: 15px;
        }
    }
    
    @media screen {
        .report-header, .report-details {
            display: none;
        }
        .report-summary {
            display: none;
        }
    }
    
    .report-header h3 {
        color: #000;
        font-size: 18px;
    }
    
    .report-header h5 {
        color: #af2910;
        font-size: 16px;
    }
    
    .report-header p {
        color: #555;
        font-size: 12px;
    }
    
    .report-details p {
        color: #333;
        font-size: 11px;
        margin-bottom: 5px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const clientTypeSlug = document.getElementById('clientTypeSlug');
    const clientTypePk = document.getElementById('clientTypePk');
    
    // Store all buyer options by client type
    const buyerOptions = {
        @foreach($clientTypes as $key => $label)
            '{{ $key }}': [
                @if(isset($clientTypeCategories[$key]))
                    @foreach($clientTypeCategories[$key] as $category)
                        { value: '{{ $category->id }}', text: '{{ $category->client_name }}' },
                    @endforeach
                @endif
            ],
        @endforeach
    };
    
    clientTypeSlug.addEventListener('change', function() {
        const selectedType = this.value;
        
        // Clear current options
        clientTypePk.innerHTML = '<option value="">All Buyers</option>';
        
        // Add options for selected type
        if (selectedType && buyerOptions[selectedType]) {
            buyerOptions[selectedType].forEach(function(option) {
                const opt = document.createElement('option');
                opt.value = option.value;
                opt.textContent = option.text;
                clientTypePk.appendChild(opt);
            });
        }
    });
});
</script>
@endsection
