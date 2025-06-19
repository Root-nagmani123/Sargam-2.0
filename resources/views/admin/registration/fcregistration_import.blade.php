{{-- @extends('admin.layouts.master')
@section('title', 'Bulk Upload Registration')

@section('content')
    <div class="container mt-4">
        @if (session('success'))
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

        <form action="{{ route('admin.registration.preview') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="file" class="form-label">Upload Excel File <span class="text-danger">*</span></label>
                <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                <small class="form-text text-muted">
                    Supported formats: .xlsx, .xls, .csv
                </small>
            </div>

            <button type="submit" class="btn btn-primary">Preview</button>
        </form>

    </div>
@endsection --}}


@extends('admin.layouts.master')

@section('title', 'Bulk Upload Registration')

@section('content')
    <div class="container py-5">
        <div class="mx-auto" style="max-width: 900px;">
            <div class="bg-white p-4 rounded-3 shadow-sm">
                <h3 class="fw-bold mb-2" style="color: #004a93;">Bulk Registration Upload</h3>
                <p class="text-muted mb-4">
                    Upload an Excel or CSV file containing user registration data. Please ensure your file follows the
                    required format. This helps streamline the bulk registration process and saves time.
                </p>

                @if (session('success'))
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

                <form action="{{ route('admin.registration.preview') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="file" class="form-label fw-semibold">
                            Upload Excel File <span class="text-danger">*</span>
                        </label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <small class="form-text text-muted">
                            Supported formats: <code>.xlsx</code>, <code>.xls</code>, <code>.csv</code>
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i> Preview Upload
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

