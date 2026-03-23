@extends('admin.layouts.master')

@section('title', 'Change Request For House - Sargam')

@section('setup_content')
<div class="container-fluid py-4">
    {{-- Breadcrumb: Home > My Requests / Complaints > Request For House --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
            <li class="breadcrumb-item"><a href="#">My Requests / Complaints</a></li>
            <li class="breadcrumb-item active" aria-current="page">Change Request For House</li>
        </ol>
    </nav>

    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
        <div class="card-body p-4 p-lg-5">
            <h1 class="h4 fw-bold text-body mb-2">Change Request For House</h1>
            <p class="text-body-secondary small mb-4">
                This page displays all list of change request details added in the system, and provides options to manage records.
            </p>

            {{-- Main data table --}}
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle mb-0" id="requestForHouseTable">
                    <caption class="visually-hidden">Request For House – list of request details</caption>
                    <thead class="table-primary">
                        <tr>
                            <th scope="col" class="text-nowrap">S.NO.</th>
                            <th scope="col" class="text-nowrap">REQUEST ID</th>
                            <th scope="col" class="text-nowrap">REQUEST DATE</th>
                            <th scope="col" class="text-nowrap">NAME / ID</th>
                            <th scope="col" class="text-nowrap">DATE OF JOINING IN ACADEMY</th>
                            <th scope="col" class="text-nowrap">STATUS OF REQUEST</th>
                            <th scope="col" class="text-nowrap">ALLOTED HOUSE</th>
                            <th scope="col" class="text-nowrap">ELIGIBILITY TYPE</th>
                            <th scope="col" class="text-nowrap">POSSESSION FROM</th>
                            <th scope="col" class="text-nowrap">POSSESSION TO</th>
                            <th scope="col" class="text-nowrap">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $requestList = $requests ?? collect(); @endphp
                        @forelse($requestList as $index => $row)
                        <tr>
                            <td></td>
                            <td>{{ $row->request_id ?? '—' }}</td>
                            <td data-order="{{ $row->request_date_sort ?? '' }}">{{ $row->request_date ?? '—' }}</td>
                            <td>{{ ($row->name ?? '—') }} ({{ $row->emp_id ?? '—' }})</td>
                            <td>{{ $row->doj_academy ?? '—' }}</td>
                            <td>{{ $row->status ?? '—' }}</td>
                            <td>{{ $row->alloted_house ?? '—' }}</td>
                            <td>{{ $row->eligibility_type ?? '—' }}</td>
                            <td>{{ $row->possession_from ?? '—' }}</td>
                            <td>{{ $row->possession_to ?? '—' }}</td>
                            <td class="text-nowrap">
                                @php
                                    $isAuthority = hasRole('Estate') || hasRole('Admin') || hasRole('Super Admin');
                                    $changeStatus = (int) ($row->change_status ?? 0); // 0=pending, 1=approved, 2=disapproved
                                @endphp

                                @if($isAuthority)
                                    @if($changeStatus === 0)
                                        <button type="button"
                                                class="btn btn-sm btn-success btn-approve-change-request me-1"
                                                data-id="{{ $row->pk }}">
                                            Approve
                                        </button>
                                        <button type="button"
                                                class="btn btn-sm btn-danger btn-disapprove-change-request"
                                                data-id="{{ $row->pk }}"
                                                data-request-id="{{ $row->request_id }}">
                                            Disapprove
                                        </button>
                                    @elseif($changeStatus === 1)
                                        <span class="badge bg-success">Approved</span>
                                    @elseif($changeStatus === 2)
                                        <span class="badge bg-danger">Disapproved</span>
                                    @endif
                                @else
                                    @if($changeStatus === 0)
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary btn-change-request"
                                                data-request-id="{{ $row->pk }}">
                                            Change
                                        </button>
                                    @elseif($changeStatus === 1)
                                        <span class="text-success small d-block mt-1">(Your request has been approved)</span>
                                    @elseif($changeStatus === 2)
                                        <span class="text-danger small d-block mt-1">(Your request has been disapproved)</span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="text-center text-body-secondary py-4">No request records found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Change Request Details Modal --}}
<div class="modal fade" id="changeRequestDetailsModal" tabindex="-1" aria-labelledby="changeRequestDetailsModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="changeRequestDetailsModalLabel">
                    <i class="material-icons me-2">edit</i>Change Request Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="changeRequestDetailsModalBody">
                <div id="changeRequestDetailsModalLoading" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                    <p class="mt-3 text-body-secondary small mb-0">Loading form...</p>
                </div>
                <div id="changeRequestDetailsModalContent"></div>
            </div>
        </div>
    </div>
</div>

