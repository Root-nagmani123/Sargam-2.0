<div class="modal fade mee-modal mee-add-modal" id="meeEditModal" tabindex="-1"
    aria-labelledby="meeEditModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content mee-form-modal shadow-lg">
            <form method="POST"
                action="{{ route('mdo-escrot-exemption.update') }}"
                id="meeEditForm"
                novalidate>
                @csrf
                <input type="hidden" name="pk" id="meeEditRecordPk" value="">

                <div class="mee-modal-header">
                    <h5 class="mee-modal-title" id="meeEditModalLabel">Edit MDO/ Escort Exemption</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="mee-modal-body">
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

                    <div class="mee-field-card">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="meeEditDutyType" class="mee-field-label">
                                Duty Type <span class="mee-req">*</span>
                            </label>
                            <select name="mdo_duty_type_master_pk" id="meeEditDutyType" class="mee-control" required>
                                <option value="">Select Duty Type</option>
                                @foreach($MDODutyTypeMaster ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="meeEditErrorDutyType">Duty type is required.</small>
                        </div>

                        <div class="col-md-6 d-none" id="meeEditFacultyContainer">
                            <label for="meeEditFaculty" class="mee-field-label">
                                Faculty <span class="mee-req">*</span>
                            </label>
                            <select name="faculty_master_pk[]" id="meeEditFaculty" class="mee-control mee-faculty-select2" multiple>
                                @foreach($facultyMaster ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="meeEditErrorFaculty">Faculty is required for Escort duty.</small>
                        </div>

                        <div class="col-12">
                            <label for="meeEditDate" class="mee-field-label">
                                Start Date <span class="mee-req">*</span>
                            </label>
                            <input type="date" name="mdo_date" id="meeEditDate" class="mee-control" required>
                            <small class="text-danger d-none mt-1" id="meeEditErrorDate">Start date is required.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="meeEditTimeFrom" class="mee-field-label">
                                Start Time <span class="mee-req">*</span>
                            </label>
                            <input type="time" name="Time_from" id="meeEditTimeFrom" class="mee-control" required>
                            <small class="text-danger d-none mt-1" id="meeEditErrorTimeFrom">Start time is required.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="meeEditTimeTo" class="mee-field-label">
                                End Time <span class="mee-req">*</span>
                            </label>
                            <input type="time" name="Time_to" id="meeEditTimeTo" class="mee-control" required>
                            <small class="text-danger d-none mt-1" id="meeEditErrorTimeTo">End time is required.</small>
                        </div>
                    </div>
                    </div>
                </div>

                <div class="mee-modal-footer">
                    <button type="button" class="btn mee-btn-cancel" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn mee-btn-submit" id="meeEditSubmitBtn">
                        Update MDO/ Escort Exemption
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
