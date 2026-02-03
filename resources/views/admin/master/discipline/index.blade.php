@extends('admin.layouts.master')

@section('title', 'Discipline Master')

@section('setup_content')
<style>
/* Discipline Master - responsive (mobile/tablet only, desktop unchanged) */
@media (max-width: 991.98px) {
    .discipline-index .datatables .table-responsive {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }
    .discipline-index .datatables #discipline-table {
        min-width: 400px;
    }
    .discipline-index .datatables #discipline-table th,
    .discipline-index .datatables #discipline-table td {
        padding: 8px 10px;
        font-size: 0.9rem;
    }
}
@media (max-width: 767.98px) {
    .discipline-index .datatables .card-body {
        padding: 1rem !important;
    }
    .discipline-index .datatables #discipline-table th,
    .discipline-index .datatables #discipline-table td {
        padding: 6px 8px;
        font-size: 0.85rem;
    }
}
@media (max-width: 575.98px) {
    .discipline-index.container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    .discipline-index .discipline-header-row {
        flex-direction: column;
        gap: 0.5rem;
    }
    .discipline-index .discipline-header-row .col-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    .discipline-index .discipline-header-row .d-flex.justify-content-end {
        justify-content: stretch !important;
    }
    .discipline-index .discipline-header-row .add-btn {
        width: 100%;
        justify-content: center;
    }
    .discipline-index .datatables .card-body {
        padding: 0.75rem !important;
    }
    .discipline-index .datatables .table-responsive {
        margin-left: -0.5rem;
        margin-right: -0.5rem;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    .discipline-index .datatables #discipline-table th,
    .discipline-index .datatables #discipline-table td {
        padding: 6px 8px;
        font-size: 0.8125rem;
    }
}
@media (max-width: 375px) {
    .discipline-index.container-fluid {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    .discipline-index .datatables .card-body {
        padding: 0.5rem !important;
    }
    .discipline-index .discipline-header-row h4 {
        font-size: 1.1rem;
    }
}
</style>
<div class="container-fluid discipline-index">
<x-breadcrum title="Discipline Master"></x-breadcrum>
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="row discipline-header-row">
                    <div class="col-6">
                        <h4>Discipline Master</h4>
                    </div>
                    <div class="col-6">
                        <div class="d-flex justify-content-end align-items-center gap-2">
                            <a href="{{ route('master.discipline.create') }}"
                                class="btn btn-primary d-flex align-items-center add-btn">
                                <i class="material-icons menu-icon material-symbols-rounded"
                                    style="font-size: 24px;">add</i>
                                Add Discipline
                            </a>
                        </div>
                    </div>
                </div>
                <hr>

                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table']) !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush