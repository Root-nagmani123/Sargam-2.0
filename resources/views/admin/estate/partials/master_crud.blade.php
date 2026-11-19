{{--
    Estate master grid — one implementation for Define Estate/Campus, Unit Type,
    Unit Sub Type and Block/Building, so the four screens cannot drift apart.

    Layout follows the Figma Estate Master frames and the house list pattern the
    Estate Management grids already use (request_for_estate): header card with
    the Add button in the x-breadcrum slot → Download / Print row → card with the
    programme-dt toolbar (Columns + search) → programme-dt table → footer.
    Row actions are the shared icon-over-label .rfe-action buttons. Tokens and
    Layer C components per docs/design.md; page styles in master_styles.

    Add / Edit is a plain form POST to the controller's existing store / update
    routes — no AJAX, no controller change. A validation failure redirects back
    here with old() input, and the modal reopens with the errors in place. The two
    underscore fields (_em_form, _em_edit_pk) only tell this page which modal to
    reopen; the controllers validate named fields, so they never reach the model.

    Download (CSV) and Print are built in the browser from the grid's own rows
    (EstateReportGrid), so they always match the search and the Columns choice.

    Expects $cfg:
      key          short id prefix ('campus')
      title        page title
      singular     record noun for the dialog ('Estate/ Campus')
      addLabel     header button text (default 'Add ' . singular)
      routePrefix  'admin.estate.define-campus' (.store / .update / .destroy)
      tableId      DataTables id
      items        Collection of rows (full set — the grid pages client-side)
      fields       [['name','label','column','type' => text|textarea,'required','maxlength','placeholder','wrap']]
                   label = dialog label, column = table heading (defaults to label)
--}}
@php
    $key = $cfg['key'];
    $items = $cfg['items'];
    $fields = $cfg['fields'];
    $singular = $cfg['singular'];
    $reopen = $errors->any() && old('_em_form') === $key;
    $reopenPk = $reopen ? (string) old('_em_edit_pk', '') : '';
    $isReopenEdit = $reopen && $reopenPk !== '';
    $updateTemplate = route($cfg['routePrefix'] . '.update', ['id' => '__ID__']);
@endphp

<div class="container-fluid rfe-page em-page em-list-page">
    <x-breadcrum :title="$cfg['title']" :showBack="false">
        <button type="button" class="btn ds-btn-primary em-btn-add" id="{{ $key }}AddBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>{{ $cfg['addLabel'] ?? 'Add ' . $singular }}</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    @include('admin.estate.partials.export_actions', ['prefix' => $key, 'formats' => ['csv']])

    <div class="card overflow-hidden em-grid-card">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="{{ $key }}BtnColumns"
                        data-bs-toggle="modal" data-bs-target="#{{ $key }}ColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="{{ $key }}DtSearch" class="programme-dt-search" data-dt-search-for="{{ $cfg['tableId'] }}"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="{{ $cfg['tableId'] }}" class="table align-middle mb-0 w-100 programme-dt-table em-table"
                        aria-label="{{ $cfg['title'] }}">
                        <thead>
                            <tr>
                                <th scope="col" class="em-col-sno">S. No.</th>
                                @foreach($fields as $f)
                                <th scope="col">{{ $f['column'] ?? $f['label'] }}</th>
                                @endforeach
                                <th scope="col" class="em-col-action no-sort">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $row)
                            @php
                                $values = collect($fields)->mapWithKeys(fn ($f) => [$f['name'] => (string) ($row->{$f['name']} ?? '')])->all();
                                $label = $values[$fields[0]['name']] ?? '';
                            @endphp
                            <tr>
                                <td class="em-col-sno">{{ $loop->iteration }}</td>
                                @foreach($fields as $f)
                                @php $v = trim($values[$f['name']]); @endphp
                                <td @class(['em-col-wrap' => ! empty($f['wrap'])])>{{ $v === '' || $v === '--' ? '-' : $v }}</td>
                                @endforeach
                                <td class="em-col-action">
                                    <div class="rfe-actions" role="group" aria-label="Actions for {{ $label }}">
                                        <button type="button" class="rfe-action rfe-action--edit js-em-edit"
                                            data-pk="{{ $row->pk }}" data-values="{{ json_encode($values) }}"
                                            title="Edit" aria-label="Edit {{ $label }}">
                                            <i class="material-icons material-symbols-rounded" aria-hidden="true">edit</i>
                                            <span class="rfe-action-label">Edit</span>
                                        </button>
                                        <button type="button" class="rfe-action rfe-action--delete js-em-delete"
                                            data-url="{{ route($cfg['routePrefix'] . '.destroy', $row->pk) }}"
                                            title="Delete" aria-label="Delete {{ $label }}">
                                            <i class="material-icons material-symbols-rounded" aria-hidden="true">delete</i>
                                            <span class="rfe-action-label">Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- DataTables paginates, so the footer is an empty slot the global UI fills. --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="{{ $cfg['tableId'] }}"></div>
            </div>
        </div>
    </div>
