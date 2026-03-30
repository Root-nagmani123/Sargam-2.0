@extends('admin.layouts.master')

@section('title', 'Programme - Sargam | Lal Bahadur')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<style>
    .programme-create-card {
        border: 0;
        border-left: 4px solid #004a93;
        box-shadow: 0 0.5rem 1rem rgba(0, 74, 147, 0.08);
        border-radius: 0.75rem;
    }

    .programme-form-section {
        border: 1px solid var(--bs-border-color);
        border-radius: 0.75rem;
        background: var(--bs-tertiary-bg);
        overflow: visible;
    }

    .assistant-coordinator-row {
        background: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: 0.75rem;
        padding: 0.75rem;
        overflow: visible;
    }

    .programme-create-choices .form-label {
        font-weight: 600;
        color: var(--bs-emphasis-color);
    }

    .programme-create-choices .choices {
        margin-bottom: 0;
        position: relative;
        z-index: 2;
    }

    .programme-create-choices .choices__inner.form-select {
        background-color: var(--bs-body-bg);
        border: var(--bs-border-width) solid var(--bs-border-color);
        min-height: calc(1.5em + 0.75rem + var(--bs-border-width) * 2);
        padding-top: 0.375rem;
        padding-bottom: 0.375rem;
        background-image: none !important;
        padding-inline-end: 2.25rem;
    }

    .programme-create-choices .choices.is-focused .choices__inner.form-select,
    .programme-create-choices .choices.is-open .choices__inner.form-select {
        border-color: var(--bs-focus-border-color);
        outline: 0;
        box-shadow: 0 0 0 0.25rem rgba(var(--bs-focus-ring-rgb), 0.25);
    }

    .programme-create-choices .choices__list--dropdown.dropdown-menu,
    .programme-create-choices .choices__list[aria-expanded].dropdown-menu {
        border: var(--bs-border-width) solid var(--bs-border-color);
        z-index: 1080;
    }

    .programme-create-choices .choices.is-open {
        z-index: 1081;
    }

    .programme-form-actions {
        position: sticky;
        bottom: 0;
        z-index: 5;
        background: color-mix(in srgb, var(--bs-body-bg) 90%, transparent);
        backdrop-filter: blur(2px);
    }
