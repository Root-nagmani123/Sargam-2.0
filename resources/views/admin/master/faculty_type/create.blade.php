@extends('admin.layouts.master')

@section('title', 'Faculty Type')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Faculty Type" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($facultyType) ? 'Edit Faculty Type' : 'Create Faculty Type' }}
            </h4>
            <hr>
            <form action="{{ route('master.faculty.type.master.store') }}" method="POST" id="facultyForm">
                @csrf
                @if(!empty($facultyType)) 
                    <input type="hidden" name="pk" value="{{ encrypt($facultyType->pk) }}">
                @endif
                <div class="row">
                    
                    <div class="col-md-12">
                        <div class="mb-3">
                            <x-input 
                                name="faculty_type_name" 
                                label="Name :" 
                                placeholder="Name" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('faculty_type_name', $facultyType->faculty_type_name ?? '') }}"
                                />
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="mb-3">
                            <x-input 
                                name="shot_faculty_type_name" 
                                label="Short Name :" 
                                placeholder="Short Name" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('shot_faculty_type_name', $facultyType->shot_faculty_type_name ?? '') }}"
                                />
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit" id="saveFacultyForm">
                        <i class="material-icons menu-icon">save</i>
                        {{ !empty($facultyType) ? 'Update' : 'Save' }}
                    </button>
                    <a href="{{ route('master.faculty.type.master.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
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