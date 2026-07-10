@extends('admin.layouts.master')

@section('title', 'Request For Estate - Sargam')

@php
    $estateSelfHomeTab = request('scope') === 'self'
        && (isEstateAuthority());
    $requestForEstateEmployeesListUrl = route('admin.estate.request-for-estate.employees');
    if (request('scope') === 'self') {
        $requestForEstateEmployeesListUrl .= (str_contains($requestForEstateEmployeesListUrl, '?') ? '&' : '?') . 'scope=self';
    }
@endphp
@section($estateSelfHomeTab ? 'content' : 'setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4 estate-request-page estate-modernized">
   <x-breadcrum title="Request For Estate" />
   <x-estate-workflow-stepper current="request-for-estate" />

    <x-session_message />

    <div class="ds-card">
        <div class="ds-card-body p-4 p-lg-4">
            <div class="im-card-head">
                <div class="flex-grow-1">
                    <h1 class="h5 fw-bold text-dark mb-1">Request For Estate</h1>
                    <p class="text-body-secondary small mb-0">This page displays all list of request details added in the system, and provides options to manage records such as add, edit, delete etc.</p>
                </div>
                <div class="flex-shrink-0 d-flex flex-wrap gap-2">
                    <!-- <a href="{{ route('admin.estate.put-in-hac') }}" class="btn btn-outline-primary px-3" title="Put In HAC"><i class="bi bi-building-check me-1"></i> Put In HAC</a>
                    <a href="{{ route('admin.estate.change-request-hac-approved') }}" class="btn btn-outline-primary px-3" title="HAC Approved"><i class="bi bi-check2-square me-1"></i> HAC Approved</a> -->
                    <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" id="btn-open-add-request-estate" title="Add Estate Request">
                        <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
                        <span>Add Estate Request</span>
                    </button>
                </div>
            </div>

            <div id="request-for-estate-card-body">
            @php
                $showUserActionHelp = request('scope') === 'self' || ! (
                    hasRole('Estate Admin') ||
                    hasRole('Super Admin') ||
                    hasRole('Super Admin')
                );
            @endphp

            @if($showUserActionHelp)
            <div class="request-action-help mb-3" role="note">
                <div class="fw-semibold text-dark mb-2">Action buttons</div>
                <div class="small text-body-secondary d-flex flex-wrap gap-3">
                    <span><i class="material-icons material-symbols-rounded align-middle text-primary">visibility</i> View request details</span>
                    <span><i class="material-icons material-symbols-rounded align-middle text-success">add_home</i> Add possession</span>
                    <span><i class="material-icons material-symbols-rounded align-middle text-success">check_circle</i> Possession already done</span>
                    <span><i class="material-icons material-symbols-rounded align-middle text-warning">logout</i> Return house</span>
                    <span><i class="material-icons material-symbols-rounded align-middle text-info">swap_horiz</i> Raise change request</span>
                </div>
            </div>
            @endif

            <div class="im-toolbar row align-items-end mb-3">
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="estateStatusFilter" class="form-label fw-semibold small mb-1">Status</label>
                    <select id="estateStatusFilter" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="0">Pending</option>
                        <option value="1">Allotted</option>
                        <option value="3">Returned</option>
                    </select>
                </div>
                <div class="col-auto mt-2 mt-md-0">
                    <button type="button" id="estateStatusClear" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                        <i class="material-icons material-symbols-rounded" style="font-size:16px;" aria-hidden="true">restart_alt</i>
                        Clear
                    </button>
                </div>
            </div>
            <div class="table-responsive request-for-estate-table-wrap">
                {!! $dataTable->table([
                    'class' => 'table text-nowrap align-middle mb-0',
                    'aria-describedby' => 'request-for-estate-caption'
                ]) !!}
            </div>
            <div id="request-for-estate-caption" class="visually-hidden">Request For Estate list</div>
            </div>
        </div>
    </div>
</div>

<!-- Add / Edit Request For Estate modal -->
<div class="modal fade estate-modernized" id="addEditRequestEstateModal" tabindex="-1" aria-labelledby="addEditRequestEstateModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="addEditRequestEstateModalLabel">Add Estate Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="addEditRequestEstateFormErrors" class="alert alert-danger d-none" role="alert"><span id="addEditRequestEstateFormErrorsText"></span></div>
                <form id="formAddEditRequestEstate" method="POST" action="{{ route('admin.estate.request-for-estate.store') }}">
                    @csrf
                    <input type="hidden" name="id" id="request_estate_id" value="">
                    <input type="hidden" name="employee_pk" id="request_employee_pk" value="">

                    {{-- ── Request details ──────────────────────────────────── --}}
                    <h6 class="im-section-title">
                        <i class="material-icons material-symbols-rounded" aria-hidden="true">assignment</i>
                        Request Details
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modal_req_id" class="form-label">Request ID</label>
                            <input type="text" class="form-control" id="modal_req_id" name="req_id" maxlength="50" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_req_date" class="form-label">Request Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="modal_req_date" name="req_date" required readonly>
                            <div class="text-danger small field-error" data-field="req_date" role="alert"></div>
                        </div>
                        <div class="col-md-4 d-none" id="modal_status_wrap">
                            <label for="modal_status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal_status" name="status">
                                <option value="0">Pending</option>
                                <option value="1">Allotted</option>
                                <option value="2">Rejected</option>
                            </select>
                        </div>
                    </div>

                    {{-- ── Employee details ─────────────────────────────────── --}}
                    <h6 class="im-section-title">
                        <i class="material-icons material-symbols-rounded" aria-hidden="true">badge</i>
                        Employee Details
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="modal_employee_pk" class="form-label">Employee Name <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal_employee_pk">
                                <option value="">— Select employee —</option>
                            </select>
                            <input type="hidden" id="modal_emp_name" name="emp_name" value="">
                            <div class="text-danger small field-error mt-1" data-field="emp_name" role="alert"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_employee_id" name="employee_id" required maxlength="50" readonly>
                            <div class="text-danger small field-error" data-field="employee_id" role="alert"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_emp_designation" class="form-label">Designation <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_emp_designation" name="emp_designation" required maxlength="50" readonly>
                            <div class="text-danger small field-error" data-field="emp_designation" role="alert"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_pay_scale" class="form-label">Pay Scale <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_pay_scale" name="pay_scale" required maxlength="50" readonly>
                            <div class="text-danger small field-error" data-field="pay_scale" role="alert"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="modal_doj_pay_scale" class="form-label">DOJ (Pay Scale) <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="modal_doj_pay_scale" name="doj_pay_scale" required readonly>
                            <div class="text-danger small field-error" data-field="doj_pay_scale" role="alert"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="modal_doj_academic" class="form-label">DOJ (Academy) <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="modal_doj_academic" name="doj_academic" required readonly>
                            <div class="text-danger small field-error" data-field="doj_academic" role="alert"></div>
                        </div>
                        <div class="col-md-4">
                            <label for="modal_doj_service" class="form-label">DOJ (Service) <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="modal_doj_service" name="doj_service" required readonly>
                            <div class="text-danger small field-error" data-field="doj_service" role="alert"></div>
                        </div>
                    </div>

                    {{-- ── Eligibility & remarks ─────────────────────────────── --}}
                    <h6 class="im-section-title">
                        <i class="material-icons material-symbols-rounded" aria-hidden="true">verified_user</i>
                        Eligibility &amp; Remarks
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="modal_eligibility_type_pk" class="form-label">Eligibility Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal_eligibility_type_pk" name="eligibility_type_pk" required>
                                <option value="">— Select eligibility type —</option>
                                @foreach($eligibilityTypes ?? [] as $pk => $name)
                                    <option value="{{ (string) $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" id="modal_eligibility_type_pk_hidden" value="">
                            <div class="text-danger small field-error" data-field="eligibility_type_pk" role="alert"></div>

                        </div>
                        <div class="col-md-8">
                            <label for="modal_remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="modal_remarks" name="remarks" rows="2" maxlength="500" placeholder="Optional remarks"></textarea>
                        </div>
                    </div>

                    <div class="im-form-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle me-2"></i>Cancel</button>
                        <button type="submit" class="btn btn-primary px-4" id="btnSubmitRequestEstate"><i class="bi bi-save me-2"></i>Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade estate-modernized" id="deleteRequestEstateModal" tabindex="-1" aria-labelledby="deleteRequestEstateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-semibold" id="deleteRequestEstateModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Are you sure you want to delete this estate request? This action cannot be undone.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteRequestEstateBtn">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<style>.ts-dropdown { z-index: 1060 !important; }</style>
<style>
/* =====================================================================
   Request For Estate — page-scoped polish.
   Tokens/components come from sargam-app.css (--ds-*, .ds-*).
   Scoped to .estate-request-page / modal ids so nothing leaks elsewhere.
   ===================================================================== */

/* Card header row */
.estate-request-page .im-card-head {
    display: flex;
    flex-direction: column;
    gap: var(--ds-space-3);
    margin-bottom: var(--ds-space-4);
    padding-bottom: var(--ds-space-3);
    border-bottom: 1px solid var(--ds-line);
}
@media (min-width: 768px) {
    .estate-request-page .im-card-head { flex-direction: row; align-items: center; justify-content: space-between; }
}
.estate-request-page .im-card-head h1 { color: var(--ds-ink); }
.estate-request-page .btn-primary { border-radius: var(--ds-radius-1); font-weight: 600; }

/* Section headings (shared by card + modals) */
.estate-modernized .im-section-title {
    display: flex;
    align-items: center;
    gap: var(--ds-space-2);
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--ds-ink);
    margin: var(--ds-space-5) 0 var(--ds-space-4);
    padding-bottom: var(--ds-space-2);
    border-bottom: 1px solid var(--ds-line);
}
.estate-modernized .im-section-title:first-of-type { margin-top: 0; }
.estate-modernized .im-section-title i { font-size: 20px; color: var(--bs-primary); }