</style>
@endpush

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Create Course"></x-breadcrum>
    <!-- start Vertical Steps Example -->
    <div class="card programme-create-card">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                <div>
                    <h4 class="card-title mb-1">
                        {{ !empty($courseMasterObj) && $courseMasterObj->pk ? 'Edit Course' : 'Create Course' }}
                    </h4>
                    <p class="text-muted mb-0 small">Enter course details and coordinator mappings.</p>
                </div>
                <span class="badge text-bg-primary-subtle text-primary-emphasis px-3 py-2 rounded-pill">
                    <i class="bi bi-mortarboard-fill me-1"></i> Programme Setup
                </span>
            </div>

            <form action="{{ route('programme.store') }}" method="POST" class="programme-create-choices">
                @csrf

                @if(!empty($courseMasterObj) && $courseMasterObj->pk)
                <input type="hidden" name="course_id" value="{{ encrypt($courseMasterObj->pk) }}">
                @endif


                <div class="programme-form-section p-3 p-lg-4">
                    <div class="row g-4" id="course_fields">
                        <div class="col-md-6">
                            <x-input
                                name="coursename"
                                label="Course Name"
                                placeholder="Course Name"
                                formLabelClass="form-label"
                                value="{{ $courseMasterObj->course_name ?? '' }}"
                                required="true" />
                        </div>
                        <div class="col-md-6">
                            <x-input
                                name="courseshortname"
                                label="Course Short Name"
                                placeholder="Course Short Name"
                                value="{{ $courseMasterObj->couse_short_name ?? '' }}"
                                formLabelClass="form-label" />
                        </div>
                        <div class="col-md-6">
                            <x-input
                                type="text"
                                name="courseyear"
                                label="Course Year"
                                placeholder="Course Year"
                                value="{{ $courseMasterObj->course_year ?? date('Y') }}"
                                formLabelClass="form-label"
                                min="1900"
                                max="2100"
                                required="true" />
                        </div>

                        <div class="col-md-6">
                            <x-input
                                type="date"
                                name="startdate"
                                label="Start Date"
                                placeholder="Start Date"
                                value="{{ $courseMasterObj->start_year ?? '' }}"
                                formLabelClass="form-label" />
                        </div>

                        <div class="col-md-6">
                            <x-input
                                type="date"
                                name="enddate"
                                label="End Date"
                                placeholder="End Date"
                                value="{{ $courseMasterObj->end_date ?? '' }}"
                                formLabelClass="form-label" />
                        </div>

                        <div class="col-md-6">

                            <x-select
                                name="coursecoordinator"
                                label="Course Coordinator"
                                placeholder="Course Coordinator"
                                formLabelClass="form-label"
                                formSelectClass="searchable-dropdown"
                                value="{{ $coordinator_name ?? '' }}"
                                :options="$facultyList" />

                        </div>
                        <div class="col-md-6">
                            <x-select
                                name="supportingsection"
                                label="Supporting Section"
                                placeholder="Select Supporting Section"
                                formLabelClass="form-label"
                                formSelectClass="searchable-dropdown"
                                value="{{ $selectedSupportingSection ?? '' }}"
                                :options="$supportingSectionList" />
                        </div>
                        <div class="col-md-12">
                            <div class="d-flex flex-wrap align-items-center justify-content-between mb-2 gap-2">
                                <label class="form-label mb-0">Assistant Course Coordinators</label>
                                <span class="text-muted small">Assign one or more assistant coordinators with roles.</span>
                            </div>
                            <div id="assistant-coordinators-container">
                                @if(!empty($assistant_coordinator_name) && is_array($assistant_coordinator_name))
                                @foreach($assistant_coordinator_name as $index => $coordinator)
                                <div class="assistant-coordinator-row row g-3 mb-3" data-index="{{ $index }}">
                                    <div class="col-md-6">
                                        <x-select
                                            name="assistantcoursecoordinator[]"
                                            id="assistant_coordinator_{{ $index }}"
                                            label="Assistant Coordinator"
                                            placeholder="Assistant Coordinator"
                                            formLabelClass="form-label"
                                            formSelectClass="searchable-dropdown"
                                            :options="$facultyList"
                                            value="{{ $coordinator }}"
                                            required="true" />
                                    </div>
                                    <div class="col-md-5">
                                        <x-select
                                            name="assistant_coordinator_role[]"
                                            label="Role"
                                            placeholder="Select Role"
                                            formLabelClass="form-label"
                                            formSelectClass="searchable-dropdown"
                                            :options="$roleOptions"
                                            value="{{ $assistant_coordinator_roles[$index] ?? '' }}" />
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end justify-content-end">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-coordinator" style="margin-bottom: 0;" title="Remove assistant coordinator">
                                            <i class="material-icons menu-icon">delete</i>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                                @else
                                <div class="assistant-coordinator-row row g-3 mb-3" data-index="0">
                                    <div class="col-md-6">
                                        <x-select
                                            name="assistantcoursecoordinator[]"
                                            label="Assistant Coordinator"
                                            placeholder="Assistant Coordinator"
                                            formLabelClass="form-label"
                                            formSelectClass="searchable-dropdown"
                                            :options="$facultyList"
                                            required="true" />
                                    </div>
                                    <div class="col-md-5">
                                        <x-select
                                            name="assistant_coordinator_role[]"
                                            label="Role"
                                            placeholder="Select Role"
                                            formLabelClass="form-label"
                                            formSelectClass="searchable-dropdown"
                                            :options="$roleOptions" />
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end justify-content-end">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-coordinator" style="margin-bottom: 0;" title="Remove assistant coordinator">
                                            <i class="material-icons menu-icon">delete</i>
                                        </button>
                                    </div>
                                </div>
                                @endif
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2 shadow-sm px-3" id="add-coordinator" aria-label="Add another assistant coordinator">
                                    <i class="bi bi-person-plus-fill fs-6"></i>
                                    <span>Add Assistant Coordinator</span>
                                </button>
                            </div>

                        </div>

                    </div>
                </div>
                <div class="programme-form-actions d-flex justify-content-end gap-2 mt-4 pt-3 border-top rounded-bottom-3">
                    <a href="{{ route('programme.index') }}" class="btn btn-outline-secondary btn-sm px-3">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    <button class="btn btn-primary btn-sm px-3" type="submit">
                        <i class="bi bi-check2-circle me-1"></i> Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>
@endsection



@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script src="{{ asset('js/programme.js') }}"></script>
@endpush