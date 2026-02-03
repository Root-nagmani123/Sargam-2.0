@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <iconify-icon icon="solar:document-text-bold" class="me-2"></iconify-icon>
            Monthly Bill Details
        </h5>
        <div>
            <a href="{{ route('admin.mess.monthly-bills.edit', $bill->id) }}" class="btn btn-warning btn-sm me-1">
                <iconify-icon icon="solar:pen-bold" class="me-1"></iconify-icon>
                Edit
            </a>
            <a href="{{ route('admin.mess.monthly-bills.index') }}" class="btn btn-secondary btn-sm">
                <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
                Back to List
            </a>
        </div>
    </div>
    
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="text-muted mb-3">Bill Information</h6>
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted" style="width: 40%;">Bill Number:</td>
                        <td><strong>{{ $bill->bill_number }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">User:</td>
                        <td>{{ $bill->user ? (trim(($bill->user->first_name ?? '') . ' ' . ($bill->user->last_name ?? '')) ?: $bill->user->user_name) : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Month:</td>
                        <td>{{ $bill->month_year ? $bill->month_year->format('F Y') : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Amount:</td>
                        <td><strong class="text-primary">₹{{ number_format($bill->total_amount, 2) }}</strong></td>
                    </tr>
                </table>
            </div>
            
            <div class="col-md-6">
                <h6 class="text-muted mb-3">Payment Information</h6>
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted" style="width: 40%;">Paid Amount:</td>
                        <td><strong class="text-success">₹{{ number_format($bill->paid_amount ?? 0, 2) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Due Amount:</td>
                        <td><strong class="text-danger">₹{{ number_format($bill->total_amount - ($bill->paid_amount ?? 0), 2) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status:</td>
                        <td>
                            @if($bill->status === 'paid')
                                <span class="badge bg-success">Paid</span>
                            @elseif($bill->status === 'unpaid')
                                <span class="badge bg-danger">Unpaid</span>
                            @else
                                <span class="badge bg-warning">Partial</span>
                            @endif
                        </td>
                    </tr>
                    @if($bill->paid_date)
                        <tr>
                            <td class="text-muted">Paid Date:</td>
                            <td>{{ $bill->paid_date->format('d M, Y') }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>
        
        @if($bill->remarks)
            <div class="row">
                <div class="col-12">
                    <h6 class="text-muted mb-2">Remarks</h6>
                    <div class="alert alert-info">
                        {{ $bill->remarks }}
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
