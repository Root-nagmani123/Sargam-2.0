@extends('admin.layouts.master')

@section('title', 'Define Unit Type - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
<x-breadcrum title="Define Unit Type" />

    <x-session_message />

    <div class="card shadow-sm border-0" style="border-left: 4px solid #0d6efd;">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h5 fw-bold text-dark mb-0">Define Unit Type</h1>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.define-unit-type.create') }}" class="btn btn-primary rounded-1 px-3 shadow-sm">
                        <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size: 1.1rem;">add</i>
                        Add New
                    </a>
                </div>
            </div>
<hr class="my-2">
            <div class="table-responsive unit-type-datatable-scroll">
                {!! $dataTable->table() !!}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush
