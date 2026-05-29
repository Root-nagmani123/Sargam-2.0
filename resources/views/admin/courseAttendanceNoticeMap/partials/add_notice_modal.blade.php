{{-- Add Notice wizard + student picker (include on index; requires $activeCourses) --}}

<div class="modal fade mnm-add-notice-modal mnm-add-notice-page" id="mnmAddNoticeModal" tabindex="-1"
    aria-labelledby="mnmAddNoticeModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered mnm-add-notice-dialog modal-dialog-scrollable">
        <div class="modal-content mnm-add-notice-content border-0 shadow">
            <div class="modal-header mnm-add-notice-header border-0">
                <h2 class="modal-title mnm-wizard-title mb-0" id="mnmAddNoticeModalLabel">Add Notice</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body mnm-add-notice-body">
                <div class="mnm-wizard-progress-wrap d-flex align-items-center gap-3 mb-4">
                    <div class="progress flex-grow-1 mnm-wizard-progress rounded-pill" role="progressbar"
                        aria-valuenow="50" aria-valuemin="0" aria-valuemax="100" id="mnmWizardProgressBar">
                        <div class="progress-bar rounded-pill" id="mnmWizardProgressFill" style="width: 50%;"></div>
                    </div>
                    <span class="mnm-wizard-percent text-nowrap" id="mnmWizardPercent">50%</span>
                </div>

                <form action="{{ route('memo.notice.management.store_memo_notice') }}" method="POST" id="mnmAddNoticeForm" novalidate>
                    @csrf

                    <div class="mnm-wizard-step" id="mnmWizardStep1" data-step="1">
                        <div class="row g-3 mnm-add-notice-fields">
                            <div class="col-12">
                                <label for="courseSelect" class="form-label">Course Name <span class="text-danger">*</span></label>
                                <select name="course_master_pk" class="form-select" id="courseSelect" required aria-label="Course Name">
                                    <option value="">Select Course Name</option>
                                    @foreach ($activeCourses as $course)
                                    <option value="{{ $course->pk }}">{{ $course->course_name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="mnm_add_date_memo_notice" class="form-label">Start Date</label>
                                <div class="input-group mnm-input-icon-group">
                                    <input type="text" class="form-control" id="mnm_add_date_memo_notice" name="date_memo_notice"
                                        placeholder="Select date" required aria-label="Start date" autocomplete="off" readonly>
                                    <span class="input-group-text text-secondary">
                                        <i class="bi bi-calendar3" aria-hidden="true"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="mnm_add_subject_master_id" class="form-label">Subject <span class="text-danger">*</span></label>
                                <select name="subject_master_id" class="form-select" id="mnm_add_subject_master_id" required aria-label="Subject">
                                    <option value="">Select Subject</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="mnm_add_topic_id" class="form-label">Topic <span class="text-danger">*</span></label>
                                <select name="topic_id" class="form-select" id="mnm_add_topic_id" required aria-label="Topic">
                                    <option value="">Select Topic</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="mnm_add_venue_select" class="form-label">Venue <span class="text-danger">*</span></label>
                                <select class="form-select mnm-locked-select" id="mnm_add_venue_select" disabled aria-label="Venue">
                                    <option value="">Select Venue</option>
                                </select>
                                <input type="hidden" id="mnm_add_venue_id" name="venue_id">
                            </div>

                            <div class="col-12 col-sm-6">
                                <label for="mnm_add_session_name" class="form-label">Session Start Time</label>
                                <div class="input-group mnm-input-icon-group">
                                    <input type="text" id="mnm_add_session_name" class="form-control" placeholder="Select the time" readonly aria-label="Session start time">
                                    <span class="input-group-text text-secondary">
                                        <i class="bi bi-clock" aria-hidden="true"></i>
                                    </span>
                                </div>
                                <input type="hidden" id="mnm_add_class_session_master_pk" name="class_session_master_pk">
                            </div>

                            <div class="col-12 col-sm-6">
                                <label for="mnm_add_session_name_end_display" class="form-label">Session End Time</label>
                                <div class="input-group mnm-input-icon-group">
                                    <input type="text" id="mnm_add_session_name_end_display" class="form-control" placeholder="Select the time" readonly aria-label="Session end time" tabindex="-1">
                                    <span class="input-group-text text-secondary">
                                        <i class="bi bi-clock" aria-hidden="true"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="mnm_add_faculty_select" class="form-label">Faculty <span class="text-danger">*</span></label>
                                <select class="form-select mnm-locked-select" id="mnm_add_faculty_select" disabled aria-label="Faculty">
                                    <option value="">Select Faculty</option>
                                </select>
                                <input type="hidden" id="mnm_add_faculty_master_pk" name="faculty_master_pk">
                            </div>
                        </div>
                    </div>

                    <div class="mnm-wizard-step d-none" id="mnmWizardStep2" data-step="2">
                        <div class="row g-3 mnm-add-notice-fields">
                            <div class="col-12">
                                <label for="mnmStudentPickerTrigger" class="form-label">Assign Students <span class="text-danger">*</span></label>
                                <button type="button"
                                    class="form-select text-start mnm-student-trigger"
                                    id="mnmStudentPickerTrigger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#mnmStudentListModal">
                                    <span class="text-secondary" id="mnmStudentTriggerLabel">Select Students</span>
                                </button>
                                <div class="mnm-selected-students mt-3 d-none" id="mnmSelectedStudentsBar">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="mnm-selected-count fw-medium" id="mnmSelectedCount">0 Selected</span>
                                        <span class="mnm-selected-divider d-none d-sm-inline" aria-hidden="true">|</span>
                                        <div class="d-flex flex-wrap gap-2 flex-grow-1" id="mnmSelectedPills"></div>
                                    </div>
                                </div>
                                <div class="mnm-dual-listbox-mount visually-hidden" id="mnmDualListboxMount" aria-hidden="true">
                                    <label for="selected_student_list" class="visually-hidden">Select Students</label>
                                    <select id="select_memo_student" class="select1 form-control" name="selected_student_list[]" multiple></select>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="mnm_add_textarea" class="form-label">Message</label>
                                <textarea class="form-control mnm-message-input" id="mnm_add_textarea" rows="4"
                                    placeholder="eg. Lorem ipsum dolor" name="Remark"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-end gap-2 pt-4 mnm-wizard-actions">
                        <button type="button"
                            class="btn mnm-btn-cancel px-4 py-2 rounded-2 fw-semibold"
                            id="mnmWizardCancel"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="button"
                            class="btn mnm-btn-cancel px-4 py-2 rounded-2 fw-semibold d-none"
                            id="mnmWizardBack">Cancel</button>
                        <button type="button"
                            class="btn btn-primary mnm-btn-next px-4 py-2 rounded-2 fw-semibold"
                            id="mnmWizardNext">Next</button>
                        <button type="submit"
                            class="btn btn-primary mnm-btn-next px-4 py-2 rounded-2 fw-semibold d-none"
                            name="submission_type"
                            value="1"
                            id="mnmWizardSubmit">Add Notice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade mnm-student-modal" id="mnmStudentListModal" tabindex="-1"
    aria-labelledby="mnmStudentListModalLabel" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 px-4 pt-4 pb-2">
                <h2 class="modal-title mnm-wizard-title fs-5" id="mnmStudentListModalLabel">Student List</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 pt-2 pb-0" id="mnmStudentModalBody">
                <p class="text-secondary small mb-3">Select defaulter students for this notice. Options load after you choose a topic.</p>
                <div id="mnmDualListboxModalHost"></div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-3 gap-2 flex-wrap justify-content-end">
                <button type="button" class="btn mnm-btn-clear px-3 py-2 rounded-2 fw-semibold" id="mnmStudentClearAll">Clear All</button>
                <button type="button" class="btn mnm-btn-outline px-3 py-2 rounded-2 fw-semibold" id="mnmStudentSelectAll">Select All</button>
                <button type="button" class="btn btn-primary px-4 py-2 rounded-2 fw-semibold" data-bs-dismiss="modal" id="mnmStudentSave">Save</button>
            </div>
        </div>
    </div>
</div>
