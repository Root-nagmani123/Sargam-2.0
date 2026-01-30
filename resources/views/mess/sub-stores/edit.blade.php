@extends('admin.layouts.master')
@section('title', 'Edit Sub Store')
@section('setup_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h4 class="mb-1">Edit Sub Store</h4>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.mess.sub-stores.update', $subStore->id) }}">
                @csrf
                @method('PUT')

                @include('mess.sub-stores._form', ['subStore' => $subStore])

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Update</button>
                    <a href="{{ route('admin.mess.sub-stores.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
