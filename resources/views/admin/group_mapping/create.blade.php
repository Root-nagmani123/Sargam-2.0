@extends('admin.layouts.master')

@section('title', 'Course Group Type')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Course Group Type" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($courseGroupTypeMaster) ? 'Edit Course Group Type' : 'Add Course Group Type' }}
            </h4>
            <hr>
            <form action="{{ route('group.mapping.store') }}" method="POST" id="classSessionForm">
                @csrf
                @if(!empty($courseGroupTypeMaster)) 
                    <input type="hidden" name="id" value="{{ encrypt($courseGroupTypeMaster->pk) }}">
                @endif
                <div class="row">
                    
                    

                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-input 
                                name="course_name" 
                                label="Course name :" 
                                placeholder="Course name" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('course_name', $courseGroupTypeMaster->course_name ?? '') }}"
                                />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-input 
                                name="type_name" 
                                label="Group Type :" 
                                placeholder="Group Type" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('type_name', $courseGroupTypeMaster->type_name ?? '') }}"
                                />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-input 
                                name="group_name" 
                                label="Group Name :" 
                                placeholder="Group Name" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('group_name', $courseGroupTypeMaster->type_name ?? '') }}"
                                />
                        </div>
                    </div>
                    
                </div>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit" id="saveClassSessionForm">
                        <i class="material-icons menu-icon">save</i>
                        Save
                    </button>
                    <a href="{{ route('master.course.group.type.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
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