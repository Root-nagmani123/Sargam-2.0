@extends('admin.layouts.master')
@section('title', 'Mess Item Subcategories')
@section('setup_content')
<div class="container-fluid">
    <h4>Mess Item Subcategories</h4>
    <a href="{{ route('mess.itemsubcategories.create') }}" class="btn btn-primary mb-3">Add Subcategory</a>
    <table class="table table-bordered">
        <thead><tr><th>Name</th><th>Category</th><th>Description</th></tr></thead>
        <tbody>
        @foreach($itemsubcategories as $subcategory)
            <tr>
                <td>{{ $subcategory->name }}</td>
                <td>{{ $subcategory->category->name ?? '' }}</td>
                <td>{{ $subcategory->description }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
