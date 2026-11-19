@extends('admin.layouts.master')

@section('title', 'Estate Approval Setting - Sargam')

{{-- Same list pattern as the other Estate Master screens (eligibility_criteria/index).
     Edit goes to Add Approved Request House for that approver; Delete posts the
     existing DELETE route through the shared confirm dialog. --}}
@section('setup_content')
<div class="container-fluid rfe-page em-page em-list-page">
    <x-breadcrum title="Estate Approval Setting" :showBack="false">
        <a href="{{ route('admin.estate.add-approved-request-house') }}" class="btn ds-btn-primary em-btn-add">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Approved Request House</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    @include('admin.estate.partials.export_actions', ['prefix' => 'approval', 'formats' => ['csv']])

    <div class="card overflow-hidden em-grid-card">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="approvalBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#approvalColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    {{-- datatable-global-ui.js moves the DataTables filter into this slot. --}}
                    <div id="approvalDtSearch" class="programme-dt-search" data-dt-search-for="estateApprovalSettingTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['aria-label' => 'Estate approval settings']) !!}
                </div>
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="estateApprovalSettingTable"></div>
            </div>
        </div>
    </div>
</div>

{{-- Columns (EstateReportGrid.columnVisibility fills the grid). --}}
<div class="modal fade" id="approvalColumnVisibilityModal" tabindex="-1" aria-labelledby="approvalColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="approvalColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="approvalColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@include('admin.estate.partials.master_delete_modal', ['key' => 'approval'])
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@include('admin.estate.partials.master_styles')
@endpush

@push('scripts')
{!! $dataTable->scripts(attributes: ['type' => 'module']) !!}
@include('admin.estate.partials.report_print_assets')
<script>
$(function () {
    var deleteModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('approvalDeleteModal'));

    // Delegated: Yajra re-renders the rows on every draw.
    $(document).on('click', '#estateApprovalSettingTable .js-em-delete', function () {
        $('#approvalDeleteForm').attr('action', $(this).data('url'));
        deleteModal.show();
    });

    $(document).on('init.dt', function (e, settings) {
        if (settings.nTable.id !== 'estateApprovalSettingTable') return;
        EstateReportGrid.columnVisibility($(settings.nTable).DataTable(), {
            grid: '#approvalColumnToggleGrid',
            storageKey: 'estate.master.approval.hiddenCols',
            skip: ['S. No.', 'Action']
        });
    });

    // Download (CSV) + Print: every row the search matches, visible columns only.
    function withEveryRow($btn, fn) {
        var table = document.getElementById('estateApprovalSettingTable');
        if (!table || !$.fn.DataTable.isDataTable(table) || !window.EstateReportGrid) return;
        var api = $(table).DataTable();
        $btn.prop('disabled', true);
        EstateReportGrid.withAllRows(api, function () {
            fn(api, EstateReportGrid.collectRows(api, { skip: ['Action'], serial: 'S. No.' }));
            $btn.prop('disabled', false);
        });
    }

    $(document).on('click', '[data-export-for="approval"]', function () {
        withEveryRow($(this), function (api, data) {
            EstateReportGrid.downloadCsv('Estate Approval Setting', data.headers, data.rows);
        });
    });

    $('#approvalPrintBtn').on('click', function () {
        withEveryRow($(this), function (api, data) {
            EstateReportGrid.printGrid({
                title: 'Estate Approval Setting',
                meta: api.search() ? ['Search: "' + api.search() + '"'] : [],
                headers: data.headers,
                rows: data.rows
            });
        });
    });
});
</script>
@endpush
