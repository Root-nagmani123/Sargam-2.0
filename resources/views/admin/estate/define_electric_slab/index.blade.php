@extends('admin.layouts.master')

@section('title', 'Define Electric Slab')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4 es-page">
    {{-- Breadcrumb carries the page title and the primary action. --}}
    <x-breadcrum title="Define Electric Slab" :showBack="false" button-text="Add Electric Slab"
        button-id="btnAddElectricSlab" button-icon="add"
        button-class="btn btn-primary d-inline-flex align-items-center gap-2" />

    <x-session_message />

    <div id="esAlerts"></div>

    {{-- No status pills on this grid, so the export row sits alone on the right
         (new-design-index-page.md §1). Print is the server-rendered view, not
         window.print(), so the printout and the Excel can't drift apart. --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 es-secondary-actions no-print">
        <a href="{{ route('admin.estate.define-electric-slab.download') }}"
            class="btn programme-dt-btn-columns border-0 text-primary" title="Download as Excel">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </a>
        <a href="{{ route('admin.estate.define-electric-slab.print') }}" target="_blank" rel="noopener"
            class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-3 p-md-4">
            {{-- Toolbar: nothing to filter by on this grid, so Columns + search
                 sit alone on the right (§2). --}}
            <div
                class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar no-print">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" data-bs-toggle="modal"
                        data-bs-target="#esColumnModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    <button type="button" class="btn es-search-toggle" id="esSearchToggle" aria-expanded="false"
                        aria-controls="esDtSearch" title="Search slabs">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </button>
                    <div id="esDtSearch" class="programme-dt-search d-none" data-dt-search-for="electricSlabTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table([
                        'class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table',
                        'aria-describedby' => 'electric-slab-caption',
                    ]) !!}
                </div>
            </div>
            <div id="electric-slab-caption" class="visually-hidden">Define Electric Slab list</div>

            {{-- DataTables paginates, so the footer is an empty slot the global
                 UI script fills (§4A). --}}
            <div id="esDtFooter"
                class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 no-print"
                data-dt-footer-for="electricSlabTable"></div>
        </div>
    </div>

    {{-- Column Visibility (column-visibility.md — colvis-item card grid) --}}
    <div class="modal fade" id="esColumnModal" tabindex="-1" aria-labelledby="esColumnModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="esColumnModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <hr class="mt-0">
                    <div class="row g-3" id="esColumnToggleGrid"></div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-primary rounded-3 px-4"
                        data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add / Edit — ONE modal serves both, so the two look alike (§3c); only the
     title, the submit caption and the target URL differ. --}}
