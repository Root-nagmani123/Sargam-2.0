<div class="modal fade mee-add-modal" id="meeBulkUploadModal" tabindex="-1"
    aria-labelledby="meeBulkUploadModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content cgt-form-modal mee-form-modal border-0 shadow-lg rounded-4">
            <form method="POST"
                action="{{ route('mdo-escrot-exemption.bulk.store') }}"
                id="meeBulkUploadForm"
                enctype="multipart/form-data"
                novalidate>
                @csrf

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="meeBulkUploadModalLabel">Bulk Upload MDO/ Escort Exemption</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body pt-3">
                    <hr class="mt-0 mb-4 opacity-50">

                    <div id="meeBulkFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="alert alert-light border d-flex align-items-start gap-2 mb-4 py-3" role="note">
                        <i class="bi bi-info-circle text-primary fs-5 mt-1" aria-hidden="true"></i>
                        <div class="small text-secondary">
                            Select the <strong>Course</strong> and <strong>Duty Type</strong> below (applied to every row), then upload an
                            Excel/CSV file with the columns <strong>Name</strong>, <strong>OT Code</strong>, <strong>Date</strong> and
                            <strong>Session</strong>. Use the <strong>Download Template</strong> button to get a ready-to-fill sheet of
                            the selected course's OTs.
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="meeBulkCourse" class="form-label cgt-field-label mb-2">
                                Course Name <span class="text-danger">*</span>
                            </label>
                            <select name="course_master_pk" id="meeBulkCourse" class="form-select rounded-3" required>
                                <option value="">Select Course Name</option>
                                @foreach($formCourses ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="meeBulkErrorCourse">Course is required.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="meeBulkDutyType" class="form-label cgt-field-label mb-2">
                                Duty Type <span class="text-danger">*</span>
                            </label>
                            <select name="mdo_duty_type_master_pk" id="meeBulkDutyType" class="form-select rounded-3" required>
                                <option value="">Select Duty Type</option>
                                @foreach($MDODutyTypeMaster ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="meeBulkErrorDutyType">Duty type is required.</small>
                        </div>

                        <div class="col-12 d-none" id="meeBulkFacultyContainer">
                            <label for="meeBulkFaculty" class="form-label cgt-field-label mb-2">
                                Faculty <span class="text-danger">*</span>
                            </label>
                            <select name="faculty_master_pk" id="meeBulkFaculty" class="form-select rounded-3">
                                <option value="">Select Faculty</option>
                                @foreach($facultyMaster ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="meeBulkErrorFaculty">Faculty is required for Escort duty.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label cgt-field-label mb-2">Sample Template</label>
                            <div>
                                <a href="#" id="meeBulkDownloadTemplate"
                                    class="btn btn-outline-primary rounded-3 d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-download" aria-hidden="true"></i>
                                    <span>Download Template</span>
                                </a>
                                <small class="text-muted d-block mt-1">Select a course first to pre-fill its OTs in the template.</small>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="meeBulkFile" class="form-label cgt-field-label mb-2">
                                Upload File <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="bulk_file" id="meeBulkFile"
                                class="form-control rounded-3" accept=".xlsx,.xls,.csv" required>
                            <small class="text-muted d-block mt-1">Accepted formats: .xlsx, .xls, .csv (max 5 MB).</small>
                            <small class="text-danger d-none mt-1" id="meeBulkErrorFile">Please select a file to upload.</small>

                            {{-- Upload progress loader for the file --}}
                            <div id="meeBulkUploadProgress" class="d-none mt-2">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <small class="text-primary fw-semibold">Uploading file… <span id="meeBulkUploadPercent">0%</span></small>
                                </div>
                                <div class="progress rounded-pill" style="height: 6px;">
                                    <div id="meeBulkUploadBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                        role="progressbar" style="width: 0%;" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="meeBulkRemark" class="form-label cgt-field-label mb-2">Description</label>
                            <textarea class="form-control rounded-3" id="meeBulkRemark" name="Remark" rows="2"
                                placeholder="Optional remark applied to all uploaded records"></textarea>
                        </div>

                        {{-- Per-row import results --}}
                        <div class="col-12 d-none" id="meeBulkResultBox">
                            <div class="border rounded-3 p-3">
                                <h6 class="fw-semibold mb-2" id="meeBulkResultSummary"></h6>
                                <div id="meeBulkResultErrors" class="small text-danger d-none">
                                    <div class="fw-semibold mb-1">Skipped rows:</div>
                                    <ul class="mb-0 ps-3" id="meeBulkResultErrorList"></ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 gap-2 justify-content-end pt-0">
                    <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4" id="meeBulkSubmitBtn">
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