</div>

{{-- Columns (EstateReportGrid.columnVisibility fills the grid). --}}
<div class="modal fade" id="{{ $key }}ColumnVisibilityModal" tabindex="-1" aria-labelledby="{{ $key }}ColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="{{ $key }}ColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="{{ $key }}ColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Add / Edit — one modal for both so they look alike. --}}
<div class="modal fade ds-modal em-modal" id="{{ $key }}FormModal" tabindex="-1"
    aria-labelledby="{{ $key }}FormModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="{{ $key }}Form" method="POST"
                action="{{ $isReopenEdit ? str_replace('__ID__', $reopenPk, $updateTemplate) : route($cfg['routePrefix'] . '.store') }}">
                @csrf
                <input type="hidden" name="_method" id="{{ $key }}FormMethod" value="{{ $isReopenEdit ? 'PUT' : 'POST' }}">
                <input type="hidden" name="_em_form" value="{{ $key }}">
                <input type="hidden" name="_em_edit_pk" id="{{ $key }}EditPk" value="{{ $reopenPk }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $key }}FormModalLabel">{{ $isReopenEdit ? 'Edit' : 'Add' }} {{ $singular }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @foreach($fields as $f)
                    @php
                        $id = $key . '_' . $f['name'];
                        $err = $reopen ? $errors->first($f['name']) : null;
                        $val = $reopen ? old($f['name'], '') : '';
                    @endphp
                    <div class="{{ $loop->last ? 'mb-0' : 'mb-2' }}">
                        <label class="ds-form-label" for="{{ $id }}">
                            {{ $f['label'] }}@if(! empty($f['required']))<span class="ds-req" aria-hidden="true">*</span>@endif
                        </label>
                        @if(($f['type'] ?? 'text') === 'textarea')
                        <textarea class="form-control @if($err) is-invalid @endif" id="{{ $id }}" name="{{ $f['name'] }}"
                            rows="3" data-em-field="{{ $f['name'] }}"
                            @if(! empty($f['maxlength'])) maxlength="{{ $f['maxlength'] }}" @endif
                            @if(! empty($f['placeholder'])) placeholder="{{ $f['placeholder'] }}" @endif
                            @if(! empty($f['required'])) required aria-required="true" @endif
                            @if($err) aria-invalid="true" aria-describedby="{{ $id }}_error" @endif>{{ $val }}</textarea>
                        @else
                        <input type="text" class="form-control @if($err) is-invalid @endif" id="{{ $id }}" name="{{ $f['name'] }}"
                            value="{{ $val }}" data-em-field="{{ $f['name'] }}" autocomplete="off"
                            @if(! empty($f['maxlength'])) maxlength="{{ $f['maxlength'] }}" @endif
                            @if(! empty($f['placeholder'])) placeholder="{{ $f['placeholder'] }}" @endif
                            @if(! empty($f['required'])) required aria-required="true" @endif
                            @if($err) aria-invalid="true" aria-describedby="{{ $id }}_error" @endif>
                        @endif
                        @if($err)
                        <div class="invalid-feedback js-em-error" id="{{ $id }}_error">{{ $err }}</div>
                        @endif
                    </div>
                    @endforeach
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ds-btn-primary" id="{{ $key }}FormSubmit">{{ $isReopenEdit ? 'Update' : 'Add' }} {{ $singular }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@include('admin.estate.partials.master_delete_modal', ['key' => $key])

@push('styles')
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@include('admin.estate.partials.master_styles')
@endpush

@push('scripts')
@include('admin.estate.partials.report_print_assets')
<script>
$(function () {
    var key = @json($key);
    var singular = @json($singular);
    var title = @json($cfg['title']);
    var storeUrl = @json(route($cfg['routePrefix'] . '.store'));
    var updateTemplate = @json($updateTemplate);
    var $form = $('#' + key + 'Form');
    var formModal = bootstrap.Modal.getOrCreateInstance(document.getElementById(key + 'FormModal'));
    var deleteModal = bootstrap.Modal.getOrCreateInstance(document.getElementById(key + 'DeleteModal'));

    function clearErrors() {
        $form.find('.is-invalid').removeClass('is-invalid').removeAttr('aria-invalid aria-describedby');
        $form.find('.js-em-error').remove();
    }

    function openForm(pk, values) {
        var isEdit = !!pk;
        clearErrors();
        $('#' + key + 'FormModalLabel').text((isEdit ? 'Edit ' : 'Add ') + singular);
        $('#' + key + 'FormSubmit').text((isEdit ? 'Update ' : 'Add ') + singular).prop('disabled', false);
        $form.attr('action', isEdit ? updateTemplate.replace('__ID__', pk) : storeUrl);
        $('#' + key + 'FormMethod').val(isEdit ? 'PUT' : 'POST');
        $('#' + key + 'EditPk').val(isEdit ? pk : '');
        $form.find('[data-em-field]').each(function () {
            var name = $(this).data('em-field');
            $(this).val(isEdit && values && values[name] != null ? values[name] : '');
        });
        formModal.show();
    }

    $('#' + key + 'AddBtn').on('click', function () { openForm(null); });

    // Delegated: DataTables re-renders rows on every page/search.
    $(document).on('click', '.js-em-edit', function () {
        openForm(String($(this).data('pk')), $(this).data('values') || {});
    });

    $(document).on('click', '.js-em-delete', function () {
        $('#' + key + 'DeleteForm').attr('action', $(this).data('url'));
        deleteModal.show();
    });

    // Focus the first field once the dialog is visible (keyboard / screen-reader users).
    $('#' + key + 'FormModal').on('shown.bs.modal', function () {
        var $bad = $form.find('.is-invalid').first();
        ($bad.length ? $bad : $form.find('[data-em-field]').first()).trigger('focus');
    });

    // One submit only — a double click must not create two rows.
    $form.on('submit', function () {
        $('#' + key + 'FormSubmit').prop('disabled', true);
    });
    // Back/forward cache restores the disabled state; undo it.
    $(window).on('pageshow', function () {
        $('#' + key + 'FormSubmit').prop('disabled', false);
    });

    @if($reopen)
    formModal.show();
    @endif

    var $table = $('#' + @json($cfg['tableId']));

    // The controller already sends newest-first; order: [] keeps that until a header is clicked.
    var dt = $table.DataTable({
        order: [],
        responsive: false, // the panel scrolls; never fold columns into child rows
        language: { emptyTable: 'No ' + singular.toLowerCase() + ' records yet — use “' + $('#' + key + 'AddBtn').text().trim() + '” to add one.' },
        columnDefs: [
            { targets: [0, -1], orderable: false, searchable: false }
        ]
    });

    // S. No. follows what is on screen, not the server's original position.
    function renumber() {
        var start = dt.page.info().start;
        dt.column(0, { page: 'current' }).nodes().each(function (cell, i) {
            cell.textContent = start + i + 1;
        });
    }
    dt.on('draw.dt', renumber);
    renumber();

    EstateReportGrid.columnVisibility(dt, {
        grid: '#' + key + 'ColumnToggleGrid',
        storageKey: 'estate.master.' + key + '.hiddenCols',
        skip: ['S. No.', 'Actions']
    });

    // Download (CSV) + Print: the rows the grid holds after search, visible columns only.
    $(document).on('click', '[data-export-for="' + key + '"]', function () {
        var data = EstateReportGrid.collectRows(dt, { skip: ['Actions'], serial: 'S. No.' });
        EstateReportGrid.downloadCsv(title, data.headers, data.rows);
    });
    $('#' + key + 'PrintBtn').on('click', function () {
        var data = EstateReportGrid.collectRows(dt, { skip: ['Actions'], serial: 'S. No.' });
        EstateReportGrid.printGrid({
            title: title,
            meta: dt.search() ? ['Search: "' + dt.search() + '"'] : [],
            headers: data.headers,
            rows: data.rows
        });
    });
});
</script>
@endpush
