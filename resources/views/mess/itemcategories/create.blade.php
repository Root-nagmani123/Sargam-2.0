@extends('admin.layouts.master')
@section('title', 'Add Mess Item Category')
@section('setup_content')
<div class="container-fluid">
    <h4>Add Mess Item Category</h4>
    <form method="POST" action="{{ route('mess.itemcategories.store') }}">
        @csrf
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
    </form>
</div>
@endsection
