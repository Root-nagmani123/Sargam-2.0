@extends('admin.layouts.master')
@section('title', 'Edit Subcategory Item')
@section('setup_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h4 class="mb-1">Edit Subcategory Item</h4>
                    <div class="text-muted small">Item Code: {{ $itemsubcategory->item_code }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.mess.itemsubcategories.update', $itemsubcategory->id) }}">
                @csrf
                @method('PUT')

                @include('mess.itemsubcategories._form', ['itemsubcategory' => $itemsubcategory, 'categories' => $categories])

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Update</button>
                    <a href="{{ route('admin.mess.itemsubcategories.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
