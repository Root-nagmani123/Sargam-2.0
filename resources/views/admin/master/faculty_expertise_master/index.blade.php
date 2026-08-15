@extends('admin.layouts.master')

@section('title', 'Faculty Expertise')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page">
    <x-breadcrum title="Faculty Expertise" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="fexAddBtn" data-bs-toggle="modal" data-bs-target="#fexFormModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Faculty Expertise</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="fexBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#fexColumnVisibilityModal"
                            title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div id="fexDtSearch" class="programme-dt-search" data-dt-search-for="facultyExpertiseTable"></div>
                </div>
            </div>

            {{-- The controller hands over the whole set and DataTables paginates
                 it client-side; search, pager and the "Showing N of M items"
                 count are relocated into #fexDtSearch / #fexDtFooter by the
                 global enhancer (public/js/datatable-global-ui.js). --}}
            <div class="programme-dt-panel">
                <div class="table-responsive">
                    <table id="facultyExpertiseTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col" class="text-nowrap" style="width: 5.5rem;">S. No.</th>
                                <th scope="col">Faculty Expertise</th>
                                <th scope="col" class="text-nowrap" style="width: 8rem;">Status</th>
                                <th scope="col" class="text-nowrap" style="width: 13rem;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (($faculties ?? []) as $index => $faculty)
                                @php
                                    $isActive = (int) $faculty->active_inactive === 1;
                                    $name = $faculty->expertise_name ?? 'N/A';
                                @endphp
                                <tr>
                                    <td class="text-nowrap">{{ $index + 1 }}</td>
                                    <td>{{ $name }}</td>
                                    <td class="text-nowrap" data-order="{{ $isActive ? 1 : 0 }}">
                                        <span class="status-pill badge rounded-1 {{ $isActive ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                                            {{ $isActive ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="mst-act-group" role="group" aria-label="Row actions">
                                            <button type="button" class="mst-act mst-act--edit fex-edit-btn"
                                                    data-id="{{ encrypt($faculty->pk) }}"
                                                    data-name="{{ $name }}"
                                                    title="Edit {{ $name }}">
                                                <span class="mst-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                                <span class="mst-act__label">Edit</span>
                                            </button>

                                            {{-- No .form-check/.form-switch wrapper: custom.css pulls the
                                                 input -2.375rem left inside one and collapses it here. --}}
                                            <label class="mst-act mst-act--toggle">
                                                <span class="mst-act__icon">
                                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                           data-table="faculty_expertise_master" data-column="active_inactive"
                                                           data-id="{{ $faculty->pk }}" @checked($isActive)
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
                                                <form action="{{ route('master.faculty.expertise.delete', ['id' => encrypt($faculty->pk)]) }}"
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
                <div id="fexDtFooter"
                     class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                     data-dt-footer-for="facultyExpertiseTable"></div>
            </div>

        </div>
    </div>
</div>

<!-- Add / Edit Faculty Expertise -->
<div class="modal fade mst-modal" id="fexFormModal" tabindex="-1" aria-labelledby="fexFormModalLabel"
     aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('master.faculty.expertise.store') }}" id="fexForm" novalidate>
                @csrf
                <input type="hidden" name="id" id="fexId" value="{{ old('id') }}">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="fexFormModalLabel">Add Faculty Expertise</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="mst-field-card">
                        <div class="mb-0">
                            <label for="fexName" class="mst-form-label d-block">
                                Expertise Name <span class="mst-req">*</span>
                            </label>
                            <input type="text"
                                   class="form-control mst-control @error('expertise_name') is-invalid @enderror"
                                   id="fexName" name="expertise_name"
                                   value="{{ old('expertise_name') }}"
                                   placeholder="eg. Public Administration" maxlength="50" required>
                            <div class="invalid-feedback @error('expertise_name') d-block @enderror"
                                 data-field="expertise_name">{{ $errors->first('expertise_name') }}</div>
                        </div>
                    </div>
                    <p class="text-body-secondary small mb-0 mt-2">
                        New records are created Active. Use the row switch to activate or deactivate one.
                    </p>
                </div>

                <div class="modal-footer border-0 gap-2 justify-content-center">
                    <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn mst-btn-submit px-4" id="fexSubmitBtn">Add Faculty Expertise</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility -->
