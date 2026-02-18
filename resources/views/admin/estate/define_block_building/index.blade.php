@extends('admin.layouts.master')

@section('title', 'Define Block/Building - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Define Block/Building" />
    <x-session_message />

    <div class="card shadow-sm border-0 overflow-hidden" style="border-left: 4px solid #0d6efd;">
        <div class="card-body p-4">
            <div class="row align-items-center g-3 mb-4">
                <div class="col-12 col-md">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div>
                            <h1 class="h5 fw-bold text-dark mb-0">Define Block/Building</h1>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-auto">
                    <a href="{{ route('admin.estate.define-block-building.create') }}" class="btn btn-primary rounded-1 px-3 shadow-sm">
                        <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size: 1.1rem;">add</i>
                        Add New
                    </a>
                </div>
            </div>
            <hr class="my-2">
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
