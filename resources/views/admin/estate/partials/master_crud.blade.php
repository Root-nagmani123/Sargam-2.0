{{--
    Estate master grid — one implementation for Define Estate/Campus, Unit Type,
    Unit Sub Type and Block/Building, so the four screens cannot drift apart.

    Page chrome: docs/new-design-index-page.md (programme-dt toolbar / panel /
    footer, §3b row actions, §3c Add/Edit modal). Tokens: docs/design.md.
    Styles: public/css/estate-request-admin.css → "Estate Masters" (.em-page / .em-modal).

    Add / Edit is a plain form POST to the controller's existing store / update
    routes — no AJAX, no controller change. A validation failure redirects back
    here with old() input, and the modal reopens with the errors in place. The two
    underscore fields (_em_form, _em_edit_pk) only tell this page which modal to
    reopen; the controllers validate named fields, so they never reach the model.

    Expects $cfg:
      key          short id prefix ('campus')
      title        page title
      intro        one-line description under the toolbar
      singular     record noun for captions ('Estate/Campus')
      routePrefix  'admin.estate.define-campus' (.store / .update / .destroy)
      tableId      DataTables id
      emptyIcon    bootstrap-icons name for the empty state
      items        Collection of rows (full set — the grid pages client-side)
      fields       [['name','label','type' => text|textarea,'required','maxlength','placeholder','wrap']]
--}}
@php
    $key = $cfg['key'];
    $items = $cfg['items'];
    $fields = $cfg['fields'];
    $singular = $cfg['singular'];
    $reopen = $errors->any() && old('_em_form') === $key;
    $reopenPk = $reopen ? (string) old('_em_edit_pk', '') : '';
    $updateTemplate = route($cfg['routePrefix'] . '.update', ['id' => '__ID__']);
@endphp

<div class="container-fluid em-page">
    <x-breadcrum :title="$cfg['title']" :showBack="false">
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold text-nowrap"
            id="{{ $key }}AddBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add {{ $singular }}</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card border-0 shadow-sm rounded-1">
        <div class="card-body p-3 p-md-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <p class="em-intro">{{ $cfg['intro'] }}</p>
                @if($items->isNotEmpty())
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    {{-- Search slot: datatable-global-ui.js moves the DataTables filter here. --}}
                    <div id="{{ $key }}DtSearch" class="programme-dt-search" data-dt-search-for="{{ $cfg['tableId'] }}"></div>
                </div>
                @endif
            </div>

            @if($items->isEmpty())
            <div class="ds-empty-state em-empty" role="status">
                <i class="bi bi-{{ $cfg['emptyIcon'] ?? 'inbox' }}" aria-hidden="true"></i>
                <p class="em-empty-title">No {{ strtolower($singular) }} records yet</p>
                <p class="small mb-3">Add the first one to make it available across the Estate module.</p>
                <button type="button" class="btn ds-btn-submit js-em-add">Add {{ $singular }}</button>
            </div>
            @else
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="{{ $cfg['tableId'] }}" class="table table-hover align-middle mb-0 w-100 programme-dt-table"
                        aria-describedby="{{ $key }}Caption">
                        <thead>
                            <tr>
                                <th scope="col" class="em-col-sno no-sort">S. No.</th>
                                @foreach($fields as $f)
                                <th scope="col">{{ $f['label'] }}</th>
                                @endforeach
                                <th scope="col" class="em-col-action no-sort">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $i => $row)
                            @php
                                $values = collect($fields)->mapWithKeys(fn ($f) => [$f['name'] => (string) ($row->{$f['name']} ?? '')])->all();
                                $label = $values[$fields[0]['name']] ?? '';
                            @endphp
                            <tr>
                                <td class="em-col-sno">{{ $loop->iteration }}</td>
                                @foreach($fields as $f)
                                @php $v = trim($values[$f['name']]); @endphp
                                <td @class(['em-col-wrap' => ! empty($f['wrap']), 'fw-medium' => $loop->first])>
                                    @if($v === '' || $v === '--')
                                    <span class="em-muted" aria-label="Not set">—</span>
                                    @else
                                    {{ $v }}
                                    @endif
                                </td>
                                @endforeach
                                <td class="em-col-action">
                                    <div class="em-act-group" role="group" aria-label="Actions for {{ $label }}">
                                        <button type="button" class="em-act em-act--edit js-em-edit"
                                            data-pk="{{ $row->pk }}" data-values="{{ json_encode($values) }}"
                                            aria-label="Edit {{ $label }}">
                                            <span class="em-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                            <span class="em-act__label">Edit</span>
                                        </button>
                                        {{-- The server refuses a delete that would break a reference
                                             (FK) and says so in the flash message. --}}
                                        <button type="button" class="em-act em-act--delete js-em-delete"
                                            data-url="{{ route($cfg['routePrefix'] . '.destroy', $row->pk) }}"
                                            data-name="{{ $label }}" aria-label="Delete {{ $label }}">
                                            <span class="em-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                                            <span class="em-act__label">Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div id="{{ $key }}Caption" class="visually-hidden">{{ $cfg['title'] }} list</div>

            {{-- DataTables paginates, so the footer is an empty slot the global UI fills (§4A). --}}
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                data-dt-footer-for="{{ $cfg['tableId'] }}"></div>
            @endif
        </div>
    </div>