/* Labels + controls */
.estate-modernized .form-label { font-size: 0.8125rem; font-weight: 600; color: var(--ds-ink); margin-bottom: 0.35rem; }
.estate-modernized .form-control,
.estate-modernized .form-select { border-radius: var(--ds-radius-1); font-size: 0.9rem; }
.estate-modernized .form-control:focus,
.estate-modernized .form-select:focus { border-color: #86b7fe; box-shadow: var(--ds-focus-ring); }

/* Action-help note — soft blue info panel */
.estate-request-page .request-action-help {
    border: 1px solid #cfe0f5;
    border-radius: var(--ds-radius-2);
    background: #eef5ff;
    padding: 0.9rem 1.1rem;
}
.estate-request-page .request-action-help .material-icons {
    font-size: 1rem;
    vertical-align: text-bottom;
}

/* Modal footer action bar */
.estate-modernized .im-form-footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--ds-space-2);
    margin-top: var(--ds-space-5);
    padding-top: var(--ds-space-4);
    border-top: 1px solid var(--ds-line);
}
.estate-modernized .im-form-footer .btn { border-radius: var(--ds-radius-1); font-weight: 600; }

/* Modal shell */
.estate-modernized .modal-content { border: 0; border-radius: var(--ds-radius-2); box-shadow: 0 10px 40px rgba(16,24,40,.18); }
.estate-modernized .modal-header { border-bottom: 1px solid var(--ds-line); padding: var(--ds-space-4); }
.estate-modernized .modal-body { padding: var(--ds-space-4); }
.estate-modernized .modal-footer { border-top: 1px solid var(--ds-line); padding: var(--ds-space-4); }

