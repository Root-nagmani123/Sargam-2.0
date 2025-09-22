@extends('admin.layouts.master')

@section('title', 'Import Students')

@push('styles')
    <style>
        body {
            background-color: #f8f9fa;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            color: white;
            font-size: 1.25rem;
            font-weight: bold;
            border-radius: 15px 15px 0 0;
        }

        .btn-primary {
            padding: 10px 20px;
            font-weight: 500;
            font-size: 1rem;
            border-radius: 8px;
            transition: 0.3s;
        }

        .btn-primary:hover {
            transform: scale(1.05);
        }

        .status-message {
            font-size: 0.95rem;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid mt-5">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @elseif(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card mx-auto">
            <div class="card-body text-center">
                <p class="mb-4 text-muted status-message">
                    Click the button below to import students from the source table into the system.
                </p>
                <form action="{{ route('admin.migrate.fc') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        Migrate Students
                    </button>
                </form>

            </div>
        </div>
    </div>
@endsection