</div>

{{-- Add / Edit — one modal for both so they look alike (§3c). --}}
<div class="modal fade ds-modal em-modal" id="{{ $key }}FormModal" tabindex="-1"
    aria-labelledby="{{ $key }}FormModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="{{ $key }}Form" method="POST"
                action="{{ $reopen && $reopenPk !== '' ? str_replace('__ID__', $reopenPk, $updateTemplate) : route($cfg['routePrefix'] . '.store') }}">
                @csrf
                <input type="hidden" name="_method" id="{{ $key }}FormMethod" value="{{ $reopen && $reopenPk !== '' ? 'PUT' : 'POST' }}">
                <input type="hidden" name="_em_form" value="{{ $key }}">
                <input type="hidden" name="_em_edit_pk" id="{{ $key }}EditPk" value="{{ $reopenPk }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $key }}FormModalLabel">
                        {{ $reopen && $reopenPk !== '' ? 'Edit' : 'Add' }} {{ $singular }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @foreach($fields as $f)
                    @php
                        $id = $key . '_' . $f['name'];
                        $err = $reopen ? $errors->first($f['name']) : null;
                        $val = $reopen ? old($f['name'], '') : '';
                    @endphp
                    <div class="{{ $loop->last ? 'mb-0' : 'mb-3' }}">
                        <label class="form-label" for="{{ $id }}">
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
                    <button type="submit" class="btn ds-btn-submit" id="{{ $key }}FormSubmit">
                        {{ $reopen && $reopenPk !== '' ? 'Update' : 'Add' }} {{ $singular }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Delete confirmation — the module's shared confirm dialog. --}}
<div class="modal fade ds-modal ds-modal-confirm" id="{{ $key }}DeleteModal" tabindex="-1"
    aria-labelledby="{{ $key }}DeleteModalLabel" aria-describedby="{{ $key }}DeleteText" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="{{ $key }}DeleteForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="modal-body">
                    <div class="ds-confirm-icon" aria-hidden="true">!</div>
                    <h5 class="ds-confirm-title" id="{{ $key }}DeleteModalLabel">Delete {{ $singular }}?</h5>
                    <p class="ds-confirm-text" id="{{ $key }}DeleteText">This action can't be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ds-btn-danger">Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@endpush

@push('scripts')
<script>
$(function () {
    var key = @json($key);
    var singular = @json($singular);
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

    $('#' + key + 'AddBtn, .js-em-add').on('click', function () { openForm(null); });

    // Delegated: DataTables re-renders rows on every page/search.
    $(document).on('click', '.js-em-edit', function () {
        openForm(String($(this).data('pk')), $(this).data('values') || {});
    });

    $(document).on('click', '.js-em-delete', function () {
        var name = String($(this).data('name') || '').trim();
        $('#' + key + 'DeleteForm').attr('action', $(this).data('url'));
        $('#' + key + 'DeleteText').text((name ? '“' + name + '” will be removed. ' : '') + "This action can't be undone.");
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
    $('#' + key + 'DeleteForm').on('submit', function () {
        $(this).find('[type="submit"]').prop('disabled', true);
    });
    // Back/forward cache restores the disabled state; undo it.
    $(window).on('pageshow', function () {
        $('#' + key + 'FormSubmit, #' + key + 'DeleteForm [type="submit"]').prop('disabled', false);
    });

    @if($reopen)
    formModal.show();
    @endif

    var $table = $('#' + @json($cfg['tableId']));
    if (!$table.length) return; // empty state: nothing to paginate

    // The controller already sends newest-first; order: [] keeps that until a header is clicked.
    var dt = $table.DataTable({
        order: [],
        responsive: false, // the panel scrolls; never fold columns into child rows
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
});
</script>
@endpush
