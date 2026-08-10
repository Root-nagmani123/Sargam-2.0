@extends('admin.layouts.master')

@section('title', 'Possession Details - Sargam')

@php
    $estateSelfHomeTab = request('scope') === 'self'
        && (isEstateAuthority());
    $estateSelfQuery = $estateSelfHomeTab ? ['scope' => 'self'] : [];
    // Home ?scope=self is read-only; add / update / delete live under Setup → Estate.
    $canMutate = isEstateAuthority() && ! $estateSelfHomeTab;
@endphp
@section($estateSelfHomeTab ? 'content' : 'setup_content')
<div class="container-fluid rfe-page pd-page">
    <x-breadcrum title="Possession Details" :showBack="false">
        @if($canMutate)
            <a href="{{ route('admin.estate.update-meter-reading') }}" id="btnUpdateReading"
                class="btn ds-btn-cancel ds-btn-cancel--primary me-2">
                Update Reading
            </a>
            <a href="{{ route('admin.estate.possession-details.create') }}" id="btnAddPossession"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold text-nowrap shadow-sm">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>Add Possession</span>
            </a>
        @endif
    </x-breadcrum>

    <x-session_message />

    {{-- Exports sit above the card (docs/new-design-index-page.md §1). Both honour
         the applied filters, the search box and the Columns choice. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3">
        <button type="button" class="btn rfe-export-btn border-0" id="pdDownloadBtn">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </button>
        <button type="button" class="btn rfe-export-btn border-0" id="pdPrintBtn">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-1">
        <div class="card-body p-3 p-md-4" id="possessionDetailsCardBody">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>

                    <div class="programme-dt-filter-select">
                        <select id="pdEstateFilter" class="form-select" aria-label="Filter by estate name">
                            <option value="">Estate Name</option>
                            @foreach($estateCampuses ?? [] as $campus)
                                <option value="{{ $campus->pk }}">{{ $campus->campus_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <input type="date" id="pdAllotmentDateFilter" class="form-control"
                            aria-label="Filter by allotment date" placeholder="Allotment date">
                    </div>

                    <button type="button" id="pdClearFilter" class="btn programme-dt-btn-reset">
                        Remove Filter
                    </button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    @if($canMutate)
                        {{-- Only meaningful with a selection, so it stays hidden until there is one. --}}
                        <button type="button" class="btn pd-btn-bulk-delete d-none" id="btnBulkDeletePossessionDetails">
                            <span>Delete Selected Records</span>
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    @endif
                    <button type="button" class="btn programme-dt-btn-columns" id="pdBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#pdColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    {{-- Search is the toggle variant here (docs/new-design-index-page.md §2):
                         two filters plus a 300px search box do not fit this toolbar. --}}
                    <button type="button" class="btn pd-search-toggle" id="pdSearchToggle"
                        aria-expanded="false" aria-controls="pdDtSearch" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                    <div id="pdDtSearch" class="programme-dt-search d-none" data-dt-search-for="estatePossessionDetailsTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['aria-describedby' => 'estate-possession-details-caption']) !!}
                </div>
                {{-- Left empty on purpose: datatable-global-ui.js fills this with the
                     pagination and the "Showing [10] of N items" count. --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="estatePossessionDetailsTable"></div>
            </div>

            <div id="estate-possession-details-caption" class="visually-hidden">Possession details list</div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="pdColumnVisibilityModal" tabindex="-1" aria-labelledby="pdColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="pdColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="pdColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@if($canMutate)
{{-- Update Meter Reading — the filter half of the Update Meter Reading page.
     Submitting hands the chosen filters to that page, which prefills them and
     loads the readings grid (where the actual readings are typed and saved). --}}
<div class="modal fade ds-modal" id="updateMeterReadingModal" tabindex="-1" aria-labelledby="updateMeterReadingModalLabel"
    aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="formUpdateMeterReading" method="GET" action="{{ route('admin.estate.update-meter-reading') }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateMeterReadingModalLabel">Update Meter Reading</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="umr_bill_month" class="form-label">Meter Change Month <span class="ds-req">*</span></label>
                            <input type="month" class="form-control" id="umr_bill_month" name="bill_month"
                                max="{{ date('Y-m') }}" required>
                            <div class="text-danger small field-error" data-field="bill_month" role="alert"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="umr_campus_id" class="form-label">Estate Name <span class="ds-req">*</span></label>
                            <select class="form-select" id="umr_campus_id" name="campus_id" required>
                                <option value="">Select Estate</option>
                                @foreach($estateCampuses ?? [] as $campus)
                                    <option value="{{ $campus->pk }}">{{ $campus->campus_name }}</option>
                                @endforeach
                            </select>
                            <div class="text-danger small field-error" data-field="campus_id" role="alert"></div>
                        </div>

                        <div class="col-md-6">
                            <label for="umr_unit_type_id" class="form-label">Unit Name <span class="ds-req">*</span></label>
                            <select class="form-select" id="umr_unit_type_id" name="unit_type_id">
                                <option value="">Select Unit</option>
                                @foreach($estateUnitTypes ?? [] as $unitType)
                                    <option value="{{ $unitType->pk }}">{{ $unitType->unit_type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="umr_block_id" class="form-label">Building Name <span class="ds-req">*</span></label>
                            <select class="form-select" id="umr_block_id" name="block_id">
                                <option value="">Select Building</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="umr_unit_sub_type_id" class="form-label">Unit Sub-type <span class="ds-req">*</span></label>
                            <select class="form-select" id="umr_unit_sub_type_id" name="unit_sub_type_id">
                                <option value="">Select Sub-type</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="umr_meter_reading_date" class="form-label">Meter Reading Date <span class="ds-req">*</span></label>
                            <input type="date" class="form-control" id="umr_meter_reading_date" name="meter_reading_date" required>
                            <div class="text-danger small field-error" data-field="meter_reading_date" role="alert"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ds-btn-submit" id="btnSubmitUpdateMeterReading">Update Meter Reading</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Add / Edit Possession modal. The body is fetched from
     admin.estate.possession-details.create?modal=1 — the same payload the
     standalone page renders, so the two can't drift. --}}
<div class="modal fade ds-modal" id="possessionFormModal" tabindex="-1" aria-labelledby="possessionFormModalLabel"
    aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="possessionFormModalLabel">Add Possession Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="possessionFormModalContent">
                <div class="modal-body text-center text-body-secondary py-5">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading…
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Delete confirmation (single row and bulk share this dialog) -->
<div class="modal fade ds-modal ds-modal-confirm" id="deletePossessionDetailsModal" tabindex="-1"
    aria-labelledby="deletePossessionDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="ds-confirm-icon" aria-hidden="true">!</div>
                <h5 class="ds-confirm-title" id="deletePossessionDetailsModalLabel">Confirm Delete?</h5>
                <p class="ds-confirm-text" id="deletePossessionDetailsText">
                    Are you sure you want to delete the selected record? This action can't be undone.
                </p>
                <div class="alert alert-danger d-none mt-3 mb-0" id="deletePossessionDetailsError" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn ds-btn-danger" id="confirmDeletePossessionDetailsBtn">Yes, Delete</button>
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
        var $table = $('#estatePossessionDetailsTable');
        var bulkDeleteUrl = '{{ route('admin.estate.possession-details.bulk-delete') }}';
        var csrf = '{{ csrf_token() }}';

        function pdNotify(type, message) {
            var $host = $('#possessionDetailsCardBody');
            var cls = type === 'success' ? 'alert-success' : 'alert-danger';
            var icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
            $host.find('.' + cls).remove();
            $host.prepend('<div class="alert ' + cls + ' alert-dismissible fade show d-flex align-items-center rounded-1 shadow-sm" role="alert">' +
                '<i class="bi ' + icon + ' me-2"></i><span class="flex-grow-1">' + message + '</span>' +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
            if (type === 'success') {
                setTimeout(function() { $host.find('.alert-success').fadeOut(); }, 4000);
            }
        }

        function reloadTable() {
            if ($.fn.DataTable.isDataTable($table)) $table.DataTable().ajax.reload(null, false);
        }

        /* ---------- Filters ---------- */
        // estate_filter / allotment_date_filter ride on the ajax data callback
        // declared in the DataTable class.
        $(document).on('change', '#pdEstateFilter, #pdAllotmentDateFilter', reloadTable);

        $('#pdClearFilter').on('click', function() {
            $('#pdEstateFilter').val('');
            $('#pdAllotmentDateFilter').val('');
            if (!$.fn.DataTable.isDataTable($table)) return;
            // "Remove Filter" resets the whole toolbar, search included.
            $table.DataTable().search('').ajax.reload(null, false);
        });

        /* ---------- Update Meter Reading modal ---------- */
        var meterModalEl = document.getElementById('updateMeterReadingModal');
        var meterModal = meterModalEl ? new bootstrap.Modal(meterModalEl) : null;
        var meterBlocksUrl = '{{ route('admin.estate.update-meter-reading.blocks') }}';
        var meterUnitSubTypesUrl = '{{ route('admin.estate.update-meter-reading.unit-sub-types') }}';

        function fillMeterSelect($sel, items, placeholder, labelKey) {
            $sel.html('<option value="">' + placeholder + '</option>');
            (items || []).forEach(function(item) {
                $sel.append($('<option></option>').attr('value', item.pk).text(item[labelKey] || item.pk));
            });
        }

        $('#umr_campus_id').on('change', function() {
            fillMeterSelect($('#umr_block_id'), [], 'Select Building', 'block_name');
            fillMeterSelect($('#umr_unit_sub_type_id'), [], 'Select Sub-type', 'unit_sub_type');
            if (!this.value) return;
            $.get(meterBlocksUrl, { campus_id: this.value }, function(res) {
                if (res.status) fillMeterSelect($('#umr_block_id'), res.data, 'Select Building', 'block_name');
            });
        });

        $('#umr_block_id').on('change', function() {
            fillMeterSelect($('#umr_unit_sub_type_id'), [], 'Select Sub-type', 'unit_sub_type');
            var campusId = $('#umr_campus_id').val();
            if (!campusId || !this.value) return;
            $.get(meterUnitSubTypesUrl, { campus_id: campusId, block_id: this.value }, function(res) {
                if (res.status) fillMeterSelect($('#umr_unit_sub_type_id'), res.data, 'Select Sub-type', 'unit_sub_type');
            });
        });

        $('#btnUpdateReading').on('click', function(e) {
            if (!isPlainClick(e) || !meterModal) return;
            e.preventDefault();
            $('#formUpdateMeterReading')[0].reset();
            fillMeterSelect($('#umr_block_id'), [], 'Select Building', 'block_name');
            fillMeterSelect($('#umr_unit_sub_type_id'), [], 'Select Sub-type', 'unit_sub_type');
            $('#formUpdateMeterReading').find('.field-error').empty();
            meterModal.show();
        });

        // Plain GET navigation: the readings grid lives on the Update Meter Reading
        // page, which prefills from these params and loads itself.
        $('#formUpdateMeterReading').on('submit', function() {
            $('#btnSubmitUpdateMeterReading').prop('disabled', true).text('Loading…');
        });

        /* ---------- Add / Edit Possession modal ---------- */
        var formModalEl = document.getElementById('possessionFormModal');
        var formModal = formModalEl ? new bootstrap.Modal(formModalEl) : null;
        var formModalUrl = '{{ route('admin.estate.possession-details.create') }}';

        function openPossessionForm(requesterId) {
            if (!formModal) return;
            var isEdit = !!requesterId;
            $('#possessionFormModalLabel').text(isEdit ? 'Edit Possession Request' : 'Add Possession Request');
            $('#possessionFormModalContent').html(
                '<div class="modal-body text-center text-body-secondary py-5">' +
                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading…</div>');
            formModal.show();

            $.get(formModalUrl, isEdit ? { modal: 1, requester_id: requesterId } : { modal: 1 })
                .done(function(html) {
                    // The partial ships its own cascade + submit wiring and runs on inject.
                    $('#possessionFormModalContent').html(html);
                })
                .fail(function(xhr) {
                    formModal.hide();
                    pdNotify('error', (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Unable to open the possession form.');
                });
        }

        // Both triggers keep their href to the standalone page (ctrl-click / no-JS),
        // so intercept plain left clicks only.
        function isPlainClick(e) {
            return !(e.which > 1 || e.ctrlKey || e.metaKey || e.shiftKey);
        }

        $('#btnAddPossession').on('click', function(e) {
            if (!isPlainClick(e) || !formModal) return;
            e.preventDefault();
            openPossessionForm(null);
        });

        $(document).on('click', '.btn-edit-possession-details', function(e) {
            if (!isPlainClick(e) || !formModal) return;
            e.preventDefault();
            openPossessionForm($(this).data('requester-id'));
        });

        document.addEventListener('pd:possession-saved', function(e) {
            if (formModal) formModal.hide();
            reloadTable();
            pdNotify('success', e.detail.message);
        });

        /* ---------- Search toggle ---------- */
        $('#pdSearchToggle').on('click', function() {
            var $slot = $('#pdDtSearch');
            var opening = $slot.hasClass('d-none');
            $slot.toggleClass('d-none', !opening);
            $(this).attr('aria-expanded', opening ? 'true' : 'false');
            if (opening) {
                $slot.find('input').trigger('focus');
            } else if ($.fn.DataTable.isDataTable($table) && $table.DataTable().search()) {
                // Collapsing clears the query — a hidden active filter is a trap.
                $table.DataTable().search('').draw();
                $slot.find('input').val('');
            }
        });

        /* ---------- Row selection ---------- */
        function selectedIds() {
            return $table.find('input.row-select-possession-details:checked').map(function() {
                var id = parseInt($(this).data('id'), 10);
                return (!isNaN(id) && id > 0) ? id : null;
            }).get();
        }

        function syncSelection() {
            var $rows = $table.find('input.row-select-possession-details');
            var n = $rows.filter(':checked').length;

            var all = document.getElementById('selectAllPossessionDetails');
            if (all) {
                all.checked = $rows.length > 0 && n === $rows.length;
                all.indeterminate = n > 0 && n < $rows.length;
            }
            // The bulk button only exists while there is something to act on.
            $('#btnBulkDeletePossessionDetails').toggleClass('d-none', n === 0);
        }

        $(document).on('change', '#selectAllPossessionDetails', function() {
            $table.find('input.row-select-possession-details').prop('checked', this.checked);
            syncSelection();
        });

        $(document).on('change', 'input.row-select-possession-details', syncSelection);

        // A redraw replaces the rows, so the previous page's ticks are gone.
        $table.on('draw.dt', syncSelection);

        /* ---------- Delete (single row + bulk share one dialog) ---------- */
        var deleteModal = new bootstrap.Modal(document.getElementById('deletePossessionDetailsModal'));
        var pendingDeleteIds = [];

        function askDelete(ids) {
            pendingDeleteIds = ids;
            $('#deletePossessionDetailsText').text(
                ids.length > 1
                    ? "Are you sure you want to delete the " + ids.length + " selected records? This action can't be undone."
                    : "Are you sure you want to delete the selected record? This action can't be undone."
            );
            $('#deletePossessionDetailsError').addClass('d-none').text('');
            deleteModal.show();
        }

        $(document).on('click', '.btn-delete-possession-details', function(e) {
            e.preventDefault();
            var id = parseInt($(this).data('id'), 10);
            if (!isNaN(id) && id > 0) askDelete([id]);
        });

        $('#btnBulkDeletePossessionDetails').on('click', function() {
            var ids = selectedIds();
            if (ids.length) askDelete(ids);
        });

        $('#confirmDeletePossessionDetailsBtn').on('click', function() {
            if (!pendingDeleteIds.length) return;
            var $btn = $(this);
            var $error = $('#deletePossessionDetailsError');
            $error.addClass('d-none').text('');
            $btn.prop('disabled', true).text('Deleting…');

            // Single and bulk both go through the bulk endpoint — one code path,
            // one set of guards (it refuses records that already have readings).
            $.ajax({
                url: bulkDeleteUrl,
                type: 'POST',
                data: { _token: csrf, ids: pendingDeleteIds },
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function(res) {
                    deleteModal.hide();
                    reloadTable();
                    pendingDeleteIds = [];
                    $('#btnBulkDeletePossessionDetails').addClass('d-none');
                    pdNotify('success', (res && res.message) || 'Deleted successfully.');
                },
                error: function(xhr) {
                    $error.removeClass('d-none').text((xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Delete failed. Please try again.');
                },
                complete: function() { $btn.prop('disabled', false).text('Yes, Delete'); }
            });
        });

        /* ---------- Column visibility (persisted per browser, per user) ---------- */
        var pdColStorageKey = 'sargam.possessionDetails.hiddenCols.{{ auth()->id() ?? 'guest' }}';
        // Header title -> export column key. Keyed by TITLE, not index: the checkbox
        // and Action columns only exist for some roles, so indices shift.
        var PD_EXPORT_KEY_BY_TITLE = {
            'S. No.': 'sno',
            'Request ID': 'request_id',
            'Name & ID': 'name_id',
            'Designation': 'emp_designation',
            'Estate Name': 'estate_name',
            'Building Name': 'building_name',
            'Unit Type': 'unit_type',
            'Unit Sub Type': 'unit_sub_type',
            'House Number': 'house_no',
            'Allotment Date': 'allotment_date',
            'Possession Date': 'possession_date',
            'Last Electric Bill Reading': 'electric_meter_reading'
        };

        function pdGetHiddenCols() {
            try {
                var raw = localStorage.getItem(pdColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return []; // private mode / storage disabled / corrupt value
            }
        }

        function pdPersistHiddenCols(arr) {
            try { localStorage.setItem(pdColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        // Stored as TITLES so adding a column never hides the wrong one later.
        function pdVisibleExportCols() {
            var hidden = pdGetHiddenCols();
            return Object.keys(PD_EXPORT_KEY_BY_TITLE)
                .filter(function(title) { return hidden.indexOf(title) === -1; })
                .map(function(title) { return PD_EXPORT_KEY_BY_TITLE[title]; });
        }

        function pdColTitle(col) {
            return $(col.header()).text().replace(/\s+/g, ' ').trim();
        }

        function setupPdColumns(dt) {
            if (!dt) return;
            var hidden = pdGetHiddenCols();

            dt.columns().every(function() {
                var title = pdColTitle(this);
                // Skip the selection checkbox (untitled), the always-hidden "ID"
                // sort column, and Action — which must stay reachable.
                if (!title || title === 'Action' || title === 'ID') return;
                this.visible(hidden.indexOf(title) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#pdColumnToggleGrid');
            if (!$grid.length) return;
            $grid.empty();

            dt.columns().every(function() {
                var idx = this.index();
                var title = pdColTitle(this);
                if (!title || title === 'Action' || title === 'ID') return;

                var inputId = 'pdcolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(title) === -1);

                $cb.on('change', function() {
                    var h = pdGetHiddenCols();
                    var pos = h.indexOf(title);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(title);
                    }
                    pdPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        $table.on('init.dt', function() { setupPdColumns($(this).DataTable()); });
        if ($.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
            setupPdColumns($table.DataTable());
        }

        /* ---------- Download / Print ---------- */
        function pdExportParams() {
            var params = {};
            if ($('#pdEstateFilter').val()) params.estate_filter = $('#pdEstateFilter').val();
            if ($('#pdAllotmentDateFilter').val()) params.allotment_date_filter = $('#pdAllotmentDateFilter').val();
            if ($.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
                var searchValue = $table.DataTable().search();
                if (searchValue) params.search = searchValue;
            }
            var scope = new URLSearchParams(window.location.search).get('scope');
            if (scope) params.scope = scope;
            params.cols = pdVisibleExportCols().join(',');
            return params;
        }

        $('#pdDownloadBtn').on('click', function() {
            window.location.href = '{{ route('admin.estate.possession-details.export') }}?' + $.param(pdExportParams());
        });

        $('#pdPrintBtn').on('click', function() {
            window.open('{{ route('admin.estate.possession-details.print') }}?' + $.param(pdExportParams()), '_blank');
        });

        syncSelection();
    });
    </script>
@endpush
