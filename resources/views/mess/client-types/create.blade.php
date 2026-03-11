@extends('admin.layouts.master')
@section('title', 'Add Client Type')
@section('setup_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <h4 class="mb-3">Add Client Type</h4>

            <form method="POST" action="{{ route('admin.mess.client-types.store') }}">
                @csrf

                @include('mess.client-types._form', ['clientType' => null])

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Save</button>
                    <a href="{{ route('admin.mess.client-types.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
