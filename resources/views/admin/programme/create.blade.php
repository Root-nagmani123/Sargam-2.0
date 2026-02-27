@extends('admin.layouts.master')

@section('title', 'Programme - Sargam | Lal Bahadur')

@section('setup_content')
<style>
/* Programme Create - Responsive */
@media (max-width: 991.98px) {
    .programme-create .card-body { padding: 1.25rem; }
    .programme-create .row.g-3.g-md-4 .col-md-6 { margin-bottom: 0; }
}

@media (max-width: 767.98px) {
    .programme-create .container-fluid { padding-left: 0.5rem !important; padding-right: 0.5rem !important; }
    .programme-create .card-body { padding: 1rem; }
    .programme-create .card-title { font-size: 1.125rem; }
    .programme-create .assistant-coordinator-row .col-12:last-child {
        display: flex;
        justify-content: flex-end;
        padding-top: 0.25rem;
    }
    .programme-create .assistant-coordinator-row .col-md-5,
    .programme-create .assistant-coordinator-row .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    .programme-create .assistant-coordinator-row .col-md-1 {
        flex: 0 0 auto;
        max-width: 100%;
    }
    .programme-create .mb-3.mt-4.d-flex { flex-direction: column; }
    .programme-create .mb-3.mt-4 .btn { width: 100%; }
}

@media (max-width: 575.98px) {
    .programme-create .container-fluid { padding-left: 0.375rem !important; padding-right: 0.375rem !important; }
    .programme-create .card-body { padding: 0.75rem; }
    .programme-create .select2-container { width: 100% !important; }
    .programme-create #add-coordinator { width: 100%; justify-content: center; }
    .programme-create .remove-coordinator { min-width: 2.5rem; }
}
</style>
<div class="container-fluid px-2 px-sm-3 px-md-4 py-3 programme-create">
    <x-breadcrum title="Create Course"></x-breadcrum>
    <!-- start Vertical Steps Example -->
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($courseMasterObj) && $courseMasterObj->pk ? 'Edit Course' : 'Create Course' }}
            </h4>
            <hr>
            <form action="{{ route('programme.store') }}" method="POST">
                @csrf
                
                @if(!empty($courseMasterObj) && $courseMasterObj->pk)
                    <input type="hidden" name="course_id" value="{{ encrypt($courseMasterObj->pk) }}">
                @endif


                <div class="row g-3 g-md-4" id="course_fields">
                        <div class="col-12 col-md-6">
                            <x-input 
                                name="coursename" 
                                label="Course Name" 
                                placeholder="Course Name" 
                                formLabelClass="form-label"
                                value="{{ $courseMasterObj->course_name ?? '' }}"
                                required="true"
                                />
                        </div>
                        <div class="col-12 col-md-6">
                            <x-input 
                                name="courseshortname" 
                                label="Course Short Name" 
                                placeholder="Course Short Name" 
                                value="{{ $courseMasterObj->couse_short_name ?? '' }}"
                                formLabelClass="form-label" />
                        </div>
                        <div class="col-12 col-md-6">
                            <x-input 
                                type="text" 
                                name="courseyear" 
                                label="Course Year" 
                                placeholder="Course Year" 
                                value="{{ $courseMasterObj->course_year ?? date('Y') }}"
                                formLabelClass="form-label"
                                min="1900"
                                max="2100" 
                                required="true"/>
                        </div>

                        <div class="col-12 col-md-6">
                            <x-input 
                                type="date" 
                                name="startdate" 
                                label="Start Date" 
                                placeholder="Start Date" 
                                value="{{ $courseMasterObj->start_year ?? '' }}"
                                formLabelClass="form-label" />
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <x-input 
                                type="date" 
                                name="enddate" 
                                label="End Date" 
                                placeholder="End Date" 
                                value="{{ $courseMasterObj->end_date ?? '' }}"
                                formLabelClass="form-label" />
                        </div>

                        <div class="col-12 col-md-6">

                            <x-select 
                                name="coursecoordinator" 
                                label="Course Coordinator" 
                                placeholder="Course Coordinator" 
                                formLabelClass="form-label" 
                                formSelectClass="select2"
                                value="{{ $coordinator_name ?? '' }}"
                                :options="$facultyList" />
                        
                        </div>
                        <div class="col-12 col-md-6">
                            <x-select 
                                name="supportingsection" 
                                label="Supporting Section" 
                                placeholder="Select Supporting Section" 
                                formLabelClass="form-label" 
                                formSelectClass="select2"
                                value="{{ $selectedSupportingSection ?? '' }}"
                                :options="$supportingSectionList" />
                        </div>
                        <div class="col-12">
                            <label class="form-label">Assistant Course Coordinators</label>
                            <div id="assistant-coordinators-container">
                                @if(!empty($assistant_coordinator_name) && is_array($assistant_coordinator_name))
                                    @foreach($assistant_coordinator_name as $index => $coordinator)
                                        <div class="assistant-coordinator-row row g-2 g-md-3 mb-3" data-index="{{ $index }}">
                                            <div class="col-12 col-md-6">
                                                <x-select 
                                                    name="assistantcoursecoordinator[]" 
                                                    id="assistant_coordinator_{{ $index }}"
                                                    label="Assistant Coordinator" 
                                                    placeholder="Assistant Coordinator" 
                                                    formLabelClass="form-label" 
                                                    formSelectClass="select2"
                                                    :options="$facultyList" 
                                                    value="{{ $coordinator }}"
                                                    required="true" />
                                            </div>
                                            <div class="col-12 col-md-5">
                                                <x-select 
                                                    name="assistant_coordinator_role[]" 
                                                    label="Role" 
                                                    placeholder="Select Role" 
                                                    formLabelClass="form-label" 
                                                    formSelectClass="select2"
                                                    :options="$roleOptions" 
                                                    value="{{ $assistant_coordinator_roles[$index] ?? '' }}" />
                                            </div>
                                            <div class="col-12 col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-danger btn-sm remove-coordinator" style="margin-bottom: 0;">
                                                    <i class="material-icons menu-icon">delete</i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="assistant-coordinator-row row g-2 g-md-3 mb-3" data-index="0">
                                        <div class="col-12 col-md-6">
                                            <x-select 
                                                name="assistantcoursecoordinator[]" 
                                                label="Assistant Coordinator" 
                                                placeholder="Assistant Coordinator" 
                                                formLabelClass="form-label" 
                                                formSelectClass="select2"
                                                :options="$facultyList" 
                                                required="true" />
                                        </div>
                                        <div class="col-12 col-md-5">
                                            <x-select 
                                                name="assistant_coordinator_role[]" 
                                                label="Role" 
                                                placeholder="Select Role" 
                                                formLabelClass="form-label" 
                                                formSelectClass="select2"
                                                :options="$roleOptions" />
                                        </div>
                                        <div class="col-12 col-md-1 d-flex align-items-end">
                                            <button type="button" class="btn btn-outline-danger btn-sm remove-coordinator" style="margin-bottom: 0;">
                                                <i class="material-icons menu-icon">delete</i>
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="mt-3">
    <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2 shadow-sm" id="add-coordinator" aria-label="Add another assistant coordinator">
        <i class="bi bi-person-plus-fill fs-6"></i>
        <span>Add Assistant Coordinator</span>
    </button>
</div>

                        </div>
                </div>
                <hr>
                <div class="mb-3 mt-4 d-flex flex-column flex-sm-row justify-content-end gap-2">
                    <button class="btn btn-primary btn-sm" type="submit">
                        Submit
                    </button>
                    <a href="{{ route('programme.index') }}" class="btn btn-secondary btn-sm" role="button">
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