@extends('admin.layouts.master')
@section('title', 'Mess Invoices')
@section('setup_content')
<div class="container-fluid">
    <h4>Mess Invoices</h4>
    <a href="{{ route('mess.invoices.create') }}" class="btn btn-primary mb-3">Add Invoice</a>
    <table class="table table-bordered">
        <thead><tr><th>Invoice No</th><th>Vendor</th><th>Date</th><th>Amount</th></tr></thead>
        <tbody>
        @foreach($invoices as $invoice)
            <tr>
                <td>{{ $invoice->invoice_no }}</td>
                <td>{{ $invoice->vendor->name ?? '' }}</td>
                <td>{{ $invoice->invoice_date }}</td>
                <td>{{ $invoice->amount }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
