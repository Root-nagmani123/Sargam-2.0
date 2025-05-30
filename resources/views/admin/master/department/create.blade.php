@extends('admin.layouts.master')

@section('title', 'Department Master')
    
@section('content')

<div class="container-fluid">
    <x-breadcrum title="Department Master" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($departmentMaster) ? 'Edit Department' : 'Create Department' }}
            </h4>
            <hr>
            <form action="{{ route('master.department.master.store') }}" method="POST" id="departmentForm">
                @csrf
                @if(!empty($departmentMaster)) 
                    <input type="hidden" name="pk" value="{{ encrypt($departmentMaster->pk) }}">
                @endif
                <div class="row">
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <x-input
                                name="department_name"
                                label="Name :" 
                                placeholder="Name" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('department_name', $departmentMaster->department_name ?? '') }}"
                                />
                        </div>
                    </div>
                    
                </div>
                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit" id="saveClassSessionForm">
                        <i class="material-icons menu-icon">save</i>
                        {{ !empty($departmentMaster) ? 'Update' : 'Save' }}
                    </button>
                    <a href="{{ route('master.class.session.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
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