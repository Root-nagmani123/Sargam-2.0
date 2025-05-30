@extends('admin.layouts.master')

@section('title', 'Designation Master')
    
@section('content')

<div class="container-fluid">
    <x-breadcrum title="Designation Master" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($designationMaster) ? 'Edit Designation' : 'Create Designation' }}
            </h4>
            <hr>
            <form action="{{ route('master.designation.store') }}" method="POST" id="designationForm">
                @csrf
                @if(!empty($designationMaster)) 
                    <input type="hidden" name="pk" value="{{ encrypt($designationMaster->pk) }}">
                @endif
                <div class="row">
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <x-input
                                name="designation_name"
                                label="Name :" 
                                placeholder="Name" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('designation_name', $designationMaster->designation_name ?? '') }}"
                                />
                        </div>
                    </div>
                    
                </div>
                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit" id="saveClassSessionForm">
                        <i class="material-icons menu-icon">save</i>
                        {{ !empty($designationMaster) ? 'Update' : 'Save' }}
                    </button>
                    <a href="{{ route('master.designation.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
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