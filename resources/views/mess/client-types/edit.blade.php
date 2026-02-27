@extends('admin.layouts.master')
@section('title', 'Edit Client Type')
@section('setup_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h4 class="mb-1">Edit Client Type</h4>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.mess.client-types.update', $clientType->id) }}">
                @csrf
                @method('PUT')

                @include('mess.client-types._form', ['clientType' => $clientType])

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Update</button>
                    <a href="{{ route('admin.mess.client-types.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
