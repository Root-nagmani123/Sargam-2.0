@extends('admin.layouts.master')

@section('title', 'Caste Master')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Caste Master" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($casteCategory) ? 'Edit Caste' : 'Create Caste' }}
            </h4>
            <hr>
            <form action="{{ route('master.caste.category.store') }}" method="POST" id="employeeGroupForm">
                @csrf
                @if(!empty($casteCategory)) 
                    <input type="hidden" name="pk" value="{{ encrypt($casteCategory->pk) }}">
                @endif
                <div class="row">
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-input
                                name="Seat_name"
                                label="Seat Name :" 
                                placeholder="Name" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('Seat_name', $casteCategory->Seat_name ?? '') }}"
                                />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-input
                                name="Seat_name_hindi"
                                label="Seat Name (Hindi) :" 
                                placeholder="Name" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('Seat_name_hindi', $casteCategory->Seat_name_hindi ?? '') }}"
                                />
                        </div>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit" id="saveClassSessionForm">
                        <i class="material-icons menu-icon">save</i>
                        {{ !empty($casteCategory) ? 'Update' : 'Save' }}
                    </button>
                    <a href="{{ route('master.caste.category.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
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