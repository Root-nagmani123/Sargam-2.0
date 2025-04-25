@extends('admin.layouts.master')

@section('title', 'Faculty')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Faculty Expertise" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">Create Faculty Expertise</h4>
            <hr>
            <form action="{{ route('master.faculty.expertise.store') }}" method="POST" id="facultyForm">
                @csrf
                @if(!empty($expertise)) 
                    <input type="hidden" name="id" value="{{ encrypt($expertise->pk) }}">
                @endif
                <div class="row">
                    
                    <div class="col-md-12">
                        <div class="mb-3">
                            <x-input 
                                name="expertise_name" 
                                label="Expertise Name :" 
                                placeholder="Expertise Name" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('expertise_name', $expertise->expertise_name ?? '') }}"
                                />
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit" id="saveFacultyForm">
                        <i class="material-icons menu-icon">save</i>
                        Save
                    </button>
                    <a href="{{ route('faculty.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
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