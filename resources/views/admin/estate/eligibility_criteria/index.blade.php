@extends('admin.layouts.master')

@section('title', 'Eligibility - Criteria - Sargam')

@php
    // Add / Edit is a plain form POST (same as the master screens, see
    // partials/master_crud.blade.php); on a validation error the controller redirects
    // back and this page reopens the modal with the errors and old() values.
    $reopen = $errors->any() && old('_em_form') === 'eligibility';
    $reopenPk = $reopen ? (string) old('_em_edit_pk', '') : '';
    $isReopenEdit = $reopen && $reopenPk !== '';
    $updateTemplate = route('admin.estate.eligibility-criteria.update', ['id' => '__ID__']);
    $selects = [
        ['name' => 'salary_grade_master_pk', 'label' => 'Pay Scale', 'options' => $payScales ?? collect(), 'placeholder' => 'Select pay scale'],
        ['name' => 'estate_unit_type_master_pk', 'label' => 'Unit Type', 'options' => $unitTypes ?? collect(), 'placeholder' => 'Select unit type'],
        ['name' => 'estate_unit_sub_type_master_pk', 'label' => 'Unit Sub-Type', 'options' => $unitSubTypes ?? collect(), 'placeholder' => 'Select unit sub type'],
    ];
@endphp

@section('setup_content')
{{-- Figma "Eligibility Criteria": same list pattern as partials/master_crud. --}}
<div class="container-fluid rfe-page em-page em-list-page">
    <x-breadcrum title="Eligibility - Criteria" :showBack="false">
        <button type="button" class="btn ds-btn-primary em-btn-add" id="eligibilityAddBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Eligibility/ Criteria</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    @include('admin.estate.partials.export_actions', ['prefix' => 'eligibility', 'formats' => ['csv']])

    <div class="card overflow-hidden em-grid-card">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="eligibilityBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#eligibilityColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="eligibilityDtSearch" class="programme-dt-search" data-dt-search-for="eligibilityCriteriaTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['aria-label' => 'Eligibility criteria']) !!}
                </div>
                {{-- Yajra paginates through DataTables, so the global UI fills this slot. --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="eligibilityCriteriaTable"></div>
            </div>
        </div>
    </div>
</div>

{{-- Columns (EstateReportGrid.columnVisibility fills the grid). --}}
<div class="modal fade" id="eligibilityColumnVisibilityModal" tabindex="-1" aria-labelledby="eligibilityColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="eligibilityColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="eligibilityColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Add / Edit — one modal for both (§3c). --}}
<div class="modal fade ds-modal em-modal" id="eligibilityFormModal" tabindex="-1"
    aria-labelledby="eligibilityFormModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="eligibilityForm" method="POST"
                action="{{ $isReopenEdit ? str_replace('__ID__', $reopenPk, $updateTemplate) : route('admin.estate.eligibility-criteria.store') }}">
                @csrf
                <input type="hidden" name="_method" id="eligibilityFormMethod" value="{{ $isReopenEdit ? 'PUT' : 'POST' }}">
                <input type="hidden" name="_em_form" value="eligibility">
                <input type="hidden" name="_em_edit_pk" id="eligibilityEditPk" value="{{ $reopenPk }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="eligibilityFormModalLabel">{{ $isReopenEdit ? 'Edit' : 'Add' }} Eligibility/ Criteria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @foreach($selects as $s)
                    @php
                        $id = 'elig_' . $s['name'];
                        $err = $reopen ? $errors->first($s['name']) : null;
                        $sel = $reopen ? (string) old($s['name'], '') : '';
                    @endphp
                    <div class="{{ $loop->last ? 'mb-0' : 'mb-2' }}">
                        <label class="ds-form-label" for="{{ $id }}">{{ $s['label'] }}<span class="ds-req" aria-hidden="true">*</span></label>
                        <select class="form-select @if($err) is-invalid @endif" id="{{ $id }}" name="{{ $s['name'] }}"
                            data-em-field="{{ $s['name'] }}" data-searchable="true" data-placeholder="{{ $s['placeholder'] }}"
                            required aria-required="true"
                            @if($err) aria-invalid="true" aria-describedby="{{ $id }}_error" @endif>
                            <option value="">{{ $s['placeholder'] }}</option>
                            @foreach($s['options'] as $pk => $label)
                            <option value="{{ $pk }}" @selected($sel === (string) $pk)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if($err)
                        <div class="invalid-feedback js-em-error" id="{{ $id }}_error">{{ $err }}</div>
                        @endif
                    </div>
                    @endforeach
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ds-btn-primary" id="eligibilityFormSubmit">{{ $isReopenEdit ? 'Update' : 'Add' }} Eligibility/ Criteria</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('admin.estate.partials.master_delete_modal', ['key' => 'eligibility'])
@endsection

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@include('admin.estate.partials.master_styles')
@endpush

@push('scripts')
{!! $dataTable->scripts(attributes: ['type' => 'module']) !!}
@include('admin.estate.partials.report_print_assets')
<script>
$(function () {
    var storeUrl = @json(route('admin.estate.eligibility-criteria.store'));
    var updateTemplate = @json($updateTemplate);
    var $form = $('#eligibilityForm');
    var formModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('eligibilityFormModal'));
    var deleteModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('eligibilityDeleteModal'));

    function clearErrors() {
        $form.find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid aria-describedby');
        $form.find('.js-em-error').remove();
    }

    function openForm(pk, values) {
        var isEdit = !!pk;
        clearErrors();
        $('#eligibilityFormModalLabel').text((isEdit ? 'Edit' : 'Add') + ' Eligibility/ Criteria');
        $('#eligibilityFormSubmit').text((isEdit ? 'Update' : 'Add') + ' Eligibility/ Criteria').prop('disabled', false);
        $form.attr('action', isEdit ? updateTemplate.replace('__ID__', pk) : storeUrl);
        $('#eligibilityFormMethod').val(isEdit ? 'PUT' : 'POST');
        $('#eligibilityEditPk').val(isEdit ? pk : '');
        $form.find('[data-em-field]').each(function () {
            var name = $(this).data('em-field');
            // Select2 only repaints on change.select2 (new-design-index-page.md §2).
            $(this).val(isEdit && values && values[name] != null ? String(values[name]) : '').trigger('change.select2');
        });
        formModal.show();
    }

    $('#eligibilityAddBtn').on('click', function () { openForm(null); });

    // Delegated: Yajra re-renders the rows on every draw.
    $(document).on('click', '#eligibilityCriteriaTable .js-em-edit', function () {
        openForm(String($(this).data('pk')), $(this).data('values') || {});
    });

    $(document).on('click', '#eligibilityCriteriaTable .js-em-delete', function () {
        $('#eligibilityDeleteForm').attr('action', $(this).data('url'));
        deleteModal.show();
    });

    $('#eligibilityFormModal').on('shown.bs.modal', function () {
        var $first = $form.find('.is-invalid').first();
        if (!$first.length) $first = $form.find('[data-em-field]').first();
        // A Select2-enhanced select is hidden; open its widget instead of focusing it.
        if ($first.data('select2')) { $first.select2('focus'); } else { $first.trigger('focus'); }
    });

    $form.on('submit', function () { $('#eligibilityFormSubmit').prop('disabled', true); });
    $(window).on('pageshow', function () { $('#eligibilityFormSubmit').prop('disabled', false); });

    @if($reopen)
    formModal.show();
    @endif

    /* ---------- Columns, Download (CSV) and Print: every row, visible columns ---------- */
    // EstateReportGrid (public/js/estate-report-grid.js) is the Estate grid helper:
    // Columns modal, row collection, CSV, and the branded print (hidden iframe).
    $(document).on('init.dt', function (e, settings) {
        if (settings.nTable.id !== 'eligibilityCriteriaTable') return;
        EstateReportGrid.columnVisibility($(settings.nTable).DataTable(), {
            grid: '#eligibilityColumnToggleGrid',
            storageKey: 'estate.master.eligibility.hiddenCols',
            skip: ['S. No.', 'Action']
        });
    });

    function withEveryRow($btn, fn) {
        var table = document.getElementById('eligibilityCriteriaTable');
        if (!table || !$.fn.DataTable.isDataTable(table) || !window.EstateReportGrid) return;
        var api = $(table).DataTable();
        $btn.prop('disabled', true);
        EstateReportGrid.withAllRows(api, function () {
            fn(api, EstateReportGrid.collectRows(api, { skip: ['Action'] }));
            $btn.prop('disabled', false);
        });
    }

    $(document).on('click', '[data-export-for="eligibility"]', function () {
        withEveryRow($(this), function (api, data) {
            EstateReportGrid.downloadCsv('Eligibility Criteria', data.headers, data.rows);
        });
    });

    $('#eligibilityPrintBtn').on('click', function () {
        withEveryRow($(this), function (api, data) {
            EstateReportGrid.printGrid({
                title: 'Eligibility - Criteria',
                meta: api.search() ? ['Search: "' + api.search() + '"'] : [],
                headers: data.headers,
                rows: data.rows
            });
        });
    });
});
</script>
@endpush
