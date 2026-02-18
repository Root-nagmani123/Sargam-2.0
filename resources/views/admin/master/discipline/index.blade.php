@extends('admin.layouts.master')

@section('title', 'Discipline Master')

@section('setup_content')
<div class="container-fluid py-3 py-md-4">
    <x-breadcrum title="Discipline Master"></x-breadcrum>
    
    <div class="card shadow-sm border-0 border-start border-4 border-primary rounded-3">
        <div class="card-header bg-white border-0 pb-0 pt-3 px-4">
            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-6">
                    <h4 class="mb-0 fw-bold text-dark d-flex align-items-center">
                        <span class="material-icons material-symbols-rounded me-2 text-primary">category</span>
                        Discipline Master
                    </h4>
                </div>
                <div class="col-12 col-md-6">
                    <div class="d-flex justify-content-md-end justify-content-start">
                        <a href="{{ route('master.discipline.create') }}"
                            class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm w-md-100 justify-content-center justify-content-md-start">
                            <span class="material-icons material-symbols-rounded fs-5">add</span>
                            <span>Add Discipline</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-body px-4 pb-4">
            <div class="table-responsive">
                {!! $dataTable->table(['class' => 'table text-nowrap align-middle mb-0']) !!}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush