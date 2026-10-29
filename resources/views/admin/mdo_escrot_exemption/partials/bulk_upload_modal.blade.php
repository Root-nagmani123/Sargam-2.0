<div class="modal fade mst-modal mee-modal mee-add-modal" id="meeBulkUploadModal" tabindex="-1"
    aria-labelledby="meeBulkUploadModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content mee-form-modal border-0 shadow">
            <form method="POST"
                action="{{ route('mdo-escrot-exemption.bulk.store') }}"
                id="meeBulkUploadForm"
                enctype="multipart/form-data"
                novalidate>
                @csrf

                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold mb-0" id="meeBulkUploadModalLabel">Bulk Upload MDO/ Escort Exemption</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div id="meeBulkFormAlert" class="alert d-none mb-3" role="alert"></div>

                    <div class="alert alert-light border d-flex align-items-start gap-2 mb-3 py-3 rounded-1" role="note">
                        <i class="bi bi-info-circle text-primary fs-5" aria-hidden="true"></i>
                        <div class="small text-secondary">
                            Select the <strong>Course</strong> and <strong>Duty Type</strong> below (applied to every row), then upload an
                            Excel/CSV file with the columns <strong>Name</strong>, <strong>OT Code</strong>, <strong>Date</strong> and
                            <strong>Session</strong>. Use the <strong>Download Template</strong> button to get a ready-to-fill sheet of
                            the selected course's OTs.
                        </div>
                    </div>

                    <div class="mst-field-card">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="meeBulkCourse" class="mst-form-label d-block">
                                    Course Name <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <select name="course_master_pk" id="meeBulkCourse"
                                    class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Course Name" required aria-required="true"
                                    aria-describedby="meeBulkErrorCourse">
                                    <option value="">Select Course Name</option>
                                    @foreach($formCourses ?? [] as $pk => $name)
                                    <option value="{{ $pk }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-danger mee-field-error d-none" id="meeBulkErrorCourse">Course is required.</small>
                            </div>

                            <div class="col-md-6">
                                <label for="meeBulkDutyType" class="mst-form-label d-block">
                                    Duty Type <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <select name="mdo_duty_type_master_pk" id="meeBulkDutyType"
                                    class="form-select mst-control mst-searchable"
                                    data-placeholder="Select Duty Type" required aria-required="true"
                                    aria-describedby="meeBulkErrorDutyType">
                                    <option value="">Select Duty Type</option>
                                    @foreach($MDODutyTypeMaster ?? [] as $pk => $name)
                                    <option value="{{ $pk }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-danger mee-field-error d-none" id="meeBulkErrorDutyType">Duty type is required.</small>
                            </div>

                            {{-- Multi-select; Select2 is initialised by the page script
                                 (initMeeBulkUploadModal) with this modal as dropdownParent. --}}
                            <div class="col-12 d-none" id="meeBulkFacultyContainer">
                                <label for="meeBulkFaculty" class="mst-form-label d-block">
                                    Faculty <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <select name="faculty_master_pk[]" id="meeBulkFaculty"
                                    class="form-select mst-control mee-faculty-select2" multiple
                                    aria-describedby="meeBulkErrorFaculty">
                                    @foreach($facultyMaster ?? [] as $pk => $name)
                                    <option value="{{ $pk }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-danger mee-field-error d-none" id="meeBulkErrorFaculty">Faculty is required for Escort duty.</small>
                            </div>

                            <div class="col-12">
                                <span class="mst-form-label d-block" id="meeBulkTemplateLabel">Sample Template</span>
                                <a href="#" id="meeBulkDownloadTemplate"
                                    class="btn btn-outline-primary rounded-1 d-inline-flex align-items-center gap-2"
                                    aria-describedby="meeBulkTemplateHint">
                                    <i class="bi bi-download" aria-hidden="true"></i>
                                    <span>Download Template</span>
                                </a>
                                <small class="mee-hint" id="meeBulkTemplateHint">Select a course first to pre-fill its OTs in the template.</small>
                            </div>

                            <div class="col-12">
                                <label for="meeBulkFile" class="mst-form-label d-block">
                                    Upload File <span class="mst-req" aria-hidden="true">*</span>
                                </label>
                                <input type="file" name="bulk_file" id="meeBulkFile"
                                    class="form-control mst-control" accept=".xlsx,.xls,.csv" required aria-required="true"
                                    aria-describedby="meeBulkFileHint meeBulkErrorFile">
                                <small class="mee-hint" id="meeBulkFileHint">Accepted formats: .xlsx, .xls, .csv (max 5 MB).</small>
                                <small class="text-danger mee-field-error d-none" id="meeBulkErrorFile">Please select a file to upload.</small>

                                {{-- Upload progress loader for the file --}}
                                <div id="meeBulkUploadProgress" class="d-none mt-2">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                        <small class="text-primary fw-semibold">Uploading file… <span id="meeBulkUploadPercent">0%</span></small>
                                    </div>
                                    <div class="progress rounded-1 mee-progress">
                                        <div id="meeBulkUploadBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                            role="progressbar" style="width: 0%;" aria-valuemin="0" aria-valuemax="100"
                                            aria-label="Upload progress"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="meeBulkRemark" class="mst-form-label d-block">Description</label>
                                <textarea class="form-control mst-control" id="meeBulkRemark" name="Remark" rows="2"
                                    placeholder="Optional remark applied to all uploaded records"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Per-row import results --}}
                    <div class="d-none mt-3" id="meeBulkResultBox">
                        <div class="mee-bulk-result" aria-live="polite">
                            <h6 class="fw-semibold mb-2" id="meeBulkResultSummary"></h6>
                            <div id="meeBulkResultErrors" class="small text-danger d-none">
                                <div class="fw-semibold mb-1">Skipped rows:</div>
                                <ul class="mb-0 ps-3 mee-bulk-errors" id="meeBulkResultErrorList"></ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 gap-2 justify-content-end">
                    <button type="button" class="btn mst-btn-cancel px-4" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn mst-btn-submit px-4" id="meeBulkSubmitBtn">
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
