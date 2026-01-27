@extends('admin.layouts.master')
@section('title', 'Material Request Details')
@section('setup_content')
<div class="container-fluid">
    <h4>Material Request Details</h4>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Request Number:</strong> {{ $materialRequest->request_number }}</p>
                    <p><strong>Request Date:</strong> {{ $materialRequest->request_date->format('d/m/Y') }}</p>
                    <p><strong>Store:</strong> {{ $materialRequest->store->store_name ?? 'N/A' }}</p>
                    <p><strong>Purpose:</strong> {{ $materialRequest->purpose }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Status:</strong> 
                        <span class="badge bg-{{ $materialRequest->status == 'approved' ? 'success' : ($materialRequest->status == 'rejected' ? 'danger' : 'warning') }}">
                            {{ ucfirst($materialRequest->status) }}
                        </span>
                    </p>
                    <p><strong>Requested By:</strong> {{ $materialRequest->requester->name ?? 'N/A' }}</p>
                    @if($materialRequest->approved_by)
                        <p><strong>Approved/Rejected By:</strong> {{ $materialRequest->approver->name ?? 'N/A' }}</p>
                        <p><strong>Approved/Rejected At:</strong> {{ $materialRequest->approved_at->format('d/m/Y H:i') }}</p>
                    @endif
                    @if($materialRequest->rejection_reason)
                        <p><strong>Rejection Reason:</strong> {{ $materialRequest->rejection_reason }}</p>
                    @endif
                </div>
            </div>
            
            <h5 class="mt-3">Items</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Requested Qty</th>
                        <th>Approved Qty</th>
                        <th>Unit</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($materialRequest->items as $item)
                        <tr>
                            <td>{{ $item->inventory->item_name ?? 'N/A' }}</td>
                            <td>{{ $item->requested_quantity }}</td>
                            <td>{{ $item->approved_quantity ?? '-' }}</td>
                            <td>{{ $item->unit ?? '-' }}</td>
                            <td>{{ $item->remarks ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            <a href="{{ route('admin.mess.materialrequests.index') }}" class="btn btn-secondary">Back</a>
            @if($materialRequest->status == 'approved')
                <a href="{{ route('admin.mess.purchaseorders.create', ['material_request_id' => $materialRequest->id]) }}" class="btn btn-primary">Create PO</a>
            @endif
        </div>
    </div>
</div>
@endsection
