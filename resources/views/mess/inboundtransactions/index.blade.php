@extends('admin.layouts.master')
@section('title', 'Inbound Transactions')
@section('setup_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Inbound Transactions (Goods Receipt)</h4>
        <a href="{{ route('admin.mess.inboundtransactions.create') }}" class="btn btn-primary">Record Receipt</a>
    </div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Transaction #</th>
                <th>Receipt Date</th>
                <th>PO Number</th>
                <th>Vendor</th>
                <th>Store</th>
                <th>Invoice</th>
                <th>Amount</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach($transactions as $txn)
            <tr>
                <td>{{ $txn->transaction_number }}</td>
                <td>{{ $txn->receipt_date->format('d/m/Y') }}</td>
                <td>{{ $txn->purchaseOrder->po_number ?? 'N/A' }}</td>
                <td>{{ $txn->vendor->name ?? 'N/A' }}</td>
                <td>{{ $txn->store->store_name ?? 'N/A' }}</td>
                <td>{{ $txn->invoice_number ?? 'N/A' }}</td>
                <td>₹{{ number_format($txn->invoice_amount ?? 0, 2) }}</td>
                <td>
                    <a href="{{ route('admin.mess.inboundtransactions.show', $txn->id) }}" class="btn btn-sm btn-info">View</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