/* Responsive table: horizontal scroll */
.request-for-estate-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    width: 100%;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-2);
}
.request-for-estate-table-wrap table {
    min-width: 992px;
}

/* DataTables controls: DS-aligned */
#requestForEstateTable_wrapper .dataTables_length label,
#requestForEstateTable_wrapper .dataTables_filter label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    color: var(--ds-ink-muted);
    font-size: 0.875rem;
}
#requestForEstateTable_wrapper .dataTables_length select {
    width: auto;
    min-width: 4.5rem;
    display: inline-block;
    padding: 0.25rem 2rem 0.25rem 0.5rem;
    font-size: 0.875rem;
    border-radius: var(--ds-radius-1);
    border: 1px solid var(--ds-line);
}
#requestForEstateTable_wrapper .dataTables_filter input {
    padding: 0.35rem 0.6rem;
    font-size: 0.875rem;
    border: 1px solid var(--ds-line);
    border-radius: var(--ds-radius-1);
    margin-left: 0.25rem;
}
#requestForEstateTable_wrapper .dataTables_filter input:focus {
    border-color: #86b7fe;
    box-shadow: var(--ds-focus-ring);
    outline: none;
}

/* Neutral uppercase header (matches the issue-management tables) */
#requestForEstateTable_wrapper thead th {
    background-color: var(--ds-surface-2);
    color: var(--ds-ink-muted);
    font-weight: 600;
    font-size: 0.8125rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    border-bottom: 1px solid var(--ds-line);
    padding: 0.75rem;
    white-space: nowrap;
}
#requestForEstateTable_wrapper tbody td {
    padding: 0.7rem 0.75rem;
    font-size: 0.9rem;
    color: var(--ds-ink);
    vertical-align: middle;
}

