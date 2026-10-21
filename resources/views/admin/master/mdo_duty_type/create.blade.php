@extends('admin.layouts.master')

@section('title', 'MDO Duty Type')

@section('setup_content')

<div class="container-fluid">
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($mdoDutyType) ? 'Edit MDO Duty Type' : 'Create MDO Duty Type' }}
            </h4>
            <hr>
            <form action="{{ route('master.mdo_duty_type.store') }}" method="POST" id="facultyForm">
                @csrf
                @if(!empty($mdoDutyType)) 
                    <input type="hidden" name="id" value="{{ encrypt($mdoDutyType->pk) }}">
                @endif
                <div class="row">
                    
                    <div class="col-md-12">
                        <div class="mb-3">
                            <x-input 
                                name="mdo_duty_type_name" 
                                label="Name :" 
                                placeholder="Name" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('mdo_duty_type_name', $mdoDutyType->mdo_duty_type_name ?? '') }}"
                                />
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit" id="saveFacultyForm">
                        <i class="material-icons menu-icon">save</i>
                        {{ !empty($mdoDutyType) ? 'Update' : 'Save' }}
                    </button>
                    <a href="{{ route('master.mdo_duty_type.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
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