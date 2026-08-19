@extends('admin.layouts.master')

@section('title', 'Faculty Type')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page">
    <x-breadcrum title="Faculty Type" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="ftmAddBtn" data-bs-toggle="modal" data-bs-target="#ftmFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Faculty Type</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Download / Print) — above the card, per §1 of
         docs/new-design-index-page.md. ?q= and ?cols= are stamped on by
         ftmUpdateExportLinks(), so every format carries the same search term and
         columns the grid is currently showing. Print is a server-rendered
         branded view, NOT window.print(). --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 mst-secondary-actions">
        <div class="dropdown">
            <button type="button" id="ftmDownloadToggle"
                    class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="ftmDownloadToggle">
                <li>
                    <a class="dropdown-item" id="ftmCsvLink"
                       href="{{ route('master.faculty.type.master.export', ['format' => 'csv']) }}">
                        <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> CSV
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" id="ftmExcelLink"
                       href="{{ route('master.faculty.type.master.export', ['format' => 'excel']) }}">
                        <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel (.xlsx)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" id="ftmPdfLink"
                       href="{{ route('master.faculty.type.master.export', ['format' => 'pdf']) }}">
                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF
                    </a>
                </li>
            </ul>
        </div>

        <a href="{{ route('master.faculty.type.master.export', ['format' => 'print']) }}"
           id="ftmPrintLink" target="_blank" rel="noopener"
           class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="ftmBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#ftmColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="ftmDtSearch" class="programme-dt-search" data-dt-search-for="facultyTypeTable"></div>
                </div>
            </div>

            {{-- The controller hands over the whole set and DataTables paginates
                 it client-side; search, pager and the "Showing N of M items"
                 count are relocated into #ftmDtSearch / #ftmDtFooter by the
                 global enhancer (public/js/datatable-global-ui.js). --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="facultyTypeTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap" style="width: 5.5rem;">S. No.</th>
                                <th scope="col">Faculty Type</th>
                                <th scope="col" class="text-nowrap" style="width: 12rem;">Short Name</th>
                                <th scope="col" class="text-nowrap" style="width: 8rem;">Status</th>
                                <th scope="col" class="text-nowrap" style="width: 13rem;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (($facultyTypes ?? []) as $index => $facultyType)
                                @php
                                    $isActive = (int) $facultyType->active_inactive === 1;
                                    $name = $facultyType->faculty_type_name ?? 'N/A';
                                    $shortName = $facultyType->shot_faculty_type_name ?? 'N/A';
                                @endphp
                                <tr>
                                    <td class="text-nowrap">{{ $index + 1 }}</td>
                                    <td>{{ $name }}</td>
                                    <td class="text-nowrap">{{ $shortName }}</td>
                                    <td class="text-nowrap" data-order="{{ $isActive ? 1 : 0 }}">
                                        <span class="status-pill badge rounded-1 {{ $isActive ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                                            {{ $isActive ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="mst-act-group" role="group" aria-label="Row actions">
                                            <button type="button" class="mst-act mst-act--edit ftm-edit-btn"
                                                    data-pk="{{ encrypt($facultyType->pk) }}"
                                                    data-name="{{ $name }}"
                                                    data-short="{{ $shortName }}"
                                                    title="Edit {{ $name }}">
                                                <span class="mst-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                                <span class="mst-act__label">Edit</span>
                                            </button>

                                            {{-- No .form-check/.form-switch wrapper: custom.css pulls the
                                                 input -2.375rem left inside one and collapses it here. --}}
                                            <label class="mst-act mst-act--toggle">
                                                <span class="mst-act__icon">
                                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                           data-table="faculty_type_master" data-column="active_inactive"
                                                           data-id="{{ $facultyType->pk }}" @checked($isActive)
                                                           aria-label="{{ $isActive ? 'Deactivate' : 'Activate' }} {{ $name }}">
                                                </span>
                                                <span class="mst-act__label">{{ $isActive ? 'Deactivate' : 'Activate' }}</span>
                                            </label>

                                            @if($isActive)
                                                <span class="mst-act mst-act--del is-disabled" aria-disabled="true"
                                                      title="Active records cannot be deleted. Deactivate it first.">
                                                    <span class="mst-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                                                    <span class="mst-act__label">Delete</span>
                                                </span>
                                            @else
                                                <form action="{{ route('master.faculty.type.master.delete', ['id' => encrypt($facultyType->pk)]) }}"
                                                      method="POST" class="mst-del-form" data-name="{{ $name }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="mst-act mst-act--del" title="Delete {{ $name }}">
                                                        <span class="mst-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                                                        <span class="mst-act__label">Delete</span>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div id="ftmDtFooter"
                     class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="facultyTypeTable"></div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Faculty Type -->
<div class="modal fade mst-modal" id="ftmFormModal" tabindex="-1" aria-labelledby="ftmFormModalLabel"
     aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('master.faculty.type.master.store') }}" id="ftmForm" novalidate>
                @csrf
                <input type="hidden" name="pk" id="ftmPk" value="{{ old('pk') }}">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="ftmFormModalLabel">Add Faculty Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mst-field-card">
                        <div class="mb-3">
                            <label for="ftmName" class="mst-form-label d-block">
                                Faculty Type <span class="mst-req">*</span>
                            </label>
                            <input type="text"
                                   class="form-control mst-control @error('faculty_type_name') is-invalid @enderror"
                                   id="ftmName" name="faculty_type_name"
                                   value="{{ old('faculty_type_name') }}"
                                   placeholder="eg. Visiting Faculty" maxlength="100" required>
                            <div class="invalid-feedback @error('faculty_type_name') d-block @enderror"
                                 data-field="faculty_type_name">{{ $errors->first('faculty_type_name') }}</div>
                        </div>

                        <div class="mb-0">
                            <label for="ftmShortName" class="mst-form-label d-block">
                                Short Name <span class="mst-req">*</span>
                            </label>
                            <input type="text"
                                   class="form-control mst-control @error('shot_faculty_type_name') is-invalid @enderror"
                                   id="ftmShortName" name="shot_faculty_type_name"
                                   value="{{ old('shot_faculty_type_name') }}"
                                   placeholder="eg. VF" maxlength="50" required>
                            <div class="invalid-feedback @error('shot_faculty_type_name') d-block @enderror"
                                 data-field="shot_faculty_type_name">{{ $errors->first('shot_faculty_type_name') }}</div>
                        </div>
                    </div>
                    <p class="text-body-secondary small mb-0 mt-2">
                        New records are created Active. Use the row switch to activate or deactivate one.
                    </p>
                </div>

                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn mst-btn-submit px-4" id="ftmSubmitBtn">Add Faculty Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility -->
