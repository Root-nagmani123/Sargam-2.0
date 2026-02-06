@extends('admin.layouts.master')
@section('title', 'Add Mess Meal Mapping')
@section('setup_content')
<div class="container-fluid">
    <h4>Add Mess Meal Mapping</h4>
    <form method="POST" action="{{ route('admin.mess.mealmappings.store') }}">
        @csrf
        <div class="mb-3">
            <label>Meal Name</label>
            <input type="text" name="meal_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Item Name</label>
            <input type="text" name="item_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Date</label>
            <input type="date" name="date" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
    </form>
</div>
@endsection
