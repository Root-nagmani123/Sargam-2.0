@extends('admin.layouts.master')

@section('title', 'Mess Summary Report - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Mess/Store Summary Report" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <h4 class="mb-4">Mess/Store Summary Report</h4>
                <hr>
                @forelse($summary as $data)
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">{{ $data['store']->store_name }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="stat-box">
                                    <h6 class="text-muted">Total Items</h6>
                                    <h3>{{ $data['total_items'] }}</h3>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-box">
                                    <h6 class="text-muted">Low Stock Items</h6>
                                    <h3 class="text-danger">{{ $data['low_stock_items'] }}</h3>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-box">
                                    <h6 class="text-muted">Total Stock Value</h6>
                                    <h3 class="text-success">₹{{ number_format($data['total_stock_value'], 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="alert alert-info">No data available</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
