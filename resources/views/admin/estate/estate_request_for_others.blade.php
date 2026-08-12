@extends('admin.layouts.master')

@section('title', 'Estate Request for Others')

@section('setup_content')
<div class="container-fluid rfe-page eor-page">
    <x-breadcrum title="Estate Request for Others" :showBack="false">
        <button type="button"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold text-nowrap shadow-sm"
            id="btn-open-add-other-request" title="Add Other Estate">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Other Estate</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Exports sit above the card (docs/new-design-index-page.md §1). Both honour
         the applied filters, the search box and the Columns choice. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3">
        <button type="button" class="btn rfe-export-btn border-0" id="eorDownloadBtn">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </button>
        <button type="button" class="btn rfe-export-btn border-0" id="eorPrintBtn">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-1">
        <div class="card-body p-3 p-md-4">
            <div id="estateRequestCardBody">

                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="programme-dt-filters-label">Filter</span>

                        <div class="programme-dt-filter-select">
                            <select id="eorSectionFilter" class="form-select" aria-label="Filter by section">
                                <option value="">Section</option>
                                @foreach($sectionOptions ?? [] as $section)
                                    <option value="{{ $section }}">{{ $section }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="programme-dt-filter-select">
                            <input type="date" id="eorDojFilter" class="form-control"
                                aria-label="Filter by date of joining in academy" title="DOJ in Academy">
                        </div>

                        <button type="button" id="eorClearFilter" class="btn programme-dt-btn-reset">
                            Remove Filter
                        </button>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <button type="button" class="btn programme-dt-btn-columns" id="eorBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#eorColumnVisibilityModal"
                            title="Show / hide columns">
                            <span>Columns</span>
                            <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                        </button>
                        <div id="eorDtSearch" class="programme-dt-search" data-dt-search-for="estateRequestTable"></div>
                    </div>
                </div>

                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        {!! $dataTable->table(['aria-describedby' => 'estate-request-caption']) !!}
                    </div>
                    {{-- Left empty on purpose: datatable-global-ui.js fills this with the
                         pagination and the "Showing [10] of N items" count. --}}
                    <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                        data-dt-footer-for="estateRequestTable"></div>
                </div>

                <div id="estate-request-caption" class="visually-hidden">Estate Request for Others list</div>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="eorColumnVisibilityModal" tabindex="-1" aria-labelledby="eorColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="eorColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="eorColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit Other Estate Request modal -->
<div class="modal fade ds-modal eor-modal" id="addEditOtherRequestModal" tabindex="-1" aria-labelledby="addEditOtherRequestModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formAddEditOtherRequest" method="POST" action="{{ route('admin.estate.add-other-estate-request.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addEditOtherRequestModalLabel">Add Other Estate Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="addEditOtherRequestFormErrors" class="alert alert-danger d-none" role="alert">
                        <ul class="mb-0 ps-3"></ul>
                    </div>

                    <input type="hidden" name="id" id="other_request_id" value="">
                    {{-- One field per row: the design keeps this form a single narrow
                         column, not the two-up grid the wider estate modals use. --}}
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="modal_employee_name" class="form-label">Employee Name<span class="ds-req">*</span></label>
                            <input type="text" class="form-control" id="modal_employee_name" name="employee_name" required
                                maxlength="500" placeholder="eg. John Doe">
                        </div>
                        <div class="col-12">
                            <label for="modal_father_name" class="form-label">Father Name<span class="ds-req">*</span></label>
                            <input type="text" class="form-control other-estate-no-special" id="modal_father_name" name="father_name" required
                                maxlength="500" title="Only letters, numbers, spaces, hyphen, apostrophe and dot are allowed." placeholder="eg. Robert Joe">
                        </div>
                        <div class="col-12">
                            <label for="modal_section" class="form-label">Section<span class="ds-req">*</span></label>
                            <input type="text" class="form-control other-estate-no-special" id="modal_section" name="section" required
                                maxlength="500" title="Only letters, numbers, spaces, hyphen, apostrophe and dot are allowed." placeholder="eg. Admin">
                        </div>
                        <div class="col-12">
                            <label for="modal_doj_academy" class="form-label">DOJ in Academy<span class="ds-req">*</span></label>
                            {{-- A native date input has no placeholder — it always shows
                                 "dd-mm-yyyy". The overlay below stands in for one while the
                                 field is empty; .is-empty is kept in sync by the JS. --}}
                            <div class="eor-date-field">
                                <input type="date" class="form-control is-empty" id="modal_doj_academy" name="doj_academy" required
                                    min="1950-01-01" max="{{ date('Y-m-d') }}"
                                    title="Date must be between 01-01-1950 and today.">
                                <span class="eor-date-placeholder" aria-hidden="true">Select Date</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ds-btn-submit" id="btnSubmitOtherRequest">Add Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade ds-modal ds-modal-confirm" id="deleteOtherRequestModal" tabindex="-1" aria-labelledby="deleteOtherRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="ds-confirm-icon" aria-hidden="true">!</div>
                <h5 class="ds-confirm-title" id="deleteOtherRequestModalLabel">Confirm Delete?</h5>
                <p class="ds-confirm-text">Are you sure you want to delete this estate request? This action can't be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn ds-btn-danger" id="confirmDeleteOtherRequestBtn">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@endpush

@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
    $(function() {
        var deleteOtherRequestUrl = '';
        var $eorTable = $('#estateRequestTable');
        var addEditModalEl = document.getElementById('addEditOtherRequestModal');
        var addEditModal = addEditModalEl ? new bootstrap.Modal(addEditModalEl) : null;
        var deleteModalEl = document.getElementById('deleteOtherRequestModal');
        var deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;

        /* ---------- Toolbar filters ---------- */
        // Values ride along on every draw via minifiedAjax (see the DataTable's html()).
        $(document).on('change', '#eorSectionFilter, #eorDojFilter', function() {
            if ($.fn.DataTable.isDataTable($eorTable)) {
                $eorTable.DataTable().ajax.reload(null, false);
            }
        });

        $('#eorClearFilter').on('click', function() {
            $('#eorSectionFilter').val('');
            $('#eorDojFilter').val('');
            if (!$.fn.DataTable.isDataTable($eorTable)) return;
            // "Remove Filter" clears the search box too — it resets the whole toolbar.
            $eorTable.DataTable().search('').ajax.reload(null, false);
        });

        /* ---------- Column visibility (persisted per browser, per user) ---------- */
        // Header index -> export column key. POSITIONAL: adding a table column means
        // adding an entry here too. '' = a column that is never exported (Action).
        var EOR_EXPORT_COLUMN_KEYS = ['sno', 'request_id', 'emp_name', 'section', 'doj_acad', ''];
        var eorColStorageKey = 'sargam.estateRequestForOthers.hiddenCols.{{ auth()->id() ?? 'guest' }}';

        function eorGetHiddenCols() {
            try {
                var raw = localStorage.getItem(eorColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return []; // private mode / storage disabled / corrupt value
            }
        }

        function eorPersistHiddenCols(arr) {
            try { localStorage.setItem(eorColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        // Export keys for the columns still on screen — sent as ?cols= so Download
        // and Print carry exactly the columns the table is showing.
        function eorVisibleExportCols() {
            var hidden = eorGetHiddenCols();
            return EOR_EXPORT_COLUMN_KEYS.filter(function(key, idx) {
                return key !== '' && hidden.indexOf(idx) === -1;
            });
        }

        function setupEorColumns(dt) {
            if (!dt) return;
            var hidden = eorGetHiddenCols();

            dt.columns().every(function() {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#eorColumnToggleGrid');
            if (!$grid.length) return;
            $grid.empty();

            dt.columns().every(function() {
                var idx = this.index();
                var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) return;

                var inputId = 'eorcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function() {
                    var h = eorGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    eorPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        $eorTable.on('init.dt', function() {
            setupEorColumns($(this).DataTable());
        });
        // Yajra may have finished initialising before this handler was bound.
        if ($.fn.DataTable && $.fn.DataTable.isDataTable($eorTable)) {
            setupEorColumns($eorTable.DataTable());
        }

        /* ---------- Download / Print (server-side, honour the applied filters) ---------- */
        function eorExportParams() {
            var params = {};
            var section = $('#eorSectionFilter').val();
            if (section) params.section_filter = section;
            var doj = $('#eorDojFilter').val();
            if (doj) params.doj_filter = doj;
            if ($.fn.DataTable && $.fn.DataTable.isDataTable($eorTable)) {
                var searchValue = $eorTable.DataTable().search();
                if (searchValue) params.search = searchValue;
            }
            params.cols = eorVisibleExportCols().join(',');
            return params;
        }

        $('#eorDownloadBtn').on('click', function() {
            window.location.href = '{{ route('admin.estate.request-for-others.export') }}?' + $.param(eorExportParams());
        });

        $('#eorPrintBtn').on('click', function() {
            window.open('{{ route('admin.estate.request-for-others.print') }}?' + $.param(eorExportParams()), '_blank');
        });

        /* ---------- Inline notice inside the card ---------- */
        function eorNotify(type, message) {
            var $host = $('#estateRequestCardBody');
            var cls = type === 'success' ? 'alert-success' : 'alert-danger';
            var icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
            $host.find('.' + cls).remove();
            $host.prepend('<div class="alert ' + cls + ' alert-dismissible fade show d-flex align-items-center rounded-1 shadow-sm" role="alert">' +
                '<i class="bi ' + icon + ' me-2"></i><span class="flex-grow-1"></span>' +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
            $host.find('.' + cls + ' span').text(message);
            if (type === 'success') {
                setTimeout(function() { $host.find('.alert-success').fadeOut(); }, 4000);
            }
        }

        function setDojAcademyMaxToday() {
            var today = new Date().toISOString().split('T')[0];
            $('#modal_doj_academy').attr('max', today);
        }

        // The "Select Date" overlay only shows while the field is empty.
        function syncDojPlaceholder() {
            $('#modal_doj_academy').toggleClass('is-empty', !$('#modal_doj_academy').val());
        }
        $(document).on('change input', '#modal_doj_academy', syncDojPlaceholder);

        // ---- Add: open modal with empty form ----
        $('#btn-open-add-other-request').on('click', function() {
            $('#addEditOtherRequestModalLabel').text('Add Other Estate Request');
            $('#btnSubmitOtherRequest').text('Add Request');
            $('#other_request_id').val('');
            $('#modal_employee_name, #modal_father_name, #modal_section, #modal_doj_academy').val('');
            $('#addEditOtherRequestFormErrors').addClass('d-none').find('ul').empty();
            setDojAcademyMaxToday();
            syncDojPlaceholder();
            if (addEditModal) addEditModal.show();
        });

        // ---- Edit: open modal with row data ----
        $(document).on('click', '.btn-edit-other-request', function(e) {
            e.preventDefault();
            var $btn = $(this);
            $('#addEditOtherRequestModalLabel').text('Edit Other Estate Request');
            $('#btnSubmitOtherRequest').text('Update Request');
            $('#other_request_id').val($btn.data('id') || '');
            $('#modal_employee_name').val($btn.data('employee_name') || '');
            $('#modal_father_name').val($btn.data('father_name') || '');
            $('#modal_section').val($btn.data('section') || '');
            $('#modal_doj_academy').val($btn.data('doj_academy') || '');
            $('#addEditOtherRequestFormErrors').addClass('d-none').find('ul').empty();
            setDojAcademyMaxToday();
            syncDojPlaceholder();
            if (addEditModal) addEditModal.show();
        });

        // Allow only letters (any language), numbers, space, hyphen, apostrophe, dot (no special characters)
        var otherEstateStripRegex = /[^\p{L}\p{N}\s.\-' ]/gu;
        $(document).on('input', '.other-estate-no-special', function() {
            var $el = $(this);
            var val = $el.val();
            var cleaned = val.replace(otherEstateStripRegex, '');
            if (cleaned !== val) $el.val(cleaned);
        });

        // ---- Form submit via AJAX ----
        $('#formAddEditOtherRequest').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $errors = $('#addEditOtherRequestFormErrors');
            var $btn = $('#btnSubmitOtherRequest');
            $errors.addClass('d-none').find('ul').empty();

            var allowedRegex = /^[\p{L}\p{N}\s.\-' ]+$/u;
            var fields = [
                { id: '#modal_father_name', name: 'Father name' },
                { id: '#modal_section', name: 'Section' }
            ];
            var invalid = [];
            fields.forEach(function(f) {
                var v = ($(f.id).val() || '').trim();
                if (v && !allowedRegex.test(v)) {
                    invalid.push(f.name + ' may only contain letters, numbers, spaces, hyphen, apostrophe and dot.');
                }
            });
            var dojVal = $('#modal_doj_academy').val() || '';
            if (dojVal) {
                var doj = new Date(dojVal);
                var minDoj = new Date('1950-01-01');
                var today = new Date();
                today.setHours(23, 59, 59, 999);
                if (doj < minDoj) invalid.push('DOJ in Academy must be on or after 01-01-1950.');
                if (doj > today) invalid.push('DOJ in Academy cannot be a future date.');
            }
            if (invalid.length > 0) {
                var $ul = $errors.removeClass('d-none').find('ul');
                invalid.forEach(function(msg) { $ul.append($('<li></li>').text(msg)); });
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function(response) {
                    if (addEditModal) addEditModal.hide();
                    if (response.success && response.message) {
                        var isNew = !$('#other_request_id').val();
                        $eorTable.DataTable().ajax.reload(null, isNew);
                        eorNotify('success', response.message);
                    }
                },
                error: function(xhr) {
                    var $ul = $errors.removeClass('d-none').find('ul');
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        $.each(xhr.responseJSON.errors, function(_, msgs) {
                            $.each(msgs, function(__, m) { $ul.append($('<li></li>').text(m)); });
                        });
                    } else {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong. Please try again.';
                        $ul.empty().append($('<li></li>').text(msg));
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });

        // ---- Delete ----
        $(document).on('click', '.btn-delete-other-request', function(e) {
            e.preventDefault();
            deleteOtherRequestUrl = $(this).data('url');
            if (deleteModal) deleteModal.show();
        });

        $('#confirmDeleteOtherRequestBtn').on('click', function() {
            if (!deleteOtherRequestUrl) return;
            $.ajax({
                url: deleteOtherRequestUrl,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (deleteModal) deleteModal.hide();
                    if (response.success) {
                        $eorTable.DataTable().ajax.reload(null, false);
                        eorNotify('success', response.message);
                    }
                },
                error: function(xhr) {
                    if (deleteModal) deleteModal.hide();
                    eorNotify('error', (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete.');
                }
            });
            deleteOtherRequestUrl = '';
        });
    });
    </script>
@endpush
