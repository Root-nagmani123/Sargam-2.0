@extends('admin.layouts.master')
@section('title', 'Add Mess Event')
@section('setup_content')
<div class="container-fluid">
    <h4>Add Mess Event</h4>
    <form method="POST" action="{{ route('admin.mess.events.store') }}">
        @csrf
        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Description</label>
            <textarea name="description" class="form-control"></textarea>
        </div>
        <div class="mb-3">
            <label>Event Date</label>
            <input type="date" name="event_date" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
    </form>
</div>
@endsection
