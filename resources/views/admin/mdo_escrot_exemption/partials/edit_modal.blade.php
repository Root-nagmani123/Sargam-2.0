<div class="modal fade mee-add-modal" id="meeEditModal" tabindex="-1"
    aria-labelledby="meeEditModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content cgt-form-modal mee-form-modal border-0 shadow-lg rounded-4">
            <form method="POST"
                action="{{ route('mdo-escrot-exemption.update') }}"
                id="meeEditForm"
                novalidate>
                @csrf
                <input type="hidden" name="pk" id="meeEditRecordPk" value="">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="meeEditModalLabel">Edit MDO/ Escort Exemption</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-3">
                    <hr class="mt-0 mb-4 opacity-50">

                    <div id="meeEditFormAlert" class="alert d-none mb-3" role="alert"></div>

                    {{-- Read-only context: Student + Course --}}
                    <div class="alert alert-light border d-flex align-items-center gap-2 mb-4 py-3" role="status">
                        <i class="bi bi-person-circle text-primary fs-5" aria-hidden="true"></i>
                        <div class="small">
                            <span class="text-secondary">Student:</span>
                            <strong class="ms-1 text-primary" id="meeEditStudentDisplay">—</strong>
                            <span class="text-secondary ms-3">Course:</span>
                            <strong class="ms-1 text-primary" id="meeEditCourseDisplay">—</strong>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="meeEditDutyType" class="form-label cgt-field-label mb-2">
                                Duty Type <span class="text-danger">*</span>
                            </label>
                            <select name="mdo_duty_type_master_pk" id="meeEditDutyType" class="form-select rounded-1" required>
                                <option value="">Select Duty Type</option>
                                @foreach($MDODutyTypeMaster ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="meeEditErrorDutyType">Duty type is required.</small>
                        </div>

                        <div class="col-md-6 d-none" id="meeEditFacultyContainer">
                            <label for="meeEditFaculty" class="form-label cgt-field-label mb-2">
                                Faculty <span class="text-danger">*</span>
                            </label>
                            <select name="faculty_master_pk[]" id="meeEditFaculty" class="form-select rounded-1 mee-faculty-select2" multiple>
                                @foreach($facultyMaster ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="meeEditErrorFaculty">Faculty is required for Escort duty.</small>
                        </div>

                        <div class="col-12">
                            <label for="meeEditDate" class="form-label cgt-field-label mb-2">
                                Start Date <span class="text-danger">*</span>
                            </label>
                            <div class="position-relative mee-datetime-wrap">
                                <input type="date" name="mdo_date" id="meeEditDate" class="form-control rounded-1" required>
                                <i class="bi bi-calendar3 mee-field-icon" aria-hidden="true"></i>
                            </div>
                            <small class="text-danger d-none mt-1" id="meeEditErrorDate">Start date is required.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="meeEditTimeFrom" class="form-label cgt-field-label mb-2">
                                Start Time <span class="text-danger">*</span>
                            </label>
                            <div class="position-relative mee-datetime-wrap">
                                <input type="time" name="Time_from" id="meeEditTimeFrom" class="form-control rounded-1" required>
                                <i class="bi bi-clock mee-field-icon" aria-hidden="true"></i>
                            </div>
                            <small class="text-danger d-none mt-1" id="meeEditErrorTimeFrom">Start time is required.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="meeEditTimeTo" class="form-label cgt-field-label mb-2">
                                End Time <span class="text-danger">*</span>
                            </label>
                            <div class="position-relative mee-datetime-wrap">
                                <input type="time" name="Time_to" id="meeEditTimeTo" class="form-control rounded-1" required>
                                <i class="bi bi-clock mee-field-icon" aria-hidden="true"></i>
                            </div>
                            <small class="text-danger d-none mt-1" id="meeEditErrorTimeTo">End time is required.</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 gap-2 justify-content-end pt-0">
                    <button type="button" class="btn btn-outline-primary rounded-1 px-4" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary rounded-1 px-4" id="meeEditSubmitBtn">
                        Update MDO/ Escort Exemption
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
