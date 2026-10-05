<div class="modal fade mst-modal mee-modal mee-add-modal" id="meeAddModal" tabindex="-1"
    aria-labelledby="meeAddModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content mee-form-modal border-0 shadow">
            <form method="POST"
                action="{{ route('mdo-escrot-exemption.store') }}"
                id="mdoDutyTypeForm"
                novalidate>
                @csrf
                <input type="hidden" name="pk" id="meeRecordPk" value="">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="meeAddModalLabel">Add MDO/ Escort Exemption</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="meeAddFormAlert" class="alert d-none mb-3" role="alert"></div>

                    {{-- Shown by prepareEditMode() (adds .d-flex); hidden again in add mode. --}}
                    <div id="meeEditStudentInfo" class="d-none mb-3" role="status">
                        <div class="mst-field-card w-100">
                            <div class="mst-facts">
                                <div>
                                    <span class="mst-fact__label">Student</span>
                                    <span class="mst-fact__value fw-semibold" id="meeEditStudentName">—</span>
                                </div>
                                <div>
                                    <span class="mst-fact__label">Course</span>
                                    <span class="mst-fact__value fw-semibold" id="meeEditCourseName">—</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mst-field-card">
                        <div class="row g-3">
                            <div class="col-md-6 mee-add-only-field">
                                <label for="meeCourseDropdown" class="mst-form-label d-block">
                                    Course Name <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <select name="course_master_pk" id="meeCourseDropdown"
                                    class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Course Name" required aria-required="true"
                                    aria-describedby="meeErrorCourse">
                                    <option value="">Select Course Name</option>
                                    @foreach($formCourses ?? [] as $pk => $name)
                                    <option value="{{ $pk }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-danger mee-field-error d-none" id="meeErrorCourse">Course is required.</small>
                            </div>

                            <div class="col-md-6">
                                <label for="mdo_duty_type_master_pk" class="mst-form-label d-block">
                                    Duty Type <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <select name="mdo_duty_type_master_pk" id="mdo_duty_type_master_pk"
                                    class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Duty Type" required aria-required="true"
                                    aria-describedby="meeErrorDutyType">
                                    <option value="">Select Duty Type</option>
                                    @foreach($MDODutyTypeMaster ?? [] as $pk => $name)
                                    <option value="{{ $pk }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-danger mee-field-error d-none" id="meeErrorDutyType">Duty type is required.</small>
                            </div>

                            <div class="col-md-12 d-none" id="duty_other_container">
                                <label for="duty_other" class="mst-form-label d-block">
                                    Duty Other <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <input type="text" name="duty_other" id="duty_other" class="form-control mst-control"
                                    placeholder="Enter duty name" maxlength="255" aria-describedby="meeErrorDutyOther">
                                <small class="text-danger mee-field-error d-none" id="meeErrorDutyOther">Duty other is required.</small>
                            </div>

                            {{-- Multi-select; Select2 is initialised by the page script
                                 (initFacultySelect2) with this modal as dropdownParent. --}}
                            <div class="col-12 d-none" id="faculty_field_container">
                                <label for="faculty_master_pk" class="mst-form-label d-block">
                                    Faculty <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <select name="faculty_master_pk[]" id="faculty_master_pk"
                                    class="form-select mst-control mee-faculty-select2" multiple
                                    aria-describedby="meeErrorFaculty">
                                    @foreach($facultyMaster ?? [] as $pk => $name)
                                    <option value="{{ $pk }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-danger mee-field-error d-none" id="meeErrorFaculty">Faculty is required for Escort duty.</small>
                            </div>

                            <div class="col-12">
                                <label for="mdo_date" class="mst-form-label d-block">
                                    Start Date <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <input type="date" name="mdo_date" id="mdo_date" class="form-control mst-control"
                                    required aria-required="true" aria-describedby="meeErrorDate">
                                <small class="text-danger mee-field-error d-none" id="meeErrorDate">Start date is required.</small>
                            </div>

                            <div class="col-md-6">
                                <label for="Time_from" class="mst-form-label d-block">
                                    Start Time <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <input type="time" name="Time_from" id="Time_from" class="form-control mst-control"
                                    required aria-required="true" aria-describedby="meeErrorTimeFrom">
                                <small class="text-danger mee-field-error d-none" id="meeErrorTimeFrom">Start time is required.</small>
                            </div>

                            <div class="col-md-6">
                                <label for="Time_to" class="mst-form-label d-block">
                                    End Time <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <input type="time" name="Time_to" id="Time_to" class="form-control mst-control"
                                    required aria-required="true" aria-describedby="meeErrorTimeTo">
                                <small class="text-danger mee-field-error d-none" id="meeErrorTimeTo">End time is required.</small>
                            </div>

                            <div class="col-12 mee-add-only-field">
                                <label for="meeAssignStudentsTrigger" class="mst-form-label d-block">
                                    Assign Students <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <button type="button"
                                    id="meeAssignStudentsTrigger"
                                    class="form-select mst-control mee-assign-students-trigger"
                                    aria-haspopup="dialog"
                                    aria-controls="meeStudentListModal"
                                    aria-describedby="meeErrorStudents">
                                    <span class="text-muted" id="meeAssignStudentsLabel">Select Students</span>
                                </button>
                                <div class="d-flex flex-wrap gap-2 mt-2 d-none" id="meeAssignStudentsTags"></div>
                                <select name="selected_student_list[]" id="hiddenStudentSelect" multiple class="d-none" aria-hidden="true" tabindex="-1"></select>
                                <small class="text-danger mee-field-error d-none" id="meeErrorStudents">Please assign at least one student.</small>
                            </div>

                            <div class="col-12 mee-add-only-field">
                                <label for="textarea" class="mst-form-label d-block">Description</label>
                                <textarea class="form-control mst-control" id="textarea" name="Remark" rows="3"
                                    placeholder="Enter a remark or description (optional)"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn mst-btn-submit px-4" id="meeAddSubmitBtn">
                        Add MDO/ Escort Exemption
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
