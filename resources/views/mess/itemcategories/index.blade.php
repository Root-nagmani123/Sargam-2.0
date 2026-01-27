@extends('admin.layouts.master')
@section('title', 'Mess Item Categories')
@section('setup_content')
<div class="container-fluid">
    <h4>Mess Item Categories</h4>
    <a href="{{ route('admin.mess.itemcategories.create') }}" class="btn btn-primary mb-3">Add Category</a>
    <table class="table table-bordered">
        <thead><tr><th>Name</th><th>Description</th></tr></thead>
        <tbody>
        @foreach($itemcategories as $category)
            <tr>
                <td>{{ $category->name }}</td>
                <td>{{ $category->description }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
