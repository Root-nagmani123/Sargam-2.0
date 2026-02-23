@extends('admin.layouts.master')

@section('title', 'Request For Estate - Sargam')

@section('setup_content')
<div class="container-fluid px-2 px-sm-3 px-md-4">
   <x-breadcrum title="Request For Estate" />
   <x-estate-workflow-stepper current="request-for-estate" />

    <x-session_message />

    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div class="flex-grow-1">
                    <h1 class="h4 fw-bold text-dark mb-1">Request For Estate</h1>
                    <p class="text-body-secondary small mb-0">This page displays all list of request details added in the system, and provides options to manage records such as add, edit, delete, excel upload, excel download, print etc.</p>
                </div>
                <div class="flex-shrink-0 d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.estate.put-in-hac') }}" class="btn btn-outline-primary px-3" title="Put In HAC"><i class="bi bi-building-check me-1"></i> Put In HAC</a>
                    <a href="{{ route('admin.estate.hac-forward') }}" class="btn btn-outline-primary px-3" title="HAC Forward"><i class="bi bi-send-fill me-1"></i> HAC Forward</a>
                    <button type="button" class="btn btn-primary px-3" id="btn-open-add-request-estate" title="Add Estate Request"><i class="bi bi-plus-lg me-1"></i> Add Estate Request</button>
                </div>
            </div>

            <div id="request-for-estate-card-body">
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
<div class="modal fade" id="addEditRequestEstateModal" tabindex="-1" aria-labelledby="addEditRequestEstateModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="addEditRequestEstateModalLabel">Add Estate Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <div id="addEditRequestEstateFormErrors" class="alert alert-danger d-none" role="alert">
                    <ul class="mb-0 ps-3"></ul>
                </div>
                <form id="formAddEditRequestEstate" method="POST" action="{{ route('admin.estate.request-for-estate.store') }}">
                    @csrf
                    <input type="hidden" name="id" id="request_estate_id" value="">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="modal_req_id" class="form-label">Request ID</label>
                            <input type="text" class="form-control" id="modal_req_id" name="req_id" maxlength="50" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_req_date" class="form-label">Request Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="modal_req_date" name="req_date" required readonly>
                        </div>
                        <div class="col-md-4 d-none" id="modal_status_wrap">
                            <label for="modal_status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal_status" name="status">
                                <option value="0">Pending</option>
                                <option value="1">Allotted</option>
                                <option value="2">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label for="modal_employee_pk" class="form-label">Employee Name <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal_employee_pk">
                                <option value="">— Select employee —</option>
                                <option value="__new__">— Add new (enter details below) —</option>
                            </select>
                            <input type="hidden" id="modal_emp_name" name="emp_name" value="">
                            <div id="modal_new_emp_name_wrap" class="mt-2 d-none">
                                <label for="modal_new_emp_name" class="form-label">Employee Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="modal_new_emp_name" placeholder="Enter full name" maxlength="50">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_employee_id" name="employee_id" required maxlength="50" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_emp_designation" class="form-label">Designation <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_emp_designation" name="emp_designation" required maxlength="50" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="modal_pay_scale" class="form-label">Pay Scale <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modal_pay_scale" name="pay_scale" required maxlength="50" readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="modal_doj_pay_scale" class="form-label">DOJ (Pay Scale) <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="modal_doj_pay_scale" name="doj_pay_scale" required readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="modal_doj_academic" class="form-label">DOJ (Academy) <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="modal_doj_academic" name="doj_academic" required readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="modal_doj_service" class="form-label">DOJ (Service) <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="modal_doj_service" name="doj_service" required readonly>
                        </div>
                        <div class="col-md-4">
                            <label for="modal_eligibility_type_pk" class="form-label">Eligibility Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="modal_eligibility_type_pk" name="eligibility_type_pk" required>
                                <option value="61">I</option>
                                <option value="62">II</option>
                                <option value="63">III</option>
                                <option value="64">IV</option>
                                <option value="65">V</option>
                                <option value="66">VI</option>
                                <option value="69">IX</option>
                                <option value="70">X</option>
                                <option value="71">XI</option>
                                <option value="73">XIII</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="modal_vacant_house_select" class="form-label">Vacant house <span class="text-muted small">(by eligibility type)</span></label>
                            <select class="form-select" id="modal_vacant_house_select">
                                <option value="">— Select vacant house —</option>
                                <option value="__manual__">— Manual entry —</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="modal_current_alot" class="form-label">Alloted House</label>
                            <input type="text" class="form-control" id="modal_current_alot" name="current_alot" maxlength="20" placeholder="e.g. TTP-II-07" title="House no (max 20 characters)">
                        </div>
                        <div class="col-md-12">
                            <label for="modal_remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="modal_remarks" name="remarks" rows="2" maxlength="500" placeholder="Optional remarks"></textarea>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-success" id="btnSubmitRequestEstate"><i class="bi bi-save me-2"></i>Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle me-2"></i>Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="deleteRequestEstateModal" tabindex="-1" aria-labelledby="deleteRequestEstateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="deleteRequestEstateModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">Are you sure you want to delete this estate request? This action cannot be undone.</div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteRequestEstateBtn">Delete</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Responsive table: horizontal scroll */
    .request-for-estate-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        width: 100%;
    }
    .request-for-estate-table-wrap table {
        min-width: 992px;
    }
    /* DataTables controls: Bootstrap 5 form classes */
    #requestForEstateTable_wrapper .dataTables_length label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    #requestForEstateTable_wrapper .dataTables_length select {
        width: auto;
        min-width: 4.5rem;
        display: inline-block;
        padding: 0.25rem 2rem 0.25rem 0.5rem;
        font-size: 0.875rem;
        border-radius: 0.375rem;
        border: 1px solid var(--bs-border-color, #dee2e6);
    }
    #requestForEstateTable_wrapper .dataTables_filter label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    #requestForEstateTable_wrapper .dataTables_filter input {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        border: 1px solid var(--bs-border-color, #dee2e6);
        border-radius: 0.375rem;
        margin-left: 0.25rem;
    }
    /* Blue header row (Bootstrap 5 table-primary style) */
    #requestForEstateTable_wrapper thead th {
        background-color: var(--bs-primary);
        color: #fff;
        font-weight: 600;
        border-color: var(--bs-primary);
        padding: 0.75rem;
        white-space: nowrap;
    }
    /* Pagination: Bootstrap 5 classes */
    #requestForEstateTable_wrapper .dataTables_paginate {
        margin-top: 0.5rem;
    }
    #requestForEstateTable_wrapper .dataTables_paginate .paginate_button {
        padding: 0.25rem 0.5rem;
        margin: 0 1px;
        border-radius: 0.375rem;
        border: 1px solid var(--bs-border-color);
    }
    #requestForEstateTable_wrapper .dataTables_paginate .paginate_button.current {
        background: var(--bs-primary);
        color: #fff !important;
        border-color: var(--bs-primary);
    }
    #requestForEstateTable_wrapper .dataTables_info {
        padding-top: 0.5rem;
        font-size: 0.875rem;
        color: var(--bs-body-secondary);
    }
    @media (max-width: 767.98px) {
        #requestForEstateTable_wrapper .col-md-6,
        #requestForEstateTable_wrapper .col-md-4,
        #requestForEstateTable_wrapper .col-md-5 { max-width: 100%; }
    }
