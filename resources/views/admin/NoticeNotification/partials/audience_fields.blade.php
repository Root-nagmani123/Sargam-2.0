{{--
    Hierarchical target-audience picker, shared by the notice create + edit forms.

    Officer trainee : Courses (none = all) -> All / Group / Individual
                      -> groups across the chosen courses, or the OT list with codes
    Staff/Faculty   : Departments (none = all) -> All / Individual -> employee list

    Course, group and department are all multi-select: a notice routinely has to
    reach several at once. Leaving a multi-select empty is how "Select All" is
    expressed, which is why none of them carries a literal "Select All" option.

    Every box below starts hidden; audience_scripts.blade.php reveals them as the
    selection above each one is made, and re-applies $audiencePreset after a
    validation bounce or when editing an existing notice.
--}}
<div class="col-12 audience-block d-none" id="otAudienceBox">
    <div class="audience-panel rounded-3 p-3">
        <div class="audience-panel-title">
            <i class="bi bi-people-fill me-1" aria-hidden="true"></i>Officer Trainee audience
        </div>

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label d-flex flex-wrap align-items-center gap-2" for="courseSelect">
                    <span>Select Course(s)</span>
                    <span class="badge rounded-1 bg-primary-subtle text-primary fw-semibold" id="courseCount">0 selected</span>
                    <button type="button" class="btn btn-link btn-sm p-0 ms-auto js-audience-select-all" data-target="courseSelect">Select all</button>
                    <button type="button" class="btn btn-link btn-sm p-0 text-secondary js-audience-clear" data-target="courseSelect">Clear</button>
                </label>
                <select name="course_master_pks[]" id="courseSelect" class="form-control" multiple></select>
                <div class="form-text text-muted small">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Leave empty to send this notice to
                    <strong>every Officer Trainee in every course</strong>. Pick one or more to narrow it.
                </div>
            </div>

            <div class="col-md-6 d-none" id="otScopeBox">
                <label class="form-label" for="otScopeSelect">
                    Recipients <span class="text-danger" aria-hidden="true">*</span>
                </label>
                <select name="ot_scope" id="otScopeSelect" class="form-control">
                    <option value="{{ \App\Models\NoticeNotification::MODE_ALL }}">All</option>
                    <option value="{{ \App\Models\NoticeNotification::MODE_GROUP }}">Group</option>
                    <option value="{{ \App\Models\NoticeNotification::MODE_INDIVIDUAL }}">Individual</option>
                </select>
            </div>

            <div class="col-md-6 d-none" id="otGroupBox">
                <label class="form-label d-flex flex-wrap align-items-center gap-2" for="otGroupSelect">
                    <span>Group Type(s) <span class="text-danger" aria-hidden="true">*</span></span>
                    <span class="badge rounded-1 bg-primary-subtle text-primary fw-semibold" id="otGroupCount">0 selected</span>
                    <button type="button" class="btn btn-link btn-sm p-0 ms-auto js-audience-select-all" data-target="otGroupSelect">Select all</button>
                    <button type="button" class="btn btn-link btn-sm p-0 text-secondary js-audience-clear" data-target="otGroupSelect">Clear</button>
                </label>
                <select name="group_type_map_pks[]" id="otGroupSelect" class="form-control" multiple></select>
            </div>

            <div class="col-12 d-none" id="studentBox">
                <label class="form-label d-flex flex-wrap align-items-center gap-2" for="studentSelect">
                    <span>Select Officer Trainees <span class="text-danger" aria-hidden="true">*</span></span>
                    <span class="badge rounded-1 bg-primary-subtle text-primary fw-semibold" id="studentCount">0 selected</span>
                    <button type="button" class="btn btn-link btn-sm p-0 ms-auto js-audience-select-all" data-target="studentSelect">Select all</button>
                    <button type="button" class="btn btn-link btn-sm p-0 text-secondary js-audience-clear" data-target="studentSelect">Clear</button>
                </label>
                <select name="student_pks[]" id="studentSelect" class="form-control" multiple></select>
                <div class="form-text text-muted small">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Search by OT code or name.
                </div>
            </div>

            <div class="col-12 d-none" id="otGroupPreviewBox">
                <label class="form-label">
                    Officer Trainees in the selected group(s)
                    <span class="badge rounded-1 bg-secondary-subtle text-secondary fw-semibold" id="otGroupPreviewCount">0</span>
                </label>
                <div class="audience-preview" id="otGroupPreview"></div>
                <div class="form-text text-muted small">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Everyone listed here will receive the notice.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-12 audience-block d-none" id="staffAudienceBox">
    <div class="audience-panel rounded-3 p-3">
        <div class="audience-panel-title">
            <i class="bi bi-person-badge-fill me-1" aria-hidden="true"></i>Staff / Faculty audience
        </div>

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label d-flex flex-wrap align-items-center gap-2" for="departmentSelect">
                    <span>Select Department(s)</span>
                    <span class="badge rounded-1 bg-primary-subtle text-primary fw-semibold" id="departmentCount">0 selected</span>
                    <button type="button" class="btn btn-link btn-sm p-0 ms-auto js-audience-select-all" data-target="departmentSelect">Select all</button>
                    <button type="button" class="btn btn-link btn-sm p-0 text-secondary js-audience-clear" data-target="departmentSelect">Clear</button>
                </label>
                <select name="department_master_pks[]" id="departmentSelect" class="form-control" multiple>
                    @foreach($departments as $department)
                    <option value="{{ $department->pk }}">{{ $department->department_name }}</option>
                    @endforeach
                </select>
                <div class="form-text text-muted small">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Leave empty to send this notice to
                    <strong>every staff / faculty member</strong>. Pick one or more to narrow it.
                </div>
            </div>

            <div class="col-md-6 d-none" id="staffScopeBox">
                <label class="form-label" for="staffScopeSelect">
                    Recipients <span class="text-danger" aria-hidden="true">*</span>
                </label>
                <select name="staff_scope" id="staffScopeSelect" class="form-control">
                    <option value="{{ \App\Models\NoticeNotification::MODE_ALL }}">All</option>
                    <option value="{{ \App\Models\NoticeNotification::MODE_INDIVIDUAL }}">Individual</option>
                </select>
            </div>

            <div class="col-12 d-none" id="employeeBox">
                <label class="form-label d-flex flex-wrap align-items-center gap-2" for="employeeSelect">
                    <span>Select Staff / Faculty <span class="text-danger" aria-hidden="true">*</span></span>
                    <span class="badge rounded-1 bg-primary-subtle text-primary fw-semibold" id="employeeCount">0 selected</span>
                    <button type="button" class="btn btn-link btn-sm p-0 ms-auto js-audience-select-all" data-target="employeeSelect">Select all</button>
                    <button type="button" class="btn btn-link btn-sm p-0 text-secondary js-audience-clear" data-target="employeeSelect">Clear</button>
                </label>
                <select name="employee_pks[]" id="employeeSelect" class="form-control" multiple></select>
                <div class="form-text text-muted small">
                    <i class="bi bi-info-circle me-1" aria-hidden="true"></i>Search by name, employee ID or designation.
                </div>
            </div>
        </div>
    </div>
</div>
