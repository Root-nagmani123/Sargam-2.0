<div class="modal fade mee-add-modal" id="meeAddModal" tabindex="-1"
    aria-labelledby="meeAddModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content cgt-form-modal mee-form-modal border-0 shadow-lg rounded-4">
            <form method="POST"
                action="{{ route('mdo-escrot-exemption.store') }}"
                id="mdoDutyTypeForm"
                novalidate>
                @csrf
                <input type="hidden" name="pk" id="meeRecordPk" value="">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="meeAddModalLabel">Add MDO/ Escort Exemption</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-3">
                    <hr class="mt-0 mb-4 opacity-50">

                    <div id="meeAddFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div id="meeEditStudentInfo" class="alert alert-light border d-none align-items-center gap-2 mb-3 py-3" role="status">
                        <i class="bi bi-person-circle text-primary fs-5" aria-hidden="true"></i>
                        <div class="small">
                            <span class="text-secondary">Student:</span>
                            <strong class="ms-1" id="meeEditStudentName">—</strong>
                            <span class="text-secondary ms-3">Course:</span>
                            <strong class="ms-1" id="meeEditCourseName">—</strong>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mee-add-only-field">
                            <label for="meeCourseDropdown" class="form-label cgt-field-label mb-2">
                                Course Name <span class="text-danger">*</span>
                            </label>
                            <select name="course_master_pk" id="meeCourseDropdown" class="form-select rounded-3" required>
                                <option value="">Select Course Name</option>
                                @foreach($formCourses ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="meeErrorCourse">Course is required.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="mdo_duty_type_master_pk" class="form-label cgt-field-label mb-2">
                                Duty Type <span class="text-danger">*</span>
                            </label>
                            <select name="mdo_duty_type_master_pk" id="mdo_duty_type_master_pk" class="form-select rounded-3" required>
                                <option value="">Select Duty Type</option>
                                @foreach($MDODutyTypeMaster ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="meeErrorDutyType">Duty type is required.</small>
                        </div>

                        <div class="col-12 d-none" id="faculty_field_container">
                            <label for="faculty_master_pk" class="form-label cgt-field-label mb-2">
                                Faculty <span class="text-danger">*</span>
                            </label>
                            <select name="faculty_master_pk[]" id="faculty_master_pk" class="form-select rounded-3 mee-faculty-select2" multiple>
                                @foreach($facultyMaster ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="meeErrorFaculty">Faculty is required for Escort duty.</small>
                        </div>

                        <div class="col-12">
                            <label for="mdo_date" class="form-label cgt-field-label mb-2">
                                Start Date <span class="text-danger">*</span>
                            </label>
                            <div class="position-relative mee-datetime-wrap">
                                <input type="date" name="mdo_date" id="mdo_date" class="form-control rounded-3" required>
                                <i class="bi bi-calendar3 mee-field-icon" aria-hidden="true"></i>
                            </div>
                            <small class="text-danger d-none mt-1" id="meeErrorDate">Start date is required.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="Time_from" class="form-label cgt-field-label mb-2">
                                Start Time <span class="text-danger">*</span>
                            </label>
                            <div class="position-relative mee-datetime-wrap">
                                <input type="time" name="Time_from" id="Time_from" class="form-control rounded-3" required>
                                <i class="bi bi-clock mee-field-icon" aria-hidden="true"></i>
                            </div>
                            <small class="text-danger d-none mt-1" id="meeErrorTimeFrom">Start time is required.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="Time_to" class="form-label cgt-field-label mb-2">
                                End Time <span class="text-danger">*</span>
                            </label>
                            <div class="position-relative mee-datetime-wrap">
                                <input type="time" name="Time_to" id="Time_to" class="form-control rounded-3" required>
                                <i class="bi bi-clock mee-field-icon" aria-hidden="true"></i>
                            </div>
                            <small class="text-danger d-none mt-1" id="meeErrorTimeTo">End time is required.</small>
                        </div>

                        <div class="col-12 mee-add-only-field">
                            <label for="meeAssignStudentsTrigger" class="form-label cgt-field-label mb-2">
                                Assign Students <span class="text-danger">*</span>
                            </label>
                            <button type="button"
                                id="meeAssignStudentsTrigger"
                                class="form-select rounded-3 text-start mee-assign-students-trigger"
                                aria-haspopup="dialog"
                                aria-controls="meeStudentListModal">
                                <span class="text-muted" id="meeAssignStudentsLabel">Select Students</span>
                            </button>
                            <div class="d-flex flex-wrap gap-2 mt-2 d-none" id="meeAssignStudentsTags"></div>
                            <select name="selected_student_list[]" id="hiddenStudentSelect" multiple class="d-none" aria-hidden="true"></select>
                            <small class="text-danger d-none mt-1" id="meeErrorStudents">Please assign at least one student.</small>
                        </div>

                        <div class="col-12 mee-add-only-field">
                            <label for="textarea" class="form-label cgt-field-label mb-2">Description</label>
                            <textarea class="form-control rounded-3" id="textarea" name="Remark" rows="3"
                                placeholder="eg. Lorem ipsum dolor"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 gap-2 justify-content-end pt-0">
                    <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4" id="meeAddSubmitBtn">
                        Add MDO/ Escort Exemption
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
