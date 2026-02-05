@extends('admin.layouts.master')

@section('title', 'Discipline Master')

@section('setup_content')
<style>
/* Discipline Master - responsive (mobile/tablet only, desktop unchanged) */

/* Tablet and below */
@media (max-width: 991.98px) {
    .discipline-index .datatables .card {
        border-radius: 0.75rem;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
        border-left: 4px solid #004a93;
    }

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
        vertical-align: middle;
    }
}

/* Small tablet / large phone */
@media (max-width: 767.98px) {
    .discipline-index .datatables .card-body {
        padding: 1rem !important;
    }

    .discipline-index .datatables #discipline-table th,
    .discipline-index .datatables #discipline-table td {
        padding: 6px 8px;
        font-size: 0.85rem;
    }

    /* Stack "Show entries" and "Search" nicely on smaller screens */
    .discipline-index #discipline-table_wrapper .row:first-child,
    .discipline-index #discipline-table_wrapper .dt-row:first-child {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    }

    .discipline-index #discipline-table_wrapper .dataTables_length,
    .discipline-index #discipline-table_wrapper .dataTables_filter {
        text-align: left !important;
        margin-bottom: 0;
        display: block;
        width: 100%;
    }

    .discipline-index #discipline-table_wrapper .dataTables_length label,
    .discipline-index #discipline-table_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
        flex-wrap: wrap;
        font-size: 0.9rem;
    }

    .discipline-index #discipline-table_wrapper .dataTables_length select {
        margin: 0;
        min-width: 80px;
        max-width: 100%;
        min-height: 36px;
        padding: 0.35rem 1.75rem 0.35rem 0.5rem;
    }

    .discipline-index #discipline-table_wrapper .dataTables_filter input {
        margin-left: 0 !important;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        min-height: 36px;
        padding: 0.375rem 0.75rem;
    }

    /* Make action items stack vertically to avoid horizontal scroll on mobile */
    .discipline-index #discipline-table td:last-child {
        white-space: normal;
        text-align: left;
    }

    .discipline-index #discipline-table td:last-child a,
    .discipline-index #discipline-table td:last-child form {
        display: block;
        margin-bottom: 0.35rem;
        line-height: 1.2;
    }
}

/* Phone */
@media (max-width: 575.98px) {
    .discipline-index.container-fluid {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    /* On phones, stack header title and "Add Discipline" button cleanly */
    .discipline-index .discipline-header-row {
        flex-direction: column;
        gap: 0.5rem;
        margin-bottom: 0.25rem;
    }

    .discipline-index .discipline-header-row .col-6 {
        flex: 0 0 100%;
        max-width: 100%;
        display: flex;
        align-items: center;
    }

    .discipline-index .discipline-header-row .d-flex.justify-content-end {
        justify-content: flex-start !important;
    }

    .discipline-index .discipline-header-row .add-btn {
        width: 100%;
        justify-content: flex-start;
        gap: 0.35rem;
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

    /* DataTables info + pagination: stack nicely */
    .discipline-index #discipline-table_wrapper .dataTables_info,
    .discipline-index #discipline-table_wrapper .dataTables_paginate {
        float: none !important;
        text-align: center;
        width: 100%;
        margin-top: 0.5rem;
    }

    .discipline-index #discipline-table_wrapper .dataTables_paginate .pagination {
        justify-content: center;
        flex-wrap: wrap;
        gap: 0.25rem;
    }
}

/* Very small phones */
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

    .discipline-index .discipline-header-row .add-btn {
        font-size: 0.85rem;
        padding: 0.4rem 0.75rem;
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