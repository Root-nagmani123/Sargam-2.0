@extends('admin.layouts.master')
@section('title', 'Add Vendor')
@section('setup_content')
<div class="container-fluid">
    <h4>Add Vendor</h4>
    <form method="POST" action="{{ route('mess.vendors.store') }}">
        @csrf
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Contact Person</label>
            <input type="text" name="contact_person" class="form-control">
        </div>
        <div class="mb-3">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control">
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        <div class="mb-3">
            <label>Address</label>
            <input type="text" name="address" class="form-control">
        </div>
        <button type="submit" class="btn btn-success">Save</button>
    </form>
</div>
@endsection
