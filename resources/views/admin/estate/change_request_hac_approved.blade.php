@extends('admin.layouts.master')

@section('title', 'Change Requests (HAC Approved) - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    <x-breadcrum title="Change Requests (HAC Approved)"></x-breadcrum>
    <x-estate-workflow-stepper current="hac-approved" />
    <x-session_message />

    <div class="card">
        <div class="card-header">
            <div class="d-flex flex-column flex-md-row flex-wrap align-items-start align-items-md-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-primary rounded-pill px-3 py-2 fs-6">
                        <i class="bi bi-check2-circle me-1"></i> HAC Approved
                    </span>
                    <h1 class="h5 fw-bold mb-0 text-body">Change Requests</h1>
                </div>
            </div>
        </div>
        <div class="card-body p-4 p-lg-5">
            <p class="text-body-secondary small mb-4 lh-sm">
                Change requests and new requests (forwarded from HAC) in one table. Use <strong class="text-success">Approve</strong> / <strong class="text-danger">Disapprove</strong> for change requests, or <strong class="text-primary">Allot</strong> for new requests to add to Possession Details.
            </p>

            <div class="estate-hac-approved-table-wrapper table-responsive">
                {!! $dataTable->table([
                    'class' => 'table text-nowrap align-middle mb-0',
                    'aria-describedby' => 'hac-approved-caption'
                ]) !!}
            </div>
            <div id="hac-approved-caption" class="visually-hidden">HAC Approved – change and new requests</div>
        </div>
    </div>
</div>

{{-- Approve modal - Approved Request House form with dependent dropdowns --}}
<div class="modal fade" id="approveChangeRequestModal" tabindex="-1" aria-labelledby="approveChangeRequestModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 shadow-lg border-0">
            <div class="modal-header bg-success bg-opacity-10 border-0 py-3 px-4">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="approveChangeRequestModalLabel">
                    <i class="bi bi-check2-circle text-success"></i> Approved Request House
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formApproveChangeRequest" method="POST" action="">
                @csrf
                <div class="modal-body p-4">
                    <div id="approveModalLoading" class="text-center py-5 d-none">
                        <div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div>
                        <p class="mt-3 text-body-secondary small mb-0">Loading details...</p>
                    </div>
                    <div id="approveModalContent" class="d-none">
                        <p class="text-body-secondary small mb-4">Please select the house to approve for this request.</p>
                        {{-- Requester Name & Designation (read-only) --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-uppercase text-body-secondary">Requester Name</label>
                                <input type="text" class="form-control form-control-lg bg-body-secondary border-0" id="approveRequesterName" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-uppercase text-body-secondary">Designation</label>
                                <input type="text" class="form-control form-control-lg bg-body-secondary border-0" id="approveDesignation" readonly>
                            </div>
                        </div>
                        {{-- 5 Dependent dropdowns --}}
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md">
                                <label for="approve_estate_campus" class="form-label fw-semibold text-primary small text-uppercase">Estate Name</label>
                                <select class="form-select form-select-sm" id="approve_estate_campus">
                                    <option value="">— Select —</option>
                                </select>
                            </div>
                            <div class="col-12 col-md">
                                <label for="approve_unit_type" class="form-label fw-semibold text-primary small text-uppercase">Unit Type</label>
                                <select class="form-select form-select-sm" id="approve_unit_type">
                                    <option value="">— Select —</option>
                                </select>
                            </div>
                            <div class="col-12 col-md">
                                <label for="approve_building" class="form-label fw-semibold text-primary small text-uppercase">Building Name</label>
                                <select class="form-select form-select-sm" id="approve_building">
                                    <option value="">— Select —</option>
                                </select>
                            </div>
                            <div class="col-12 col-md">
                                <label for="approve_unit_sub_type" class="form-label fw-semibold text-primary small text-uppercase">Unit Sub Type</label>
                                <select class="form-select form-select-sm" id="approve_unit_sub_type">
                                    <option value="">— Select —</option>
                                </select>
                            </div>
                            <div class="col-12 col-md">
                                <label for="estate_house_master_pk" class="form-label fw-semibold text-primary small text-uppercase">House No.</label>
                                <select class="form-select form-select-sm" id="estate_house_master_pk" name="estate_house_master_pk" required>
                                    <option value="">— Select —</option>
                                </select>
                            </div>
                        </div>
                        <div id="approveNoHouses" class="alert alert-warning alert-dismissible fade show small mt-3 d-none" role="alert">No vacant houses available. Select Estate, Unit Type, Building, and Unit Sub Type first.<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button></div>
                        <div id="approveFormError" class="alert alert-danger alert-dismissible fade show mt-3 d-none" role="alert"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-body-secondary bg-opacity-50 py-3 px-4 gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success px-4" id="btnSubmitApprove">
                        <i class="bi bi-check-lg me-1"></i> Approve
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Disapprove reason modal --}}
<div class="modal fade" id="disapproveChangeRequestModal" tabindex="-1" aria-labelledby="disapproveChangeRequestModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow-lg border-0">
            <div class="modal-header bg-danger bg-opacity-10 border-0 py-3 px-4">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="disapproveChangeRequestModalLabel">
                    <i class="bi bi-x-circle text-danger"></i> Reason for Disapproval
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formDisapproveChangeRequest" method="POST" action="">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-body-secondary small mb-3">Request ID: <strong class="text-body" id="disapproveModalRequestId"></strong></p>
                    <label for="disapprove_reason" class="form-label fw-semibold">Reason / Remark <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="disapprove_reason" name="disapprove_reason" rows="4" maxlength="500" placeholder="Enter reason for disapproval..." required></textarea>
                    <div class="form-text small">Max 500 characters. This remark will be saved and shown in the table.</div>
                    <div id="disapproveFormError" class="alert alert-danger alert-dismissible fade show mt-3 d-none" role="alert"></div>
                </div>
                <div class="modal-footer border-0 bg-body-secondary bg-opacity-50 py-3 px-4 gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-danger px-4" id="btnSubmitDisapprove">
                        <i class="bi bi-x-circle me-1"></i> Submit Disapproval
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Allot new request modal (forwarded from HAC → add to Possession Details) --}}
<div class="modal fade" id="allotNewRequestModal" tabindex="-1" aria-labelledby="allotNewRequestModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content rounded-4 shadow-lg border-0">
            <div class="modal-header bg-primary bg-opacity-10 border-0 py-3 px-4">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="allotNewRequestModalLabel">
                    <i class="bi bi-house-add text-primary"></i> Allot House (Add to Possession Details)
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formAllotNewRequest" method="POST" action="">
                @csrf
                <div class="modal-body p-4">
                    <div id="allotModalLoading" class="text-center py-5 d-none">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                        <p class="mt-3 text-body-secondary small mb-0">Loading details...</p>
                    </div>
                    <div id="allotModalContent" class="d-none">
                        <p class="text-body-secondary small mb-4">Select house to allot. This will add the record to Possession Details.</p>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-uppercase text-body-secondary">Requester Name</label>
                                <input type="text" class="form-control form-control-lg bg-body-secondary border-0" id="allotRequesterName" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold small text-uppercase text-body-secondary">Designation</label>
                                <input type="text" class="form-control form-control-lg bg-body-secondary border-0" id="allotDesignation" readonly>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md">
                                <label for="allot_estate_campus" class="form-label fw-semibold text-primary small text-uppercase">Estate Name</label>
                                <select class="form-select form-select-sm" id="allot_estate_campus">
                                    <option value="">— Select —</option>
                                </select>
                            </div>
                            <div class="col-12 col-md">
                                <label for="allot_unit_type" class="form-label fw-semibold text-primary small text-uppercase">Unit Type</label>
                                <select class="form-select form-select-sm" id="allot_unit_type">
                                    <option value="">— Select —</option>
                                </select>
                            </div>
                            <div class="col-12 col-md">
                                <label for="allot_building" class="form-label fw-semibold text-primary small text-uppercase">Building Name</label>
                                <select class="form-select form-select-sm" id="allot_building">
                                    <option value="">— Select —</option>
                                </select>
                            </div>
                            <div class="col-12 col-md">
                                <label for="allot_unit_sub_type" class="form-label fw-semibold text-primary small text-uppercase">Unit Sub Type</label>
                                <select class="form-select form-select-sm" id="allot_unit_sub_type">
                                    <option value="">— Select —</option>
                                </select>
                            </div>
                            <div class="col-12 col-md">
                                <label for="allot_estate_house_master_pk" class="form-label fw-semibold text-primary small text-uppercase">House No.</label>
                                <select class="form-select form-select-sm" id="allot_estate_house_master_pk" name="estate_house_master_pk" required>
                                    <option value="">— Select —</option>
                                </select>
                            </div>
                        </div>
                        <div id="allotNoHouses" class="alert alert-warning alert-dismissible fade show small mt-3 d-none" role="alert">No vacant houses available. Select Estate, Unit Type, Building, and Unit Sub Type first.<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button></div>
                        <div id="allotFormError" class="alert alert-danger alert-dismissible fade show mt-3 d-none" role="alert"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-body-secondary bg-opacity-50 py-3 px-4 gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success px-4" id="btnSubmitAllot">
                        <i class="bi bi-house-add me-1"></i> Allot
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* DataTables: Bootstrap 5.3 form controls */
    #estateHacApprovedTable_wrapper .dataTables_length label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    #estateHacApprovedTable_wrapper .dataTables_length select {
        width: auto;
        min-width: 4.5rem;
        display: inline-block;
        padding: 0.25rem 2rem 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: var(--bs-border-radius);
        border: 1px solid var(--bs-border-color);
        background-color: var(--bs-body-bg);
    }
    #estateHacApprovedTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    #estateHacApprovedTable_wrapper .dataTables_filter input {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border: 1px solid var(--bs-border-color);
        border-radius: var(--bs-border-radius);
        margin-left: 0.25rem;
    }
    #estateHacApprovedTable_wrapper .dataTables_filter input:focus {
        border-color: var(--bs-primary);
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-primary-rgb), 0.25);
    }
    /* Table: primary header, striped rows, hover */
    #estateHacApprovedTable_wrapper thead th {
        background-color: var(--bs-primary);
        color: var(--bs-white);
        font-weight: 600;
        border-color: var(--bs-primary);
        padding: 0.75rem 1rem;
        white-space: nowrap;
        font-size: 0.875rem;
    }
    #estateHacApprovedTable_wrapper tbody tr:nth-of-type(even) {
        background-color: rgba(var(--bs-primary-rgb), 0.04);
    }
    #estateHacApprovedTable_wrapper tbody tr:hover {
        background-color: rgba(var(--bs-primary-rgb), 0.08);
    }
    #estateHacApprovedTable_wrapper tbody td {
        padding: 0.75rem 1rem;
        border-color: var(--bs-border-color);
        font-size: 0.875rem;
    }
    .estate-hac-approved-table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    #estateHacApprovedTable_wrapper table {
        min-width: 992px;
    }
    /* Pagination: btn-like buttons */
    #estateHacApprovedTable_wrapper .dataTables_paginate .paginate_button {
        padding: 0.375rem 0.75rem;
        margin: 0 0.125rem;
        border-radius: var(--bs-border-radius);
        border: 1px solid var(--bs-border-color);
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out;
    }
    #estateHacApprovedTable_wrapper .dataTables_paginate .paginate_button:hover {
        background-color: var(--bs-secondary-bg);
        border-color: var(--bs-border-color);
    }
    #estateHacApprovedTable_wrapper .dataTables_paginate .paginate_button.current {
        background: var(--bs-primary);
        color: var(--bs-white) !important;
        border-color: var(--bs-primary);
    }
    #estateHacApprovedTable_wrapper .dataTables_info {
        font-size: 0.875rem;
        color: var(--bs-secondary-color);
    }
