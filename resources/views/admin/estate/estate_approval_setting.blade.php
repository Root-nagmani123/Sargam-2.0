@extends('admin.layouts.master')

@section('title', 'Estate Approval Setting - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Estate Approval Setting" />

    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Estate Approval Setting</h1>
                    <p class="text-muted small mb-0">This page displays all estate approval settings in the system and provides options to manage records such as add, edit, delete, excel download, print etc.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.add-approved-request-house') }}" class="btn btn-success" title="Add">
                        <i class="bi bi-plus-lg me-1"></i> Add Approved Request House
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                {!! $dataTable->table([
                    'class' => 'table text-nowrap align-middle mb-0',
                    'aria-describedby' => 'estate-approval-caption'
                ]) !!}
            </div>
            <div id="estate-approval-caption" class="visually-hidden">Estate Approval Setting list</div>
        </div>
    </div>
</div>

@push('scripts')
    {!! $dataTable->scripts(attributes: ['type' => 'module']) !!}
@endpush
@endsection
