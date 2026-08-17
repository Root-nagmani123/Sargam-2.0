<div class="modal fade mee-modal mee-add-modal" id="meeAddModal" tabindex="-1"
    aria-labelledby="meeAddModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content mee-form-modal shadow-lg">
            <form method="POST"
                action="{{ route('mdo-escrot-exemption.store') }}"
                id="mdoDutyTypeForm"
                novalidate>
                @csrf
                <input type="hidden" name="pk" id="meeRecordPk" value="">

                <div class="mee-modal-header">
                    <h5 class="mee-modal-title" id="meeAddModalLabel">Add MDO/ Escort Exemption</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="mee-modal-body">
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

                    <div class="mee-field-card">
                    <div class="row g-3">
                        <div class="col-md-6 mee-add-only-field">
                            <label for="meeCourseDropdown" class="mee-field-label">
                                Course Name <span class="mee-req">*</span>
                            </label>
                            <select name="course_master_pk" id="meeCourseDropdown" class="mee-control" required>
                                <option value="">Select Course Name</option>
                                @foreach($formCourses ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="meeErrorCourse">Course is required.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="mdo_duty_type_master_pk" class="mee-field-label">
                                Duty Type <span class="mee-req">*</span>
                            </label>
                            <select name="mdo_duty_type_master_pk" id="mdo_duty_type_master_pk" class="mee-control" required>
                                <option value="">Select Duty Type</option>
                                @foreach($MDODutyTypeMaster ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="meeErrorDutyType">Duty type is required.</small>
                        </div>

                        <div class="col-md-12 d-none" id="duty_other_container">
                            <label for="duty_other" class="mee-field-label">
                                Duty Other <span class="mee-req">*</span>
                            </label>
                            <input type="text" name="duty_other" id="duty_other" class="mee-control"
                                placeholder="Enter duty name" maxlength="255">
                            <small class="text-danger d-none mt-1" id="meeErrorDutyOther">Duty other is required.</small>
                        </div>

                        <div class="col-12 d-none" id="faculty_field_container">
                            <label for="faculty_master_pk" class="mee-field-label">
                                Faculty <span class="mee-req">*</span>
                            </label>
                            <select name="faculty_master_pk[]" id="faculty_master_pk" class="mee-control mee-faculty-select2" multiple>
                                @foreach($facultyMaster ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="meeErrorFaculty">Faculty is required for Escort duty.</small>
                        </div>

                        <div class="col-12">
                            <label for="mdo_date" class="mee-field-label">
                                Start Date <span class="mee-req">*</span>
                            </label>
                            <input type="date" name="mdo_date" id="mdo_date" class="mee-control" required>
                            <small class="text-danger d-none mt-1" id="meeErrorDate">Start date is required.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="Time_from" class="mee-field-label">
                                Start Time <span class="mee-req">*</span>
                            </label>
                            <input type="time" name="Time_from" id="Time_from" class="mee-control" required>
                            <small class="text-danger d-none mt-1" id="meeErrorTimeFrom">Start time is required.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="Time_to" class="mee-field-label">
                                End Time <span class="mee-req">*</span>
                            </label>
                            <input type="time" name="Time_to" id="Time_to" class="mee-control" required>
                            <small class="text-danger d-none mt-1" id="meeErrorTimeTo">End time is required.</small>
                        </div>

                        <div class="col-12 mee-add-only-field">
                            <label for="meeAssignStudentsTrigger" class="mee-field-label">
                                Assign Students <span class="mee-req">*</span>
                            </label>
                            <button type="button"
                                id="meeAssignStudentsTrigger"
                                class="mee-control text-start mee-assign-students-trigger"
                                aria-haspopup="dialog"
                                aria-controls="meeStudentListModal">
                                <span class="text-muted" id="meeAssignStudentsLabel">Select Students</span>
                            </button>
                            <div class="d-flex flex-wrap gap-2 mt-2 d-none" id="meeAssignStudentsTags"></div>
                            <select name="selected_student_list[]" id="hiddenStudentSelect" multiple class="d-none" aria-hidden="true"></select>
                            <small class="text-danger d-none mt-1" id="meeErrorStudents">Please assign at least one student.</small>
                        </div>

                        <div class="col-12 mee-add-only-field">
                            <label for="textarea" class="mee-field-label">Description</label>
                            <textarea class="mee-control" id="textarea" name="Remark" rows="3"
                                placeholder="Enter a remark or description (optional)"></textarea>
                        </div>
                    </div>
                    </div>
                </div>

                <div class="mee-modal-footer">
                    <button type="button" class="btn mee-btn-cancel" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn mee-btn-submit" id="meeAddSubmitBtn">
                        Add MDO/ Escort Exemption
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
