@extends('admin.layouts.master')

@section('title', 'Employee Type Master')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Employee Type Master" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($employeeTypeMaster) ? 'Edit Employee Type' : 'Create Employee Type' }}
            </h4>
            <hr>
            <form action="{{ route('master.employee.type.store') }}" method="POST" id="employeeTypeForm">
                @csrf
                @if(!empty($employeeTypeMaster))
                    <input type="hidden" name="pk" value="{{ encrypt($employeeTypeMaster->pk) }}">
                @endif
                <div class="row">

                    <div class="col-md-4">
                        <div class="mb-3">
                            <x-input
                                name="employee_type_name"
                                label="Name :"
                                placeholder="Name"
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('employee_type_name', $employeeTypeMaster->category_type_name ?? '') }}"
                                />
                        </div>
                    </div>

                </div>
                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit" id="saveClassSessionForm">
                        <i class="material-icons menu-icon">save</i>
                        {{ !empty($employeeTypeMaster) ? 'Update' : 'Save' }}
                    </button>
                    <a href="{{ route('master.employee.type.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
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
