{{--
    Estate master grid — one implementation for Define Estate/Campus, Unit Type,
    Unit Sub Type and Block/Building, so the four screens cannot drift apart.

    Design: docs/design.md — Layer C components from sargam-app.css
    (.ds-page-header, .ds-card, .ds-toolbar, .ds-table-wrap / .ds-table-sticky,
    .ds-actions + .btn-icon, .ds-empty-state, .ds-form-label / .ds-req,
    .ds-btn-primary / .ds-btn-cancel) and --ds-* tokens. Page-scoped styles:
    partials/master_styles.blade.php, pushed into @stack('styles').

    Add / Edit is a plain form POST to the controller's existing store / update
    routes — no AJAX, no controller change. A validation failure redirects back
    here with old() input, and the modal reopens with the errors in place. The two
    underscore fields (_em_form, _em_edit_pk) only tell this page which modal to
    reopen; the controllers validate named fields, so they never reach the model.

    Expects $cfg:
      key          short id prefix ('campus')
      title        page title
      intro        one-line description under the title
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
    $isReopenEdit = $reopen && $reopenPk !== '';
    $updateTemplate = route($cfg['routePrefix'] . '.update', ['id' => '__ID__']);
@endphp

<div class="container-fluid em-page">
    {{-- Breadcrumb trail + page title (the app-wide header component). --}}
    <x-breadcrum :title="$cfg['title']" :showBack="false" />

    <x-session_message />

    {{-- .ds-page-header: what the screen is for, and its one primary action. --}}
    <div class="ds-page-header">
        <p class="ds-page-subtitle">{{ $cfg['intro'] }}</p>
        <button type="button" class="btn ds-btn-primary" id="{{ $key }}AddBtn">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add {{ $singular }}</span>
        </button>
    </div>

    <section class="card ds-card" aria-labelledby="{{ $key }}CardTitle">
        <div class="ds-card-header">
            <h2 class="em-card-title" id="{{ $key }}CardTitle">{{ $cfg['title'] }} list</h2>
            <span class="em-count">{{ $items->count() }} {{ $items->count() === 1 ? 'record' : 'records' }}</span>
        </div>

        <div class="ds-card-body">
            @if($items->isEmpty())
            <div class="ds-empty-state" role="status">
                <i class="bi bi-{{ $cfg['emptyIcon'] ?? 'inbox' }}" aria-hidden="true"></i>
                <p class="em-empty-title">No {{ strtolower($singular) }} records yet</p>
                <p class="small mb-3">Add the first one to make it available across the Estate module.</p>
                <button type="button" class="btn ds-btn-primary js-em-add">Add {{ $singular }}</button>
            </div>
            @else
            {{-- .ds-toolbar: search on the right. The slot keeps its programme-dt
                 hook; datatable-global-ui.js moves the DataTables filter into it. --}}
            <div class="ds-toolbar">
                <span class="ds-toolbar-spacer"></span>
                <div id="{{ $key }}DtSearch" class="programme-dt-search" data-dt-search-for="{{ $cfg['tableId'] }}"></div>
            </div>

            <div class="ds-table-wrap">
                <table id="{{ $cfg['tableId'] }}" class="table table-hover align-middle w-100 ds-table-sticky em-table"
                    aria-describedby="{{ $key }}CardTitle">
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
                        @foreach($items as $row)
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
                                {{-- .ds-actions + .btn-icon: 32px, 4px-radius icon buttons. --}}
                                <div class="ds-actions" role="group" aria-label="Actions for {{ $label }}">
                                    <button type="button" class="btn btn-icon em-act-edit js-em-edit"
                                        data-pk="{{ $row->pk }}" data-values="{{ json_encode($values) }}"
                                        title="Edit" aria-label="Edit {{ $label }}">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    </button>
                                    {{-- The server refuses a delete that would break a reference
                                         (FK) and says so in the flash message. --}}
                                    <button type="button" class="btn btn-icon em-act-delete js-em-delete"
                                        data-url="{{ route($cfg['routePrefix'] . '.destroy', $row->pk) }}"
                                        data-name="{{ $label }}" title="Delete" aria-label="Delete {{ $label }}">
                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        @if($items->isNotEmpty())
        {{-- DataTables paginates, so the footer is an empty slot the global UI fills. --}}
        <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
            data-dt-footer-for="{{ $cfg['tableId'] }}"></div>
        @endif
    </section>
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
                    <div class="{{ $loop->last ? 'mb-0' : 'mb-3' }}">
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

{{-- Delete confirmation (.ds-modal-confirm). --}}
<div class="modal fade ds-modal ds-modal-confirm em-modal" id="{{ $key }}DeleteModal" tabindex="-1"
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
                    <button type="submit" class="btn ds-btn-primary em-btn-delete">Yes, Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
@include('admin.estate.partials.master_styles')
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
        responsive: false, // the wrap scrolls; never fold columns into child rows
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
