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

    <div class="card mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0 fw-semibold">Voucher Details</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        <tr><th width="40%">Request Date:</th><td>{{ $kitchenIssue->request_date ? \Carbon\Carbon::parse($kitchenIssue->request_date)->format('d/m/Y') : '-' }}</td></tr>
                        <tr><th>Issue Date:</th><td>{{ $kitchenIssue->issue_date ? \Carbon\Carbon::parse($kitchenIssue->issue_date)->format('d/m/Y') : '-' }}</td></tr>
                        <tr><th>Transfer From Store:</th><td>{{ $kitchenIssue->storeMaster->store_name ?? 'N/A' }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless table-sm">
                        <tr><th width="40%">Client Type:</th><td>{{ $kitchenIssue->clientTypeCategory ? ucfirst($kitchenIssue->clientTypeCategory->client_type ?? '') : '-' }}</td></tr>
                        <tr><th>Client Name:</th><td>{{ $kitchenIssue->client_name ?? '-' }}</td></tr>
                        <tr><th>Payment Type:</th><td>{{ $kitchenIssue->payment_type == 1 ? 'Credit' : ($kitchenIssue->payment_type == 0 ? 'Cash' : ($kitchenIssue->payment_type == 2 ? 'Online' : '-')) }}</td></tr>
                        <tr><th>Status:</th><td>
                            @if($kitchenIssue->status == 0)<span class="badge bg-warning">Pending</span>
                            @elseif($kitchenIssue->status == 2)<span class="badge bg-success">Approved</span>
                            @elseif($kitchenIssue->status == 4)<span class="badge bg-primary">Completed</span>
                            @else<span class="badge bg-secondary">{{ $kitchenIssue->status }}</span>@endif
                        </td></tr>
                    </table>
                </div>
            </div>
            @if($kitchenIssue->remarks)
                <p class="mb-0 mt-2"><strong>Remarks:</strong> {{ $kitchenIssue->remarks }}</p>
            @endif
        </div>
    </div>

    @if($kitchenIssue->items->isNotEmpty())
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold">Item Details</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead style="background-color: #af2910;">
                            <tr>
                                <th style="color: #fff;">Item Name</th>
                                <th style="color: #fff;">Unit</th>
                                <th style="color: #fff;">Issue Qty</th>
                                <th style="color: #fff;">Return Qty</th>
                                <th style="color: #fff;">Rate</th>
                                <th style="color: #fff;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kitchenIssue->items as $item)
                                <tr>
                                    <td>{{ $item->item_name ?: ($item->itemSubcategory->item_name ?? '—') }}</td>
                                    <td>{{ $item->unit ?? '—' }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ $item->return_quantity ?? 0 }}</td>
                                    <td>₹{{ number_format($item->rate, 2) }}</td>
                                    <td>₹{{ number_format($item->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light d-flex justify-content-end">
                <strong>Grand Total: ₹{{ number_format($kitchenIssue->items->sum('amount'), 2) }}</strong>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body">
                <table class="table table-borderless table-sm">
                    <tr><th>Item:</th><td>{{ $kitchenIssue->itemMaster->item_name ?? 'N/A' }}</td></tr>
                    <tr><th>Quantity:</th><td>{{ $kitchenIssue->quantity }}</td></tr>
                    <tr><th>Unit Price:</th><td>₹{{ number_format($kitchenIssue->unit_price, 2) }}</td></tr>
                    <tr><th>Total:</th><td><strong>₹{{ number_format($kitchenIssue->unit_price * $kitchenIssue->quantity, 2) }}</strong></td></tr>
                </table>
            </div>
        </div>
    @endif

    <div class="mt-3 text-muted small">
        Created: {{ $kitchenIssue->created_at ? $kitchenIssue->created_at->format('d/m/Y H:i') : '-' }}
        @if($kitchenIssue->updated_at)<span class="ms-3">Last Updated: {{ $kitchenIssue->updated_at->format('d/m/Y H:i') }}</span>@endif
    </div>
</div>
@endsection
