@extends('admin.layouts.master')
@section('title', 'Bulk Upload Registration')

@section('content')
<div class="container mt-4">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Import failed:</strong> {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('admin.registration.import') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label for="file" class="form-label">Upload Excel File</label>
            <input type="file" name="file" class="form-control" required>
            <small class="text-muted">Only .xlsx, .xls or .csv allowed</small>
        </div>
        <button type="submit" class="btn btn-primary">Import</button>
    </form>
</div>
@endsection
