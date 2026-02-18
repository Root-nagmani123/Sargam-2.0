@extends('admin.layouts.master')

@section('title', 'Discipline Master')

@section('setup_content')
<div class="container-fluid discipline-master-index py-3 py-md-4 px-2 px-sm-3 px-md-4">
    <x-breadcrum title="Discipline Master"></x-breadcrum>

    <div class="card discipline-master-card border-0 border-start border-4 border-primary shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-body-secondary bg-opacity-50 border-0 py-3 py-md-4 px-3 px-md-4">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-6">
                    <h1 class="h5 mb-0 fw-semibold text-body-emphasis d-flex align-items-center gap-2">
                        <span class="material-icons material-symbols-rounded text-primary opacity-90" style="font-size: 1.5rem;">category</span>
                        Discipline Master
                    </h1>
                    <p class="text-body-secondary small mb-0 mt-1 opacity-90">Manage disciplines, mark deduction and status.</p>
                </div>
                <div class="col-12 col-md-6">
                    <div class="d-flex justify-content-md-end justify-content-start">
                        <a href="{{ route('master.discipline.create') }}"
                            class="btn btn-primary d-inline-flex align-items-center gap-2 rounded-2 px-3 shadow-sm focus-ring focus-ring-primary">
                            <span class="material-icons material-symbols-rounded" style="font-size: 1.25rem;">add</span>
                            <span>Add Discipline</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-3 p-md-4">
            <div class="table-responsive rounded-2">
                {!! $dataTable->table(['class' => 'table table-hover table-striped align-middle mb-0']) !!}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush