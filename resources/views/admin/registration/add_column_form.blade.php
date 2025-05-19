@extends('admin.layouts.master')

@section('title', 'Add Column - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Add Column</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="{{ route('dashboard') }}">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Add Column
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Start Form Card -->
    <div class="card mt-4">
        <div class="card-body">
            <h4 class="card-title mb-3">Add New Column to Table</h4>
            <hr>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('admin.column.add') }}" method="POST">
                @csrf
                <div class="row">
                    <!-- Table Name -->
                    <div class="col-sm-6">
                        <label for="tablename" class="form-label">Table Name:</label>
                        <div class="mb-3">
                            <input type="text" class="form-control" id="tablename" name="tablename" value="{{ old('tablename') }}" placeholder="Enter Table Name">
                            @error('tablename') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <!-- Column Name -->
                    <div class="col-sm-6">
                        <label for="columnname" class="form-label">Column Name:</label>
                        <div class="mb-3">
                            <input type="text" class="form-control" id="columnname" name="columnname" value="{{ old('columnname') }}" placeholder="Enter Column Name">
                            @error('columnname') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <!-- Data Type -->
                    <div class="col-sm-6">
                        <label for="datatype" class="form-label">Data Type:</label>
                        <div class="mb-3">
                            <select class="form-select" id="datatype" name="datatype">
                                <option value="">Select Data Type</option>
                                @foreach(['VARCHAR', 'TEXT', 'DATE'] as $type)
                                    <option value="{{ $type }}" {{ old('datatype') == $type ? 'selected' : '' }}>{{ $type }}</option>
                                @endforeach
                            </select>
                            @error('datatype') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <!-- Length -->
                    <div class="col-sm-6">
                        <label for="length" class="form-label">Length (for INT/VARCHAR):</label>
                        <div class="mb-3">
                            <input type="text" class="form-control" id="length" name="length" value="{{ old('length') }}" placeholder="Optional Length">
                            @error('length') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <!-- Default Value -->
                    <div class="col-sm-6">
                        <label for="defaultvalue" class="form-label">Default Value:</label>
                        <div class="mb-3">
                            <input type="text" class="form-control" id="defaultvalue" name="defaultvalue" value="{{ old('defaultvalue') }}" placeholder="Optional Default Value">
                            @error('defaultvalue') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <!-- Nullable Toggle -->
                    <div class="col-sm-6">
                        <label class="form-label">Nullable:</label>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="nullable" name="nullable" {{ old('nullable') ? 'checked' : '' }}>
                            <label class="form-check-label" for="nullable">Allow NULL values</label>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-2 float-end" type="submit">
                        <i class="material-icons menu-icon">add</i> Add Column
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- End Form Card -->
</div>

@endsection
