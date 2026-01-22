@extends('admin.layouts.master')
@section('title', 'Add Inventory')
@section('setup_content')
<div class="container-fluid">
    <h4>Add Inventory</h4>
    <form method="POST" action="{{ route('mess.inventories.store') }}">
        @csrf
        <div class="mb-3">
            <label>Item Name</label>
            <input type="text" name="item_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Category</label>
            <input type="text" name="category" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Quantity</label>
            <input type="number" name="quantity" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Unit</label>
            <input type="text" name="unit" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Expiry Date</label>
            <input type="date" name="expiry_date" class="form-control">
        </div>
        <button type="submit" class="btn btn-success">Save</button>
    </form>
</div>
@endsection
