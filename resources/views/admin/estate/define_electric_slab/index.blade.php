@extends('admin.layouts.master')

@section('title', 'Define Electric Slab - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Define Electric Slab" />

    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div>
                    <h1 class="h4 fw-bold text-dark mb-1">Define Electric Slab</h1>
                    <p class="text-muted small mb-0">This page displays all electric slab settings in the system and provides options to manage records such as add, edit, delete, excel download, print etc.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.define-electric-slab.create') }}" class="btn btn-success" title="Add">
                        <i class="material-icons material-symbols-rounded">add</i> Add
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                {!! $dataTable->table([
                    'class' => 'table text-nowrap align-middle mb-0',
                    'aria-describedby' => 'electric-slab-caption'
                ]) !!}
            </div>
            <div id="electric-slab-caption" class="visually-hidden">Define Electric Slab list</div>
        </div>
    </div>
</div>

@push('scripts')
    {!! $dataTable->scripts(attributes: ['type' => 'module']) !!}
@endpush
@endsection
