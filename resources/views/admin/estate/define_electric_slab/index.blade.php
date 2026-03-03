@extends('admin.layouts.master')

@section('title', 'Define Electric Slab - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Define Electric Slab" />

    <x-session_message />

    <div class="card shadow-sm border-0 border-start border-primary border-4">
        <div class="card-body p-4">
            <p class="text-muted small mb-3">This page displays all Electric Slab settings in the system and provides options to manage records such as add, edit, delete, excel download, print etc.</p>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <h1 class="h4 fw-bold text-dark mb-0">Define Electric Slab</h1>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.define-electric-slab.create') }}" class="btn btn-success">
                        <i class="bi bi-plus-lg me-1"></i> Add New
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                {!! $dataTable->table() !!}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush
