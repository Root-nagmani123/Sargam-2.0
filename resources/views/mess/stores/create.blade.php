@extends('admin.layouts.master')
@section('title', 'Add Mess Store')
@section('setup_content')
<div class="container-fluid mess-select2">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">Add Store</h4>

            <form method="POST" action="{{ route('admin.mess.stores.store') }}">
                @csrf

                @include('mess.stores._form', ['store' => null])

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Save</button>
                    <a href="{{ route('admin.mess.stores.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@include('mess.partials.select2-search')
@endsection
