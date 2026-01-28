@extends('admin.layouts.master')
@section('title', 'Edit Item Category')
@section('setup_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h4 class="mb-1">Edit Item Category</h4>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.mess.itemcategories.update', $itemcategory->id) }}">
                @csrf
                @method('PUT')

                @include('mess.itemcategories._form', ['itemcategory' => $itemcategory])

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Update</button>
                    <a href="{{ route('admin.mess.itemcategories.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
