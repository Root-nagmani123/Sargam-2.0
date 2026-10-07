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
        ['name' => 'estate_unit_sub_type_master_pk', 'label' => 'Unit Sub Type', 'options' => $unitSubTypes ?? collect(), 'placeholder' => 'Select unit sub type'],
    ];
@endphp

@section('setup_content')
<div class="container-fluid em-page">
    <x-breadcrum title="Eligibility - Criteria" :showBack="false">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold text-nowrap"
            id="eligibilityAddBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Criteria</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- No status pills on this grid, so the export row sits alone on the right (§1). --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
        <button type="button" class="btn programme-dt-btn-columns border-0 text-primary" id="btnPrintEligibilityCriteria" title="Print the full list">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-1">
        <div class="card-body p-3 p-md-4">
            {{-- How a row is read: the rules themselves are the rows below. --}}
            <p class="em-flow mb-4" aria-label="How eligibility criteria work">
                <span class="em-flow__step"><i class="bi bi-cash-stack" aria-hidden="true"></i> Pay Scale</span>
                <span class="em-flow__arrow" aria-hidden="true">→</span>
                <span class="em-flow__step"><i class="bi bi-house" aria-hidden="true"></i> Unit Type</span>
                <span class="em-flow__arrow" aria-hidden="true">→</span>
                <span class="em-flow__step"><i class="bi bi-diagram-3" aria-hidden="true"></i> Unit Sub Type</span>
                <span class="em-flow__note">Each row makes employees on that pay scale eligible to request houses of that unit type and sub type.</span>
            </p>

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <div id="eligibilityDtSearch" class="programme-dt-search" data-dt-search-for="eligibilityCriteriaTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['aria-describedby' => 'eligibility-criteria-caption']) !!}
                </div>
            </div>
            <div id="eligibility-criteria-caption" class="visually-hidden">Eligibility Criteria list</div>

            {{-- Yajra paginates through DataTables, so the global UI fills this slot (§4A). --}}
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                data-dt-footer-for="eligibilityCriteriaTable"></div>
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
                    <h5 class="modal-title" id="eligibilityFormModalLabel">{{ $isReopenEdit ? 'Edit' : 'Add' }} Eligibility Criteria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @foreach($selects as $s)
                    @php
                        $id = 'elig_' . $s['name'];
                        $err = $reopen ? $errors->first($s['name']) : null;
                        $sel = $reopen ? (string) old($s['name'], '') : '';
                    @endphp
                    <div class="{{ $loop->last ? 'mb-0' : 'mb-3' }}">
                        <label class="form-label" for="{{ $id }}">{{ $s['label'] }}<span class="ds-req" aria-hidden="true">*</span></label>
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
                    <button type="submit" class="btn ds-btn-submit" id="eligibilityFormSubmit">{{ $isReopenEdit ? 'Update' : 'Add' }} Criteria</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete confirmation — the module's shared confirm dialog. --}}
<div class="modal fade ds-modal ds-modal-confirm" id="eligibilityDeleteModal" tabindex="-1"
    aria-labelledby="eligibilityDeleteModalLabel" aria-describedby="eligibilityDeleteText" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="eligibilityDeleteForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <div class="ds-confirm-icon" aria-hidden="true">!</div>
                    <h5 class="ds-confirm-title" id="eligibilityDeleteModalLabel">Delete eligibility criteria?</h5>
                    <p class="ds-confirm-text" id="eligibilityDeleteText">This action can't be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ds-btn-danger">Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@endpush

@push('scripts')
{!! $dataTable->scripts(attributes: ['type' => 'module']) !!}
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
        $('#eligibilityFormModalLabel').text((isEdit ? 'Edit' : 'Add') + ' Eligibility Criteria');
        $('#eligibilityFormSubmit').text((isEdit ? 'Update' : 'Add') + ' Criteria').prop('disabled', false);
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
        var name = String($(this).data('name') || '').trim();
        $('#eligibilityDeleteForm').attr('action', $(this).data('url'));
        $('#eligibilityDeleteText').text((name ? '“' + name + '” will be removed. ' : '') + "This action can't be undone.");
        deleteModal.show();
    });

    $('#eligibilityFormModal').on('shown.bs.modal', function () {
        var $first = $form.find('.is-invalid').first();
        if (!$first.length) $first = $form.find('[data-em-field]').first();
        // A Select2-enhanced select is hidden; open its widget instead of focusing it.
        if ($first.data('select2')) { $first.select2('focus'); } else { $first.trigger('focus'); }
    });

    $form.on('submit', function () { $('#eligibilityFormSubmit').prop('disabled', true); });
    $('#eligibilityDeleteForm').on('submit', function () { $(this).find('[type="submit"]').prop('disabled', true); });
    $(window).on('pageshow', function () {
        $('#eligibilityFormSubmit, #eligibilityDeleteForm [type="submit"]').prop('disabled', false);
    });

    @if($reopen)
    formModal.show();
    @endif

    /* ---------- Print: the full list, as on screen ---------- */
    function buildPrintableTableHtml(tableElement) {
        var clone = tableElement.cloneNode(true);
        // Drop the Action column from the printout.
        Array.from(clone.querySelectorAll('tr')).forEach(function (tr) {
            if (tr.lastElementChild) tr.removeChild(tr.lastElementChild);
        });
        return clone.outerHTML;
    }

    function openPrintWindow(tableHtml) {
        var win = window.open('', '_blank', 'width=1200,height=900');
        if (!win) {
            alert('Please allow popups to print this list.');
            return;
        }
        win.document.open();
        win.document.write(
            '<!doctype html><html><head><title>Eligibility - Criteria</title><style>' +
            'body{font-family:Arial,sans-serif;padding:16px;color:#111827;}' +
            'h2{margin:0 0 12px 0;font-size:20px;}' +
            'table{width:100%;border-collapse:collapse;font-size:12px;}' +
            'th,td{border:1px solid #d1d5db;padding:8px;vertical-align:top;text-align:left;}' +
            'th{background:#f3f4f6;font-weight:600;}' +
            '</style></head><body><h2>Eligibility - Criteria</h2>' + tableHtml + '</body></html>'
        );
        win.document.close();
        win.onafterprint = function () { win.close(); };
        setTimeout(function () { win.focus(); win.print(); }, 250);
    }

    $('#btnPrintEligibilityCriteria').on('click', function () {
        var table = document.getElementById('eligibilityCriteriaTable');
        if (!table) return;
        var api = $.fn.DataTable.isDataTable(table) ? $(table).DataTable() : null;
        if (!api) { openPrintWindow(buildPrintableTableHtml(table)); return; }

        // Print every row, then put the pager back the way it was.
        var originalLen = api.page.len();
        var originalPage = api.page();
        api.one('draw', function () {
            setTimeout(function () {
                openPrintWindow(buildPrintableTableHtml(table));
                setTimeout(function () {
                    api.page.len(originalLen);
                    api.page(originalPage);
                    api.draw(false);
                }, 500);
            }, 250);
        });
        api.page.len(-1).draw();
    });
});
</script>
@endpush
