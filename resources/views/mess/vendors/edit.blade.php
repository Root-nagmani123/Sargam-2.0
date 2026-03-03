@extends('admin.layouts.master')
@section('title', 'Edit Mess Vendor')
@section('setup_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h4 class="mb-1">Edit Vendor</h4>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.mess.vendors.update', $vendor->id) }}">
                @csrf
                @method('PUT')

                @include('mess.vendors._form', ['vendor' => $vendor])

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Update</button>
                    <a href="{{ route('admin.mess.vendors.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
