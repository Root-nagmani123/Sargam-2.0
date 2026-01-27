@extends('admin.layouts.master')
@section('title', 'Add Mess Store Allocation')
@section('setup_content')
<div class="container-fluid">
    <h4>Add Mess Store Allocation</h4>
    <form method="POST" action="{{ route('admin.mess.storeallocations.store') }}">
        @csrf
        <div class="mb-3">
            <label>Store Name</label>
            <input type="text" name="store_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Allocated To</label>
            <input type="text" name="allocated_to" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Date</label>
            <input type="date" name="allocation_date" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
    </form>
</div>
@endsection
