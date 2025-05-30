@extends('admin.layouts.master')
@section('css')
{{--
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.8.1/themes/prism.min.css" rel="stylesheet" /> --}}
<link rel="stylesheet" href="{{asset('admin_assets/css/dual-listbox.css')}}">
@endsection
@section('title', 'MDO Escrot Exemption')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="MDO Escrot Exemption" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($mdoDutyType) ? 'Edit MDO Escrot Exemption' : 'Create MDO Escrot Exemption' }}
            </h4>
            <hr>
            <form action="{{ route('mdo-escrot-exemption.store') }}" method="POST" id="mdoDutyTypeForm">
                @csrf
                @if(!empty($mdoDutyType))
                <input type="hidden" name="id" value="{{ encrypt($mdoDutyType->pk) }}">
                @endif
                <div class="row">

                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-select name="course_master_pk" label="Course Name :" formLabelClass="form-label"
                                formSelectClass="select2 course-selected" :options="$courseMaster" labelRequired="true"
                                value="{{ old('course_master_pk', $mdoDutyType->course_master_pk ?? '') }}" />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">

                            <x-select name="mdo_duty_type_master_pk" label="Duty Type :" formLabelClass="form-label"
                                formSelectClass="select2 "
                                value="{{ old('mdo_duty_type_master_pk', $mdoDutyType->mdo_duty_type_master_pk ?? '') }}"
                                :options="$MDODutyTypeMaster" labelRequired="true" />
                        </div>

                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-input type="date" name="mdo_date" label="Select Date & Time :"
                                placeholder="Select Date & Time : " formLabelClass="form-label" 
                                value="{{ old('mdo_date', $mdoDutyType->mdo_date ?? '') }}" labelRequired="true"/>
                        </div>
                    </div>

                    <div class="col-md-3">

                        <x-input type="time" name="Time_from" label="From Time :" placeholder="From Time : "
                            formLabelClass="form-label" labelRequired="true"
                            value="{{ old('Time_from', $mdoDutyType->Time_from ?? '') }}" />

                    </div>
                    <div class="col-md-3">

                        <x-input type="time" name="Time_to" label="To Time :" placeholder="To Time : "
                            formLabelClass="form-label" labelRequired="true"
                            value="{{ old('Time_to', $mdoDutyType->Time_to ?? '') }}" />

                    </div>

                    <div class="col-md-12">
                        <label for="selected_student_list" class="form-label">Select Students</label>
                        <select id="select" class="select1 form-control" name="selected_student_list[]" multiple>

                        </select>
                        @error('selected_student_list')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-12">
                        <label for="textarea" class="form-label">Remarks (If Any) </label>
                        <textarea class="form-control" id="textarea" rows="3" placeholder="Enter remarks..."
                            name="Remark"></textarea>
                        @error('Remark')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <hr>
                {{-- <div class="my-3 gap-2 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit" id="saveFacultyForm">
                        Save
                    </button>
                    <a href="{{ route('mdo-escrot-exemption.index') }}"
                        class="btn btn-secondary ">
                        Back
                    </a>
                </div> --}}

                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                        <i class="material-icons menu-icon">save</i>
                        Save
                    </button>
                    <a href="{{ route('mdo-escrot-exemption.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
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