/* Pagination pills */
#requestForEstateTable_wrapper .dataTables_paginate { margin-top: 0.75rem; }
#requestForEstateTable_wrapper .dataTables_paginate .paginate_button {
    padding: 0.3rem 0.6rem;
    margin: 0 2px;
    border-radius: var(--ds-radius-1);
    border: 1px solid var(--ds-line);
    color: var(--ds-ink) !important;
    background: #fff;
}
#requestForEstateTable_wrapper .dataTables_paginate .paginate_button:hover {
    background: var(--ds-surface-2) !important;
    border-color: #c4ccd6;
    color: var(--ds-ink) !important;
}
#requestForEstateTable_wrapper .dataTables_paginate .paginate_button.current,
#requestForEstateTable_wrapper .dataTables_paginate .paginate_button.current:hover {
    background: var(--bs-primary) !important;
    color: #fff !important;
    border-color: var(--bs-primary);
}
#requestForEstateTable_wrapper .dataTables_paginate .paginate_button.disabled {
    color: var(--ds-ink-muted) !important;
    background: var(--ds-surface-2);
    opacity: 0.6;
}
#requestForEstateTable_wrapper .dataTables_info {
    padding-top: 0.75rem;
    font-size: 0.875rem;
    color: var(--ds-ink-muted);
}

@media (max-width: 767.98px) {
    #requestForEstateTable_wrapper .col-md-6,
    #requestForEstateTable_wrapper .col-md-4,
    #requestForEstateTable_wrapper .col-md-5 { max-width: 100%; }
}
</style>
@endpush

