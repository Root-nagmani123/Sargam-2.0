@extends('admin.layouts.master')
@section('title', 'Inbound Transaction Details')
@section('setup_content')
<div class="container-fluid">
    <h4>Inbound Transaction Details</h4>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Transaction Number:</strong> {{ $transaction->transaction_number }}</p>
                    <p><strong>Receipt Date:</strong> {{ $transaction->receipt_date->format('d/m/Y') }}</p>
                    <p><strong>Purchase Order:</strong> {{ $transaction->purchaseOrder->po_number ?? 'N/A' }}</p>
                    <p><strong>Vendor:</strong> {{ $transaction->vendor->name ?? 'N/A' }}</p>
                    <p><strong>Store:</strong> {{ $transaction->store->store_name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Invoice Number:</strong> {{ $transaction->invoice_number ?? 'N/A' }}</p>
                    <p><strong>Invoice Amount:</strong> ₹{{ number_format($transaction->invoice_amount ?? 0, 2) }}</p>
                    <p><strong>Received By:</strong> {{ $transaction->receiver->name ?? 'N/A' }}</p>
                    <p><strong>Remarks:</strong> {{ $transaction->remarks ?? 'N/A' }}</p>
                </div>
            </div>
            
            <h5 class="mt-3">Received Items</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>Unit Price</th>
                        <th>Total Price</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transaction->items as $item)
                        <tr>
                            <td>{{ $item->inventory->item_name ?? 'N/A' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->unit ?? '-' }}</td>
                            <td>{{ $item->unit_price ? '₹'.number_format($item->unit_price, 2) : '-' }}</td>
                            <td>{{ $item->total_price ? '₹'.number_format($item->total_price, 2) : '-' }}</td>
                            <td>{{ $item->remarks ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            <a href="{{ route('admin.mess.inboundtransactions.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>
</div>
@endsection
