@extends('admin.layouts.master')

@section('title', 'Estate Possession for Others')

@php
    $canMutate = isEstateAuthority();
@endphp

@section('setup_content')
<div class="container-fluid rfe-page epo-page">
    <x-breadcrum title="Estate Possession for Others" :showBack="false">
        <a href="{{ route('admin.estate.update-meter-reading-of-other') }}" id="btnUpdateReading"
            class="btn ds-btn-cancel ds-btn-cancel--primary me-2">
            Update Reading
        </a>
        <a href="{{ route('admin.estate.possession-view') }}" id="btnAddPossession"
            class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 rounded-1 fw-semibold text-nowrap shadow-sm">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            <span>Add Possession</span>
        </a>
    </x-breadcrum>

    <x-session_message />

    {{-- Exports sit above the card (docs/new-design-index-page.md §1). Both honour
         the applied filters, the search box and the Columns choice. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3">
        <button type="button" class="btn rfe-export-btn border-0" id="epoDownloadBtn">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </button>
        <button type="button" class="btn rfe-export-btn border-0" id="epoPrintBtn">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-1">
        <div class="card-body p-3 p-md-4" id="possessionCardBody">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filter</span>

                    <div class="programme-dt-filter-select">
                        <select id="epoEstateFilter" class="form-select" aria-label="Filter by estate name">
                            <option value="">Estate Name</option>
                            @foreach($estateCampuses ?? [] as $campus)
                                <option value="{{ $campus->pk }}">{{ $campus->campus_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <input type="date" id="epoAllotmentDateFilter" class="form-control"
                            aria-label="Filter by allotment date" title="Allotment date">
                    </div>

                    <button type="button" id="epoClearFilter" class="btn programme-dt-btn-reset">
                        Remove Filter
                    </button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    @if($canMutate)
                        {{-- Only meaningful with a selection, so it stays hidden until there is one. --}}
                        <button type="button" class="btn pd-btn-bulk-delete d-none" id="btnBulkDeletePossessionOthers">
                            <span>Delete Selected Records</span>
                            <i class="bi bi-trash" aria-hidden="true"></i>
                        </button>
                    @endif
                    <button type="button" class="btn programme-dt-btn-columns" id="epoBtnColumns"
                        data-bs-toggle="modal" data-bs-target="#epoColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    {{-- Search is the toggle variant here (docs/new-design-index-page.md §2):
                         two filters plus a 300px search box do not fit this toolbar. --}}
                    <button type="button" class="btn pd-search-toggle" id="epoSearchToggle"
                        aria-expanded="false" aria-controls="epoDtSearch" title="Search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span class="visually-hidden">Search</span>
                    </button>
                    <div id="epoDtSearch" class="programme-dt-search d-none" data-dt-search-for="estatePossessionTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {!! $dataTable->table(['aria-describedby' => 'estate-possession-caption']) !!}
                </div>
                {{-- Left empty on purpose: datatable-global-ui.js fills this with the
                     pagination and the "Showing [10] of N items" count. --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                    data-dt-footer-for="estatePossessionTable"></div>
            </div>

            <div id="estate-possession-caption" class="visually-hidden">Estate Possession for Others list</div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="epoColumnVisibilityModal" tabindex="-1" aria-labelledby="epoColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="epoColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="epoColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Update Meter Reading. The body (filters + readings grid) is fetched from
     admin.estate.update-meter-reading-of-other?modal=1 — the same partial the
     standalone page renders, so the two can't drift. --}}
<div class="modal fade ds-modal epo-modal" id="updateMeterReadingModal" tabindex="-1" aria-labelledby="updateMeterReadingModalLabel"
    aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateMeterReadingModalLabel">Update Meter Reading</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="updateMeterReadingModalContent">
                <div class="modal-body text-center text-body-secondary py-5">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading…
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add / Edit Possession. The body is fetched from
     admin.estate.possession-view?modal=1 — the same partial the standalone page
     renders, so the two can't drift. --}}
<div class="modal fade ds-modal epo-modal" id="possessionFormModal" tabindex="-1" aria-labelledby="possessionFormModalLabel"
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

@if($canMutate)
<!-- Delete confirmation (single row and bulk share this dialog) -->
<div class="modal fade ds-modal ds-modal-confirm" id="deletePossessionOthersModal" tabindex="-1"
    aria-labelledby="deletePossessionOthersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="ds-confirm-icon" aria-hidden="true">!</div>
                <h5 class="ds-confirm-title" id="deletePossessionOthersModalLabel">Confirm Delete?</h5>
                <p class="ds-confirm-text" id="deletePossessionOthersText">
                    Are you sure you want to delete the selected record? This action can't be undone.
                </p>
                <div class="alert alert-danger d-none mt-3 mb-0" id="deletePossessionOthersError" role="alert"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn ds-btn-danger" id="confirmDeletePossessionOthersBtn">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/estate-request-admin.css') }}?v={{ @filemtime(public_path('css/estate-request-admin.css')) ?: time() }}">
@endpush

@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
    $(function() {
        var $table = $('#estatePossessionTable');
        var canMutate = @json($canMutate);
        var bulkDeleteUrl = '{{ route('admin.estate.possession-bulk-delete') }}';
        var csrf = '{{ csrf_token() }}';

        function epoNotify(type, message) {
            var $host = $('#possessionCardBody');
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

        function reloadTable() {
            if ($.fn.DataTable.isDataTable($table)) $table.DataTable().ajax.reload(null, false);
        }

        /* ---------- Update Meter Reading modal ---------- */
        // Both header buttons keep their href to the standalone page (ctrl-click /
        // no-JS), so intercept plain left clicks only.
        function isPlainClick(e) {
            return !(e.which > 1 || e.ctrlKey || e.metaKey || e.shiftKey);
        }

        function modalLoading() {
            return '<div class="modal-body text-center text-body-secondary py-5">' +
                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading…</div>';
        }

        var meterModalEl = document.getElementById('updateMeterReadingModal');
        var meterModal = meterModalEl ? new bootstrap.Modal(meterModalEl) : null;

        $('#btnUpdateReading').on('click', function(e) {
            if (!isPlainClick(e) || !meterModal) return;
            e.preventDefault();
            $('#updateMeterReadingModalContent').html(modalLoading());
            meterModal.show();

            $.get('{{ route('admin.estate.update-meter-reading-of-other') }}', { modal: 1 })
                .done(function(html) {
                    // The partial ships its own cascade, grid and save wiring.
                    $('#updateMeterReadingModalContent').html(html);
                })
                .fail(function(xhr) {
                    meterModal.hide();
                    epoNotify('error', (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Unable to open the meter reading form.');
                });
        });

        document.addEventListener('epo:readings-saved', function(e) {
            if (meterModal) meterModal.hide();
            reloadTable();
            epoNotify('success', e.detail.message);
        });

        /* ---------- Add / Edit Possession modal ---------- */
        var formModalEl = document.getElementById('possessionFormModal');
        var formModal = formModalEl ? new bootstrap.Modal(formModalEl) : null;
        var formModalUrl = '{{ route('admin.estate.possession-view') }}';

        function openPossessionForm(id) {
            if (!formModal) return;
            var isEdit = !!id;
            $('#possessionFormModalLabel').text(isEdit ? 'Edit Possession Request' : 'Add Possession Request');
            $('#possessionFormModalContent').html(modalLoading());
            formModal.show();

            $.get(formModalUrl, isEdit ? { modal: 1, id: id } : { modal: 1 })
                .done(function(html) {
                    // The partial ships its own cascade + submit wiring and runs on inject.
                    $('#possessionFormModalContent').html(html);
                })
                .fail(function(xhr) {
                    formModal.hide();
                    epoNotify('error', (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Unable to open the possession form.');
                });
        }

        $('#btnAddPossession').on('click', function(e) {
            if (!isPlainClick(e) || !formModal) return;
            e.preventDefault();
            openPossessionForm(null);
        });

        $(document).on('click', '.btn-edit-possession-other', function(e) {
            if (!isPlainClick(e) || !formModal) return;
            e.preventDefault();
            openPossessionForm($(this).data('id'));
        });

        document.addEventListener('epo:possession-saved', function(e) {
            if (formModal) formModal.hide();
            reloadTable();
            epoNotify('success', e.detail.message);
        });

        /* ---------- Filters ---------- */
        // estate_filter / allotment_date_filter ride on the ajax data callback
        // declared in the DataTable class.
        $(document).on('change', '#epoEstateFilter, #epoAllotmentDateFilter', reloadTable);

        $('#epoClearFilter').on('click', function() {
            $('#epoEstateFilter').val('');
            $('#epoAllotmentDateFilter').val('');
            if (!$.fn.DataTable.isDataTable($table)) return;
            // "Remove Filter" resets the whole toolbar, search included.
            $table.DataTable().search('').ajax.reload(null, false);
        });

        /* ---------- Search toggle ---------- */
        $('#epoSearchToggle').on('click', function() {
            var $slot = $('#epoDtSearch');
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
            return $table.find('input.row-select-possession:checked').map(function() {
                var id = parseInt($(this).data('id'), 10);
                return (!isNaN(id) && id > 0) ? id : null;
            }).get();
        }

        function syncSelection() {
            var $rows = $table.find('input.row-select-possession');
            var n = $rows.filter(':checked').length;

            var all = document.getElementById('selectAllPossessionOthers');
            if (all) {
                all.checked = $rows.length > 0 && n === $rows.length;
                all.indeterminate = n > 0 && n < $rows.length;
            }
            // The bulk button only exists while there is something to act on.
            $('#btnBulkDeletePossessionOthers').toggleClass('d-none', n === 0);
        }

        $(document).on('change', '#selectAllPossessionOthers', function() {
            $table.find('input.row-select-possession').prop('checked', this.checked);
            syncSelection();
        });

        $(document).on('change', 'input.row-select-possession', syncSelection);

        // A redraw replaces the rows, so the previous page's ticks are gone.
        $table.on('draw.dt', syncSelection);

        /* ---------- Delete (single row + bulk share one dialog) ---------- */
        var deleteModalEl = document.getElementById('deletePossessionOthersModal');
        var deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
        var pendingDeleteIds = [];

        function askDelete(ids) {
            if (!deleteModal) return;
            pendingDeleteIds = ids;
            $('#deletePossessionOthersText').text(
                ids.length > 1
                    ? "Are you sure you want to delete the " + ids.length + " selected records? This action can't be undone."
                    : "Are you sure you want to delete the selected record? This action can't be undone."
            );
            $('#deletePossessionOthersError').addClass('d-none').text('');
            deleteModal.show();
        }

        $(document).on('click', '.btn-delete-possession-other', function(e) {
            e.preventDefault();
            var id = parseInt($(this).data('id'), 10);
            if (!isNaN(id) && id > 0) askDelete([id]);
        });

        $('#btnBulkDeletePossessionOthers').on('click', function() {
            var ids = selectedIds();
            if (ids.length) askDelete(ids);
        });

        $('#confirmDeletePossessionOthersBtn').on('click', function() {
            if (!pendingDeleteIds.length) return;
            var $btn = $(this);
            var $error = $('#deletePossessionOthersError');
            $error.addClass('d-none').text('');
            $btn.prop('disabled', true).text('Deleting…');

            // Single and bulk both go through the bulk endpoint — one code path,
            // one set of guards.
            $.ajax({
                url: bulkDeleteUrl,
                type: 'POST',
                data: { _token: csrf, ids: pendingDeleteIds },
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function(res) {
                    if (deleteModal) deleteModal.hide();
                    reloadTable();
                    pendingDeleteIds = [];
                    $('#btnBulkDeletePossessionOthers').addClass('d-none');
                    epoNotify('success', (res && res.message) || 'Deleted successfully.');
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
        var epoColStorageKey = 'sargam.possessionForOthers.hiddenCols.{{ auth()->id() ?? 'guest' }}';
        // Header title -> export column key. Keyed by TITLE, not index: the checkbox
        // column only exists for estate authority, so indices shift.
        var EPO_EXPORT_KEY_BY_TITLE = {
            'S. No.': 'sno',
            'Request ID': 'request_id',
            'Employee Name': 'name',
            'Section Name': 'section_name',
            'Estate Name': 'estate_name',
            'Building Name': 'building_name',
            'Unit Type': 'unit_type',
            'Unit Sub Type': 'unit_sub_type',
            'House Number': 'house_no',
            'Allotment Date': 'allotment_date',
            'Possession Date': 'possession_date',
            'Last Electric Bill Reading': 'electric_meter_reading'
        };

        function epoGetHiddenCols() {
            try {
                var raw = localStorage.getItem(epoColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return []; // private mode / storage disabled / corrupt value
            }
        }

        function epoPersistHiddenCols(arr) {
            try { localStorage.setItem(epoColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        // Stored as TITLES so adding a column never hides the wrong one later.
        function epoVisibleExportCols() {
            var hidden = epoGetHiddenCols();
            return Object.keys(EPO_EXPORT_KEY_BY_TITLE)
                .filter(function(title) { return hidden.indexOf(title) === -1; })
                .map(function(title) { return EPO_EXPORT_KEY_BY_TITLE[title]; });
        }

        function epoColTitle(col) {
            return $(col.header()).text().replace(/\s+/g, ' ').trim();
        }

        function setupEpoColumns(dt) {
            if (!dt) return;
            var hidden = epoGetHiddenCols();

            dt.columns().every(function() {
                var title = epoColTitle(this);
                // Skip the selection checkbox (untitled) and Action, which must stay reachable.
                if (!title || title === 'Action') return;
                this.visible(hidden.indexOf(title) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#epoColumnToggleGrid');
            if (!$grid.length) return;
            $grid.empty();

            dt.columns().every(function() {
                var idx = this.index();
                var title = epoColTitle(this);
                if (!title || title === 'Action') return;

                var inputId = 'epocolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(title) === -1);

                $cb.on('change', function() {
                    var h = epoGetHiddenCols();
                    var pos = h.indexOf(title);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(title);
                    }
                    epoPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        $table.on('init.dt', function() { setupEpoColumns($(this).DataTable()); });
        // Yajra may have finished initialising before this handler was bound.
        if ($.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
            setupEpoColumns($table.DataTable());
        }

        /* ---------- Download / Print ---------- */
        function epoExportParams() {
            var params = {};
            if ($('#epoEstateFilter').val()) params.estate_filter = $('#epoEstateFilter').val();
            if ($('#epoAllotmentDateFilter').val()) params.allotment_date_filter = $('#epoAllotmentDateFilter').val();
            if ($.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
                var searchValue = $table.DataTable().search();
                if (searchValue) params.search = searchValue;
            }
            params.cols = epoVisibleExportCols().join(',');
            return params;
        }

        $('#epoDownloadBtn').on('click', function() {
            window.location.href = '{{ route('admin.estate.possession-for-others.export') }}?' + $.param(epoExportParams());
        });

        $('#epoPrintBtn').on('click', function() {
            window.open('{{ route('admin.estate.possession-for-others.print') }}?' + $.param(epoExportParams()), '_blank');
        });

        if (canMutate) syncSelection();
    });
    </script>
@endpush
