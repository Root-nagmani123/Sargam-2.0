@extends('admin.layouts.master')
@section('title', 'Edit Mess Store')
@section('setup_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h4 class="mb-1">Edit Store</h4>
                    <div class="text-muted small">Store Code: {{ $store->store_code }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.mess.stores.update', $store->id) }}">
                @csrf
                @method('PUT')

                @include('mess.stores._form', ['store' => $store])

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Update</button>
                    <a href="{{ route('admin.mess.stores.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
