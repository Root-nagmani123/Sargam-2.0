<div class="modal fade sme-add-modal" id="smeAddModal" tabindex="-1"
    aria-labelledby="smeAddModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content cgt-form-modal sme-form-modal border-0 shadow-lg rounded-4">
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
                    <hr class="mt-0 mb-4 opacity-50">

                    <div id="smeAddFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <select name="employee_master_pk" id="smeEmployeeMaster" class="d-none" aria-hidden="true" tabindex="-1">
                        @if(Auth::user())
                        <option value="{{ Auth::user()->user_id }}" selected>
                            {{ Auth::user()->first_name }} {{ Auth::user()->last_name }}
                        </option>
                        @endif
                    </select>

                    <input type="hidden" name="student_master_pk" id="smeStudentMasterPk" value="">
                    <input type="hidden" name="from_date" id="smeFromDate" value="">
                    <input type="hidden" name="to_date" id="smeToDate" value="">

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="courseDropdown" class="form-label cgt-field-label mb-2">
                                Course Name <span class="text-danger">*</span>
                            </label>
                            <select name="course_master_pk" id="courseDropdown" class="form-select rounded-3" required>
                                <option value="">Select Course Name</option>
                                @foreach($formCourses ?? [] as $course)
                                <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="smeErrorCourse">Course is required.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="smeExemptionCategory" class="form-label cgt-field-label mb-2">
                                Exemption Category <span class="text-danger">*</span>
                            </label>
                            <select name="exemption_category_master_pk" id="smeExemptionCategory" class="form-select rounded-3" required>
                                <option value="">Select Category</option>
                                @foreach($categories ?? [] as $cat)
                                <option value="{{ $cat->pk }}">{{ $cat->exemp_category_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="smeErrorCategory">Exemption category is required.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="smeMedicalSpeciality" class="form-label cgt-field-label mb-2">
                                Medical Speciality <span class="text-danger">*</span>
                            </label>
                            <select name="exemption_medical_speciality_pk" id="smeMedicalSpeciality" class="form-select rounded-3" required>
                                <option value="">Select Speciality</option>
                                @foreach($specialities ?? [] as $spec)
                                <option value="{{ $spec->pk }}">{{ $spec->speciality_name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="smeErrorSpeciality">Medical speciality is required.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="smeOpdCategory" class="form-label cgt-field-label mb-2">OPD Category</label>
                            <select name="opd_category" id="smeOpdCategory" class="form-select rounded-3">
                                <option value="">Select Type</option>
                                @foreach(['OPD', 'Referred', 'IPD', 'Other'] as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="otCodeField" class="form-label cgt-field-label mb-2">OT Code</label>
                            <input type="text" class="form-control rounded-3" name="ot_code" id="otCodeField"
                                placeholder="eg. OT12345" readonly>
                        </div>

                        <div class="col-12">
                            <label for="smeStartDate" class="form-label cgt-field-label mb-2">
                                Start Date <span class="text-danger">*</span>
                            </label>
                            <div class="position-relative sme-datetime-wrap">
                                <input type="date" id="smeStartDateInput" class="form-control rounded-3" required>
                                <i class="bi bi-calendar3 sme-field-icon" aria-hidden="true"></i>
                            </div>
                            <small class="text-danger d-none mt-1" id="smeErrorFromDate">Start date is required.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="smeStartTime" class="form-label cgt-field-label mb-2">Start Time</label>
                            <div class="position-relative sme-datetime-wrap">
                                <input type="time" id="smeStartTime" class="form-control rounded-3" placeholder="Select the time">
                                <i class="bi bi-clock sme-field-icon" aria-hidden="true"></i>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="smeEndTime" class="form-label cgt-field-label mb-2">End Time</label>
                            <div class="position-relative sme-datetime-wrap">
                                <input type="time" id="smeEndTime" class="form-control rounded-3" placeholder="Select the time">
                                <i class="bi bi-clock sme-field-icon" aria-hidden="true"></i>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="smeAssignStudentsTrigger" class="form-label cgt-field-label mb-2">
                                Assign Students <span class="text-danger">*</span>
                            </label>
                            <button type="button"
                                id="smeAssignStudentsTrigger"
                                class="form-select rounded-3 text-start sme-assign-students-trigger"
                                aria-haspopup="dialog"
                                aria-controls="smeStudentListModal">
                                <span class="text-muted" id="smeAssignStudentsLabel">Select Students</span>
                            </button>
                            <div class="d-flex flex-wrap gap-2 mt-2 d-none" id="smeAssignStudentsTags"></div>
                            <small class="text-danger d-none mt-1" id="smeErrorStudent">Student is required.</small>
                        </div>

                        <div class="col-12">
                            <label for="smeDescription" class="form-label cgt-field-label mb-2">Description</label>
                            <textarea name="Description" id="smeDescription" class="form-control rounded-3" rows="3"
                                placeholder="eg. Lorem ipsum dolor"></textarea>
                        </div>

                        <div class="col-12">
                            <label for="smeDocUpload" class="form-label cgt-field-label mb-2">Attachment</label>
                            <div class="sme-file-upload">
                                <label for="smeDocUpload" class="sme-file-choose btn btn-light border rounded-3">Choose file</label>
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
                            <select name="active_inactive" id="smeActiveInactive" class="form-select rounded-3">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 gap-2 justify-content-end pt-0">
                    <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4" id="smeWizardSubmit">
                        Add Student Medical Exemption
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
