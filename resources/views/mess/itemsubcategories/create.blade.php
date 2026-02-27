@extends('admin.layouts.master')
@section('title', 'Add Subcategory Item')
@section('setup_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">Add Subcategory Item</h4>

            <form method="POST" action="{{ route('admin.mess.itemsubcategories.store') }}">
                @csrf

                @include('mess.itemsubcategories._form', ['itemsubcategory' => null, 'categories' => $categories])

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Save</button>
                    <a href="{{ route('admin.mess.itemsubcategories.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
