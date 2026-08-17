@php
    $course = $courseMasterObj ?? null;
    $isEdit = $isEdit ?? (!empty($course) && $course->pk);
    $startDateValue = old('startdate', ($course && filled($course->start_year))
        ? \Carbon\Carbon::parse($course->start_year)->format('Y-m-d')
        : '');
    $endDateValue = old('enddate', ($course && filled($course->end_date))
        ? \Carbon\Carbon::parse($course->end_date)->format('Y-m-d')
        : '');
    $ptStartTimeValue = old('pt_start_time', ($course && filled($course->pt_start_time))
        ? \Carbon\Carbon::parse($course->pt_start_time)->format('H:i')
        : '');
    $ptEndTimeValue = old('pt_end_time', ($course && filled($course->pt_end_time))
        ? \Carbon\Carbon::parse($course->pt_end_time)->format('H:i')
        : '');
    $assistantCoordinators = $assistant_coordinator_name ?? [];
    $assistantRoles = $assistant_coordinator_roles ?? [];
@endphp

<form action="{{ route('programme.store') }}" method="POST" class="programme-create-form programme-create-choices">
    @csrf

    @if($isEdit && $course)
    <input type="hidden" name="course_id" value="{{ encrypt($course->pk) }}">
    @endif

    {{-- Basic Details --}}
    <div class="prog-card programme-create-card">
            <div class="prog-section"><h2 class="prog-section-title">Basic Details</h2></div>

            <div class="row g-4" id="course_fields">
                <div class="col-md-6">
                    <x-input
                        name="courseshortname"
                        label="Course Short Name"
                        placeholder="eg. IAS 01"
                        formLabelClass="prog-field-label programme-field-label"
                        value="{{ old('courseshortname', $course?->couse_short_name ?? '') }}"
                        formInputClass="prog-control programme-form-control" />
                </div>
                <div class="col-md-6">
                    <x-input
                        name="coursename"
                        label="Course Name"
                        placeholder="eg. IAS Training Course"
                        formLabelClass="prog-field-label programme-field-label"
                        value="{{ old('coursename', $course?->course_name ?? '') }}"
                        formInputClass="prog-control programme-form-control"
                        required="true" />
                </div>

                <div class="col-12">
                    <x-input
                        type="text"
                        name="courseyear"
                        label="Course Year"
                        placeholder="Select the year"
                        value="{{ old('courseyear', $course?->course_year ?? date('Y')) }}"
                        formLabelClass="prog-field-label programme-field-label"
                        formInputClass="prog-control programme-form-control"
                        min="1900"
                        max="2100"
                        required="true" />
                </div>

                <div class="col-md-6">
                    <label class="prog-field-label programme-field-label" for="startdate">Start Date</label>
                    <div class="programme-input-icon-wrap">
                        <x-input
                            type="date"
                            name="startdate"
                            label=""
                            placeholder="Select the date"
                            value="{{ $startDateValue }}"
                            formLabelClass="d-none"
                            formInputClass="prog-control programme-form-control prog-control programme-form-control--icon" />
                        <i class="bi bi-calendar3 programme-field-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="prog-field-label programme-field-label" for="enddate">End Date</label>
                    <div class="programme-input-icon-wrap">
                        <x-input
                            type="date"
                            name="enddate"
                            label=""
                            placeholder="Select the date"
                            value="{{ $endDateValue }}"
                            formLabelClass="d-none"
                            formInputClass="prog-control programme-form-control prog-control programme-form-control--icon" />
                        <i class="bi bi-calendar3 programme-field-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="prog-field-label programme-field-label" for="pt_start_time">PT Start Time</label>
                    <div class="programme-input-icon-wrap">
                        <x-input
                            type="time"
                            name="pt_start_time"
                            label=""
                            placeholder="Select PT start time"
                            value="{{ $ptStartTimeValue }}"
                            formLabelClass="d-none"
                            formInputClass="prog-control programme-form-control prog-control programme-form-control--icon" />
                        <i class="bi bi-clock programme-field-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="prog-field-label programme-field-label" for="pt_end_time">PT End Time</label>
                    <div class="programme-input-icon-wrap">
                        <x-input
                            type="time"
                            name="pt_end_time"
                            label=""
                            placeholder="Select PT end time"
                            value="{{ $ptEndTimeValue }}"
                            formLabelClass="d-none"
                            formInputClass="prog-control programme-form-control prog-control programme-form-control--icon" />
                        <i class="bi bi-clock programme-field-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="prog-field-label programme-field-label" for="coursecoordinator">Course Coordinator</label>
                    <div class="programme-input-icon-wrap programme-input-icon-wrap--select">
                        <x-select
                            name="coursecoordinator"
                            label=""
                            placeholder="Select the Coordinator"
                            formLabelClass="d-none"
                            formSelectClass="searchable-dropdown prog-control programme-form-control prog-control programme-form-control--icon"
                            value="{{ old('coursecoordinator', $coordinator_name ?? '') }}"
                            :options="$facultyList" />
                        <i class="bi bi-list-ul programme-field-icon" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="prog-field-label programme-field-label" for="supportingsection">Supporting Section</label>
                    <div class="programme-input-icon-wrap programme-input-icon-wrap--select">
                        <x-select
                            name="supportingsection"
                            label=""
                            placeholder="Select the Supporting section"
                            formLabelClass="d-none"
                            formSelectClass="searchable-dropdown prog-control programme-form-control prog-control programme-form-control--icon"
                            value="{{ old('supportingsection', $selectedSupportingSection ?? '') }}"
                            :options="$supportingSectionList" />
                        <i class="bi bi-list-ul programme-field-icon" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
    </div>

    {{-- Assistant Course Coordinators --}}
    <div class="prog-card programme-create-card">
            <div class="prog-section">
            <div>
                <h2 class="prog-section-title">Assistant Course Coordinators</h2>
                <p class="prog-section-note">Add one or more assistant coordinators with roles</p>
            </div>
        </div>

            <div id="assistant-coordinators-container">
                @if(!empty($assistantCoordinators) && is_array($assistantCoordinators))
                @foreach($assistantCoordinators as $index => $coordinator)
                <div class="assistant-coordinator-row row g-3 align-items-end mb-3" data-index="{{ $index }}">
                    <div class="col-md-5 col-lg-5">
                        <x-select
                            name="assistantcoursecoordinator[]"
                            id="assistant_coordinator_{{ $index }}"
                            label="Assistant Coordinator"
                            placeholder="John Doe"
                            formLabelClass="prog-field-label programme-field-label"
                            formSelectClass="searchable-dropdown prog-control programme-form-control"
                            :options="$facultyList"
                            value="{{ old('assistantcoursecoordinator.'.$index, $coordinator) }}"
                            required="true" />
                    </div>
                    <div class="col-md-5 col-lg-5">
                        <x-select
                            name="assistant_coordinator_role[]"
                            label="Role"
                            placeholder="Memo"
                            formLabelClass="prog-field-label programme-field-label"
                            formSelectClass="searchable-dropdown prog-control programme-form-control"
                            :options="$roleOptions"
                            value="{{ old('assistant_coordinator_role.'.$index, $assistantRoles[$index] ?? '') }}" />
                    </div>
                    <div class="col-md-2 col-lg-2 d-flex align-items-end justify-content-end gap-2 programme-coord-actions mb-1">
                        <button type="button" class="btn programme-coord-btn programme-coord-btn--remove remove-coordinator" title="Remove assistant coordinator" aria-label="Remove assistant coordinator">
                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                        </button>
                        @if($loop->last)
                        <button type="button" class="btn programme-coord-btn programme-coord-btn--add" id="add-coordinator" aria-label="Add another assistant coordinator" title="Add assistant coordinator">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        </button>
                        @endif
                    </div>
                </div>
                @endforeach
                @else
                <div class="assistant-coordinator-row row g-3 align-items-end mb-3" data-index="0">
                    <div class="col-md-5 col-lg-5">
                        <x-select
                            name="assistantcoursecoordinator[]"
                            label="Assistant Coordinator"
                            placeholder="John Doe"
                            formLabelClass="prog-field-label programme-field-label"
                            formSelectClass="searchable-dropdown prog-control programme-form-control"
                            :options="$facultyList"
                            required="true" />
                    </div>
                    <div class="col-md-5 col-lg-5">
                        <x-select
                            name="assistant_coordinator_role[]"
                            label="Role"
                            placeholder="Memo"
                            formLabelClass="prog-field-label programme-field-label"
                            formSelectClass="searchable-dropdown prog-control programme-form-control"
                            :options="$roleOptions" />
                    </div>
                    <div class="col-md-2 col-lg-2 d-flex align-items-end justify-content-end gap-2 programme-coord-actions mb-1">
                        <button type="button" class="btn programme-coord-btn programme-coord-btn--remove remove-coordinator" title="Remove assistant coordinator" aria-label="Remove assistant coordinator">
                            <i class="bi bi-dash-lg" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="btn programme-coord-btn programme-coord-btn--add" id="add-coordinator" aria-label="Add another assistant coordinator" title="Add assistant coordinator">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                @endif
            </div>
    </div>

    <div class="prog-form-footer programme-form-actions">
        <a href="{{ route('programme.index') }}" class="btn prog-btn-cancel programme-btn-cancel">Cancel</a>
        <button class="btn prog-btn-submit programme-btn-submit" type="submit">
            {{ $isEdit ? 'Update Course' : 'Create Course' }}
        </button>
    </div>
</form>
