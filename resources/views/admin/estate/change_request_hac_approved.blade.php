@extends('admin.layouts.master')

@section('title', 'HAC Approval - Sargam')

@php
    $estateSelfHomeTab = request('scope') === 'self'
        && (isEstateAuthority());
@endphp
@section($estateSelfHomeTab ? 'content' : 'setup_content')
<div class="container-fluid rfe-page hac-page">
    <x-breadcrum title="HAC Approval" :showBack="false" />

    <x-session_message />

    {{-- Exports sit above the card (docs/new-design-index-page.md §1). Both honour
         the applied Request Type filter, the search box and the Columns choice. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-end gap-2 mb-3">
        <button type="button" class="btn rfe-export-btn border-0" id="hacDownloadBtn">
            <i class="bi bi-download" aria-hidden="true"></i>
            <span>Download</span>
        </button>
        <button type="button" class="btn rfe-export-btn border-0" id="hacPrintBtn">
            <i class="bi bi-printer" aria-hidden="true"></i>
            <span>Print</span>
        </button>
    </div>

    <div class="card overflow-hidden rounded-1">
        <div class="card-body p-3 p-md-4">
            <div id="hac-approved-card-body">

                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <span class="programme-dt-filters-label">Filter</span>

                        <div class="programme-dt-filter-select">
                            <select id="hacApprovedTypeFilter" class="form-select" aria-label="Filter by request type">
                                <option value="">Request Type</option>
                                <option value="change">Change Request</option>
                                <option value="new">New Request</option>
                            </select>
                        </div>

                        <button type="button" id="hacClearFilter" class="btn programme-dt-btn-reset">
                            Remove Filter
                        </button>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                        <button type="button" class="btn programme-dt-btn-columns" id="hacBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#hacColumnVisibilityModal"
                            title="Show / hide columns">
                            <span>Columns</span>
                            <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                        </button>
                        <div id="hacDtSearch" class="programme-dt-search" data-dt-search-for="estateHacApprovedTable"></div>
                    </div>
                </div>

                <div class="programme-dt-panel">
                    <div class="table-responsive">
                        {!! $dataTable->table(['aria-describedby' => 'hac-approved-caption']) !!}
                    </div>
                    {{-- Left empty on purpose: datatable-global-ui.js fills this with the
                         pagination and the "Showing [10] of N items" count. --}}
                    <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3"
                        data-dt-footer-for="estateHacApprovedTable"></div>
                </div>

                <div id="hac-approved-caption" class="visually-hidden">HAC Approval – change and new requests</div>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="hacColumnVisibilityModal" tabindex="-1" aria-labelledby="hacColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="hacColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="hacColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Approve — confirm only. Used when the house was already chosen at Raise
     Change Request time, so there is nothing left to pick. --}}
<div class="modal fade ds-modal ds-modal-confirm ds-modal-confirm--success" id="approveConfirmModal" tabindex="-1"
    aria-labelledby="approveConfirmModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formApproveConfirm" method="POST" action="">
                @csrf
                <input type="hidden" name="estate_house_master_pk" id="approveConfirmHousePk" value="">
                <div class="modal-body">
                    <div class="ds-confirm-icon" aria-hidden="true">&check;</div>
                    <h5 class="ds-confirm-title" id="approveConfirmModalLabel">Confirm Approval?</h5>
                    <p class="ds-confirm-text">
                        Are you sure you want to approve this Estate Request? This action can't be undone.
                    </p>
                    <p class="ds-confirm-text mt-2 mb-0" id="approveConfirmDetail"></p>
                    <div class="alert alert-danger d-none mt-3 mb-0" id="approveConfirmError" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn ds-btn-cancel ds-btn-cancel--success" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ds-btn-success" id="btnSubmitApproveConfirm">Yes, Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Approve — house picker. Used when no house was chosen at raise time. --}}
