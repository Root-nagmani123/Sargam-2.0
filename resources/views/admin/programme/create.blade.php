@extends('admin.layouts.master')

@section('title', 'Programme - Sargam | Lal Bahadur')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Programme" />
    <x-session_message />

    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">Create Programme</h4>
            <hr>
            <form action="{{ route('programme.store') }}" method="POST">
                @csrf
                
                @if(!empty($courseMasterObj) && $courseMasterObj->pk)
                    <input type="hidden" name="course_id" value="{{ encrypt($courseMasterObj->pk) }}">
                @endif


                <div class="row">
                    <div class="row" id="course_fields">
                        <div class="col-md-6">
                            <x-input 
                                name="coursename" 
                                label="Course Name" 
                                placeholder="Course Name" 
                                formLabelClass="form-label"
                                value="{{ $courseMasterObj->course_name ?? '' }}"
                                required="true"
                                />
                        </div>
                        <div class="col-md-6">
                            <x-input 
                                name="courseshortname" 
                                label="Course Short Name" 
                                placeholder="Course Short Name" 
                                value="{{ $courseMasterObj->couse_short_name ?? '' }}"
                                formLabelClass="form-label" />
                        </div>
                        <div class="col-md-6 mt-4">
                            <x-input 
                                type="month" 
                                name="courseyear" 
                                label="Course Year" 
                                placeholder="Course Year" 
                                value="{{ $courseMasterObj->course_year ?? '' }}"
                                formLabelClass="form-label" />
                        </div>

                        <div class="col-md-6 mt-4">
                            <x-input 
                                type="date" 
                                name="startdate" 
                                label="Start Date" 
                                placeholder="Start Date" 
                                value="{{ $courseMasterObj->start_year ?? '' }}"
                                formLabelClass="form-label" />
                        </div>
                        
                        <div class="col-md-6 mt-4">
                            <x-input 
                                type="date" 
                                name="enddate" 
                                label="End Date" 
                                placeholder="End Date" 
                                value="{{ $courseMasterObj->end_date ?? '' }}"
                                formLabelClass="form-label" />
                        </div>

                        <div class="col-md-6 mt-4">

                            <x-select 
                                name="coursecoordinator" 
                                label="Course Coordinator" 
                                placeholder="Course Coordinator" 
                                formLabelClass="form-label" 
                                value="{{ $coordinator_name ?? '' }}"
                                :options="$deputationEmployeeList" />

                        </div>
                        <div class="col-md-6 mt-4">

                            <x-select 
                                name="assistantcoursecoordinator[]" 
                                label="Assistant Course Coordinator" 
                                placeholder="Assistant Course Coordinator" 
                                formLabelClass="form-label" 
                                :options="$deputationEmployeeList" 
                                :value="$assistant_coordinator_name ?? []"
                                multiple="true" />
                            
                        </div>

                    </div>
                </div>
                <hr>
                <div class="mb-3 mt-4">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                    <i class="material-icons menu-icon">send</i>
                        Submit
                    </button>
                    <a href="{{ route('programme.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
                        <i class="material-icons menu-icon">arrow_back</i>
                        Back
                    </a>
                </div>
            </form>
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection