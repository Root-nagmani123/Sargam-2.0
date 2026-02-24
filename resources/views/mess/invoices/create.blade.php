@extends('admin.layouts.master')
@section('title', 'Add Mess Invoice')
@section('setup_content')
<div class="container-fluid">
    <h4>Add Mess Invoice</h4>
    <form method="POST" action="{{ route('admin.mess.invoices.store') }}">
        @csrf
        <div class="mb-3">
            <label>Invoice No</label>
            <input type="text" name="invoice_no" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Vendor</label>
            <select name="vendor_id" class="form-select select2" required>
                @foreach($vendors as $vendor)
                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Date</label>
            <input type="date" name="invoice_date" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Amount</label>
            <input type="number" name="amount" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
    </form>
</div>
@endsection
