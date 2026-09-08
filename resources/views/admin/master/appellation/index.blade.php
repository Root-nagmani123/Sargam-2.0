@extends('admin.layouts.master')
@section('title', 'Appellation Master')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush
@section('setup_content')
<div class="container-fluid mst-page">
    <x-breadcrum title="Appellation Master" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="applAddBtn" data-bs-toggle="modal" data-bs-target="#applFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Appellation</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Download / Print) — above the card, per §1 of
         docs/new-design-index-page.md. ?q=, ?cols= and ?status_filter= are
         stamped on by applUpdateExportLinks(), so every format carries the same
         search term, columns and status the grid is currently showing.
         Print is a server-rendered branded view, NOT window.print(). --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 mst-secondary-actions">
        <div class="dropdown">
            <button type="button" id="applDownloadToggle"
                    class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="applDownloadToggle">
                <li>
                    <a class="dropdown-item" id="applCsvLink"
                       href="{{ route('master.appellation.export', ['format' => 'csv']) }}">
                        <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> CSV
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" id="applExcelLink"
                       href="{{ route('master.appellation.export', ['format' => 'excel']) }}">
                        <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel (.xlsx)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" id="applPdfLink"
                       href="{{ route('master.appellation.export', ['format' => 'pdf']) }}">
                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF
                    </a>
                </li>
            </ul>
        </div>

        <a href="{{ route('master.appellation.export', ['format' => 'print']) }}"
           id="applPrintLink" target="_blank" rel="noopener"
           class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="applBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#applColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="applDtSearch" class="programme-dt-search" data-dt-search-for="appellation-master-table"></div>
                </div>
            </div>

            {{-- Search box, pagination and the "Showing N of M items" count are
                 relocated into #applDtSearch / #applDtFooter by the global
                 enhancer (public/js/datatable-global-ui.js). Don't rebuild them. --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['class' => 'table table-hover align-middle mb-0 w-100 programme-dt-table']) !!}
                </div>
                <div id="applDtFooter"
                     class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="appellation-master-table"></div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Appellation -->
<div class="modal fade mst-modal" id="applFormModal" tabindex="-1" aria-labelledby="applFormModalLabel"
     aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('master.appellation.store') }}" id="applForm" novalidate>
                @csrf
                <input type="hidden" name="id" id="applId" value="{{ old('id') }}">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="applFormModalLabel">Add Appellation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mst-field-card">
                        <div class="mb-3">
                            <label for="applName" class="mst-form-label d-block">
                                Appellation Name <span class="mst-req">*</span>
                            </label>
                            <input type="text"
                                   class="form-control mst-control @error('appettation_name') is-invalid @enderror"
                                   id="applName" name="appettation_name"
                                   value="{{ old('appettation_name') }}"
                                   placeholder="eg. Shri" maxlength="50" required>
                            <div class="invalid-feedback @error('appettation_name') d-block @enderror"
                                 data-field="appettation_name">{{ $errors->first('appettation_name') }}</div>
                        </div>

                        <div class="mb-0">
                            <label for="applStatus" class="mst-form-label d-block">
                                Status <span class="mst-req">*</span>
                            </label>
                            <select class="form-select mst-control @error('active_inactive') is-invalid @enderror"
                                    id="applStatus" name="active_inactive" required>
                                <option value="">Select Status</option>
                                <option value="1" {{ old('active_inactive') == '1' ? 'selected' : '' }}>Active</option>
                                <option value="2" {{ old('active_inactive') == '2' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            <div class="invalid-feedback @error('active_inactive') d-block @enderror"
                                 data-field="active_inactive">{{ $errors->first('active_inactive') }}</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn mst-btn-submit px-4" id="applSubmitBtn">Add Appellation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility -->
<div class="modal fade" id="applColumnVisibilityModal" tabindex="-1"
     aria-labelledby="applColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="applColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="applColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
{!! $dataTable->scripts() !!}
<script>
    $(function () {
        var TABLE_ID = '#appellation-master-table';

        /* ---------- run once the Yajra table has initialised ---------- */
        function applWhenTableReady(callback) {
            var tries = 0;
            (function poll() {
                if ($.fn.DataTable.isDataTable(TABLE_ID)) {
                    callback($(TABLE_ID).DataTable());
                    return;
                }
                if (++tries > 40) { return; }
                setTimeout(poll, 50);
            })();
        }

        /* ---------- Export links follow the grid ----------
         * Positional map: header index -> the export key the server understands
         * (AppellationMasterController::exportColumnDefs()). '' marks a column
         * that is not in the export at all — here, Action.
         * ⚠️ Adding a column to the table means adding an entry here too. */
        var APPL_EXPORT_COLUMN_KEYS = ['sno', 'appellation', 'status', ''];
        var APPL_EXPORT_COL_COUNT = APPL_EXPORT_COLUMN_KEYS.filter(Boolean).length;
        var APPL_EXPORT_LINK_IDS = ['applCsvLink', 'applExcelLink', 'applPdfLink', 'applPrintLink'];

        function applUpdateExportLinks() {
            var dt = $.fn.DataTable.isDataTable(TABLE_ID) ? $(TABLE_ID).DataTable() : null;
            var keys = [];
            var term = '';

            if (dt) {
                dt.columns().every(function () {
                    var key = APPL_EXPORT_COLUMN_KEYS[this.index()];
                    if (key && this.visible()) { keys.push(key); }
                });
                // The term lives in DataTables, which sends it to the grid feed
                // as search[value]; the export reads ?q=. Without carrying it the
                // download returns every row and its header can't name the filter.
                term = dt.search() || '';
            }

            APPL_EXPORT_LINK_IDS.forEach(function (id) {
                var link = document.getElementById(id);
                if (!link) { return; }

                var base = link.href.split('?')[0];
                var params = new URLSearchParams(link.href.split('?')[1] || '');

                params.delete('q');
                if (term !== '') { params.set('q', term); }

                params.delete('status_filter');
                if (window.applStatusFilter) { params.set('status_filter', window.applStatusFilter); }

                params.delete('cols');
                // Omit ?cols= entirely while nothing is hidden — the server reads
                // "no cols" as "every column".
                if (dt && keys.length !== APPL_EXPORT_COL_COUNT) { params.set('cols', keys.join(',')); }

                var qs = params.toString();
                link.href = base + (qs ? '?' + qs : '');
            });
        }

        /* ---------- Status pills → server-side filter ---------- */
        window.applStatusFilter = '';

        $('#applStatusTabs').on('click', '.programme-status-pill', function () {
            var $pill = $(this);
            if ($pill.hasClass('active')) { return; }

            $('#applStatusTabs .programme-status-pill')
                .removeClass('active')
                .attr('aria-pressed', 'false')
                .removeAttr('aria-current');
            $pill.addClass('active').attr({ 'aria-pressed': 'true', 'aria-current': 'true' });

            var value = $pill.attr('data-appl-status');
            window.applStatusFilter = (value === null || typeof value === 'undefined') ? '' : String(value);

            if ($.fn.DataTable.isDataTable(TABLE_ID)) {
                $(TABLE_ID).DataTable().ajax.reload();
            }

            applUpdateExportLinks();
        });

        /* ---------- Column visibility (stores LABELS, not indices — see docs) ---------- */
        var APPL_COLVIS_KEY = 'sargam.appellation.hiddenCols.{{ auth()->id() ?? 'guest' }}';

        function applReadHiddenCols() {
            try {
                var raw = window.localStorage.getItem(APPL_COLVIS_KEY);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function applSaveHiddenCols(hidden) {
            try {
                window.localStorage.setItem(APPL_COLVIS_KEY, JSON.stringify(hidden));
            } catch (e) { /* private mode — the preference just won't persist */ }
        }

        function applBuildColumnGrid(dt) {
            var hidden = applReadHiddenCols();
            var $grid = $('#applColumnToggleGrid');
            if (!$grid.length) { return; }
            $grid.empty();

            dt.columns().every(function () {
                var idx = this.index();
                var label = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!label) { return; }

                // Restore last session's choice before drawing the checkbox.
                this.visible(hidden.indexOf(label) === -1, false);

                var inputId = 'applColvis_' + idx;
                var $checkbox = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr({ id: inputId, 'data-col': idx, 'data-label': label })
                    .prop('checked', hidden.indexOf(label) === -1);

                $checkbox.on('change', function () {
                    var current = applReadHiddenCols();
                    var pos = current.indexOf(label);

                    if (this.checked) {
                        if (pos !== -1) { current.splice(pos, 1); }
                    } else if (pos === -1) {
                        current.push(label);
                    }

                    applSaveHiddenCols(current);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                    applUpdateExportLinks();
                });

                $grid.append(
                    $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                        $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                            .attr({ 'for': inputId, title: label })
                            .append($checkbox)
                            .append($('<span></span>').text(label))
                    )
                );
            });

            dt.columns.adjust();
        }

        applWhenTableReady(function (dt) {
            applBuildColumnGrid(dt);
            // Search-as-you-type has to re-stamp the links, not just redraw.
            dt.on('search.dt', applUpdateExportLinks);
            applUpdateExportLinks();
        });

        /* ---------- Add / Edit modal ---------- */
        var $form = $('#applForm');

        function applClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').removeClass('d-block').text('');
        }

        function applSetMode(mode) {
            var isEdit = mode === 'edit';
            $('#applFormModalLabel').text(isEdit ? 'Edit Appellation' : 'Add Appellation');
            $('#applSubmitBtn').text(isEdit ? 'Update' : 'Add Appellation');
        }

        $('#applAddBtn').on('click', function () {
            $form[0].reset();
            $('#applId').val('');
            $('#applStatus').val('1');
            applClearErrors();
            applSetMode('add');
        });

        $(document).on('click', '#appellation-master-table .appl-edit-btn', function () {
            var $btn = $(this);

            $form[0].reset();
            applClearErrors();
            applSetMode('edit');

            $('#applId').val($btn.data('id'));
            $('#applName').val($btn.data('name'));
            $('#applStatus').val(String($btn.data('status')));

            bootstrap.Modal.getOrCreateInstance(document.getElementById('applFormModal')).show();
        });

        // Appellation names are letters, spaces and dots only.
        var APPL_NAME_RE = /^[a-zA-Z\s.]+$/;

        $('#applName').on('keypress', function (e) {
            if (!APPL_NAME_RE.test(String.fromCharCode(e.which))) {
                e.preventDefault();
            }
        }).on('paste', function (e) {
            var clipboard = (e.originalEvent && e.originalEvent.clipboardData) || window.clipboardData;
            if (clipboard && !APPL_NAME_RE.test(clipboard.getData('text'))) {
                e.preventDefault();
            }
        });

        // novalidate is on the form (a hidden required field would otherwise
        // dead-end the submit), so validate the two fields here.
        $form.on('submit', function (e) {
            applClearErrors();
            var valid = true;

            var name = $.trim($('#applName').val());
            if (!name) {
                valid = false;
                $('#applName').addClass('is-invalid');
                $form.find('.invalid-feedback[data-field="appettation_name"]')
                     .addClass('d-block').text('Appellation name is required.');
            } else if (!APPL_NAME_RE.test(name)) {
                valid = false;
                $('#applName').addClass('is-invalid');
                $form.find('.invalid-feedback[data-field="appettation_name"]')
                     .addClass('d-block').text('Appellation name must contain only letters and spaces.');
            }

            if (!$('#applStatus').val()) {
                valid = false;
                $('#applStatus').addClass('is-invalid');
                $form.find('.invalid-feedback[data-field="active_inactive"]')
                     .addClass('d-block').text('Status is required.');
            }

            if (!valid) {
                e.preventDefault();
                return;
            }

            $('#applSubmitBtn').prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');
        });

        // A stale edit must not leak into the next Add.
        document.getElementById('applFormModal').addEventListener('hidden.bs.modal', function () {
            $form[0].reset();
            $('#applId').val('');
            applClearErrors();
            applSetMode('add');
        });

        /* ---------- Delete confirmation ---------- */
        $(document).on('submit', '.appl-del-form', function (e) {
            var form = this;
            if (form.dataset.confirmed === '1') { return; }

            e.preventDefault();
            var name = form.dataset.name || 'this record';

            if (typeof Swal === 'undefined' || typeof Swal.fire !== 'function') {
                if (window.confirm('Are you sure you want to delete ' + name + '?')) {
                    form.dataset.confirmed = '1';
                    form.submit();
                }
                return;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: 'Delete "' + name + '"? This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.dataset.confirmed = '1';
                    form.submit();
                }
            });
        });

        /* ---------- Re-open the modal when the server rejected the submit ---------- */
        @if ($errors->any())
            applSetMode('{{ old('id') ? 'edit' : 'add' }}');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('applFormModal')).show();
        @endif
    });
</script>
@endpush
