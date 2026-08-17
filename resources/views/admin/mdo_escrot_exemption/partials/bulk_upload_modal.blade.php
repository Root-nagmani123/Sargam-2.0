<div class="modal fade mee-modal mee-add-modal" id="meeBulkUploadModal" tabindex="-1"
    aria-labelledby="meeBulkUploadModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content mee-form-modal shadow-lg">
            <form method="POST"
                action="{{ route('mdo-escrot-exemption.bulk.store') }}"
                id="meeBulkUploadForm"
                enctype="multipart/form-data"
                novalidate>
                @csrf

                <div class="mee-modal-header">
                    <h5 class="mee-modal-title" id="meeBulkUploadModalLabel">Bulk Upload MDO/ Escort Exemption</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="mee-modal-body">
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

                    <div class="mee-field-card">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="meeBulkCourse" class="mee-field-label">
                                Course Name <span class="mee-req">*</span>
                            </label>
                            <select name="course_master_pk" id="meeBulkCourse" class="mee-control" required>
                                <option value="">Select Course Name</option>
                                @foreach($formCourses ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="meeBulkErrorCourse">Course is required.</small>
                        </div>

                        <div class="col-md-6">
                            <label for="meeBulkDutyType" class="mee-field-label">
                                Duty Type <span class="mee-req">*</span>
                            </label>
                            <select name="mdo_duty_type_master_pk" id="meeBulkDutyType" class="mee-control" required>
                                <option value="">Select Duty Type</option>
                                @foreach($MDODutyTypeMaster ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="meeBulkErrorDutyType">Duty type is required.</small>
                        </div>

                        <div class="col-12 d-none" id="meeBulkFacultyContainer">
                            <label for="meeBulkFaculty" class="mee-field-label">
                                Faculty <span class="mee-req">*</span>
                            </label>
                            <select name="faculty_master_pk[]" id="meeBulkFaculty" class="mee-control mee-faculty-select2" multiple>
                                @foreach($facultyMaster ?? [] as $pk => $name)
                                <option value="{{ $pk }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            <small class="text-danger d-none mt-1" id="meeBulkErrorFaculty">Faculty is required for Escort duty.</small>
                        </div>

                        <div class="col-12">
                            <label class="mee-field-label">Sample Template</label>
                            <div>
                                <a href="#" id="meeBulkDownloadTemplate"
                                    class="btn mee-btn-cancel d-inline-flex align-items-center gap-2">
                                    <i class="bi bi-download" aria-hidden="true"></i>
                                    <span>Download Template</span>
                                </a>
                                <small class="text-muted d-block mt-1">Select a course first to pre-fill its OTs in the template.</small>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="meeBulkFile" class="mee-field-label">
                                Upload File <span class="mee-req">*</span>
                            </label>
                            <input type="file" name="bulk_file" id="meeBulkFile"
                                class="mee-control" accept=".xlsx,.xls,.csv" required>
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
                            <label for="meeBulkRemark" class="mee-field-label">Description</label>
                            <textarea class="mee-control" id="meeBulkRemark" name="Remark" rows="2"
                                placeholder="Optional remark applied to all uploaded records"></textarea>
                        </div>

                        {{-- Per-row import results --}}
                        <div class="col-12 d-none" id="meeBulkResultBox">
                            <div class="border rounded-3 p-3">
                                <h6 class="fw-semibold mb-2" id="meeBulkResultSummary"></h6>
                                <div id="meeBulkResultErrors" class="small text-danger d-none">
                                    <div class="fw-semibold mb-1">Skipped rows:</div>
                                    <ul class="mb-0 ps-3" id="meeBulkResultErrorList"
                                        style="max-height: 200px; overflow-y: auto;"></ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>

                <div class="mee-modal-footer">
                    <button type="button" class="btn mee-btn-cancel" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="submit" class="btn mee-btn-submit" id="meeBulkSubmitBtn">
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
