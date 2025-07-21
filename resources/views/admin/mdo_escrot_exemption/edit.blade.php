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

            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="alert alert-info d-flex align-items-center" role="alert">
                        <i class="material-icons me-2">person</i>
                        <div>
                            <strong>Student Name:</strong>
                            <span class="ms-2 fs-5">{{ $mdoDutyType->studentMaster->display_name ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('mdo-escrot-exemption.update') }}" method="POST" id="mdoDutyTypeForm">
                @csrf
                @if(!empty($mdoDutyType))
                <input type="hidden" name="pk" value="{{ encrypt($mdoDutyType->pk) }}">
                @endif
                <div class="row">

                    

                    <div class="col-md-3">
                        <div class="mb-3">

                            <x-select name="mdo_duty_type_master_pk" label="Duty Type :" formLabelClass="form-label"
                                formSelectClass="select2 "
                                value="{{ old('mdo_duty_type_master_pk', $mdoDutyType->mdo_duty_type_master_pk ?? '') }}"
                                :options="$MDODutyTypeMaster" labelRequired="true" />
                        </div>

                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <x-input type="date" name="mdo_date" label="Select Date & Time :"
                                placeholder="Select Date & Time : " formLabelClass="form-label" 
                                value="{{ old('mdo_date', format_date($mdoDutyType->mdo_date, 'Y-m-d') ?? '') }}" labelRequired="true"/>
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
                </div>
                <hr>
                
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                        <i class="material-icons menu-icon">save</i>
                        Update
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