@extends('admin.layouts.master')

@section('title', 'Programme - Sargam | Lal Bahadur')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
    <style>
        .programme-create-page .programme-create-card {
            border-radius: var(--bs-border-radius-xl);
        }

        .programme-create-page .programme-create-choices .choices {
            margin-bottom: 0;
            position: relative;
            z-index: 2;
        }

        .programme-create-page .programme-create-choices .choices__inner.form-select {
            background-color: var(--bs-body-bg);
            border: var(--bs-border-width) solid var(--bs-border-color);
            min-height: calc(1.5em + 0.75rem + var(--bs-border-width) * 2);
            padding-top: 0.375rem;
            padding-bottom: 0.375rem;
            background-image: none !important;
            padding-inline-end: 2.25rem;
            border-radius: var(--bs-border-radius-lg);
        }

        .programme-create-page .programme-create-choices .choices.is-focused .choices__inner.form-select,
        .programme-create-page .programme-create-choices .choices.is-open .choices__inner.form-select {
            border-color: var(--bs-focus-border-color);
            outline: 0;
            box-shadow: 0 0 0 0.25rem rgba(var(--bs-focus-ring-rgb), 0.25);
        }

        .programme-create-page .programme-create-choices .choices__list--dropdown.dropdown-menu,
        .programme-create-page .programme-create-choices .choices__list[aria-expanded].dropdown-menu {
            border: var(--bs-border-width) solid var(--bs-border-color);
            z-index: 1080;
        }

        .programme-create-page .programme-create-choices .choices.is-open {
            z-index: 1081;
        }

        .programme-create-page .assistant-coordinator-row {
            background: var(--bs-body-bg);
            border: 1px solid var(--bs-border-color-translucent);
            border-radius: var(--bs-border-radius-lg);
            padding: 1rem;
        }

        .programme-create-page .assistant-coordinator-row .btn-coordinator-icon {
            width: 2.25rem;
            height: 2.25rem;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--bs-border-radius);
        }

        .programme-create-page .programme-form-footer {
            gap: 0.75rem;
        }
    </style>
@endpush

