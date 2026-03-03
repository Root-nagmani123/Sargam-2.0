@extends('admin.layouts.master')

@section('title', 'Eligibility - Criteria - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Protocol</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.estate.request-for-others') }}">Estate Management</a></li>
            <li class="breadcrumb-item active" aria-current="page">Eligibility - Criteria</li>
        </ol>
    </nav>

    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden" style="border-left: 4px solid #0d6efd;">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div class="flex-grow-1 min-w-0">
                    <h1 class="h4 fw-bold text-body-emphasis mb-2">Eligibility - Criteria</h1>
                    <p class="text-body-secondary small mb-0 lh-sm">This page displays all the Estate Eligibility Block Mapping added in the system and provides options such as add, edit, delete, excel upload, print etc.</p>
                </div>
                <div class="flex-shrink-0 d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.eligibility-criteria.create') }}" class="btn btn-success px-3" title="Add New"><i class="bi bi-plus-lg me-1"></i> Add New</a>
                    <button type="button" class="btn btn-outline-secondary px-3" onclick="window.print()" title="Print"><i class="bi bi-printer"></i></button>
                </div>
            </div>

            <div class="table-responsive">
                {!! $dataTable->table([
                    'class' => 'table table-bordered table-striped table-hover align-middle mb-0',
                    'aria-describedby' => 'eligibility-criteria-caption'
                ]) !!}
            </div>
            <div id="eligibility-criteria-caption" class="visually-hidden">Eligibility Criteria list</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts(attributes: ['type' => 'module']) !!}
@endpush
