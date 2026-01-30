@extends('admin.layouts.master')
@section('title', 'View Material Management')
@section('setup_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Material Management Details</h4>
        <div>
            @if($kitchenIssue->status == 'pending')
                <a href="{{ route('admin.mess.material-management.edit', $kitchenIssue->pk) }}" class="btn btn-warning">Edit</a>
            @endif
            <a href="{{ route('admin.mess.material-management.index') }}" class="btn btn-secondary">Back to List</a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Issue #{{ $kitchenIssue->pk }}</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Request Date:</th>
                            <td>{{ $kitchenIssue->request_date ? \Carbon\Carbon::parse($kitchenIssue->request_date)->format('d/m/Y') : '-' }}</td>
                        </tr>
                        <tr>
                            <th>Issue Date:</th>
                            <td>{{ $kitchenIssue->issue_date ? \Carbon\Carbon::parse($kitchenIssue->issue_date)->format('d/m/Y') : '-' }}</td>
                        </tr>
                        <tr>
                            <th>Store:</th>
                            <td>{{ $kitchenIssue->storeMaster->store_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Item:</th>
                            <td>{{ $kitchenIssue->itemMaster->item_name ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Quantity:</th>
                            <td>{{ $kitchenIssue->quantity }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Unit Price:</th>
                            <td>₹{{ number_format($kitchenIssue->unit_price, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Total Amount:</th>
                            <td><strong>₹{{ number_format($kitchenIssue->unit_price * $kitchenIssue->quantity, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                @if($kitchenIssue->status == 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($kitchenIssue->status == 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($kitchenIssue->status == 'issued')
                                    <span class="badge bg-primary">Issued</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($kitchenIssue->status) }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Payment Status:</th>
                            <td>
                                @if($kitchenIssue->payment_type == 1)
                                    <span class="badge bg-success">Paid</span>
                                @else
                                    <span class="badge bg-danger">Unpaid</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Transfer To:</th>
                            <td>{{ $kitchenIssue->transfer_to ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            @if($kitchenIssue->remarks)
            <div class="row mt-3">
                <div class="col-12">
                    <h6>Remarks:</h6>
                    <p>{{ $kitchenIssue->remarks }}</p>
                </div>
            </div>
            @endif
            
            @if($kitchenIssue->requested_store_id)
            <div class="row mt-2">
                <div class="col-12">
                    <h6>Requested Store:</h6>
                    <p>{{ $kitchenIssue->requestedStore->store_name ?? 'N/A' }}</p>
                </div>
            </div>
            @endif
        </div>
        <div class="card-footer text-muted">
            <small>Created: {{ $kitchenIssue->created_at ? $kitchenIssue->created_at->format('d/m/Y H:i') : '-' }}</small>
            @if($kitchenIssue->updated_at)
                <small class="ms-3">Last Updated: {{ $kitchenIssue->updated_at->format('d/m/Y H:i') }}</small>
            @endif
        </div>
    </div>
</div>
@endsection