<div class="modal fade ds-modal" id="approveHouseModal" tabindex="-1" aria-labelledby="approveHouseModalLabel"
    aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="formApproveHouse" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="approveHouseModalLabel">Allot House</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="approveModalLoading" class="text-center py-5 d-none">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading details…
                    </div>
                    <div id="approveModalContent" class="d-none">
                        <div class="alert alert-warning d-flex align-items-start gap-2 d-none" id="approveNoHouses" role="alert">
                            <i class="bi bi-info-circle-fill flex-shrink-0" aria-hidden="true"></i>
                            <span>No vacant houses available. Select Estate, Unit Type, Building, and Unit Sub Type first.</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="approveRequesterName" class="form-label">Requester Name <span class="ds-req">*</span></label>
                                <input type="text" class="form-control" id="approveRequesterName" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="approveDesignation" class="form-label">Requester Designation <span class="ds-req">*</span></label>
                                <input type="text" class="form-control" id="approveDesignation" readonly>
                            </div>
                        </div>

                        <h6 class="ds-modal-section-title mt-4">Allotment Details</h6>

                        <div class="row g-3 mt-0 hac-allot-grid">
                            <div class="col-6 col-lg">
                                <label for="approve_estate_campus" class="form-label">Estate Name <span class="ds-req">*</span></label>
                                <select class="form-select" id="approve_estate_campus"><option value="">Select Estate</option></select>
                            </div>
                            <div class="col-6 col-lg">
                                <label for="approve_unit_type" class="form-label">Unit Type <span class="ds-req">*</span></label>
                                <select class="form-select" id="approve_unit_type"><option value="">Select Unit</option></select>
                            </div>
                            <div class="col-6 col-lg">
                                <label for="approve_building" class="form-label">Building Name <span class="ds-req">*</span></label>
                                <select class="form-select" id="approve_building"><option value="">Select Building</option></select>
                            </div>
                            <div class="col-6 col-lg">
                                <label for="approve_unit_sub_type" class="form-label">Unit Sub-type <span class="ds-req">*</span></label>
                                <select class="form-select" id="approve_unit_sub_type"><option value="">Select Sub-type</option></select>
                            </div>
                            <div class="col-6 col-lg">
                                <label for="estate_house_master_pk" class="form-label">House Number <span class="ds-req">*</span></label>
                                <select class="form-select" id="estate_house_master_pk" name="estate_house_master_pk" required><option value="">Select House</option></select>
                            </div>
                        </div>

                        <div class="alert alert-danger d-none mt-3 mb-0" id="approveFormError" role="alert"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ds-btn-submit" id="btnSubmitApprove">Allot House</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Reject. The confirm styling is the mockup's; the reason field stays because
     the server requires a remark on every disapproval (audit trail). --}}
<div class="modal fade ds-modal ds-modal-confirm" id="disapproveChangeRequestModal" tabindex="-1"
    aria-labelledby="disapproveChangeRequestModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formDisapproveChangeRequest" method="POST" action="">
                @csrf
                <div class="modal-body">
                    <div class="ds-confirm-icon" aria-hidden="true">!</div>
                    <h5 class="ds-confirm-title" id="disapproveChangeRequestModalLabel">Confirm Reject?</h5>
                    <p class="ds-confirm-text">
                        Are you sure you want to reject this Estate Request? This action can't be undone.
                    </p>
                    <p class="ds-confirm-text mt-2">Request ID: <strong id="disapproveModalRequestId"></strong></p>

                    <div class="text-start mt-3">
                        <label for="disapprove_reason" class="form-label">Reason for rejection <span class="ds-req">*</span></label>
                        <textarea class="form-control" id="disapprove_reason" name="disapprove_reason" rows="3" maxlength="500"
                            placeholder="e.g. No suitable vacancy in the requested block" required></textarea>
                        <div class="form-text">Saved against the request and shown in the listing.</div>
                    </div>

                    <div class="alert alert-danger d-none mt-3 mb-0" id="disapproveFormError" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ds-btn-danger" id="btnSubmitDisapprove">Yes, Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Allot House — new requests, adds the record to Possession Details. --}}