<div class="modal fade" id="esFormModal" tabindex="-1" aria-labelledby="esFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered es-form-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="esFormModalLabel">Add Estate Electric Slab</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="esForm" method="post" action="{{ route('admin.estate.define-electric-slab.store') }}">
                @csrf
                <input type="hidden" name="_method" id="esFormMethod" value="POST">
                <div class="modal-body">
                    <div id="esFormAlerts"></div>

                    <div class="mb-3">
                        <label class="ds-form-label" for="start_unit_range">Start Unit Range<span
                                class="ds-req">*</span></label>
                        <input type="number" class="form-control" id="start_unit_range" name="start_unit_range"
                            value="0.00" step="1" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="ds-form-label" for="end_unit_range">End Unit Range<span
                                class="ds-req">*</span></label>
                        <input type="number" class="form-control" id="end_unit_range" name="end_unit_range"
                            value="0.00" step="1" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="ds-form-label" for="rate_per_unit">Rate Per Unit<span
                                class="ds-req">*</span></label>
                        <input type="number" class="form-control" id="rate_per_unit" name="rate_per_unit"
                            value="0.00" step="0.01" min="0" required>
                    </div>

                    <div class="mb-0">
                        <label class="ds-form-label" for="estate_unit_type_master_pk">Merge with House<span
                                class="ds-req">*</span></label>
                        <select class="form-select" id="estate_unit_type_master_pk" name="estate_unit_type_master_pk"
                            required>
                            <option value="">Select Estate</option>
                            @foreach($unitTypes ?? [] as $pk => $label)
                            <option value="{{ $pk }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ds-btn-primary" id="esFormSubmit">Add Electric Slab</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* ── Define Electric Slab — page-scoped remainder only. The toolbar, panel,
       footer, form labels and buttons are design-system components; what is
       here is specific to this grid and uses --ds-* tokens (design.md rule 2). */
    .es-page .es-secondary-actions .btn i {
        font-size: 1rem;
        line-height: 1;
    }

    .es-page .es-search-toggle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: var(--ds-control-h, 2.5rem);
        height: var(--ds-control-h, 2.5rem);
        padding: 0;
        color: var(--ds-primary, #004a93);
        background: var(--ds-surface, #fff);
        border: 1px solid var(--ds-line, #d0d5dd);
        border-radius: var(--ds-radius, 4px);
    }

    .es-page .es-search-toggle[aria-expanded="true"],
    .es-page .es-search-toggle:hover {
        border-color: var(--ds-primary, #004a93);
        background: #f2f7fc;
    }

    /* Row actions — icon over caption (§3b). */
    .es-page .es-act-group {
        display: inline-flex;
        align-items: stretch;
        gap: var(--ds-space-1, 0.25rem);
    }

    .es-page .es-act {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        min-width: 52px;
        margin: 0;
        padding: 0;
        border: 0;
        background: transparent;
        font-size: 0.72rem;
        font-weight: 500;
        line-height: 1;
        cursor: pointer;
    }

    .es-page .es-act__icon > i {
        font-size: 1.1rem;
        line-height: 1;
    }

    .es-page .es-act--edit {
        color: #2563eb;
    }

    .es-page .es-act--delete {
        color: var(--ds-danger, #ef4444);
    }

    .es-page .programme-dt-footer:empty {
        display: none;
    }

    .es-page .programme-dt-footer .paginate_button.first,
    .es-page .programme-dt-footer .paginate_button.last {
        display: none;
    }

    /* The form modal follows the design's single-column Add screen. */
    .es-form-modal .modal-content {
        border: 0;
        border-radius: var(--ds-radius-2, 8px);
        box-shadow: var(--ds-shadow-lg);
    }

    .es-form-modal .modal-header {
        align-items: center;
        padding: var(--ds-space-3) var(--ds-space-4);
        border-bottom: 1px solid var(--ds-line);
    }

    .es-form-modal .modal-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--ds-ink);
    }

    .es-form-modal .modal-body {
        padding: var(--ds-space-4);
    }

    .es-form-modal .modal-footer {
        justify-content: flex-end;
        gap: var(--ds-space-2);
        padding: var(--ds-space-3) var(--ds-space-4);
        border-top: 1px solid var(--ds-line);
    }

    .es-form-modal .modal-footer > * {
        margin: 0;
    }

    .es-form-modal .form-control,
    .es-form-modal .form-select {
        min-height: var(--ds-control-h);
        border-color: var(--ds-line);
        border-radius: var(--ds-radius-1);
        color: var(--ds-ink);
        font-size: 0.875rem;
    }

    .es-form-modal .form-control:focus,
    .es-form-modal .form-select:focus {
        border-color: var(--ds-primary);
        box-shadow: var(--ds-focus-ring);
    }
</style>
@endpush

@push('scripts')
{!! $dataTable->scripts(attributes: ['type' => 'module']) !!}
<script>
    $(function () {
        var storeUrl = @json(route('admin.estate.define-electric-slab.store'));
        var updateBase = @json(route('admin.estate.define-electric-slab.update', ['id' => '__ID__']));
        var ES_COLVIS_KEY = 'sargam.electricSlab.hiddenCols.' + @json(auth()->id() ?? 'guest');

        var dt = null;

        function alertBox(target, type, message) {
            var icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
            $(target).html(
                $('<div class="alert alert-' + type + ' alert-dismissible fade show d-flex align-items-center rounded-3" role="alert">')
                    .append('<i class="bi ' + icon + ' me-2 flex-shrink-0" aria-hidden="true"></i>')
                    .append($('<span class="flex-grow-1">').text(message))
                    .append('<button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="alert" aria-label="Close"></button>')
            );
        }

        // ── Column visibility, remembered by LABEL not index (column-visibility.md §3)
        function readHidden() {
            try {
                var raw = window.localStorage.getItem(ES_COLVIS_KEY);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) { return []; }
        }

        function saveHidden() {
            var hidden = [];
            $('#esColumnToggleGrid .es-col-toggle').each(function () {
                if (!this.checked) hidden.push($(this).data('label'));
            });
            try { window.localStorage.setItem(ES_COLVIS_KEY, JSON.stringify(hidden)); } catch (e) { /* private mode */ }
        }

        function buildToggles() {
            if (!dt) return;
            var $grid = $('#esColumnToggleGrid').empty();
            dt.columns().every(function (i) {
                var col = this;
                var label = $(col.header()).text().trim();
                if (!label || label === 'Action') return;
                var id = 'esColVis_' + i;
                var $cb = $('<input type="checkbox" class="form-check-input m-0 es-col-toggle">')
                    .attr({ id: id, 'data-column': i, 'data-label': label })
                    .prop('checked', col.visible());
                $cb.on('change', function () {
                    dt.column(i).visible(this.checked);
                    saveHidden();
                });
                $grid.append(
                    $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                        $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                            .attr('for', id).append($cb).append($('<span></span>').text(label))
                    )
                );
            });
        }

        function applySavedVisibility() {
            var hidden = readHidden();
            if (!hidden.length || !dt) return;
            dt.columns().every(function () {
                var label = $(this.header()).text().trim();
                if (label && hidden.indexOf(label) !== -1) this.visible(false, false);
            });
            dt.columns.adjust().draw(false);
        }

        // Yajra builds the table itself, so hook its init rather than creating it.
        $(document).on('init.dt', function (e, settings) {
            if (!settings.nTable || settings.nTable.id !== 'electricSlabTable') return;
            dt = new $.fn.dataTable.Api(settings);
            buildToggles();
            applySavedVisibility();
            buildToggles();
        });

        $('#esSearchToggle').on('click', function () {
            var $wrap = $('#esDtSearch');
            var open = $wrap.hasClass('d-none');
            $wrap.toggleClass('d-none', !open);
            $(this).attr('aria-expanded', open ? 'true' : 'false');
            if (open) $wrap.find('input').trigger('focus');
        });

        // ── Add / Edit modal ────────────────────────────────────────────────
        function openForm(mode, data) {
            var isEdit = mode === 'edit';
            $('#esFormModalLabel').text(isEdit ? 'Edit Estate Electric Slab' : 'Add Estate Electric Slab');
            $('#esFormSubmit').text(isEdit ? 'Update Electric Slab' : 'Add Electric Slab');
            $('#esFormAlerts').empty();

            $('#esForm').attr('action', isEdit ? updateBase.replace('__ID__', data.pk) : storeUrl);
            $('#esFormMethod').val(isEdit ? 'PUT' : 'POST');

            $('#start_unit_range').val(isEdit ? data.start : '0.00');
            $('#end_unit_range').val(isEdit ? data.end : '0.00');
            $('#rate_per_unit').val(isEdit ? data.rate : '0.00');
            $('#estate_unit_type_master_pk').val(isEdit ? String(data.unitType) : '');

            $('#esFormModal').modal('show');
        }

        $('#btnAddElectricSlab').on('click', function (e) {
            e.preventDefault();
            openForm('add');
        });

        $(document).on('click', '.js-slab-edit', function () {
            var $b = $(this);
            openForm('edit', {
                pk: $b.data('pk'),
                start: $b.data('start'),
                end: $b.data('end'),
                rate: $b.data('rate'),
                unitType: $b.data('unit-type')
            });
        });

        $('#esForm').on('submit', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $('#esFormSubmit').prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).done(function (res) {
                $('#esFormModal').modal('hide');
                if (dt) dt.ajax.reload(null, false);
                alertBox('#esAlerts', 'success', (res && res.message) ? res.message : 'Saved.');
            }).fail(function (xhr) {
                var msg = 'Failed to save.';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
                    if (xhr.responseJSON.errors) {
                        var k = Object.keys(xhr.responseJSON.errors)[0];
                        if (k) msg = xhr.responseJSON.errors[k][0];
                    }
                }
                alertBox('#esFormAlerts', 'danger', msg);
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });

        $(document).on('click', '.js-slab-delete', function () {
            var url = $(this).data('url');
            if (!url || !confirm('Are you sure you want to delete this electric slab?')) return;
            $.ajax({
                url: url,
                type: 'POST',
                data: { _method: 'DELETE', _token: @json(csrf_token()) },
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).done(function (res) {
                if (dt) dt.ajax.reload(null, false);
                alertBox('#esAlerts', 'success', (res && res.message) ? res.message : 'Deleted.');
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete.';
                alertBox('#esAlerts', 'danger', msg);
            });
        });
    });
</script>
@endpush
