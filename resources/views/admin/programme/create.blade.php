@extends('admin.layouts.master')

@section('title', 'Programme - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Programme" />
    <x-session_message />

    <!-- start Vertical Steps Example -->
    <div class="card border-0 shadow-sm rounded-4" style="border-left: 4px solid #004a93;">
    <div class="card-body p-4">

        <!-- Page Title -->
        <h4 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-journal-text fs-5 text-primary"></i>
            {{ !empty($courseMasterObj) && $courseMasterObj->pk ? 'Edit Programme' : 'Create Programme' }}
        </h4>
        <hr>

        <form action="{{ route('programme.store') }}" method="POST" aria-label="Programme Form">
            @csrf

            @if(!empty($courseMasterObj) && $courseMasterObj->pk)
                <input type="hidden" name="course_id" value="{{ encrypt($courseMasterObj->pk) }}">
            @endif

            <div class="row g-4">

                <!-- Course Name -->
                <div class="col-md-6">
                    <x-input 
                        name="coursename" 
                        label="Course Name"
                        placeholder="Enter course name"
                        formLabelClass="form-label fw-semibold"
                        value="{{ $courseMasterObj->course_name ?? '' }}"
                        required="true"
                    />
                </div>

                <!-- Short Name -->
                <div class="col-md-6">
                    <x-input 
                        name="courseshortname" 
                        label="Course Short Name"
                        placeholder="Enter short name"
                        formLabelClass="form-label fw-semibold"
                        value="{{ $courseMasterObj->couse_short_name ?? '' }}"
                    />
                </div>

                <!-- Course Year -->
                <div class="col-md-6">
                    <x-input 
                        type="text"
                        name="courseyear" 
                        label="Course Year"
                        placeholder="YYYY"
                        formLabelClass="form-label fw-semibold"
                        value="{{ $courseMasterObj->course_year ?? '' }}"
                        min="1900"
                        max="2100"
                        required="true"
                    />
                </div>

                <!-- Start Date -->
                <div class="col-md-6">
                    <x-input 
                        type="date"
                        name="startdate" 
                        label="Start Date"
                        placeholder="Select start date"
                        formLabelClass="form-label fw-semibold"
                        value="{{ $courseMasterObj->start_year ?? '' }}"
                    />
                </div>

                <!-- End Date -->
                <div class="col-md-6">
                    <x-input 
                        type="date"
                        name="enddate" 
                        label="End Date"
                        placeholder="Select end date"
                        formLabelClass="form-label fw-semibold"
                        value="{{ $courseMasterObj->end_date ?? '' }}"
                    />
                </div>

                <!-- Coordinator -->
                <div class="col-md-6">
                    <x-select
                        name="coursecoordinator"
                        label="Course Coordinator"
                        placeholder="Select coordinator"
                        formLabelClass="form-label fw-semibold"
                        :options="$facultyList"
                        value="{{ $coordinator_name ?? '' }}"
                    />
                </div>

                <!-- Assistant Coordinators -->
                <div class="col-md-12 mt-2">
                    <label class="form-label fw-semibold">Assistant Course Coordinators</label>

                    <div id="assistant-coordinators-container">

                        @if(!empty($assistant_coordinator_name) && is_array($assistant_coordinator_name))
                            @foreach($assistant_coordinator_name as $index => $coordinator)
                            <div class="assistant-coordinator-row row g-3 mb-3 p-3 rounded-3 border bg-light"
                                 data-index="{{ $index }}">

                                <div class="col-md-6">
                                    <x-select
                                        name="assistantcoursecoordinator[]"
                                        label="Assistant Coordinator"
                                        placeholder="Select coordinator"
                                        formLabelClass="form-label fw-semibold"
                                        :options="$facultyList"
                                        value="{{ $coordinator }}"
                                        required="true"
                                    />
                                </div>

                                <div class="col-md-5">
                                    <x-select
                                        name="assistant_coordinator_role[]"
                                        label="Role"
                                        placeholder="Select role"
                                        formLabelClass="form-label fw-semibold"
                                        :options="$roleOptions"
                                        value="{{ $assistant_coordinator_roles[$index] ?? '' }}"
                                        required="true"
                                    />
                                </div>

                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" 
                                            class="btn btn-outline-danger w-100 remove-coordinator" 
                                            aria-label="Remove coordinator">
                                        <i class="bi bi-trash fs-5"></i>
                                    </button>
                                </div>

                            </div>
                            @endforeach
                        @else
                            <div class="assistant-coordinator-row row g-3 mb-3 p-3 rounded-3 border bg-light" data-index="0">

                                <div class="col-md-6">
                                    <x-select
                                        name="assistantcoursecoordinator[]"
                                        label="Assistant Coordinator"
                                        placeholder="Select coordinator"
                                        formLabelClass="form-label fw-semibold"
                                        :options="$facultyList"
                                        required="true"
                                    />
                                </div>

                                <div class="col-md-5">
                                    <x-select
                                        name="assistant_coordinator_role[]"
                                        label="Role"
                                        placeholder="Select role"
                                        formLabelClass="form-label fw-semibold"
                                        :options="$roleOptions"
                                        required="true"
                                    />
                                </div>

                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" 
                                            class="btn btn-outline-danger w-100 remove-coordinator" 
                                            aria-label="Remove coordinator">
                                        <i class="bi bi-trash fs-5"></i>
                                    </button>
                                </div>

                            </div>
                        @endif

                    </div>

                    <!-- Add Coordinator Button -->
                    <div class="mt-3">
                        <button type="button" 
                                class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm"
                                id="add-coordinator"
                                aria-label="Add assistant coordinator">
                            <i class="bi bi-person-plus-fill"></i>
                            Add Assistant Coordinator
                        </button>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- Form Buttons -->
            <div class="text-end d-flex justify-content-end gap-2">
                <button class="btn btn-primary px-4" type="submit" aria-label="Submit form">
                    Submit
                </button>
                <a href="{{ route('programme.index') }}" 
                   class="btn btn-secondary px-4" 
                   aria-label="Go back">
                    Back
                </a>
            </div>

        </form>
    </div>
</div>

    <!-- end Vertical Steps Example -->
</div>


@endsection

@push('scripts')
<script src="{{ asset('js/programme.js') }}"></script>
@endpush