<div class="modal fade" id="ftmColumnVisibilityModal" tabindex="-1"
     aria-labelledby="ftmColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="ftmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="ftmColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        var TABLE_ID = '#facultyTypeTable';

        /* ---------- Client-side DataTable ----------
           No custom `dom` / buttons here: datatable-global-ui.js supplies the
           defaults (page length, length menu, "Showing N of M items") and moves
           the chrome into the slots above and below the panel. */
        var dt = $(TABLE_ID).DataTable({
            order: [],
            columnDefs: [
                { targets: 0, orderable: false, searchable: false },
                { targets: 4, orderable: false, searchable: false }
            ]
        });

        // S. No. is a running number, so it has to be recomputed whenever the
        // rows are re-ordered, filtered or paged.
        function ftmRenumber() {
            var start = dt.page.info().start;
            dt.rows({ page: 'current', order: 'applied' }).nodes().each(function (row, i) {
                $(row).children('td').eq(0).text(start + i + 1);
            });
        }

        dt.on('draw', ftmRenumber);
        ftmRenumber();

        /* ---------- Export links follow the grid ----------
         * Positional map: header index -> the export key the server understands
         * (FacultyTypeMasterController::exportColumnDefs()). '' marks a column
         * that is not in the export at all — here, Action.
         * ⚠️ Adding a column to the table means adding an entry here too. */
        var FTM_EXPORT_COLUMN_KEYS = ['sno', 'faculty_type', 'short_name', 'status', ''];
        var FTM_EXPORT_COL_COUNT = FTM_EXPORT_COLUMN_KEYS.filter(Boolean).length;
        var FTM_EXPORT_LINK_IDS = ['ftmCsvLink', 'ftmExcelLink', 'ftmPdfLink', 'ftmPrintLink'];

        function ftmUpdateExportLinks() {
            var keys = [];
            dt.columns().every(function () {
                var key = FTM_EXPORT_COLUMN_KEYS[this.index()];
                if (key && this.visible()) { keys.push(key); }
            });

            // This grid searches client-side; the export reads ?q=. Without
            // carrying the term the download returns every row and its header
            // can't name the filter that was applied.
            var term = dt.search() || '';

            FTM_EXPORT_LINK_IDS.forEach(function (id) {
                var link = document.getElementById(id);
                if (!link) { return; }

                var base = link.href.split('?')[0];
                var params = new URLSearchParams(link.href.split('?')[1] || '');

                params.delete('q');
                if (term !== '') { params.set('q', term); }

                params.delete('cols');
                // Omit ?cols= entirely while nothing is hidden — the server reads
                // "no cols" as "every column".
                if (keys.length !== FTM_EXPORT_COL_COUNT) { params.set('cols', keys.join(',')); }

                var qs = params.toString();
                link.href = base + (qs ? '?' + qs : '');
            });
        }

        // Search-as-you-type has to re-stamp the links, not just redraw the grid.
        dt.on('search.dt', ftmUpdateExportLinks);

        /* ---------- Column visibility (stores LABELS, not indices — see docs) ---------- */
        var FTM_COLVIS_KEY = 'sargam.facultyType.hiddenCols.{{ auth()->id() ?? 'guest' }}';

        function ftmReadHiddenCols() {
            try {
                var raw = window.localStorage.getItem(FTM_COLVIS_KEY);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function ftmSaveHiddenCols(hidden) {
            try {
                window.localStorage.setItem(FTM_COLVIS_KEY, JSON.stringify(hidden));
            } catch (e) { /* private mode — the preference just won't persist */ }
        }

        (function ftmBuildColumnGrid() {
            var hidden = ftmReadHiddenCols();
            var $grid = $('#ftmColumnToggleGrid');
            if (!$grid.length) { return; }
            $grid.empty();

            dt.columns().every(function () {
                var idx = this.index();
                var label = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!label) { return; }

                // Restore last session's choice before drawing the checkbox.
                this.visible(hidden.indexOf(label) === -1, false);

                var inputId = 'ftmColvis_' + idx;
                var $checkbox = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr({ id: inputId, 'data-col': idx, 'data-label': label })
                    .prop('checked', hidden.indexOf(label) === -1);

                $checkbox.on('change', function () {
                    var current = ftmReadHiddenCols();
                    var pos = current.indexOf(label);

                    if (this.checked) {
                        if (pos !== -1) { current.splice(pos, 1); }
                    } else if (pos === -1) {
                        current.push(label);
                    }

                    ftmSaveHiddenCols(current);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                    ftmUpdateExportLinks();
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
        })();

        ftmUpdateExportLinks();

        /* ---------- Add / Edit modal ---------- */
        var $form = $('#ftmForm');

        function ftmClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').removeClass('d-block').text('');
        }

        function ftmSetMode(mode) {
            var isEdit = mode === 'edit';
            $('#ftmFormModalLabel').text(isEdit ? 'Edit Faculty Type' : 'Add Faculty Type');
            $('#ftmSubmitBtn').text(isEdit ? 'Update' : 'Add Faculty Type');
        }

        $('#ftmAddBtn').on('click', function () {
            $form[0].reset();
            $('#ftmPk').val('');
            ftmClearErrors();
            ftmSetMode('add');
        });

        $(document).on('click', '#facultyTypeTable .ftm-edit-btn', function () {
            var $btn = $(this);

            $form[0].reset();
            ftmClearErrors();
            ftmSetMode('edit');

            $('#ftmPk').val($btn.data('pk'));
            $('#ftmName').val($btn.data('name'));
            $('#ftmShortName').val($btn.data('short'));

            bootstrap.Modal.getOrCreateInstance(document.getElementById('ftmFormModal')).show();
        });

        // novalidate is on the form (a hidden required field would otherwise
        // dead-end the submit), so validate here.
        $form.on('submit', function (e) {
            ftmClearErrors();
            var valid = true;

            if (!$.trim($('#ftmName').val())) {
                valid = false;
                $('#ftmName').addClass('is-invalid');
                $form.find('.invalid-feedback[data-field="faculty_type_name"]')
                     .addClass('d-block').text('Faculty type name is required.');
            }

            if (!$.trim($('#ftmShortName').val())) {
                valid = false;
                $('#ftmShortName').addClass('is-invalid');
                $form.find('.invalid-feedback[data-field="shot_faculty_type_name"]')
                     .addClass('d-block').text('Short name is required.');
            }

            if (!valid) {
                e.preventDefault();
                return;
            }

            $('#ftmSubmitBtn').prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');
        });

        // A stale edit must not leak into the next Add.
        document.getElementById('ftmFormModal').addEventListener('hidden.bs.modal', function () {
            $form[0].reset();
            $('#ftmPk').val('');
            ftmClearErrors();
            ftmSetMode('add');
        });

        /* ---------- Status switch → refresh the badge ----------
           The badge and the switch live in different columns, and this table is
           client-side (custom.js's ajax.reload() has nothing to re-fetch), so
           reload the page once the toggle has been persisted. The generic
           toggle endpoint bumps this listing's cache epoch, so the reload gets
           fresh rows rather than the cached ones. */
        $(document).ajaxSuccess(function (e, xhr, settings) {
            var url = (settings && settings.url) || '';
            if (url.indexOf('toggle-status') === -1 && url.indexOf('toggleStatus') === -1) { return; }
            setTimeout(function () { window.location.reload(); }, 300);
        });

        /* ---------- Delete confirmation ---------- */
        $(document).on('submit', '.mst-del-form', function (e) {
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
            ftmSetMode('{{ old('pk') ? 'edit' : 'add' }}');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('ftmFormModal')).show();
        @endif
    });
</script>
@endpush