</style>
@endpush

@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
    $(function() {
        var deleteRequestEstateUrl = '';
        var addEditModalEl = document.getElementById('addEditRequestEstateModal');
        var addEditModal = addEditModalEl ? new bootstrap.Modal(addEditModalEl) : null;
        var deleteModalEl = document.getElementById('deleteRequestEstateModal');
        var deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;

        function loadRequestEstateEmployees(includePk, thenSelectPk) {
            var url = '{{ route("admin.estate.request-for-estate.employees") }}';
            if (includePk) url += '?include_pk=' + includePk;
            var $sel = $('#modal_employee_pk');
            $sel.find('option[value!=""][value!="__new__"]').remove();
            $.get(url, function(list) {
                if (Array.isArray(list)) {
                    list.forEach(function(o) {
                        $sel.append($('<option></option>').attr('value', o.pk).text(o.label || (o.emp_name + ' (' + o.employee_id + ')')));
                    });
                    if (thenSelectPk) $sel.val(thenSelectPk);
                }
            });
        }

        function fillFromEmployeeDetails(data) {
            $('#modal_emp_name').val(data.emp_name || '');
            $('#modal_employee_id').val(data.employee_id || '');
            $('#modal_emp_designation').val(data.emp_designation || '');
            $('#modal_pay_scale').val(data.pay_scale || '');
            $('#modal_doj_pay_scale').val(data.doj_pay_scale || '');
            $('#modal_doj_academic').val(data.doj_academic || '');
            $('#modal_doj_service').val(data.doj_service || '');
            $('#modal_eligibility_type_pk').val(data.eligibility_type_pk !== undefined ? String(data.eligibility_type_pk) : '62');
        }

        function clearEmployeeDerivedFields() {
            fillFromEmployeeDetails({});
        }

        function loadVacantHouses(eligibilityTypePk, thenSelectValue) {
            if (!eligibilityTypePk) {
                $('#modal_vacant_house_select').find('option:not([value=""])').not('[value="__manual__"]').remove();
                $('#modal_vacant_house_select').val('');
                return;
            }
            var url = '{{ route("admin.estate.request-for-estate.vacant-houses") }}?eligibility_type_pk=' + eligibilityTypePk;
            var $sel = $('#modal_vacant_house_select');
            $sel.find('option[value!=""][value!="__manual__"]').remove();
            $.get(url, function(res) {
                var data = res.data || [];
                data.forEach(function(o) {
                    var label = o.label || (o.block_name + ' - ' + o.house_no);
                    $sel.append($('<option></option>').attr('value', label).text(label));
                });
                if (thenSelectValue !== undefined && thenSelectValue !== '') {
                    $sel.val(thenSelectValue);
                    if ($sel.val() !== thenSelectValue) {
                        $sel.val('__manual__');
                    }
                }
            });
        }

        $('#btn-open-add-request-estate').on('click', function() {
            $('#addEditRequestEstateModalLabel').text('Add Estate Request');
            $('#request_estate_id').val('');
            $('#formAddEditRequestEstate')[0].reset();
            $('#request_estate_id').val('');
            $('#modal_employee_pk').val('');
            $('#modal_vacant_house_select').val('');
            $('#modal_req_id').val('');
            $('#modal_req_date').val(new Date().toISOString().slice(0, 10));
            $('#modal_status_wrap').addClass('d-none');
            $('#modal_status').removeAttr('required');
            clearEmployeeDerivedFields();
            $('#addEditRequestEstateFormErrors').addClass('d-none').find('ul').empty();
            loadRequestEstateEmployees();
            var defElig = $('#modal_eligibility_type_pk').val();
            loadVacantHouses(defElig || 62);
            $.get('{{ route("admin.estate.request-for-estate.next-req-id") }}', function(res) {
                if (res.next_req_id) $('#modal_req_id').val(res.next_req_id);
                if (addEditModal) addEditModal.show();
            });
        });

        $(document).on('click', '.btn-edit-request-estate', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var rowPk = $btn.data('id');
            $('#addEditRequestEstateModalLabel').text('Edit Estate Request');
            $('#request_estate_id').val(rowPk || '');
            $('#modal_req_id').val($btn.data('req_id') || '');
            $('#modal_req_date').val($btn.data('req_date') || '');
            $('#modal_emp_name').val($btn.data('emp_name') || '');
            $('#modal_employee_id').val($btn.data('employee_id') || '');
            $('#modal_emp_designation').val($btn.data('emp_designation') || '');
            $('#modal_pay_scale').val($btn.data('pay_scale') || '');
            $('#modal_doj_pay_scale').val($btn.data('doj_pay_scale') || '');
            $('#modal_doj_academic').val($btn.data('doj_academic') || '');
            $('#modal_doj_service').val($btn.data('doj_service') || '');
            $('#modal_eligibility_type_pk').val($btn.data('eligibility_type_pk') !== undefined ? String($btn.data('eligibility_type_pk')) : '62');
            $('#modal_status').val($btn.data('status') !== undefined ? String($btn.data('status')) : '0');
            $('#modal_current_alot').val($btn.data('current_alot') || '');
            $('#modal_remarks').val($btn.data('remarks') || '');
            $('#addEditRequestEstateFormErrors').addClass('d-none').find('ul').empty();
            loadRequestEstateEmployees(rowPk, rowPk);
            var eligPk = $btn.data('eligibility_type_pk');
            var currentAlot = $btn.data('current_alot') || '';
            var valueForSelect = (currentAlot.indexOf(' - ') !== -1) ? currentAlot.split(' - ').pop().trim() : currentAlot;
            loadVacantHouses(eligPk, valueForSelect);
            if (addEditModal) addEditModal.show();
        });

        $('#modal_employee_pk').on('change', function() {
            var pk = $(this).val();
            var $ro = $('#modal_employee_id, #modal_emp_designation, #modal_pay_scale, #modal_doj_pay_scale, #modal_doj_academic, #modal_doj_service');
            if (!pk) {
                clearEmployeeDerivedFields();
                $('#modal_new_emp_name_wrap').addClass('d-none');
                $ro.prop('readonly', true);
                return;
            }
            if (pk === '__new__') {
                clearEmployeeDerivedFields();
                $('#modal_new_emp_name_wrap').removeClass('d-none');
                $('#modal_new_emp_name').val('');
                $ro.prop('readonly', false);
                return;
            }
            $('#modal_new_emp_name_wrap').addClass('d-none');
            $ro.prop('readonly', true);
            $.get('{{ route("admin.estate.request-for-estate.employee-details", ["pk" => "__PK__"]) }}'.replace('__PK__', pk), function(data) {
                fillFromEmployeeDetails(data);
            }).fail(function() {
                clearEmployeeDerivedFields();
            });
        });

        $('#modal_new_emp_name').on('input blur', function() {
            if ($('#modal_employee_pk').val() === '__new__') {
                $('#modal_emp_name').val($(this).val().trim());
            }
        });

        $('#modal_eligibility_type_pk').on('change', function() {
            var pk = $(this).val();
            loadVacantHouses(pk);
            $('#modal_vacant_house_select').val('');
            $('#modal_current_alot').val('');
        });

        $('#modal_vacant_house_select').on('change', function() {
            var v = $(this).val();
            if (v === '__manual__') {
                $('#modal_current_alot').val('').focus();
            } else if (v) {
                $('#modal_current_alot').val(v);
            } else {
                $('#modal_current_alot').val('');
            }
        });

        $('#formAddEditRequestEstate').on('submit', function(e) {
            e.preventDefault();
            if ($('#modal_employee_pk').val() === '__new__') {
                $('#modal_emp_name').val($('#modal_new_emp_name').val().trim());
            }
            var $form = $(this);
            var $errors = $('#addEditRequestEstateFormErrors');
            var $btn = $('#btnSubmitRequestEstate');
            $errors.addClass('d-none').find('ul').empty();
            $btn.prop('disabled', true);
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                success: function(res) {
                    if (addEditModal) addEditModal.hide();
                    if (res.success && res.message) {
                        $('#requestForEstateTable').DataTable().ajax.reload(null, false);
                        var alertHtml = '<div class="alert alert-success alert-dismissible fade show d-flex align-items-center rounded-3 shadow-sm" role="alert"><i class="bi bi-check-circle-fill me-2"></i><span class="flex-grow-1">' + res.message + '</span><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
                        $('#request-for-estate-card-body').find('.alert-success').remove();
                        $('#request-for-estate-card-body').prepend(alertHtml);
                        setTimeout(function() { $('#request-for-estate-card-body .alert-success').fadeOut(); }, 4000);
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        var $ul = $errors.removeClass('d-none').find('ul');
                        $.each(xhr.responseJSON.errors, function(_, msgs) {
                            $.each(msgs, function(__, m) { $ul.append('<li>' + m + '</li>'); });
                        });
                    } else {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong. Please try again.';
                        $errors.removeClass('d-none').find('ul').html('<li>' + msg + '</li>');
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
