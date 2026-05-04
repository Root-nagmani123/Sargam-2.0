@extends('admin.layouts.master')
@section('title', 'Edit Vehicle Pass Application - Security Management')
@section('content')
<div class="container-fluid">
    <div class="card" style="border-left:4px solid #004a93;">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Edit Vehicle Pass Application - {{ $vehiclePass->vehicle_req_id }}</h4>
                <a href="{{ route('admin.security.vehicle_pass.index') }}" class="btn btn-secondary">
                    <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">arrow_back</i>
                    Back to List
                </a>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted">Please update the vehicle pass application details below. Note: Only pending applications can be edited.</p>
            
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Whoops!</strong> There were some problems with your input.
                    <ul class="mb-0 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $storedApplicantTypeStr = \App\Models\VehiclePassTWApply::applicantTypeToFormValue($vehiclePass->applicant_type ?? null);
                if (! in_array($storedApplicantTypeStr, ['employee', 'others', 'government_vehicle'], true)) {
                    $storedApplicantTypeStr = $vehiclePass->gov_veh == 1 ? 'government_vehicle' : ($vehiclePass->emp_master_pk ? 'employee' : 'others');
                }
                $editApplicantType = old('applicant_type', $storedApplicantTypeStr ?? 'others');
                $editOthersMode = ($editApplicantType === 'others');
                $editName = old('applicant_name', $editApplicantDisplay['name'] ?? '');
                $editDesignation = old('designation', $editApplicantDisplay['designation'] ?? '');
                $editDepartment = old('department', $editApplicantDisplay['department'] ?? '');
            @endphp

            <form action="{{ route('admin.security.vehicle_pass.update', encrypt($vehiclePass->vehicle_tw_pk)) }}" method="POST" enctype="multipart/form-data" id="vehiclePassForm">
                @csrf
                <input type="hidden" name="emp_master_pk" value="{{ old('emp_master_pk', $vehiclePass->emp_master_pk) }}">

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap gap-4 align-items-center">
                            <span class="fw-semibold text-dark me-2">Applicant type:</span>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="applicant_type" id="applicant_type_employee" value="employee" {{ $editApplicantType === 'employee' ? 'checked' : '' }}>
                                <label class="form-check-label" for="applicant_type_employee">Employee</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="applicant_type" id="applicant_type_others" value="others" {{ $editApplicantType === 'others' ? 'checked' : '' }}>
                                <label class="form-check-label" for="applicant_type_others">Others</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="applicant_type" id="applicant_type_government" value="government_vehicle" {{ $editApplicantType === 'government_vehicle' ? 'checked' : '' }}>
                                <label class="form-check-label" for="applicant_type_government">Government Vehicle</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold text-primary">Vehicle & Applicant Details</label>
                        <hr class="mt-1">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="employee_id_card" class="form-label">ID Card Number</label>
                            <input type="text" name="employee_id_card" id="employee_id_card" class="form-control {{ $editOthersMode ? '' : 'bg-light' }}"
                                value="{{ old('employee_id_card', $vehiclePass->employee_id_card) }}" placeholder="Enter ID Card Number" maxlength="100"
                                @unless($editOthersMode) readonly @endunless>
                            <div id="editVehiclePassIdCardLookupHint" class="small mt-1 d-none" role="status"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="applicant_name" class="form-label">Name</label>
                            <input type="text" name="applicant_name" id="applicant_name" class="form-control {{ $editOthersMode ? '' : 'bg-light' }}"
                                value="{{ $editName }}" placeholder="—"
                                @unless($editOthersMode) readonly @endunless>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="designation" class="form-label">Designation</label>
                            <input type="text" name="designation" id="designation" class="form-control {{ $editOthersMode ? '' : 'bg-light' }}"
                                value="{{ $editDesignation }}" placeholder="—"
                                @unless($editOthersMode) readonly @endunless>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" name="department" id="department" class="form-control {{ $editOthersMode ? '' : 'bg-light' }}"
                                value="{{ $editDepartment }}" placeholder="—"
                                @unless($editOthersMode) readonly @endunless>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="vehicle_type" class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                            <select name="vehicle_type" id="vehicle_type" class="form-select @error('vehicle_type') is-invalid @enderror" required>
                                <option value="">Select</option>
                                @foreach($vehicleTypes as $vt)
                                    <option value="{{ $vt->pk }}" {{ (old('vehicle_type', $vehiclePass->vehicle_type) == $vt->pk) ? 'selected' : '' }}>{{ $vt->vehicle_type }}</option>
                                @endforeach
                            </select>
                            @error('vehicle_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="vehicle_no" class="form-label">Vehicle Number <span class="text-danger">*</span></label>
                            <input type="text" name="vehicle_no" id="vehicle_no" class="form-control @error('vehicle_no') is-invalid @enderror"
                                value="{{ old('vehicle_no', $vehiclePass->vehicle_no) }}" placeholder="Enter Vehicle Number" required maxlength="50">
                            @error('vehicle_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3 mt-2">
                        <label class="form-label fw-bold text-primary">Validity Period</label>
                        <hr class="mt-1">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="veh_card_valid_from" class="form-label">
                                Valid From <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="veh_card_valid_from" id="veh_card_valid_from" 
                                class="form-control @error('veh_card_valid_from') is-invalid @enderror" 
                                value="{{ old('veh_card_valid_from', $vehiclePass->veh_card_valid_from ? \Carbon\Carbon::parse($vehiclePass->veh_card_valid_from)->format('Y-m-d') : '') }}" 
                                min="{{ now()->format('Y-m-d') }}"
                                required>
                            @error('veh_card_valid_from')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="vech_card_valid_to" class="form-label">
                                Valid To <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="vech_card_valid_to" id="vech_card_valid_to" 
                                class="form-control @error('vech_card_valid_to') is-invalid @enderror" 
                                value="{{ old('vech_card_valid_to', $vehiclePass->vech_card_valid_to ? \Carbon\Carbon::parse($vehiclePass->vech_card_valid_to)->format('Y-m-d') : '') }}" 
                                min="{{ now()->format('Y-m-d') }}"
                                required>
                            @error('vech_card_valid_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3 mt-2">
                        <label class="form-label fw-bold text-primary">Additional Information</label>
                        <hr class="mt-1">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="doc_upload" class="form-label">Ownership Documents</label>
                            @php
                                $docPath = $vehiclePass->doc_upload;
                                $docExists = $docPath && \Storage::disk('public')->exists($docPath);
                            @endphp
                            @if($docExists)
                                @php $ext = strtolower(pathinfo($docPath, PATHINFO_EXTENSION)); @endphp
                                <div class="vehicle-pass-edit-current mb-2 position-relative d-inline-block">
                                    <div class="vehicle-pass-preview-inner position-relative p-2 rounded border bg-light">
                                        @if(in_array($ext, ['jpg','jpeg','png','gif']))
                                            <img src="{{ asset('storage/' . $docPath) }}" alt="Current document" style="max-height:120px; border-radius:4px; display:block;">
                                        @else
                                            <div class="d-flex flex-column align-items-center justify-content-center p-2">
                                                <i class="material-icons material-symbols-rounded text-muted mb-1">description</i>
                                                <span class="small text-break text-center">{{ basename($docPath) }}</span>
                                            </div>
                                        @endif
                                        <a href="{{ asset('storage/' . $docPath) }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2 w-100">View</a>
                                    </div>
                                </div>
                            @elseif($docPath)
                                <div class="text-warning small mb-2">No file available in storage</div>
                            @endif
                            <div class="vehicle-pass-upload-zone position-relative" id="editDocUploadZone" style="min-height:120px; border:2px dashed #dee2e6; border-radius:0.5rem; padding:1rem; cursor:pointer; background:#f8f9fa;">
                                <input type="file" name="doc_upload" id="doc_upload" class="d-none" accept=".pdf,.jpg,.jpeg,.png">
                                <div class="vehicle-pass-upload-placeholder text-center" id="editDocPlaceholder">
                                    <i class="material-icons material-symbols-rounded text-muted">upload</i>
                                    <p class="small mb-0 mt-1">Click to upload or drag and drop (optional - replace current)</p>
                                    <span class="small text-muted">Allowed: PDF, JPG, PNG. Max size: 2 MB</span>
                                </div>
                                <div class="vehicle-pass-upload-preview d-none" id="editDocPreview">
                                    <div class="vehicle-pass-preview-inner position-relative d-inline-block p-2 rounded border bg-white shadow-sm" style="min-width:200px; min-height:100px;">
                                        <img src="" alt="Preview" class="d-none" id="editDocPreviewImg" style="max-height:120px; border-radius:4px;">
                                        <div class="d-none flex-column align-items-center justify-content-center p-2" id="editDocFileNameWrap">
                                            <i class="material-icons material-symbols-rounded text-muted mb-1">description</i>
                                            <p class="small text-muted mb-0 text-break text-center" id="editDocFileName"></p>
                                        </div>
                                        <button type="button" class="btn btn-danger btn-sm position-absolute rounded-circle shadow border border-2 border-white" id="editDocRemove" style="top:-8px;right:-8px;width:32px;height:32px;padding:0;z-index:10;display:inline-flex;align-items:center;justify-content:center;" aria-label="Remove" title="Remove"><i class="material-icons material-symbols-rounded" style="font-size:20px;">close</i></button>
                                    </div>
                                </div>
                            </div>
                            @error('doc_upload')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-success me-2">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">update</i>
                            Update Application
                        </button>
                        <a href="{{ route('admin.security.vehicle_pass.index') }}" class="btn btn-secondary">
                            <i class="material-icons material-symbols-rounded" style="font-size:20px;vertical-align:middle;">cancel</i>
                            Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    window.editVehiclePassLoggedInCapYmd = @json($idCardValidityCapYmd ?? null);
    window.editOthersLookupIdCardCapYmd = null;

    function editEffectiveIdCardCapYmd() {
        var emp = document.getElementById('applicant_type_employee');
        var gov = document.getElementById('applicant_type_government');
        if ((emp && emp.checked) || (gov && gov.checked)) {
            return window.editVehiclePassLoggedInCapYmd || null;
        }
        return window.editOthersLookupIdCardCapYmd || null;
    }

    // Others: ID card lookup (employee_master) + applicant type toggle (same behaviour as create)
    (function () {
        var applicantTypeEmployee = document.getElementById('applicant_type_employee');
        var applicantTypeOthers = document.getElementById('applicant_type_others');
        var applicantTypeGovernment = document.getElementById('applicant_type_government');
        var empMasterPkInput = document.getElementById('emp_master_pk');
        var currentUserEmployee = @json($currentUserEmployee ?? null);
        var idCardInput = document.getElementById('employee_id_card');
        var nameEl = document.getElementById('applicant_name');
        var desEl = document.getElementById('designation');
        var deptEl = document.getElementById('department');
        var validToInput = document.getElementById('vech_card_valid_to');
        var lookupHint = document.getElementById('editVehiclePassIdCardLookupHint');
        var othersLookupAbort = null;
        var lookupUrl = @json(route('admin.security.vehicle_pass.lookup.by_id_card'));

        function isEmployeeOrGovVehicle() {
            return (applicantTypeEmployee && applicantTypeEmployee.checked) || (applicantTypeGovernment && applicantTypeGovernment.checked);
        }

        function setEditLookupHint(message, kind) {
            if (!lookupHint) return;
            if (!message) {
                lookupHint.classList.add('d-none');
                lookupHint.textContent = '';
                lookupHint.classList.remove('text-success', 'text-danger', 'text-muted');
                return;
            }
            lookupHint.classList.remove('d-none', 'text-success', 'text-danger', 'text-muted');
            lookupHint.classList.add(kind === 'error' ? 'text-danger' : (kind === 'success' ? 'text-success' : 'text-muted'));
            lookupHint.textContent = message;
        }

        function setApplicantFieldsReadonly(readonly) {
            var ro = !!readonly;
            [idCardInput, nameEl, desEl, deptEl].forEach(function (el) {
                if (!el) return;
                el.readOnly = ro;
                if (ro) el.classList.add('bg-light');
                else el.classList.remove('bg-light');
            });
        }

        function applyEditOthersIdCardLookup() {
            if (!applicantTypeOthers || !applicantTypeOthers.checked) return;
            if (!idCardInput) return;
            var idVal = (idCardInput.value || '').trim();
            if (!idVal) {
                setEditLookupHint('', '');
                window.editOthersLookupIdCardCapYmd = null;
                if (typeof syncEditDateConstraints === 'function') syncEditDateConstraints();
                return;
            }
            if (othersLookupAbort) {
                try { othersLookupAbort.abort(); } catch (e) {}
            }
            othersLookupAbort = new AbortController();
            setEditLookupHint('Looking up employee…', 'muted');
            var url = lookupUrl + (lookupUrl.indexOf('?') >= 0 ? '&' : '?') + 'id_card_number=' + encodeURIComponent(idVal);
            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                signal: othersLookupAbort.signal
            }).then(function (r) { return r.json().then(function (body) { return { ok: r.ok, body: body }; }); })
                .then(function (res) {
                    if (!res.ok || !res.body.success || !res.body.data) {
                        var msg = (res.body && res.body.message) ? res.body.message : 'Could not load details for this ID.';
                        setEditLookupHint(msg, 'error');
                        window.editOthersLookupIdCardCapYmd = null;
                        if (typeof syncEditDateConstraints === 'function') syncEditDateConstraints();
                        return;
                    }
                    var d = res.body.data;
                    if (idCardInput) idCardInput.value = d.employee_id_card || idVal;
                    if (nameEl) nameEl.value = d.applicant_name || '';
                    if (desEl) desEl.value = d.designation || '';
                    if (deptEl) deptEl.value = d.department || '';
                    if (empMasterPkInput && d.emp_master_pk) empMasterPkInput.value = String(d.emp_master_pk);
                    window.editOthersLookupIdCardCapYmd = d.id_card_valid_to || null;
                    if (typeof syncEditDateConstraints === 'function') syncEditDateConstraints();
                    setEditLookupHint(
                        d.id_card_valid_to
                            ? 'Name, designation and department loaded. Pass dates cannot exceed ID card validity.'
                            : 'Name, designation and department loaded from employee master.',
                        'success'
                    );
                }).catch(function (err) {
                    if (err && err.name === 'AbortError') return;
                    setEditLookupHint('Request failed. Please try again.', 'error');
                });
        }

        function updateEditApplicantTypeFields() {
            if (isEmployeeOrGovVehicle()) {
                setEditLookupHint('', '');
                if (currentUserEmployee && empMasterPkInput) {
                    empMasterPkInput.value = String(currentUserEmployee.pk);
                    if (idCardInput) idCardInput.value = currentUserEmployee.emp_id || '';
                    if (nameEl) nameEl.value = currentUserEmployee.name || '';
                    if (desEl) desEl.value = currentUserEmployee.designation || '';
                    if (deptEl) deptEl.value = currentUserEmployee.department || '';
                } else if (empMasterPkInput) {
                    empMasterPkInput.value = '';
                    if (idCardInput) idCardInput.value = '';
                    if (nameEl) nameEl.value = '';
                    if (desEl) desEl.value = '';
                    if (deptEl) deptEl.value = '';
                }
                setApplicantFieldsReadonly(true);
                window.editOthersLookupIdCardCapYmd = null;
            } else {
                if (empMasterPkInput) empMasterPkInput.value = '';
                if (idCardInput) idCardInput.value = '';
                if (nameEl) nameEl.value = '';
                if (desEl) desEl.value = '';
                if (deptEl) deptEl.value = '';
                setApplicantFieldsReadonly(false);
                window.editOthersLookupIdCardCapYmd = null;
                setEditLookupHint('', '');
            }
            if (typeof syncEditDateConstraints === 'function') {
                syncEditDateConstraints();
            }
        }

        function initEditApplicantUiFromServer() {
            if (isEmployeeOrGovVehicle()) {
                setApplicantFieldsReadonly(true);
                window.editOthersLookupIdCardCapYmd = null;
            } else {
                setApplicantFieldsReadonly(false);
            }
            if (typeof syncEditDateConstraints === 'function') syncEditDateConstraints();
        }

        if (applicantTypeEmployee) applicantTypeEmployee.addEventListener('change', updateEditApplicantTypeFields);
        if (applicantTypeOthers) applicantTypeOthers.addEventListener('change', updateEditApplicantTypeFields);
        if (applicantTypeGovernment) applicantTypeGovernment.addEventListener('change', updateEditApplicantTypeFields);
        initEditApplicantUiFromServer();

        if (idCardInput) {
            idCardInput.addEventListener('blur', function () {
                if (applicantTypeOthers && applicantTypeOthers.checked) {
                    applyEditOthersIdCardLookup();
                } else {
                    setEditLookupHint('', '');
                }
            });
            idCardInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (applicantTypeOthers && applicantTypeOthers.checked) applyEditOthersIdCardLookup();
                }
            });
        }
    })();

    // Edit doc upload: click zone, preview, remove
    var editZone = document.getElementById('editDocUploadZone');
    var editInput = document.getElementById('doc_upload');
    var editPlaceholder = document.getElementById('editDocPlaceholder');
    var editPreview = document.getElementById('editDocPreview');
    var editPreviewImg = document.getElementById('editDocPreviewImg');
    var editFileName = document.getElementById('editDocFileName');
    var editRemove = document.getElementById('editDocRemove');
    if (editZone && editInput) {
        editZone.addEventListener('click', function(e) {
            if (!$(e.target).closest('#editDocRemove').length) editInput.click();
        });
        editInput.addEventListener('change', function() {
            var file = this.files[0];
            if (!file) return;
            var editFileNameWrap = document.getElementById('editDocFileNameWrap');
            if (file.type && file.type.indexOf('image/') === 0) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    editPreviewImg.src = e.target.result;
                    editPreviewImg.classList.remove('d-none');
                    if (editFileNameWrap) editFileNameWrap.classList.add('d-none');
                    editFileName.textContent = '';
                    editPlaceholder.classList.add('d-none');
                    editPreview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                editPreviewImg.classList.add('d-none');
                editPreviewImg.src = '';
                editFileName.textContent = file.name;
                if (editFileNameWrap) { editFileNameWrap.classList.remove('d-none'); editFileNameWrap.classList.add('d-flex'); }
                editPlaceholder.classList.add('d-none');
                editPreview.classList.remove('d-none');
            }
        });
    }
    if (editRemove) {
        editRemove.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (editInput) editInput.value = '';
            if (editPreviewImg) { editPreviewImg.src = ''; editPreviewImg.classList.add('d-none'); }
            if (editFileName) editFileName.textContent = '';
            var editFileNameWrap = document.getElementById('editDocFileNameWrap');
            if (editFileNameWrap) { editFileNameWrap.classList.add('d-none'); editFileNameWrap.classList.remove('d-flex'); }
            if (editPreview) editPreview.classList.add('d-none');
            if (editPlaceholder) editPlaceholder.classList.remove('d-none');
        });
    }

    // Date guards: block past dates and keep valid_to >= valid_from
    function getTodayYmdLocal() {
        var now = new Date();
        var tzOffsetMs = now.getTimezoneOffset() * 60000;
        return new Date(now.getTime() - tzOffsetMs).toISOString().slice(0, 10);
    }

    function syncEditDateConstraints() {
        var fromEl = document.getElementById('veh_card_valid_from');
        var toEl = document.getElementById('vech_card_valid_to');
        if (!fromEl || !toEl) return;
        var todayYmd = getTodayYmdLocal();
        fromEl.min = todayYmd;
        // End date must be >= start date (and also not in past).
        var minTo = todayYmd;
        if (fromEl.value) {
            minTo = (fromEl.value > todayYmd) ? fromEl.value : todayYmd;
        }
        toEl.min = minTo;

        if (fromEl.value && fromEl.value < todayYmd) {
            fromEl.value = '';
        }
        if (toEl.value && fromEl.value && toEl.value < fromEl.value) {
            toEl.value = '';
        } else         if (toEl.value && toEl.value < toEl.min) {
            toEl.value = '';
        }

        var capY = (typeof editEffectiveIdCardCapYmd === 'function') ? editEffectiveIdCardCapYmd() : null;
        if (capY && capY >= todayYmd) {
            fromEl.setAttribute('max', capY);
            toEl.setAttribute('max', capY);
            if (fromEl.value && fromEl.value > capY) {
                fromEl.value = '';
            }
            if (toEl.value && toEl.value > capY) {
                toEl.value = '';
            }
        } else {
            fromEl.removeAttribute('max');
            toEl.removeAttribute('max');
        }
    }

    syncEditDateConstraints();
    $('#veh_card_valid_from').on('change', syncEditDateConstraints);
    $('#vech_card_valid_to').on('change', syncEditDateConstraints);

    // Validate valid_to date is after valid_from
    $('#vech_card_valid_to').on('change', function() {
        var validFrom = new Date($('#veh_card_valid_from').val());
        var validTo = new Date($(this).val());
        if (validTo < validFrom) {
            if (typeof toastr !== 'undefined') toastr.error('Valid To date must be after or equal to Valid From date');
            $(this).val('');
        }
    });

    $('#vehiclePassForm').on('submit', function(e) {
        var validFrom = new Date($('#veh_card_valid_from').val());
        var validTo = new Date($('#vech_card_valid_to').val());
        if (validTo < validFrom) {
            e.preventDefault();
            if (typeof toastr !== 'undefined') toastr.error('Valid To date must be after or equal to Valid From date');
            return false;
        }
    });
});
</script>
@endpush
