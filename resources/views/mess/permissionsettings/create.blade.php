@extends('admin.layouts.master')
@section('title', 'Add Mess Permission Setting')
@section('setup_content')
<div class="container-fluid">
    <h4>Add Mess Permission Setting</h4>
    <form method="POST" action="{{ route('admin.mess.permissionsettings.store') }}">
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