@section('setup_content')
    @php
        $isEdit = !empty($courseMasterObj) && $courseMasterObj->pk;
        $pageTitle = $isEdit ? 'Edit Course' : 'Create Course';
        $submitLabel = $isEdit ? 'Save Course' : 'Create Course';
        $courseYearValue = old('courseyear', $courseMasterObj->course_year ?? date('Y'));
    @endphp

    <div class="container-fluid programme-create-page">
        <x-breadcrum :title="$pageTitle" :items="[
            ['label' => 'Home', 'url' => url('/')],
            'Academic',
            ['label' => 'Course Master', 'url' => route('programme.index')],
            $pageTitle
        ]"></x-breadcrum>

        <form action="{{ route('programme.store') }}" method="POST" class="programme-create-choices">
            @csrf

            @if ($isEdit)
                <input type="hidden" name="course_id" value="{{ encrypt($courseMasterObj->pk) }}">
            @endif

            {{-- Basic Details --}}
            <div class="card border rounded-3 shadow-sm programme-create-card mb-4">
                <div class="card-body p-4 p-lg-5">
                    <h2 class="h5 fw-bold text-dark mb-4">Basic Details</h2>

                    <div class="row g-4" id="course_fields">
                        <div class="col-md-6">
                            <x-input name="courseshortname" label="Course Short Name" placeholder="eg. IAS 01"
                                formLabelClass="form-label fw-semibold small text-body-secondary"
                                value="{{ $courseMasterObj->couse_short_name ?? '' }}" required="true"
                                labelRequired="true" formInputClass="rounded-3 shadow-sm" />
                        </div>
                        <div class="col-md-6">
                            <x-input name="coursename" label="Course Name" placeholder="eg. IAS Training Course"
                                formLabelClass="form-label fw-semibold small text-body-secondary"
                                value="{{ $courseMasterObj->course_name ?? '' }}" required="true" labelRequired="true"
                                formInputClass="rounded-3 shadow-sm" />
                        </div>

                        <div class="col-12">
                            <label for="courseyear" class="form-label fw-semibold small text-body-secondary">Course Year
                                <span class="text-danger">*</span></label>
                            <select name="courseyear" id="courseyear"
                                class="form-select rounded-3 shadow-sm @error('courseyear') is-invalid @enderror" required>
                                <option value="">Select the year</option>
                                @for ($y = 2099; $y >= 1900; $y--)
                                    <option value="{{ $y }}" @selected((string) $courseYearValue === (string) $y)>{{ $y }}
                                    </option>
                                @endfor
                            </select>
                            @error('courseyear')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="startdate" class="form-label fw-semibold small text-body-secondary">Start Date
                                <span class="text-danger">*</span></label>
                            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                <input type="date" name="startdate" id="startdate"
                                    class="form-control border-end-0 @error('startdate') is-invalid @enderror"
                                    value="{{ old('startdate', $courseMasterObj->start_year ?? '') }}" required>
                                <span class="input-group-text bg-body text-body-secondary border-start-0 rounded-end">
                                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                                </span>
                            </div>
                            @error('startdate')
                                <span class="text-danger small d-block mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="enddate" class="form-label fw-semibold small text-body-secondary">End Date
                                <span class="text-danger">*</span></label>
                            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                <input type="date" name="enddate" id="enddate"
                                    class="form-control border-end-0 @error('enddate') is-invalid @enderror"
                                    value="{{ old('enddate', $courseMasterObj->end_date ?? '') }}" required>
                                <span class="input-group-text bg-body text-body-secondary border-start-0 rounded-end">
                                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                                </span>
                            </div>
                            @error('enddate')
                                <span class="text-danger small d-block mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <x-select name="coursecoordinator" label="Course Coordinator"
                                placeholder="Select the Coordinator"
                                formLabelClass="form-label fw-semibold small text-body-secondary"
                                formSelectClass="searchable-dropdown rounded-3 shadow-sm" value="{{ $coordinator_name ?? '' }}"
                                :options="$facultyList" required="true" labelRequired="true" />
                        </div>
                        <div class="col-md-6">
                            <x-select name="supportingsection" label="Supporting Section"
                                placeholder="Select the Supporting section"
                                formLabelClass="form-label fw-semibold small text-body-secondary"
                                formSelectClass="searchable-dropdown rounded-3 shadow-sm"
                                value="{{ $selectedSupportingSection ?? '' }}" :options="$supportingSectionList" />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Assistant Course Coordinators --}}
            <div class="card border rounded-3 shadow-sm programme-create-card mb-4">
                <div class="card-body p-4 p-lg-5">
                    <h2 class="h5 fw-bold text-dark mb-1">Assistant Course Coordinators</h2>
                    <p class="text-body-secondary small mb-3">Add one or more assistant coordinators with roles</p>
                    <hr class="border-top opacity-50 my-3">

                    <div id="assistant-coordinators-container">
                        @if (!empty($assistant_coordinator_name) && is_array($assistant_coordinator_name))
                            @foreach ($assistant_coordinator_name as $index => $coordinator)
                                <div class="assistant-coordinator-row row g-3 mb-4 align-items-end"
                                    data-index="{{ $index }}">
                                    <div class="col-md-5">
                                        <x-select name="assistantcoursecoordinator[]"
                                            id="assistant_coordinator_{{ $index }}" label="Assistant Coordinator"
                                            placeholder="Select assistant coordinator"
                                            formLabelClass="form-label fw-semibold small text-body-secondary"
                                            formSelectClass="searchable-dropdown rounded-3 shadow-sm" :options="$facultyList"
                                            value="{{ $coordinator }}" required="true" labelRequired="true" />
                                    </div>
                                    <div class="col-md-5">
                                        <x-select name="assistant_coordinator_role[]" label="Role"
                                            placeholder="Select Role"
                                            formLabelClass="form-label fw-semibold small text-body-secondary"
                                            formSelectClass="searchable-dropdown rounded-3 shadow-sm" :options="$roleOptions"
                                            value="{{ $assistant_coordinator_roles[$index] ?? '' }}" />
                                    </div>
                                    <div class="col-md-2 d-flex justify-content-end gap-2 pb-1">
                                        <button type="button"
                                            class="btn btn-coordinator-icon bg-danger-subtle text-danger border-0 remove-coordinator"
                                            title="Remove row" aria-label="Remove assistant coordinator row">
                                            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">delete</i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-coordinator-icon bg-primary-subtle text-primary border-0 add-coordinator-inline {{ $loop->last ? '' : 'd-none' }}"
                                            title="Add row" aria-label="Add assistant coordinator row">
                                            <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="assistant-coordinator-row row g-3 mb-4 align-items-end" data-index="0">
                                <div class="col-md-5">
                                    <x-select name="assistantcoursecoordinator[]" label="Assistant Coordinator"
                                        placeholder="Select assistant coordinator"
                                        formLabelClass="form-label fw-semibold small text-body-secondary"
                                        formSelectClass="searchable-dropdown rounded-3 shadow-sm" :options="$facultyList"
                                        required="true" labelRequired="true" />
                                </div>
                                <div class="col-md-5">
                                    <x-select name="assistant_coordinator_role[]" label="Role" placeholder="Select Role"
                                        formLabelClass="form-label fw-semibold small text-body-secondary"
                                        formSelectClass="searchable-dropdown rounded-3 shadow-sm" :options="$roleOptions" />
                                </div>
                                <div class="col-md-2 d-flex justify-content-end gap-2 pb-1">
                                    <button type="button"
                                        class="btn btn-coordinator-icon bg-danger-subtle text-danger border-0 remove-coordinator"
                                        title="Remove row" aria-label="Remove assistant coordinator row">
                                        <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">delete</i>
                                    </button>
                                    <button type="button"
                                        class="btn btn-coordinator-icon bg-primary-subtle text-primary border-0 add-coordinator-inline"
                                        title="Add row" aria-label="Add assistant coordinator row">
                                        <i class="material-icons material-symbols-rounded fs-6 lh-1" aria-hidden="true">add</i>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-end programme-form-footer pb-4">
                <a href="{{ route('programme.index') }}" class="btn btn-outline-primary rounded-3 px-4 fw-semibold">
                    Cancel
                </a>
                <button class="btn btn-primary rounded-3 px-4 fw-semibold shadow-sm" type="submit">
                    {{ $submitLabel }}
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    <script src="{{ asset('js/programme.js') }}"></script>
@endpush
