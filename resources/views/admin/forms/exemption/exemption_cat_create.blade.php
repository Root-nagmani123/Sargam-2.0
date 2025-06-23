@extends('admin.layouts.master')

@section('title', 'Add Exemption')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold">Add New Exemption</h4>
            <a href="{{ route('admin.exemptionIndex') }}" class="btn btn-secondary">← Back to List</a>
        </div>

        <div class="card card-body">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!--display errors if any -->
            @if ($errors->any())
                <div class="alert alert-danger mb-3">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('exemptionStore') }}">
                @csrf

                <div class="mb-3">
                    <label for="Exemption_name" class="form-label">Exemption Heading <span
                            class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="Exemption_name" id="Exemption_name"
                        value="{{ old('Exemption_name') }}" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Exemption Subheading <span
                            class="text-danger">*</span></label>
                    <textarea class="form-control" name="description" id="description" rows="4" required>{{ old('description') }}</textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary px-4">Save</button>
                </div>
            </form>

        </div>
    </div>
@endsection