<div class="modal fade" id="fexColumnVisibilityModal" tabindex="-1"
     aria-labelledby="fexColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-3 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="fexColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="fexColumnToggleGrid"></div>
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
        var TABLE_ID = '#facultyExpertiseTable';

        /* ---------- Client-side DataTable ----------
           No custom `dom` / buttons here: datatable-global-ui.js supplies the
           defaults (page length, length menu, "Showing N of M items") and moves
           the chrome into the slots above and below the panel. */
        var dt = $(TABLE_ID).DataTable({
            order: [],
            columnDefs: [
                { targets: 0, orderable: false, searchable: false },
                { targets: 3, orderable: false, searchable: false }
            ]
        });

        // S. No. is a running number, so it has to be recomputed whenever the
        // rows are re-ordered, filtered or paged.
        function fexRenumber() {
            var start = dt.page.info().start;
            dt.rows({ page: 'current', order: 'applied' }).nodes().each(function (row, i) {
                $(row).children('td').eq(0).text(start + i + 1);
            });
        }

        dt.on('draw', fexRenumber);
        fexRenumber();

        /* ---------- Column visibility (stores LABELS, not indices — see docs) ---------- */
        var FEX_COLVIS_KEY = 'sargam.facultyExpertise.hiddenCols.{{ auth()->id() ?? 'guest' }}';

        function fexReadHiddenCols() {
            try {
                var raw = window.localStorage.getItem(FEX_COLVIS_KEY);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return [];
            }
        }

        function fexSaveHiddenCols(hidden) {
            try {
                window.localStorage.setItem(FEX_COLVIS_KEY, JSON.stringify(hidden));
            } catch (e) { /* private mode — the preference just won't persist */ }
        }

        (function fexBuildColumnGrid() {
            var hidden = fexReadHiddenCols();
            var $grid = $('#fexColumnToggleGrid');
            if (!$grid.length) { return; }
            $grid.empty();

            dt.columns().every(function () {
                var idx = this.index();
                var label = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!label) { return; }

                // Restore last session's choice before drawing the checkbox.
                this.visible(hidden.indexOf(label) === -1, false);

                var inputId = 'fexColvis_' + idx;
                var $checkbox = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr({ id: inputId, 'data-col': idx, 'data-label': label })
                    .prop('checked', hidden.indexOf(label) === -1);

                $checkbox.on('change', function () {
                    var current = fexReadHiddenCols();
                    var pos = current.indexOf(label);

                    if (this.checked) {
                        if (pos !== -1) { current.splice(pos, 1); }
                    } else if (pos === -1) {
                        current.push(label);
                    }

                    fexSaveHiddenCols(current);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
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

        /* ---------- Add / Edit modal ---------- */
        var $form = $('#fexForm');

        function fexClearErrors() {
            $form.find('.is-invalid').removeClass('is-invalid');
            $form.find('.invalid-feedback').removeClass('d-block').text('');
        }

        function fexSetMode(mode) {
            var isEdit = mode === 'edit';
            $('#fexFormModalLabel').text(isEdit ? 'Edit Faculty Expertise' : 'Add Faculty Expertise');
            $('#fexSubmitBtn').text(isEdit ? 'Update' : 'Add Faculty Expertise');
        }

        $('#fexAddBtn').on('click', function () {
            $form[0].reset();
            $('#fexId').val('');
            fexClearErrors();
            fexSetMode('add');
        });

        $(document).on('click', '#facultyExpertiseTable .fex-edit-btn', function () {
            var $btn = $(this);

            $form[0].reset();
            fexClearErrors();
            fexSetMode('edit');

            $('#fexId').val($btn.data('id'));
            $('#fexName').val($btn.data('name'));

            bootstrap.Modal.getOrCreateInstance(document.getElementById('fexFormModal')).show();
        });

        // novalidate is on the form (a hidden required field would otherwise
        // dead-end the submit), so validate here.
        $form.on('submit', function (e) {
            fexClearErrors();

            var name = $.trim($('#fexName').val());
            if (!name) {
                e.preventDefault();
                $('#fexName').addClass('is-invalid');
                $form.find('.invalid-feedback[data-field="expertise_name"]')
                     .addClass('d-block').text('Expertise name is required.');
                return;
            }

            $('#fexSubmitBtn').prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Saving...');
        });

        // A stale edit must not leak into the next Add.
        document.getElementById('fexFormModal').addEventListener('hidden.bs.modal', function () {
            $form[0].reset();
            $('#fexId').val('');
            fexClearErrors();
            fexSetMode('add');
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
            fexSetMode('{{ old('id') ? 'edit' : 'add' }}');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('fexFormModal')).show();
        @endif
    });
</script>
@endpush
