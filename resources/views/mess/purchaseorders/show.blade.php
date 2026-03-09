@extends('admin.layouts.master')
@section('title', 'Purchase Order Details')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Purchase Order Details"></x-breadcrum>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-body-tertiary border-0 py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h5 class="mb-1 fw-semibold">PO {{ $purchaseOrder->po_number }}</h5>
                    <p class="mb-0 text-body-secondary small">Created by {{ $purchaseOrder->creator->name ?? 'N/A' }}</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-body-secondary small">Status</span>
                    <span class="badge rounded-pill bg-{{ $purchaseOrder->status == 'approved' ? 'success' : ($purchaseOrder->status == 'rejected' ? 'danger' : ($purchaseOrder->status == 'completed' ? 'primary' : 'warning')) }}">
                        {{ ucfirst($purchaseOrder->status) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="card-body p-3 p-lg-4">
            <div class="row g-4">
                <div class="col-12 col-xl-6">
                    <div class="h-100 border rounded-3 p-3 bg-light-subtle">
                        <h6 class="fw-semibold mb-3">Order Information</h6>
                        <dl class="row mb-0 gy-2">
                            <dt class="col-sm-5 text-body-secondary fw-normal">PO Number</dt>
                            <dd class="col-sm-7 mb-0 fw-medium">{{ $purchaseOrder->po_number }}</dd>

                            <dt class="col-sm-5 text-body-secondary fw-normal">PO Date</dt>
                            <dd class="col-sm-7 mb-0">{{ $purchaseOrder->po_date->format('d/m/Y') }}</dd>

                            <dt class="col-sm-5 text-body-secondary fw-normal">Delivery Date</dt>
                            <dd class="col-sm-7 mb-0">{{ $purchaseOrder->delivery_date ? $purchaseOrder->delivery_date->format('d/m/Y') : 'N/A' }}</dd>

                            <dt class="col-sm-5 text-body-secondary fw-normal">Vendor</dt>
                            <dd class="col-sm-7 mb-0">{{ $purchaseOrder->vendor->name ?? 'N/A' }}</dd>

                            <dt class="col-sm-5 text-body-secondary fw-normal">Store</dt>
                            <dd class="col-sm-7 mb-0">{{ $purchaseOrder->store->store_name ?? 'N/A' }}</dd>

                            <dt class="col-sm-5 text-body-secondary fw-normal">Payment Mode</dt>
                            <dd class="col-sm-7 mb-0">{{ $purchaseOrder->payment_code ?? 'N/A' }}</dd>

                            <dt class="col-sm-5 text-body-secondary fw-normal">Contact Number</dt>
                            <dd class="col-sm-7 mb-0">{{ $purchaseOrder->contact_number ?? 'N/A' }}</dd>

                            @if($purchaseOrder->delivery_address)
                                <dt class="col-sm-5 text-body-secondary fw-normal">Delivery Address</dt>
                                <dd class="col-sm-7 mb-0">{{ $purchaseOrder->delivery_address }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="h-100 border rounded-3 p-3 bg-light-subtle">
                        <h6 class="fw-semibold mb-3">Financial & Approval</h6>
                        <dl class="row mb-0 gy-2">
                            <dt class="col-sm-5 text-body-secondary fw-normal">Grand Total</dt>
                            <dd class="col-sm-7 mb-0 fw-semibold text-primary">&#8377;{{ number_format($purchaseOrder->total_amount, 2) }}</dd>

                            <dt class="col-sm-5 text-body-secondary fw-normal">Created By</dt>
                            <dd class="col-sm-7 mb-0">{{ $purchaseOrder->creator->name ?? 'N/A' }}</dd>

                            @if($purchaseOrder->approved_by)
                                <dt class="col-sm-5 text-body-secondary fw-normal">Approved By</dt>
                                <dd class="col-sm-7 mb-0">{{ $purchaseOrder->approver->name ?? 'N/A' }}</dd>

                                <dt class="col-sm-5 text-body-secondary fw-normal">Approved At</dt>
                                <dd class="col-sm-7 mb-0">{{ $purchaseOrder->approved_at->format('d/m/Y H:i') }}</dd>
                            @endif

                            <dt class="col-sm-5 text-body-secondary fw-normal">Remarks</dt>
                            <dd class="col-sm-7 mb-0">{{ $purchaseOrder->remarks ?? 'N/A' }}</dd>

                            <dt class="col-sm-5 text-body-secondary fw-normal">Bill</dt>
                            <dd class="col-sm-7 mb-0">
                                @if($purchaseOrder->bill_path)
                                    <a href="{{ asset('storage/' . $purchaseOrder->bill_path) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                        View / Download Bill
                                    </a>
                                @else
                                    <span class="text-muted">No bill uploaded</span>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold">Items</h6>
            <span class="badge text-bg-light border">{{ $purchaseOrder->items->count() }} line items</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Item Code</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Unit Price</th>
                            <th>Tax (%)</th>
                            <th>Total Amount</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrder->items as $item)
                            <tr>
                                <td>{{ optional($item->itemSubcategory)->item_name ?? optional($item->inventory)->item_name ?? 'N/A' }}</td>
                                <td>{{ optional($item->itemSubcategory)->item_code ?? '-' }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ $item->unit ?? '-' }}</td>
                                <td>&#8377;{{ number_format($item->unit_price, 2) }}</td>
                                <td>{{ number_format((float) ($item->tax_percent ?? 0), 2) }}%</td>
                                <td class="fw-semibold">&#8377;{{ number_format($item->total_price, 2) }}</td>
                                <td>{{ $item->description ?? '-' }}</td>
                            </tr>
                        @endforeach
                        <tr class="table-light fw-bold">
                            <td colspan="6" class="text-end">Grand Total:</td>
                            <td colspan="2" class="text-primary">&#8377;{{ number_format($purchaseOrder->total_amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-3">
            <a href="{{ route('admin.mess.purchaseorders.index') }}" class="btn btn-outline-secondary px-4">Back</a>
        </div>
    </div>
</div>
@endsection
