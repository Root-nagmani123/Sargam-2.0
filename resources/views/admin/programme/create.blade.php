@extends('admin.layouts.master')

@section('title', 'Create Course - Programme - Sargam | Lal Bahadur')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid programme-create-page">
    <x-breadcrum title="Create Course"></x-breadcrum>
    <x-session_message />

            <form action="{{ route('programme.store') }}" method="POST" class="programme-create-choices">
                @csrf

                @if(!empty($courseMasterObj) && $courseMasterObj->pk)
                <input type="hidden" name="course_id" value="{{ encrypt($courseMasterObj->pk) }}">
                @endif

                @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
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
                                :options="$supportingSectionList"
                                labelRequired="true" />
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
                                            value="{{ $coordinator }}" />
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
                                            :options="$facultyList" />
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