<div class="modal fade ds-modal" id="allotNewRequestModal" tabindex="-1" aria-labelledby="allotNewRequestModalLabel"
    aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="formAllotNewRequest" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="allotNewRequestModalLabel">Allot House</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="allotModalLoading" class="text-center py-5 d-none">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Loading details…
                    </div>
                    <div id="allotModalContent" class="d-none">
                        <div class="alert alert-warning d-flex align-items-start gap-2 d-none" id="allotNoHouses" role="alert">
                            <i class="bi bi-info-circle-fill flex-shrink-0" aria-hidden="true"></i>
                            <span>No vacant houses available. Select Estate, Unit Type, Building, and Unit Sub Type first.</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="allotRequesterName" class="form-label">Requester Name <span class="ds-req">*</span></label>
                                <input type="text" class="form-control" id="allotRequesterName" readonly>
                            </div>
                            <div class="col-md-6">
                                <label for="allotDesignation" class="form-label">Requester Designation <span class="ds-req">*</span></label>
                                <input type="text" class="form-control" id="allotDesignation" readonly>
                            </div>
                        </div>

                        <h6 class="ds-modal-section-title mt-4">Allotment Details</h6>

                        <div class="row g-3 mt-0 hac-allot-grid">
                            <div class="col-6 col-lg">
                                <label for="allot_estate_campus" class="form-label">Estate Name <span class="ds-req">*</span></label>
                                <select class="form-select allot-required" id="allot_estate_campus"><option value="">Select Estate</option></select>
                            </div>
                            <div class="col-6 col-lg">
                                <label for="allot_unit_type" class="form-label">Unit Type <span class="ds-req">*</span></label>
                                <select class="form-select allot-required" id="allot_unit_type"><option value="">Select Unit</option></select>
                            </div>
                            <div class="col-6 col-lg">
                                <label for="allot_building" class="form-label">Building Name <span class="ds-req">*</span></label>
                                <select class="form-select allot-required" id="allot_building"><option value="">Select Building</option></select>
                            </div>
                            <div class="col-6 col-lg">
                                <label for="allot_unit_sub_type" class="form-label">Unit Sub-type <span class="ds-req">*</span></label>
                                <select class="form-select allot-required" id="allot_unit_sub_type"><option value="">Select Sub-type</option></select>
                            </div>
                            <div class="col-6 col-lg">
                                <label for="allot_estate_house_master_pk" class="form-label">House Number <span class="ds-req">*</span></label>
                                <select class="form-select allot-required" id="allot_estate_house_master_pk" name="estate_house_master_pk" required><option value="">Select House</option></select>
                            </div>
                        </div>

                        <div class="alert alert-danger d-none mt-3 mb-0" id="allotFormError" role="alert"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn ds-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ds-btn-submit" id="btnSubmitAllot" disabled>Allot House</button>
                </div>
            </form>
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
        var $table = $('#estateHacApprovedTable');
        var blocksUrl = '{{ route("admin.estate.possession.blocks") }}';
        var unitSubTypesUrl = '{{ route("admin.estate.possession.unit-sub-types") }}';
        var vacantHousesUrl = '{{ route("admin.estate.change-request.vacant-houses") }}';

        function hacNotify(type, message) {
            var $host = $('#hac-approved-card-body');
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

        /* ---------- Filter ---------- */
        // type_filter rides on the ajax data callback declared in the DataTable class.
        $(document).on('change', '#hacApprovedTypeFilter', reloadTable);

        $('#hacClearFilter').on('click', function() {
            $('#hacApprovedTypeFilter').val('');
            if (!$.fn.DataTable.isDataTable($table)) return;
            // "Remove Filter" resets the whole toolbar, search included.
            $table.DataTable().search('').ajax.reload(null, false);
        });

        /* ---------- Column visibility (persisted per browser, per user) ---------- */
        // Header index -> export column key. POSITIONAL: adding a table column means
        // adding an entry here too. Index 1 is the hidden Request Date column.
        var HAC_EXPORT_COLUMN_KEYS = ['sno', '', 'request_type', 'request_id', 'name_id', 'emp_designation', 'pay_scale', ''];
        var hacColStorageKey = 'sargam.hacApproved.hiddenCols.{{ auth()->id() ?? 'guest' }}';

        function hacGetHiddenCols() {
            try {
                var raw = localStorage.getItem(hacColStorageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) {
                return []; // private mode / storage disabled / corrupt value
            }
        }

        function hacPersistHiddenCols(arr) {
            try { localStorage.setItem(hacColStorageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function hacVisibleExportCols() {
            var hidden = hacGetHiddenCols();
            return HAC_EXPORT_COLUMN_KEYS.filter(function(key, idx) {
                return key !== '' && hidden.indexOf(idx) === -1;
            });
        }

        function setupHacColumns(dt) {
            if (!dt) return;
            var hidden = hacGetHiddenCols();

            dt.columns().every(function() {
                var idx = this.index();
                // Request Date (index 1) is deliberately hidden by the DataTable —
                // it exists only to drive the default sort, so leave it alone.
                if (idx === 1) return;
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#hacColumnToggleGrid');
            if (!$grid.length) return;
            $grid.empty();

            dt.columns().every(function() {
                var idx = this.index();
                if (idx === 1) return;
                var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) return;

                var inputId = 'haccolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-1 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function() {
                    var h = hacGetHiddenCols();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    hacPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        $table.on('init.dt', function() { setupHacColumns($(this).DataTable()); });
        if ($.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
            setupHacColumns($table.DataTable());
        }

        /* ---------- Download / Print ---------- */
        function hacExportParams() {
            var params = {};
            var type = $('#hacApprovedTypeFilter').val();
            if (type) params.type_filter = type;
            if ($.fn.DataTable && $.fn.DataTable.isDataTable($table)) {
                var searchValue = $table.DataTable().search();
                if (searchValue) params.search = searchValue;
            }
            var scope = new URLSearchParams(window.location.search).get('scope');
            if (scope) params.scope = scope;
            params.cols = hacVisibleExportCols().join(',');
            return params;
        }

        $('#hacDownloadBtn').on('click', function() {
            window.location.href = '{{ route('admin.estate.change-request-hac-approved.export') }}?' + $.param(hacExportParams());
        });

        $('#hacPrintBtn').on('click', function() {
            window.open('{{ route('admin.estate.change-request-hac-approved.print') }}?' + $.param(hacExportParams()), '_blank');
        });

        /* ---------- Shared cascade: estate → building → sub-type → house ---------- */
        // Both allotment forms (approve-with-picker and allot-new-request) use the
        // same four endpoints; only their element ids differ.
        function buildCascade(cfg) {
            var $campus = $(cfg.campus), $unitType = $(cfg.unitType), $building = $(cfg.building),
                $unitSub = $(cfg.unitSub), $house = $(cfg.house), $noHouses = $(cfg.noHouses);

            function fill($sel, items, placeholder, valueKey, labelKey) {
                $sel.html('<option value="">' + placeholder + '</option>');
                (items || []).forEach(function(item) {
                    var val = item[valueKey] != null ? item[valueKey] : item[labelKey];
                    var label = item[labelKey] != null ? item[labelKey] : item[valueKey];
                    $sel.append($('<option></option>').attr('value', val).text(label));
                });
            }

            function resetHouse() {
                fill($house, [], cfg.housePlaceholder, 'pk', 'house_no');
                if ($noHouses.length) $noHouses.addClass('d-none');
            }

            function loadBlocks() {
                fill($building, [], cfg.buildingPlaceholder, 'pk', 'block_name');
                fill($unitSub, [], cfg.unitSubPlaceholder, 'pk', 'unit_sub_type');
                resetHouse();
                if (!$campus.val()) return;
                $.get(blocksUrl, { campus_id: $campus.val(), unit_type_id: $unitType.val() || '' }, function(res) {
                    if (res.status) fill($building, res.data, cfg.buildingPlaceholder, 'pk', 'block_name');
                });
            }

            function loadUnitSubTypes() {
                fill($unitSub, [], cfg.unitSubPlaceholder, 'pk', 'unit_sub_type');
                resetHouse();
                if (!$campus.val() || !$building.val()) return;
                $.get(unitSubTypesUrl, { campus_id: $campus.val(), block_id: $building.val(), unit_type_id: $unitType.val() || '' }, function(res) {
                    if (res.status) fill($unitSub, res.data, cfg.unitSubPlaceholder, 'pk', 'unit_sub_type');
                });
            }

            function loadHouses() {
                resetHouse();
                if (!$campus.val() || !$building.val() || !$unitSub.val()) return;
                var params = {
                    campus_id: $campus.val(),
                    block_id: $building.val(),
                    unit_sub_type_id: $unitSub.val(),
                    unit_type_id: $unitType.val() || ''
                };
                if (typeof cfg.employeePk === 'function' && cfg.employeePk()) params.employee_pk = cfg.employeePk();
                $.get(vacantHousesUrl, params, function(res) {
                    if (!res.status) return;
                    fill($house, res.data, cfg.housePlaceholder, 'pk', 'house_no');
                    if ($noHouses.length) $noHouses.toggleClass('d-none', (res.data || []).length > 0);
                    if (typeof cfg.onChange === 'function') cfg.onChange();
                });
            }

            $campus.on('change', function() {
                // Unit types are scoped per campus and come with the details payload.
                var list = (cfg.unitTypesByCampus() || {})[$campus.val()] || [];
                fill($unitType, list, cfg.unitTypePlaceholder, 'pk', 'unit_type');
                loadBlocks();
                if (typeof cfg.onChange === 'function') cfg.onChange();
            });
            $unitType.on('change', function() { loadBlocks(); if (cfg.onChange) cfg.onChange(); });
            $building.on('change', function() { loadUnitSubTypes(); if (cfg.onChange) cfg.onChange(); });
            $unitSub.on('change', function() { loadHouses(); if (cfg.onChange) cfg.onChange(); });
            $house.on('change', function() { if (cfg.onChange) cfg.onChange(); });

            return {
                fill: fill,
                reset: function() {
                    fill($unitType, [], cfg.unitTypePlaceholder, 'pk', 'unit_type');
                    fill($building, [], cfg.buildingPlaceholder, 'pk', 'block_name');
                    fill($unitSub, [], cfg.unitSubPlaceholder, 'pk', 'unit_sub_type');
                    resetHouse();
                }
            };
        }

        /* ---------- Approve ---------- */
        var approveConfirmModal = new bootstrap.Modal(document.getElementById('approveConfirmModal'));
        var approveHouseModal = new bootstrap.Modal(document.getElementById('approveHouseModal'));
        var approveUnitTypesByCampus = {};

        var approveCascade = buildCascade({
            campus: '#approve_estate_campus', unitType: '#approve_unit_type', building: '#approve_building',
            unitSub: '#approve_unit_sub_type', house: '#estate_house_master_pk', noHouses: '#approveNoHouses',
            unitTypePlaceholder: 'Select Unit', buildingPlaceholder: 'Select Building',
            unitSubPlaceholder: 'Select Sub-type', housePlaceholder: 'Select House',
            unitTypesByCampus: function() { return approveUnitTypesByCampus; }
        });

        $(document).on('click', '.btn-approve-change-request', function() {
            var id = $(this).data('id');
            var requestId = $(this).data('request-id');
            var approveAction = '{{ route("admin.estate.change-request.approve", ["id" => "__ID__"]) }}'.replace('__ID__', id);

            $('#formApproveConfirm').attr('action', approveAction);
            $('#formApproveHouse').attr('action', approveAction);
            $('#approveConfirmError, #approveFormError').addClass('d-none').text('');

            $('#approveModalLoading').removeClass('d-none');
            $('#approveModalContent').addClass('d-none');

            $.get('{{ url("admin/estate/change-request/approve-details") }}/' + id)
                .done(function(data) {
                    var emp = data.employee || {};
                    var chReq = data.change_request || {};
                    var housePk = chReq.requested_house_pk ? parseInt(chReq.requested_house_pk, 10) : null;
                    var houseNo = chReq.change_house_no || '';

                    if (housePk && houseNo) {
                        // Nothing to choose — straight to the confirm dialog.
                        $('#approveConfirmHousePk').val(housePk);
                        $('#approveConfirmDetail').text(
                            (requestId ? requestId + ' — ' : '') + (emp.emp_name || '') + ' → House ' + houseNo
                        );
                        approveConfirmModal.show();
                        return;
                    }

                    $('#approveRequesterName').val(emp.emp_name || '');
                    $('#approveDesignation').val(emp.emp_designation || '');
                    approveUnitTypesByCampus = data.unit_types_by_campus || {};
                    approveCascade.reset();
                    approveCascade.fill($('#approve_estate_campus'), data.campuses || [], 'Select Estate', 'pk', 'campus_name');
                    $('#approveModalLoading').addClass('d-none');
                    $('#approveModalContent').removeClass('d-none');
                    approveHouseModal.show();
                })
                .fail(function() {
                    hacNotify('error', 'Could not load the approval details. Please try again.');
                });
        });

        function submitApprove($form, $btn, $error, modal, busyLabel, idleLabel) {
            $error.addClass('d-none').text('');
            $btn.prop('disabled', true).text(busyLabel);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function(res) {
                    if (res && res.success) {
                        modal.hide();
                        reloadTable();
                        hacNotify('success', res.message || 'Change request approved and house allotted.');
                    } else {
                        $error.removeClass('d-none').text((res && res.message) || 'Something went wrong.');
                    }
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong. Please try again.';
                    $error.removeClass('d-none').text(msg);
                },
                complete: function() { $btn.prop('disabled', false).text(idleLabel); }
            });
        }

        $('#formApproveConfirm').on('submit', function(e) {
            e.preventDefault();
            submitApprove($(this), $('#btnSubmitApproveConfirm'), $('#approveConfirmError'), approveConfirmModal, 'Approving…', 'Yes, Approve');
        });

        $('#formApproveHouse').on('submit', function(e) {
            e.preventDefault();
            if (!$('#estate_house_master_pk').val()) {
                $('#approveFormError').removeClass('d-none')
                    .text('Please select Estate, Unit Type, Building, Unit Sub-type and House Number.');
                return;
            }
            submitApprove($(this), $('#btnSubmitApprove'), $('#approveFormError'), approveHouseModal, 'Allotting…', 'Allot House');
        });

        /* ---------- Reject ---------- */
        var disapproveModal = new bootstrap.Modal(document.getElementById('disapproveChangeRequestModal'));

        $(document).on('click', '.btn-disapprove-change-request', function() {
            var id = $(this).data('id');
            $('#formDisapproveChangeRequest').attr('action',
                '{{ route("admin.estate.change-request.disapprove", ["id" => "__ID__"]) }}'.replace('__ID__', id));
            $('#disapproveModalRequestId').text($(this).data('request-id') || ('#' + id));
            $('#disapprove_reason').val('');
            $('#disapproveFormError').addClass('d-none').text('');
            disapproveModal.show();
        });

        $('#formDisapproveChangeRequest').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $('#btnSubmitDisapprove');
            var $error = $('#disapproveFormError');
            $error.addClass('d-none').text('');
            $btn.prop('disabled', true).text('Rejecting…');
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function(res) {
                    if (res && res.success) {
                        disapproveModal.hide();
                        reloadTable();
                        hacNotify('success', res.message || 'Change request rejected. Remark saved.');
                    } else {
                        $error.removeClass('d-none').text((res && res.message) || 'Something went wrong.');
                    }
                },
                error: function(xhr) {
                    var j = xhr.responseJSON || {};
                    var msg = j.message
                        || (j.errors && Object.keys(j.errors).map(function(k) { return j.errors[k][0]; }).join(' '))
                        || 'Something went wrong. Please try again.';
                    $error.removeClass('d-none').text(msg);
                },
                complete: function() { $btn.prop('disabled', false).text('Yes, Reject'); }
            });
        });

        /* ---------- Allot House (new requests) ---------- */
        var allotModal = new bootstrap.Modal(document.getElementById('allotNewRequestModal'));
        var allotUnitTypesByCampus = {};
        var allotEmployeePk = null;

        function isAllotFormValid() {
            return !!($('#allot_estate_campus').val() && $('#allot_unit_type').val() && $('#allot_building').val()
                && $('#allot_unit_sub_type').val() && $('#allot_estate_house_master_pk').val());
        }

        function updateAllotSubmitButton() {
            $('#btnSubmitAllot').prop('disabled', !isAllotFormValid());
        }

        var allotCascade = buildCascade({
            campus: '#allot_estate_campus', unitType: '#allot_unit_type', building: '#allot_building',
            unitSub: '#allot_unit_sub_type', house: '#allot_estate_house_master_pk', noHouses: '#allotNoHouses',
            unitTypePlaceholder: 'Select Unit', buildingPlaceholder: 'Select Building',
            unitSubPlaceholder: 'Select Sub-type', housePlaceholder: 'Select House',
            unitTypesByCampus: function() { return allotUnitTypesByCampus; },
            employeePk: function() { return allotEmployeePk; },
            onChange: updateAllotSubmitButton
        });

        $(document).on('click', '.btn-allot-new-request', function() {
            var id = $(this).data('id');
            var detailsUrl = $(this).data('details-url');
            $('#formAllotNewRequest').attr('action',
                '{{ route("admin.estate.new-request.allot", ["id" => "__ID__"]) }}'.replace('__ID__', id));
            $('#allotFormError').addClass('d-none').text('');
            $('#allotModalLoading').removeClass('d-none');
            $('#allotModalContent').addClass('d-none');
            allotModal.show();

            $.get(detailsUrl)
                .done(function(data) {
                    var emp = data.employee || {};
                    $('#allotRequesterName').val(emp.emp_name || '');
                    $('#allotDesignation').val(emp.emp_designation || '');
                    allotEmployeePk = (emp.employee_pk != null && emp.employee_pk !== '') ? parseInt(emp.employee_pk, 10) : null;
                    allotUnitTypesByCampus = data.unit_types_by_campus || {};
                    allotCascade.reset();
                    allotCascade.fill($('#allot_estate_campus'), data.campuses || [], 'Select Estate', 'pk', 'campus_name');
                    $('#allotNoHouses').removeClass('d-none');
                    updateAllotSubmitButton();
                })
                .fail(function() {
                    $('#allotFormError').removeClass('d-none').text('Could not load the request details. Please try again.');
                })
                .always(function() {
                    $('#allotModalLoading').addClass('d-none');
                    $('#allotModalContent').removeClass('d-none');
                });
        });

        $('#formAllotNewRequest').on('submit', function(e) {
            e.preventDefault();
            if (!isAllotFormValid()) return;
            var $form = $(this);
            var $btn = $('#btnSubmitAllot');
            var $error = $('#allotFormError');
            $error.addClass('d-none').text('');
            $btn.prop('disabled', true).text('Allotting…');
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function(res) {
                    if (res && res.success) {
                        allotModal.hide();
                        reloadTable();
                        hacNotify('success', res.message || 'House allotted. Record is now in Possession Details.');
                    } else {
                        $error.removeClass('d-none').text((res && res.message) || 'Something went wrong.');
                    }
                },
                error: function(xhr) {
                    $error.removeClass('d-none').text((xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Something went wrong. Please try again.');
                },
                complete: function() { $btn.prop('disabled', false).text('Allot House'); updateAllotSubmitButton(); }
            });
        });
    });
    </script>
@endpush
