@extends('admin.layouts.master')

@section('title', 'Define Pay Scale - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Define Pay Scale"></x-breadcrum>
    <x-session_message />

    <div class="card shadow-sm border-0" style="border-left: 4px solid #0d6efd;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Define Pay Scale</h1>
                    <p class="text-muted small mb-0">Manage pay scale range and level for eligibility mapping.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.define-pay-scale.create') }}" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i> Add New</a>
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
