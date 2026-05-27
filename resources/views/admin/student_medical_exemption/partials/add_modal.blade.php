<div class="modal fade sme-add-modal" id="smeAddModal" tabindex="-1"
    aria-labelledby="smeAddModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content cgt-form-modal sme-wizard-modal border-0 shadow-lg rounded-4">
            <form method="POST"
                action="{{ route('student.medical.exemption.store') }}"
                enctype="multipart/form-data"
                id="smeAddExemptionForm"
                novalidate>
                @csrf

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="smeAddModalLabel">Add Student Medical Exemption</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-3">
                    <div class="d-flex align-items-center gap-3 mb-4 sme-wizard-progress-wrap">
                        <div class="progress flex-grow-1 sme-wizard-progress rounded-pill" role="progressbar"
                            aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"
                            aria-label="Form completion progress">
                            <div class="progress-bar rounded-pill" id="smeWizardProgressBar" style="width: 50%;"></div>
                        </div>
                        <span class="sme-wizard-pct text-muted small fw-medium" id="smeWizardPct">50%</span>
                    </div>

                    <div id="smeAddFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div id="smeWizardStep1" class="sme-wizard-step">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="smeEmployeeMaster" class="form-label cgt-field-label mb-2">
                                    Doctor Name <span class="text-danger">*</span>
                                </label>
                                <select name="employee_master_pk" id="smeEmployeeMaster" class="form-select" required readonly>
                                    @if(Auth::user())
                                    <option value="{{ Auth::user()->user_id }}" selected>
                                        {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                                    </option>
                                    @endif
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="courseDropdown" class="form-label cgt-field-label mb-2">
                                    Course Name <span class="text-danger">*</span>
                                </label>
                                <select name="course_master_pk" id="courseDropdown" class="form-select" required>
                                    <option value="">Select Course Name</option>
                                    @foreach($formCourses ?? [] as $course)
                                    <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-danger d-none mt-1" id="smeErrorCourse">Course is required.</small>
                            </div>

                            <div class="col-12">
                                <label for="otCodeField" class="form-label cgt-field-label mb-2">OT Code</label>
                                <input type="text" class="form-control" name="ot_code" id="otCodeField"
                                    placeholder="eg. OT12345" readonly>
                            </div>

                            <div class="col-12">
                                <label for="smeStudentDropdown" class="form-label cgt-field-label mb-2">
                                    Student Name <span class="text-danger">*</span>
                                </label>
                                <div class="sme-student-field" id="smeStudentFieldWrap">
                                    <select name="student_master_pk"
                                        id="smeStudentDropdown"
                                        class="js-sme-student-select"
                                        data-sme-no-global-select2="1"
                                        required>
                                        <option value="">Search Student</option>
                                    </select>
                                </div>
                                <small class="text-danger d-none mt-1" id="smeErrorStudent">Student is required.</small>
                            </div>
                        </div>
                    </div>

                    <div id="smeWizardStep2" class="sme-wizard-step d-none">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="smeExemptionCategory" class="form-label cgt-field-label mb-2">
                                    Exemption Category <span class="text-danger">*</span>
                                </label>
                                <select name="exemption_category_master_pk" id="smeExemptionCategory" class="form-select" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories ?? [] as $cat)
                                    <option value="{{ $cat->pk }}">{{ $cat->exemp_category_name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-danger d-none mt-1" id="smeErrorCategory">Exemption category is required.</small>
                            </div>

                            <div class="col-12">
                                <label for="smeOpdCategory" class="form-label cgt-field-label mb-2">OPD Category</label>
                                <select name="opd_category" id="smeOpdCategory" class="form-select">
                                    <option value="">Select Type</option>
                                    @foreach(['OPD', 'Referred', 'IPD', 'Other'] as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="smeMedicalSpeciality" class="form-label cgt-field-label mb-2">
                                    Medical Speciality <span class="text-danger">*</span>
                                </label>
                                <select name="exemption_medical_speciality_pk" id="smeMedicalSpeciality" class="form-select" required>
                                    <option value="">Select Speciality</option>
                                    @foreach($specialities ?? [] as $spec)
                                    <option value="{{ $spec->pk }}">{{ $spec->speciality_name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-danger d-none mt-1" id="smeErrorSpeciality">Medical speciality is required.</small>
                            </div>

                            <div class="col-md-6">
                                <label for="smeFromDate" class="form-label cgt-field-label mb-2">
                                    Start Date <span class="text-danger">*</span>
                                </label>
                                <div class="position-relative sme-datetime-wrap">
                                    <input type="datetime-local" name="from_date" id="smeFromDate" class="form-control" required>
                                    <i class="bi bi-calendar3 sme-field-icon" aria-hidden="true"></i>
                                </div>
                                <small class="text-danger d-none mt-1" id="smeErrorFromDate">Start date is required.</small>
                            </div>

                            <div class="col-md-6">
                                <label for="smeToDate" class="form-label cgt-field-label mb-2">End Date</label>
                                <div class="position-relative sme-datetime-wrap">
                                    <input type="datetime-local" name="to_date" id="smeToDate" class="form-control">
                                    <i class="bi bi-calendar3 sme-field-icon" aria-hidden="true"></i>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="smeDescription" class="form-label cgt-field-label mb-2">Description</label>
                                <textarea name="Description" id="smeDescription" class="form-control" rows="3"
                                    placeholder="Enter description"></textarea>
                            </div>

                            <div class="col-12">
                                <label for="smeDocUpload" class="form-label cgt-field-label mb-2">Attachment</label>
                                <div class="sme-file-upload">
                                    <label for="smeDocUpload" class="sme-file-choose btn btn-light border">Choose file</label>
                                    <input type="file" name="Doc_upload" id="smeDocUpload" class="sme-file-input">
                                    <span class="sme-file-name text-muted" id="smeFileName">No file chosen</span>
                                </div>
                                <div class="d-none mt-2" id="smeExistingDocWrap">
                                    <a href="#" target="_blank" rel="noopener noreferrer" id="smeExistingDoc" class="sme-doc-link">View existing file</a>
                                </div>
                            </div>

                            <div class="col-12 d-none" id="smeStatusWrap">
                                <label for="smeActiveInactive" class="form-label cgt-field-label mb-2">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <select name="active_inactive" id="smeActiveInactive" class="form-select">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 gap-2 justify-content-end pt-0">
                    <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal" id="smeWizardCancel">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-outline-primary rounded-3 px-4 d-none" id="smeWizardBack">
                        Back
                    </button>
                    <button type="button" class="btn btn-primary rounded-3 px-4" id="smeWizardNext">
                        Next
                    </button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 d-none" id="smeWizardSubmit">
                        Add Student Medical Exemption
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
