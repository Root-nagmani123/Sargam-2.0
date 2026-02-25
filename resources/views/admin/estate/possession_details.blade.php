@extends('admin.layouts.master')

@section('title', 'Possession Details - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Possession Details"></x-breadcrum>
    <x-estate-workflow-stepper current="possession-details" />

    <div class="card border-0 shadow-sm rounded-3 border-start border-4 border-primary">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-md-row flex-wrap align-items-start align-items-md-center justify-content-between gap-3 mb-4 no-print">
                <div>
                    <h1 class="h4 fw-semibold mb-1">Possession Details</h1>
                    <p class="text-muted small mb-0">LBSNAA employee possession records (allotted via HAC Approved flow).</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.possession-details.create') }}" class="btn btn-success btn-sm d-inline-flex align-items-center gap-2" title="Add possession">
                        <i class="bi bi-plus-lg"></i>
                        <span>Add</span>
                    </a>
                    <a href="{{ route('admin.estate.possession-for-others') }}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2">
                        <i class="bi bi-arrow-right-circle"></i>
                        <span>Possession for Other</span>
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2 flex-shrink-0" aria-hidden="true"></i>
                    <span class="flex-grow-1">{{ session('success') }}</span>
                    <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 flex-shrink-0" aria-hidden="true"></i>
                    <span class="flex-grow-1">{{ session('error') }}</span>
                    <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <hr class="my-4">
            <div class="table-responsive overflow-auto rounded-3">
                {!! $dataTable->table(['class' => 'table table-bordered table-striped table-hover align-middle text-nowrap mb-0 w-100', 'style' => 'min-width: 1200px;', 'id' => 'estatePossessionDetailsTable', 'aria-describedby' => 'estate-possession-details-caption']) !!}
            </div>
            <div id="estate-possession-details-caption" class="visually-hidden">Possession details list</div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #estatePossessionDetailsTable_wrapper .dataTables_length label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    #estatePossessionDetailsTable_wrapper .dataTables_length select {
        width: auto;
        min-width: 4.5rem;
        padding: 0.25rem 2rem 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: var(--bs-border-radius);
        border: 1px solid var(--bs-border-color, #dee2e6);
    }
    #estatePossessionDetailsTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    #estatePossessionDetailsTable_wrapper .dataTables_filter input {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
    }
    #estatePossessionDetailsTable_wrapper thead th {
        background-color: var(--bs-primary);
        color: #fff;
        font-weight: 600;
        border-color: var(--bs-primary);
        padding: 0.75rem;
        white-space: nowrap;
    }
</style>
@endpush

@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush
