@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <iconify-icon icon="solar:document-text-bold" class="me-2"></iconify-icon>
            Finance Booking Details
        </h5>
        <a href="{{ route('admin.mess.finance-bookings.index') }}" class="btn btn-secondary btn-sm">
            <iconify-icon icon="solar:arrow-left-bold" class="me-1"></iconify-icon>
            Back to List
        </a>
    </div>
    
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">Booking Number</th>
                        <td>{{ $booking->booking_number }}</td>
                    </tr>
                    <tr>
                        <th>Invoice</th>
                        <td>
                            @if($booking->invoice)
                                Invoice #{{ $booking->invoice->invoice_no ?? $booking->invoice->id }}
                            @else
                                N/A
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>User</th>
                        <td>{{ $booking->user ? (trim(($booking->user->first_name ?? '') . ' ' . ($booking->user->last_name ?? '')) ?: $booking->user->user_name) : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Amount</th>
                        <td>₹{{ number_format($booking->amount, 2) }}</td>
                    </tr>
                </table>
            </div>
            
            <div class="col-md-6">
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">Booking Date</th>
                        <td>{{ $booking->booking_date->format('d-m-Y') }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if($booking->status == 'approved')
                                <span class="badge badge-success">Approved</span>
                            @elseif($booking->status == 'rejected')
                                <span class="badge badge-danger">Rejected</span>
                            @else
                                <span class="badge badge-warning">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @if($booking->approved_by)
                    <tr>
                        <th>Approved/Rejected By</th>
                        <td>{{ $booking->approver ? (trim(($booking->approver->first_name ?? '') . ' ' . ($booking->approver->last_name ?? '')) ?: $booking->approver->user_name) : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <th>Approved/Rejected At</th>
                        <td>{{ $booking->approved_at ? $booking->approved_at->format('d-m-Y H:i') : 'N/A' }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
        
        @if($booking->remarks)
        <div class="row mt-3">
            <div class="col-md-12">
                <h5>Remarks</h5>
                <div class="alert alert-info">
                    {{ $booking->remarks }}
                </div>
            </div>
        </div>
        @endif
        
        @if($booking->status == 'pending')
        <div class="row mt-3">
            <div class="col-md-12">
                <form method="POST" action="{{ route('admin.mess.finance-bookings.approve', $booking->id) }}" 
                      style="display: inline-block;">
                    @csrf
                    <button type="submit" class="btn btn-success" 
                            onclick="return confirm('Are you sure you want to approve this booking?')">
                        <i class="fas fa-check"></i> Approve
                    </button>
                </form>
                
                <form method="POST" action="{{ route('admin.mess.finance-bookings.reject', $booking->id) }}" 
                      style="display: inline-block;">
                    @csrf
                    <button type="submit" class="btn btn-danger" 
                            onclick="return confirm('Are you sure you want to reject this booking?')">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </form>
                
                <a href="{{ route('admin.mess.finance-bookings.edit', $booking->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
