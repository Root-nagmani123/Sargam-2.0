@extends('admin.layouts.master')

@section('title', 'Employee Group Master')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Employee Group Master" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($employeeGroupMaster) ? 'Edit Employee Group' : 'Create Employee Group' }}
            </h4>
            <hr>
            <form action="{{ route('master.employee.group.store') }}" method="POST" id="employeeGroupForm">
                @csrf
                @if(!empty($employeeGroupMaster)) 
                    <input type="hidden" name="pk" value="{{ encrypt($employeeGroupMaster->pk) }}">
                @endif
                <div class="row">
                    {{-- @dump($employeeGroupMaster->emp_group_name) --}}
                    <div class="col-md-4">
                        <div class="mb-3">
                            <x-input
                                name="group_name"
                                label="Group Name :" 
                                placeholder="Name" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('group_name', $employeeGroupMaster->emp_group_name) }}"
                                />
                        </div>
                    </div>
                    
                </div>
                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit" id="saveClassSessionForm">
                        <i class="material-icons menu-icon">save</i>
                        {{ !empty($employeeGroupMaster) ? 'Update' : 'Save' }}
                    </button>
                    <a href="{{ route('master.employee.group.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
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