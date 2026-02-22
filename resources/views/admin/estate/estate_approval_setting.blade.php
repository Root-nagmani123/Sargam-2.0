@extends('admin.layouts.master')

@section('title', 'Estate Approval Setting - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4 py-2">
    <x-breadcrum title="Estate Approval Setting" />

    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div class="flex-grow-1 min-w-0">
                    <h1 class="h4 fw-bold text-body-emphasis mb-2">Estate Approval Setting</h1>
                    <p class="text-body-secondary small mb-0 lh-sm">This page displays all estate approval settings in the system and provides options to manage records such as add, edit, delete, excel download, print etc.</p>
                </div>
                <div class="flex-shrink-0 d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.add-approved-request-house') }}" class="btn btn-primary px-3" title="Add Approved Request House">
                        <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Add Approved Request House
                    </a>
                </div>
            </div>

<<<<<<< HEAD
            <div class="table-responsive estate-approval-table-wrap">
=======
            <div class="table-responsive table-scroll-vertical">
>>>>>>> 693d3a5f (estate approval setting delete button)
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
