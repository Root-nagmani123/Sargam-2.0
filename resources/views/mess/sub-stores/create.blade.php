@extends('admin.layouts.master')
@section('title', 'Add Sub Store')
@section('setup_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">Add Sub Store</h4>

            <form method="POST" action="{{ route('admin.mess.sub-stores.store') }}">
                @csrf

                @include('mess.sub-stores._form', ['subStore' => null, 'stores' => $stores])

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Save</button>
                    <a href="{{ route('admin.mess.sub-stores.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