</style>
@endpush

@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
    function wrapTableScroll() {
        var tbl = document.getElementById('estateHacApprovedTable');
        if (tbl && tbl.parentNode && !tbl.parentNode.classList.contains('table-responsive')) {
            var wrap = document.createElement('div');
            wrap.className = 'table-responsive';
            wrap.style.overflowX = 'auto';
            wrap.style.webkitOverflowScrolling = 'touch';
            tbl.parentNode.insertBefore(wrap, tbl);
            wrap.appendChild(tbl);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', wrapTableScroll);
    } else {
        wrapTableScroll();
    }
    document.addEventListener('DOMContentLoaded', function() {
        var approveModalEl = document.getElementById('approveChangeRequestModal');
        var approveModal = approveModalEl ? new bootstrap.Modal(approveModalEl) : null;
        var approveForm = document.getElementById('formApproveChangeRequest');
        var approveLoading = document.getElementById('approveModalLoading');
        var approveContent = document.getElementById('approveModalContent');
        var approveFormError = document.getElementById('approveFormError');

        var approveCampuses = [];
        var approveUnitTypesByCampus = {};
        var blocksUrl = '{{ route("admin.estate.possession.blocks") }}';
        var unitSubTypesUrl = '{{ route("admin.estate.possession.unit-sub-types") }}';
        var vacantHousesUrl = '{{ route("admin.estate.change-request.vacant-houses") }}';

        function resetApproveDropdowns() {
            $('#approve_estate_campus, #approve_unit_type, #approve_building, #approve_unit_sub_type, #estate_house_master_pk')
                .html('<option value="">---select---</option>').val('').prop('disabled', false);
        }

        function loadApproveBlocks() {
            var campusId = $('#approve_estate_campus').val();
            var unitTypeId = $('#approve_unit_type').val();
            $('#approve_building').html('<option value="">---select---</option>');
            $('#approve_unit_sub_type').html('<option value="">---select---</option>');
            $('#estate_house_master_pk').html('<option value="">---select---</option>');
            if (!campusId) return;
            $.get(blocksUrl, { campus_id: campusId, unit_type_id: unitTypeId || '' }, function(res) {
                if (res.status && res.data) {
                    res.data.forEach(function(b) {
                        $('#approve_building').append('<option value="' + b.pk + '">' + (b.block_name || b.pk) + '</option>');
                    });
                }
            });
        }

        function loadApproveUnitSubTypes() {
            var campusId = $('#approve_estate_campus').val();
            var blockId = $('#approve_building').val();
            var unitTypeId = $('#approve_unit_type').val();
            $('#approve_unit_sub_type').html('<option value="">---select---</option>');
            $('#estate_house_master_pk').html('<option value="">---select---</option>');
            if (!campusId || !blockId) return;
            $.get(unitSubTypesUrl, { campus_id: campusId, block_id: blockId, unit_type_id: unitTypeId || '' }, function(res) {
                if (res.status && res.data) {
                    res.data.forEach(function(u) {
                        $('#approve_unit_sub_type').append('<option value="' + u.pk + '">' + (u.unit_sub_type || u.pk) + '</option>');
                    });
                }
            });
        }

        function loadApproveVacantHouses() {
            var campusId = $('#approve_estate_campus').val();
            var blockId = $('#approve_building').val();
            var unitSubId = $('#approve_unit_sub_type').val();
            var unitTypeId = $('#approve_unit_type').val();
            $('#estate_house_master_pk').html('<option value="">---select---</option>');
            if (!campusId || !blockId || !unitSubId) return;
            $.get(vacantHousesUrl, {
                campus_id: campusId,
                block_id: blockId,
                unit_sub_type_id: unitSubId,
                unit_type_id: unitTypeId || ''
            }, function(res) {
                if (res.status && res.data) {
                    res.data.forEach(function(h) {
                        $('#estate_house_master_pk').append('<option value="' + h.pk + '">' + (h.house_no || h.pk) + '</option>');
                    });
                    $('#approveNoHouses').toggleClass('d-none', (res.data || []).length > 0);
                }
            });
        }

        $(document).on('click', '.btn-approve-change-request', function() {
            var id = $(this).data('id');
            if (!approveModal || !approveForm) return;
            approveForm.action = '{{ route("admin.estate.change-request.approve", ["id" => "__ID__"]) }}'.replace('__ID__', id);
            approveLoading.classList.remove('d-none');
            approveContent.classList.add('d-none');
            approveFormError.classList.add('d-none');
            resetApproveDropdowns();
            approveModal.show();
            fetch('{{ url("admin/estate/change-request/approve-details") }}/' + id, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function(res) { return res.json(); }).then(function(data) {
                approveLoading.classList.add('d-none');
                approveContent.classList.remove('d-none');
                if (data.error) {
                    approveFormError.textContent = data.error || 'Failed to load details';
                    approveFormError.classList.remove('d-none');
                    return;
                }
                var emp = data.employee || {};
                $('#approveRequesterName').val(emp.emp_name || '');
                $('#approveDesignation').val(emp.emp_designation || '');
                approveCampuses = data.campuses || [];
                approveUnitTypesByCampus = data.unit_types_by_campus || {};
                $('#approve_estate_campus').html('<option value="">---select---</option>');
                approveCampuses.forEach(function(c) {
                    $('#approve_estate_campus').append('<option value="' + c.pk + '">' + (c.campus_name || c.pk) + '</option>');
                });
                $('#approve_unit_type, #approve_building, #approve_unit_sub_type, #estate_house_master_pk').html('<option value="">---select---</option>');
                $('#approveNoHouses').addClass('d-none');
            }).catch(function() {
                approveLoading.classList.add('d-none');
                approveContent.classList.remove('d-none');
                approveFormError.textContent = 'Network error. Please try again.';
                approveFormError.classList.remove('d-none');
            });
        });

        $('#approve_estate_campus').on('change', function() {
            var campusId = $(this).val();
            var list = approveUnitTypesByCampus[campusId] || [];
            $('#approve_unit_type').html('<option value="">---select---</option>');
            list.forEach(function(ut) {
                $('#approve_unit_type').append('<option value="' + ut.pk + '">' + (ut.unit_type || ut.pk) + '</option>');
            });
            loadApproveBlocks();
        });
        $('#approve_unit_type').on('change', loadApproveBlocks);
        $('#approve_building').on('change', loadApproveUnitSubTypes);
        $('#approve_unit_sub_type').on('change', loadApproveVacantHouses);

        if (approveForm) {
            approveForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var submitBtn = document.getElementById('btnSubmitApprove');
                var housePk = document.getElementById('estate_house_master_pk').value;
                if (!housePk) {
                    approveFormError.textContent = 'Please select Estate, Unit Type, Building, Unit Sub Type, and House No. to allot.';
                    approveFormError.classList.remove('d-none');
                    return;
                }
                if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Submitting...'; }
                approveFormError.classList.add('d-none');
                var formData = new FormData(approveForm);
                fetch(approveForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                }).then(function(res) { return res.json().then(function(d) { return { ok: res.ok, data: d }; }); })
                .then(function(result) {
                    if (result.ok && result.data.success) {
                        approveModal.hide();
                        var dt = $('#estateHacApprovedTable').DataTable();
                        if (dt && dt.ajax) dt.ajax.reload(null, false);
                        var alert = document.createElement('div');
                        alert.className = 'alert alert-success alert-dismissible fade show';
                        alert.setAttribute('role', 'alert');
                        alert.innerHTML = '<span>' + (result.data.message || 'Change request approved and house allotted.') + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        var cardBody = approveForm.closest('.container-fluid').querySelector('.card-body');
                        var wrapper = cardBody && cardBody.querySelector('.estate-hac-approved-table-wrapper');
                        if (cardBody && wrapper) cardBody.insertBefore(alert, wrapper);
                    } else {
                        approveFormError.textContent = (result.data && result.data.message) || 'Something went wrong.';
                        approveFormError.classList.remove('d-none');
                    }
                }).catch(function() {
                    approveFormError.textContent = 'Network error. Please try again.';
                    approveFormError.classList.remove('d-none');
                }).finally(function() {
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Approve'; }
                });
            });
        }

        var disapproveModalEl = document.getElementById('disapproveChangeRequestModal');
        var disapproveModal = disapproveModalEl ? new bootstrap.Modal(disapproveModalEl) : null;
        var form = document.getElementById('formDisapproveChangeRequest');
        var disapproveRequestIdSpan = document.getElementById('disapproveModalRequestId');
        var reasonTextarea = document.getElementById('disapprove_reason');
        var formErrorEl = document.getElementById('disapproveFormError');

        $(document).on('click', '.btn-disapprove-change-request', function() {
            var id = $(this).data('id');
            var requestId = $(this).data('request-id');
            if (!disapproveModal || !form) return;
            form.action = '{{ route("admin.estate.change-request.disapprove", ["id" => "__ID__"]) }}'.replace('__ID__', id);
            if (disapproveRequestIdSpan) disapproveRequestIdSpan.textContent = requestId || ('#' + id);
            if (reasonTextarea) { reasonTextarea.value = ''; reasonTextarea.removeAttribute('disabled'); }
            if (formErrorEl) { formErrorEl.classList.add('d-none'); formErrorEl.textContent = ''; }
            disapproveModal.show();
        });

        $(document).on('submit', 'form[data-confirm]', function(e) {
            if (!confirm($(this).data('confirm') || 'Are you sure?')) e.preventDefault();
        });

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var submitBtn = document.getElementById('btnSubmitDisapprove');
                if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Submitting...'; }
                if (formErrorEl) { formErrorEl.classList.add('d-none'); formErrorEl.textContent = ''; }

                var formData = new FormData(form);
                var actionUrl = form.getAttribute('action');

                fetch(actionUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(function(res) { return res.json().then(function(data) { return { ok: res.ok, data: data }; }); })
                .then(function(result) {
                    if (result.ok && result.data.success) {
                        disapproveModal.hide();
                        var dt = $('#estateHacApprovedTable').DataTable();
                        if (dt && dt.ajax) dt.ajax.reload(null, false);
                        var alert = document.createElement('div');
                        alert.className = 'alert alert-success alert-dismissible fade show';
                        alert.setAttribute('role', 'alert');
                        alert.innerHTML = '<span>' + (result.data.message || 'Change request disapproved. Remark saved.') + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        var cardBody = form.closest('.container-fluid').querySelector('.card-body');
var wrapper = cardBody && cardBody.querySelector('.estate-hac-approved-table-wrapper');
if (cardBody && wrapper) cardBody.insertBefore(alert, wrapper);
                    } else {
                        var msg = (result.data && result.data.message) || (result.data && result.data.errors && Object.values(result.data.errors).flat().join(' ')) || 'Something went wrong.';
                        if (formErrorEl) { formErrorEl.textContent = msg; formErrorEl.classList.remove('d-none'); }
                    }
                })
                .catch(function() {
                    if (formErrorEl) { formErrorEl.textContent = 'Network error. Please try again.'; formErrorEl.classList.remove('d-none'); }
                })
                .finally(function() {
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Submit Disapproval'; }
                });
            });
        }

        // Allot new request (forwarded from HAC → Possession Details)
        var allotModalEl = document.getElementById('allotNewRequestModal');
        var allotModal = allotModalEl ? new bootstrap.Modal(allotModalEl) : null;
        var allotForm = document.getElementById('formAllotNewRequest');
        var allotLoading = document.getElementById('allotModalLoading');
        var allotContent = document.getElementById('allotModalContent');
        var allotFormError = document.getElementById('allotFormError');
        var allotCampuses = [];
        var allotUnitTypesByCampus = {};
        var blocksUrlAllot = '{{ route("admin.estate.possession.blocks") }}';
        var unitSubTypesUrlAllot = '{{ route("admin.estate.possession.unit-sub-types") }}';
        var vacantHousesUrlAllot = '{{ route("admin.estate.change-request.vacant-houses") }}';

        function resetAllotDropdowns() {
            $('#allot_estate_campus, #allot_unit_type, #allot_building, #allot_unit_sub_type, #allot_estate_house_master_pk')
                .html('<option value="">---select---</option>').val('').prop('disabled', false);
        }

        function loadAllotBlocks() {
            var campusId = $('#allot_estate_campus').val();
            var unitTypeId = $('#allot_unit_type').val();
            $('#allot_building').html('<option value="">---select---</option>');
            $('#allot_unit_sub_type').html('<option value="">---select---</option>');
            $('#allot_estate_house_master_pk').html('<option value="">---select---</option>');
            if (!campusId) return;
            $.get(blocksUrlAllot, { campus_id: campusId, unit_type_id: unitTypeId || '' }, function(res) {
                if (res.status && res.data) {
                    res.data.forEach(function(b) {
                        $('#allot_building').append('<option value="' + b.pk + '">' + (b.block_name || b.pk) + '</option>');
                    });
                }
            });
        }

        function loadAllotUnitSubTypes() {
            var campusId = $('#allot_estate_campus').val();
            var blockId = $('#allot_building').val();
            var unitTypeId = $('#allot_unit_type').val();
            $('#allot_unit_sub_type').html('<option value="">---select---</option>');
            $('#allot_estate_house_master_pk').html('<option value="">---select---</option>');
            if (!campusId || !blockId) return;
            $.get(unitSubTypesUrlAllot, { campus_id: campusId, block_id: blockId, unit_type_id: unitTypeId || '' }, function(res) {
                if (res.status && res.data) {
                    res.data.forEach(function(u) {
                        $('#allot_unit_sub_type').append('<option value="' + u.pk + '">' + (u.unit_sub_type || u.pk) + '</option>');
                    });
                }
            });
        }

        function loadAllotVacantHouses() {
            var campusId = $('#allot_estate_campus').val();
            var blockId = $('#allot_building').val();
            var unitSubId = $('#allot_unit_sub_type').val();
            var unitTypeId = $('#allot_unit_type').val();
            $('#allot_estate_house_master_pk').html('<option value="">---select---</option>');
            if (!campusId || !blockId || !unitSubId) return;
            $.get(vacantHousesUrlAllot, {
                campus_id: campusId,
                block_id: blockId,
                unit_sub_type_id: unitSubId,
                unit_type_id: unitTypeId || ''
            }, function(res) {
                if (res.status && res.data) {
                    res.data.forEach(function(h) {
                        $('#allot_estate_house_master_pk').append('<option value="' + h.pk + '">' + (h.house_no || h.pk) + '</option>');
                    });
                    $('#allotNoHouses').toggleClass('d-none', (res.data || []).length > 0);
                }
            });
        }

        $(document).on('click', '.btn-allot-new-request', function() {
            var id = $(this).data('id');
            var detailsUrl = $(this).data('details-url');
            if (!allotModal || !allotForm) return;
            allotForm.action = '{{ route("admin.estate.new-request.allot", ["id" => "__ID__"]) }}'.replace('__ID__', id);
            allotLoading.classList.remove('d-none');
            allotContent.classList.add('d-none');
            allotFormError.classList.add('d-none');
            resetAllotDropdowns();
            allotModal.show();
            fetch(detailsUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    allotLoading.classList.add('d-none');
                    allotContent.classList.remove('d-none');
                    if (data.error) {
                        allotFormError.textContent = data.error || 'Failed to load details';
                        allotFormError.classList.remove('d-none');
                        return;
                    }
                    var emp = data.employee || {};
                    $('#allotRequesterName').val(emp.emp_name || '');
                    $('#allotDesignation').val(emp.emp_designation || '');
                    allotCampuses = data.campuses || [];
                    allotUnitTypesByCampus = data.unit_types_by_campus || {};
                    $('#allot_estate_campus').html('<option value="">---select---</option>');
                    allotCampuses.forEach(function(c) {
                        $('#allot_estate_campus').append('<option value="' + c.pk + '">' + (c.campus_name || c.pk) + '</option>');
                    });
                    $('#allot_unit_type, #allot_building, #allot_unit_sub_type, #allot_estate_house_master_pk').html('<option value="">---select---</option>');
                    $('#allotNoHouses').addClass('d-none');
                })
                .catch(function() {
                    allotLoading.classList.add('d-none');
                    allotContent.classList.remove('d-none');
                    allotFormError.textContent = 'Network error. Please try again.';
                    allotFormError.classList.remove('d-none');
                });
        });

        $('#allot_estate_campus').on('change', function() {
            var campusId = $(this).val();
            var list = allotUnitTypesByCampus[campusId] || [];
            $('#allot_unit_type').html('<option value="">---select---</option>');
            list.forEach(function(ut) {
                $('#allot_unit_type').append('<option value="' + ut.pk + '">' + (ut.unit_type || ut.pk) + '</option>');
            });
            loadAllotBlocks();
        });
        $('#allot_unit_type').on('change', loadAllotBlocks);
        $('#allot_building').on('change', loadAllotUnitSubTypes);
        $('#allot_unit_sub_type').on('change', loadAllotVacantHouses);

        if (allotForm) {
            allotForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var submitBtn = document.getElementById('btnSubmitAllot');
                var housePk = document.getElementById('allot_estate_house_master_pk').value;
                if (!housePk) {
                    allotFormError.textContent = 'Please select Estate, Unit Type, Building, Unit Sub Type, and House No. to allot.';
                    allotFormError.classList.remove('d-none');
                    return;
                }
                if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Submitting...'; }
                allotFormError.classList.add('d-none');
                var formData = new FormData(allotForm);
                fetch(allotForm.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                }).then(function(res) { return res.json().then(function(d) { return { ok: res.ok, data: d }; }); })
                .then(function(result) {
                    if (result.ok && result.data.success) {
                        allotModal.hide();
                        var dt = $('#estateHacApprovedTable').DataTable();
                        if (dt && dt.ajax) dt.ajax.reload(null, false);
                        var alert = document.createElement('div');
                        alert.className = 'alert alert-success alert-dismissible fade show';
                        alert.setAttribute('role', 'alert');
                        alert.innerHTML = '<span>' + (result.data.message || 'House allotted. Record is now in Possession Details.') + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        var cardBody = allotForm.closest('.container-fluid').querySelector('.card-body');
                        var wrapper = cardBody && cardBody.querySelector('.estate-hac-approved-table-wrapper');
                        if (cardBody && wrapper) cardBody.insertBefore(alert, wrapper);
                    } else {
                        allotFormError.textContent = (result.data && result.data.message) || 'Something went wrong.';
                        allotFormError.classList.remove('d-none');
                    }
                }).catch(function() {
                    allotFormError.textContent = 'Network error. Please try again.';
                    allotFormError.classList.remove('d-none');
                }).finally(function() {
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = '<i class="bi bi-house-add me-1"></i> Allot'; }
                });
            });
        }
    });
    </script>
@endpush