{{-- Approve Change Request Modal --}}
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
                        <div id="approveConfirmOnly" class="d-none mb-4">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Requester Name</label>
                                    <input type="text" class="form-control form-control-lg bg-body-secondary border-0" id="approveRequesterNameConfirm" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold small text-uppercase text-body-secondary">Designation</label>
                                    <input type="text" class="form-control form-control-lg bg-body-secondary border-0" id="approveDesignationConfirm" readonly>
                                </div>
                            </div>
                            <p class="mb-0 text-body">Requested house was already selected when the change request was raised. Approve with house: <strong id="approveRequestedHouseNo" class="text-success"></strong>?</p>
                        </div>
                        <div id="approveFullForm">
                            <p class="text-body-secondary small mb-4">Please select the house to approve for this request.</p>
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

{{-- Disapprove Reason Modal --}}
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

@push('styles')
<style>
    #requestForHouseTable thead th {
        background-color: var(--bs-primary);
        color: var(--bs-white);
        font-weight: 600;
        border-color: var(--bs-primary);
        padding: 0.75rem 0.5rem;
        font-size: 0.8125rem;
    }
    #requestForHouseTable tbody td {
        padding: 0.65rem 0.5rem;
        font-size: 0.875rem;
    }
    #requestForHouseTable tbody tr:nth-of-type(even) {
        background-color: rgba(var(--bs-primary-rgb), 0.04);
    }
    #requestForHouseTable tbody tr:hover {
        background-color: rgba(var(--bs-primary-rgb), 0.08);
    }
    /* Let Bootstrap handle horizontal scroll via table-responsive wrapper */
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var SUCCESS_FLASH_KEY = 'estate_request_for_house_success_message';
    var pageCardBody = document.querySelector('.container-fluid .card .card-body');

    function showPageSuccess(message) {
        if (!pageCardBody || !message) return;
        var oldAlert = pageCardBody.querySelector('.request-house-inline-success');
        if (oldAlert) oldAlert.remove();
        var alert = document.createElement('div');
        alert.className = 'alert alert-success alert-dismissible fade show request-house-inline-success';
        alert.setAttribute('role', 'alert');
        alert.innerHTML = '<span>' + message + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        pageCardBody.insertBefore(alert, pageCardBody.firstChild);
    }

    function persistAndReloadSuccess(message) {
        try {
            window.sessionStorage.setItem(SUCCESS_FLASH_KEY, message || 'Action completed successfully.');
        } catch (e) {}
        window.location.reload();
    }

    try {
        var persistedSuccess = window.sessionStorage.getItem(SUCCESS_FLASH_KEY);
        if (persistedSuccess) {
            showPageSuccess(persistedSuccess);
            window.sessionStorage.removeItem(SUCCESS_FLASH_KEY);
        }
    } catch (e) {}

    var modalEl = document.getElementById('changeRequestDetailsModal');
    var modalBody = document.getElementById('changeRequestDetailsModalContent');
    var modalLoading = document.getElementById('changeRequestDetailsModalLoading');
    var modalUrlTemplate = '{{ route("admin.estate.change-request-details.modal", ["id" => "__ID__"]) }}';
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

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-change-request');
        if (!btn) return;
        e.preventDefault();
        var id = btn.getAttribute('data-request-id');
        if (!id) return;

        var url = modalUrlTemplate.replace('__ID__', encodeURIComponent(id));
        modalBody.innerHTML = '';
        modalLoading.classList.remove('d-none');

        var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.show();

        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function(res) { return res.text(); })
        .then(function(html) {
            modalLoading.classList.add('d-none');
            modalBody.innerHTML = html;
            [].forEach.call(modalBody.querySelectorAll('script'), function(script) {
                var el = document.createElement('script');
                el.textContent = script.textContent;
                document.body.appendChild(el);
                document.body.removeChild(el);
            });
            if (window.initChangeRequestDetailsCascade) window.initChangeRequestDetailsCascade(modalBody);
        })
        .catch(function() {
            modalLoading.classList.add('d-none');
            modalBody.innerHTML = '<div class="alert alert-danger">Failed to load form. Please try again.</div>';
        });
    });

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

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-approve-change-request');
        if (!btn) return;
        e.preventDefault();
        var id = btn.getAttribute('data-id');
        if (!id || !approveModal || !approveForm) return;
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
            var chReq = data.change_request || {};
            var requestedHousePk = chReq.requested_house_pk ? parseInt(chReq.requested_house_pk, 10) : null;
            var requestedHouseNo = chReq.change_house_no || '';

            if (requestedHousePk && requestedHouseNo) {
                document.getElementById('approveConfirmOnly').classList.remove('d-none');
                document.getElementById('approveFullForm').classList.add('d-none');
                $('#approveRequesterNameConfirm').val(emp.emp_name || '');
                $('#approveDesignationConfirm').val(emp.emp_designation || '');
                document.getElementById('approveRequestedHouseNo').textContent = requestedHouseNo;
                var sel = document.getElementById('estate_house_master_pk');
                sel.innerHTML = '<option value="' + requestedHousePk + '">' + requestedHouseNo + '</option>';
                sel.removeAttribute('required');
            } else {
                document.getElementById('approveConfirmOnly').classList.add('d-none');
                document.getElementById('approveFullForm').classList.remove('d-none');
                $('#approveRequesterName').val(emp.emp_name || '');
                $('#approveDesignation').val(emp.emp_designation || '');
                approveCampuses = data.campuses || [];
                approveUnitTypesByCampus = data.unit_types_by_campus || {};
                $('#approve_estate_campus').html('<option value="">---select---</option>');
                approveCampuses.forEach(function(c) {
                    $('#approve_estate_campus').append('<option value="' + c.pk + '">' + (c.campus_name || c.pk) + '</option>');
                });
                $('#approve_unit_type, #approve_building, #approve_unit_sub_type, #estate_house_master_pk').html('<option value="">---select---</option>');
                document.getElementById('estate_house_master_pk').setAttribute('required', 'required');
                $('#approveNoHouses').addClass('d-none');
            }
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
                        persistAndReloadSuccess(result.data.message || 'Change request approved and house allotted successfully.');
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
    var disapproveForm = document.getElementById('formDisapproveChangeRequest');
    var disapproveRequestIdSpan = document.getElementById('disapproveModalRequestId');
    var reasonTextarea = document.getElementById('disapprove_reason');
    var disapproveFormError = document.getElementById('disapproveFormError');

    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-disapprove-change-request');
        if (!btn) return;
        e.preventDefault();
        var id = btn.getAttribute('data-id');
        var requestId = btn.getAttribute('data-request-id');
        if (!id || !disapproveModal || !disapproveForm) return;
        disapproveForm.action = '{{ route("admin.estate.change-request.disapprove", ["id" => "__ID__"]) }}'.replace('__ID__', id);
        if (disapproveRequestIdSpan) disapproveRequestIdSpan.textContent = requestId || ('#' + id);
        if (reasonTextarea) {
            reasonTextarea.value = '';
            reasonTextarea.removeAttribute('disabled');
        }
        if (disapproveFormError) {
            disapproveFormError.classList.add('d-none');
            disapproveFormError.textContent = '';
        }
        disapproveModal.show();
    });

    if (disapproveForm) {
        disapproveForm.addEventListener('submit', function(e) {
            e.preventDefault();
            var submitBtn = document.getElementById('btnSubmitDisapprove');
            if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Submitting...'; }
            if (disapproveFormError) {
                disapproveFormError.classList.add('d-none');
                disapproveFormError.textContent = '';
            }
            var formData = new FormData(disapproveForm);
            fetch(disapproveForm.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function(res) { return res.json().then(function(d) { return { ok: res.ok, data: d }; }); })
                .then(function(result) {
                    if (result.ok && result.data.success) {
                        disapproveModal.hide();
                        persistAndReloadSuccess(result.data.message || 'Change request disapproved. Remark saved.');
                    } else {
                        var msg = (result.data && result.data.message) || 'Something went wrong.';
                        if (disapproveFormError) {
                            disapproveFormError.textContent = msg;
                            disapproveFormError.classList.remove('d-none');
                        }
                    }
                }).catch(function() {
                    if (disapproveFormError) {
                        disapproveFormError.textContent = 'Network error. Please try again.';
                        disapproveFormError.classList.remove('d-none');
                    }
                }).finally(function() {
                    if (submitBtn) submitBtn.textContent = 'Submit Disapproval';
                    if (submitBtn) submitBtn.disabled = false;
                });
        });
    }

    modalEl.addEventListener('hidden.bs.modal', function() {
        modalBody.innerHTML = '';
    });

    if (window.jQuery && jQuery.fn && jQuery.fn.DataTable) {
        var table = jQuery('#requestForHouseTable');
        if (table.length && !jQuery.fn.DataTable.isDataTable(table)) {
            var dt = table.DataTable({
                responsive: false,
                autoWidth: false,
                scrollX: false,
                ordering: true,
                searching: true,
                lengthChange: true,
                pageLength: 10,
                // Default sort by Request Date (index 2)
                order: [[2, 'desc']],
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                columnDefs: [
                    // S.NO. column: dynamic serial number 1,2,3... respecting paging
                    {
                        targets: 0,
                        orderable: false,
                        searchable: false,
                        render: function (data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },
                    // Change column (last)
                    { targets: 10, orderable: false, searchable: false }
                ],
                language: {
                    search: 'Search within table:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'Showing 0 to 0 of 0 entries',
                    infoFiltered: '(filtered from _MAX_ total entries)',
                    paginate: {
                        first: 'First',
                        last: 'Last',
                        next: 'Next',
                        previous: 'Previous'
                    }
                },
                dom: '<"row align-items-center mb-3"<"col-12 col-md-4"l><"col-12 col-md-8"f>>rt<"row align-items-center mt-2"<"col-12 col-md-5"i><"col-12 col-md-7"p>>'
            });

            // Ensure S.NO. column always shows 1,2,3... based on current
            // sorting, searching and paging (no matter how user interacts).
            dt.on('order.dt search.dt draw.dt', function () {
                dt.column(0, {search: 'applied', order: 'applied'}).nodes().each(function (cell, i) {
                    cell.innerHTML = i + 1;
                });
            });
            dt.draw();
        }
    }
});
</script>
@endpush
@endsection