@push('scripts')
    <script>
        window.requestEstateSelfEmployeePk = @json($selfEmployeePk ?? null);
        window.requestEstateCanChooseEligibilityOnAdd = @json(isEstateAuthority());
        window.requestEstateLockEligibilityOnSelfScopeAdd = @json(request('scope') === 'self');
    </script>
    {!! $dataTable->scripts() !!}
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
    $(function() {
        var deleteRequestEstateUrl = '';
        var $requestForEstateTable = $('#requestForEstateTable');

        var tsFilter = null, tsModalEmployee = null, tsModalStatus = null, tsModalEligibility = null;
        var tsCommon = { allowEmptyOption: true, create: false, dropdownParent: 'body', maxOptions: null, hideSelected: false, onInitialize: function() { this.activeOption = null; } };
        function getSelectVal(el) { return (el && el.tomselect) ? el.tomselect.getValue() : $(el).val(); }
        function initTs(el, opts) {
            if (!el || typeof TomSelect === 'undefined') return null;
            if (el.tomselect) { try { el.tomselect.destroy(); } catch (e) {} }
            return new TomSelect(el, Object.assign({}, tsCommon, opts || {}));
        }

        if (typeof TomSelect !== 'undefined') {
            var filterEl = document.getElementById('estateStatusFilter');
            if (filterEl) tsFilter = initTs(filterEl, { placeholder: 'All' });
        }

        if ($requestForEstateTable.length && $.fn.DataTable && $requestForEstateTable.DataTable) {
            $requestForEstateTable.on('preXhr.dt', function(e, settings, data) {
                var el = document.getElementById('estateStatusFilter');
                var val = el ? getSelectVal(el) : '';
                data.status_filter = (val !== undefined && val !== null) ? val : '';
            });
            $(document).on('change', '#estateStatusFilter', function() {
                if ($requestForEstateTable.DataTable) $requestForEstateTable.DataTable().ajax.reload(null, false);
            });
            $('#estateStatusClear').on('click', function() {
                var el = document.getElementById('estateStatusFilter');
                if (el && el.tomselect) el.tomselect.setValue('', true); else $(el).val('');
                if ($requestForEstateTable.DataTable) $requestForEstateTable.DataTable().ajax.reload(null, false);
            });
        }
        var addEditModalEl = document.getElementById('addEditRequestEstateModal');
        var addEditModal = addEditModalEl ? new bootstrap.Modal(addEditModalEl) : null;
        var deleteModalEl = document.getElementById('deleteRequestEstateModal');
        var deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;

        if (typeof TomSelect !== 'undefined') {
            var statusEl = document.getElementById('modal_status');
            var eligEl = document.getElementById('modal_eligibility_type_pk');
            if (statusEl) tsModalStatus = initTs(statusEl, { placeholder: 'Pending' });
            if (eligEl) tsModalEligibility = initTs(eligEl, { placeholder: '— Select eligibility type —' });
        }

        function loadRequestEstateEmployees(includePk, thenSelectPk, onDone) {
            var url = @json($requestForEstateEmployeesListUrl);
            if (includePk) {
                url += (url.indexOf('?') >= 0 ? '&' : '?') + 'include_pk=' + encodeURIComponent(includePk);
            }
            var selEl = document.getElementById('modal_employee_pk');
            var $sel = $('#modal_employee_pk');
            if (selEl && selEl.tomselect) { try { selEl.tomselect.destroy(); } catch (e) {} tsModalEmployee = null; }
            $sel.find('option[value!=""]').remove();
            $.get(url, function(list) {
                if (Array.isArray(list)) {
                    list.forEach(function(o) {
                        $sel.append($('<option></option>').attr('value', o.pk).text(o.label || (o.emp_name + ' (' + o.employee_id + ')')));
                    });
                }
                if (typeof TomSelect !== 'undefined' && selEl) {
                    tsModalEmployee = initTs(selEl, { placeholder: '— Select employee —' });
                    if (thenSelectPk) tsModalEmployee.setValue(String(thenSelectPk), true);
                } else if (thenSelectPk) {
                    $sel.val(thenSelectPk);
                }
                if (typeof onDone === 'function') onDone();
            });
        }

        var eligibilityLocked = false;
        function setEligibilityLock(locked) {
            eligibilityLocked = !!locked;
            var $sel = $('#modal_eligibility_type_pk');
            var $hidden = $('#modal_eligibility_type_pk_hidden');
            if (eligibilityLocked) {
                $sel.prop('disabled', true).removeAttr('name');
                $hidden.attr('name', 'eligibility_type_pk');
            } else {
                $sel.prop('disabled', false).attr('name', 'eligibility_type_pk');
                $hidden.removeAttr('name');
            }
            var eligNative = document.getElementById('modal_eligibility_type_pk');
            if (eligNative && eligNative.tomselect) {
                try {
                    if (eligibilityLocked) {
                        eligNative.tomselect.disable();
                    } else {
                        eligNative.tomselect.enable();
                    }
                } catch (e) {}
            }
        }

        function ensureEligibilityOptionAndSetVal(pk, label, force) {
            if (eligibilityLocked && !force) return;
            var selEl = document.getElementById('modal_eligibility_type_pk');
            var $sel = $('#modal_eligibility_type_pk');
            var $hidden = $('#modal_eligibility_type_pk_hidden');
            var val = (pk !== undefined && pk !== null && pk !== '') ? String(pk) : '';
            if (!val) {
                if (selEl && selEl.tomselect) selEl.tomselect.setValue('', true); else $sel.val('').trigger('change');
                $hidden.val('');
                return;
            }
            if ($sel.find('option[value="' + val + '"]').length === 0) {
                $sel.append(new Option(label || ('Type ' + val), val, false, false));
                if (selEl && selEl.tomselect) selEl.tomselect.addOption({ value: val, text: label || ('Type ' + val) });
            }
            if (selEl && selEl.tomselect) selEl.tomselect.setValue(val, true); else $sel.val(val).trigger('change');
            $hidden.val(val);
        }

        function fillFromEmployeeDetails(data) {
            $('#modal_emp_name').val(data.emp_name || '');
            $('#modal_employee_id').val(data.employee_id || '');
            $('#modal_emp_designation').val(data.emp_designation || '');
            $('#modal_pay_scale').val(data.pay_scale || '');
            $('#modal_doj_pay_scale').val(data.doj_pay_scale || '');
            $('#modal_doj_academic').val(data.doj_academic || '');
            $('#modal_doj_service').val(data.doj_service || '');
            ensureEligibilityOptionAndSetVal(data.eligibility_type_pk, data.eligibility_type_name, eligibilityLocked);
        }

        function clearEmployeeDerivedFields() {
            fillFromEmployeeDetails({});
        }

        $('#btn-open-add-request-estate').on('click', function() {
            $('#addEditRequestEstateModalLabel').text('Add Estate Request');
            $('#request_estate_id').val('');
            $('#request_employee_pk').val('0');
            $('#formAddEditRequestEstate')[0].reset();
            $('#request_estate_id').val('');
            $('#modal_employee_pk').val('');
            $('#modal_req_id').val('');
            $('#modal_req_date').val(new Date().toISOString().slice(0, 10));
            $('#modal_status_wrap').addClass('d-none');
            $('#modal_status').removeAttr('required');
            setEligibilityLock(!window.requestEstateCanChooseEligibilityOnAdd || window.requestEstateLockEligibilityOnSelfScopeAdd);
            $('#modal_eligibility_self_scope_hint').removeClass('d-none');
            clearEmployeeDerivedFields();
            $('#addEditRequestEstateFormErrors').addClass('d-none').find('#addEditRequestEstateFormErrorsText').empty();
            $('#formAddEditRequestEstate').find('.field-error').empty().end().find('.is-invalid').removeClass('is-invalid');
            var selfPk = (typeof window.requestEstateSelfEmployeePk !== 'undefined' && window.requestEstateSelfEmployeePk)
                ? String(window.requestEstateSelfEmployeePk)
                : null;
            loadRequestEstateEmployees(null, selfPk, function() {
                if (selfPk) {
                    // Ensure change handler runs so derived fields (designation, pay scale, DOJ, eligibility type) are filled.
                    $('#modal_employee_pk').trigger('change');
                }
            });
            $.get('{{ route("admin.estate.request-for-estate.next-req-id") }}', function(res) {
                if (res.next_req_id) $('#modal_req_id').val(res.next_req_id);
                if (addEditModal) addEditModal.show();
            });
        });

        $(document).on('click', '.btn-edit-request-estate', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var rowPk = $btn.data('id');
            var employeePk = $btn.data('employee_pk') || 0;
            $('#addEditRequestEstateModalLabel').text('Edit Estate Request');
            $('#request_estate_id').val(rowPk || '');
            $('#request_employee_pk').val(employeePk);
            $('#modal_req_id').val($btn.data('req_id') || '');
            $('#modal_req_date').val($btn.data('req_date') || '');
            $('#modal_emp_name').val($btn.data('emp_name') || '');
            $('#modal_employee_id').val($btn.data('employee_id') || '');
            $('#modal_emp_designation').val($btn.data('emp_designation') || '');
            $('#modal_pay_scale').val($btn.data('pay_scale') || '');
            $('#modal_doj_pay_scale').val($btn.data('doj_pay_scale') || '');
            $('#modal_doj_academic').val($btn.data('doj_academic') || '');
            $('#modal_doj_service').val($btn.data('doj_service') || '');
            var eligPk = $btn.data('eligibility_type_pk');
            var eligLabel = $btn.data('eligibility_type_label');
            // Match add modal: editable only for Estate/Admin/Super Admin and not on ?scope=self.
            setEligibilityLock(!window.requestEstateCanChooseEligibilityOnAdd || window.requestEstateLockEligibilityOnSelfScopeAdd);
            $('#modal_eligibility_self_scope_hint').addClass('d-none');
            ensureEligibilityOptionAndSetVal(eligPk, eligLabel, true);
            var statusVal = $btn.data('status') !== undefined ? String($btn.data('status')) : '0';
            $('#modal_status').val(statusVal);
            if (document.getElementById('modal_status').tomselect) document.getElementById('modal_status').tomselect.setValue(statusVal, true);
            $('#modal_remarks').val($btn.data('remarks') || '');
            $('#addEditRequestEstateFormErrors').addClass('d-none').find('#addEditRequestEstateFormErrorsText').empty();
            $('#formAddEditRequestEstate').find('.field-error').empty().end().find('.is-invalid').removeClass('is-invalid');
            loadRequestEstateEmployees(rowPk, employeePk, function() {
                if (addEditModal) addEditModal.show();
            });
        });

        $(document).on('change', '#modal_employee_pk', function() {
            var pk = getSelectVal(this);
            var $ro = $('#modal_employee_id, #modal_emp_designation, #modal_pay_scale, #modal_doj_pay_scale, #modal_doj_academic, #modal_doj_service');
            if (!pk) {
                clearEmployeeDerivedFields();
                $ro.prop('readonly', true);
                return;
            }
            $ro.prop('readonly', true);
            $.get('{{ route("admin.estate.request-for-estate.employee-details", ["pk" => "__PK__"]) }}'.replace('__PK__', pk), function(data) {
                fillFromEmployeeDetails(data);
            }).fail(function() {
                clearEmployeeDerivedFields();
            });
        });

        $('#formAddEditRequestEstate').on('submit', function(e) {
            e.preventDefault();
            var selVal = getSelectVal(document.getElementById('modal_employee_pk'));
            $('#request_employee_pk').val(selVal || '0');
            var eligVal = getSelectVal(document.getElementById('modal_eligibility_type_pk'));
            if (!eligibilityLocked) {
                $('#modal_eligibility_type_pk_hidden').val(eligVal || '');
            } else {
                $('#modal_eligibility_type_pk_hidden').val(eligVal || $('#modal_eligibility_type_pk_hidden').val() || '');
            }
            var $form = $(this);
            var $errors = $('#addEditRequestEstateFormErrors');
            var $btn = $('#btnSubmitRequestEstate');
            $errors.addClass('d-none').find('#addEditRequestEstateFormErrorsText').empty();
            $form.find('.field-error').empty();
            $form.find('.is-invalid').removeClass('is-invalid');
            $btn.prop('disabled', true);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function(res) {
                    if (addEditModal) addEditModal.hide();
                    if (res.success && res.message) {
                        var isNew = !$('#request_estate_id').val();
                        $('#requestForEstateTable').DataTable().ajax.reload(null, isNew);
                        var alertHtml = '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-check-circle-fill me-2"></i><span class="flex-grow-1">' + res.message + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                        $('#request-for-estate-card-body').find('.alert-success').remove();
                        $('#request-for-estate-card-body').prepend(alertHtml);
                        setTimeout(function() { $('#request-for-estate-card-body .alert-success').fadeOut(); }, 4000);
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var errs = xhr.responseJSON.errors;
                        var globalMsg = xhr.responseJSON.message || '';
                        $.each(errs, function(key, msgs) {
                            var msg = Array.isArray(msgs) ? msgs[0] : msgs;
                            var $err = $form.find('.field-error[data-field="' + key + '"]');
                            if ($err.length) $err.text(msg);
                            if (key === 'emp_name') {
                                $('#modal_employee_pk').addClass('is-invalid');
                            } else if (key === 'eligibility_type_pk') {
                                $('#modal_eligibility_type_pk').addClass('is-invalid');
                            } else if (key === 'employee_pk') {
                                // Business rule errors like "already have an active request" surface as employee_pk.
                                // Show them in the top alert so user can see clearly.
                                $errors.removeClass('d-none').find('#addEditRequestEstateFormErrorsText').text(msg || globalMsg);
                            } else {
                                $form.find('[name="' + key + '"]').addClass('is-invalid');
                            }
                        });
                        var $firstErr = $form.find('.field-error:not(:empty)').first();
                        if ($firstErr.length) {
                            $firstErr[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        } else if (!$errors.hasClass('d-none')) {
                            $errors[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    } else {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong. Please try again.';
                        $errors.removeClass('d-none').find('#addEditRequestEstateFormErrorsText').text(msg);
                    }
                },
                complete: function() { $btn.prop('disabled', false); }
            });
        });

        $(document).on('click', '.btn-delete-request-estate', function(e) {
            e.preventDefault();
            deleteRequestEstateUrl = $(this).data('url');
            if (deleteModal) deleteModal.show();
        });

        $('#confirmDeleteRequestEstateBtn').on('click', function() {
            if (!deleteRequestEstateUrl) return;
            $.ajax({
                url: deleteRequestEstateUrl,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    if (deleteModal) deleteModal.hide();
                    if (res.success) {
                        $('#requestForEstateTable').DataTable().ajax.reload(null, false);
                        var alertHtml = '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-check-circle-fill me-2"></i><span class="flex-grow-1">' + res.message + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                        $('#request-for-estate-card-body').find('.alert-success').remove();
                        $('#request-for-estate-card-body').prepend(alertHtml);
                        setTimeout(function() { $('#request-for-estate-card-body .alert-success').fadeOut(); }, 4000);
                    }
                },
                error: function(xhr) {
                    if (deleteModal) deleteModal.hide();
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Failed to delete.';
                    var alertHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' + msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                    $('#request-for-estate-card-body').find('.alert-danger').remove();
                    $('#request-for-estate-card-body').prepend(alertHtml);
                }
            });
            deleteRequestEstateUrl = '';
        });
    });
    </script>
@endpush
