@extends('admin.layouts.master')

@section('title', 'Define Pay Scale - Sargam')

{{-- Same list pattern as the other Estate Master screens (partials/master_crud).
     Add / Edit stay full-page forms (define_pay_scale/form); the controller hands
     over the full list and the client-side DataTable pages, searches and exports it. --}}
@section('setup_content')
<div class="container-fluid rfe-page em-page em-list-page">
    <x-breadcrum title="Define Pay Scale" :showBack="false">
        <a href="{{ route('admin.estate.define-pay-scale.create') }}" class="btn ds-btn-primary em-btn-add">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Pay Scale Mapping</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    @include('admin.estate.partials.export_actions', ['prefix' => 'payScale', 'formats' => ['csv']])

    <div class="card overflow-hidden em-grid-card">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="payScaleBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#payScaleColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="payScaleDtSearch" class="programme-dt-search" data-dt-search-for="payScaleTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="payScaleTable" class="table align-middle mb-0 w-100 programme-dt-table em-table" aria-label="Pay scale mappings">
                        <thead>
                            <tr>
                                <th scope="col" class="em-col-sno">S. No.</th>
                                <th scope="col">Pay Scale / Salary Grade</th>
                                <th scope="col">Unit Type</th>
                                <th scope="col">Unit Sub Type</th>
                                <th scope="col" class="em-col-action no-sort">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $row)
                            <tr>
                                <td class="em-col-sno">{{ $loop->iteration }}</td>
                                <td>{{ $row->salaryGrade?->display_label_text ?: '-' }}</td>
                                <td>{{ $row->unitType?->name ?: '-' }}</td>
                                <td>{{ $row->unitSubType?->name ?: '-' }}</td>
                                <td class="em-col-action">
                                    <div class="rfe-actions" role="group">
                                        <a href="{{ route('admin.estate.define-pay-scale.edit', $row->pk) }}" class="rfe-action rfe-action--edit"
                                            title="Edit" aria-label="Edit {{ $row->salaryGrade?->display_label_text ?? 'row ' . $row->pk }}">
                                            <i class="material-icons material-symbols-rounded" aria-hidden="true">edit</i>
                                            <span class="rfe-action-label">Edit</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="payScaleTable"></div>
            </div>
        </div>
    </div>
</div>

{{-- Columns (EstateReportGrid.columnVisibility fills the grid). --}}
<div class="modal fade" id="payScaleColumnVisibilityModal" tabindex="-1" aria-labelledby="payScaleColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="payScaleColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="payScaleColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@include('admin.estate.partials.master_styles')
@endpush

@push('scripts')
@include('admin.estate.partials.report_print_assets')
<script>
$(function () {
    var dt = $('#payScaleTable').DataTable({
        order: [],
        responsive: false,
        language: { emptyTable: 'No pay scale mappings yet — use “Add Pay Scale Mapping” to add one.' },
        columnDefs: [{ targets: [0, -1], orderable: false, searchable: false }]
    });

    // S. No. follows what is on screen, not the server's original position.
    dt.on('draw.dt', function () {
        var start = dt.page.info().start;
        dt.column(0, { page: 'current' }).nodes().each(function (cell, i) { cell.textContent = start + i + 1; });
    });

    EstateReportGrid.columnVisibility(dt, {
        grid: '#payScaleColumnToggleGrid',
        storageKey: 'estate.master.payScale.hiddenCols',
        skip: ['S. No.', 'Action']
    });

    function rows() { return EstateReportGrid.collectRows(dt, { skip: ['Action'], serial: 'S. No.' }); }

    $(document).on('click', '[data-export-for="payScale"]', function () {
        var data = rows();
        EstateReportGrid.downloadCsv('Define Pay Scale', data.headers, data.rows);
    });
    $('#payScalePrintBtn').on('click', function () {
        var data = rows();
        EstateReportGrid.printGrid({
            title: 'Define Pay Scale',
            meta: dt.search() ? ['Search: "' + dt.search() + '"'] : [],
            headers: data.headers,
            rows: data.rows
        });
    });
});
</script>
@endpush
