@extends('admin.layouts.master')

@section('title', 'Class Session Master')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Class Session Master" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($classSessionMaster) ? 'Edit Class Session' : 'Create Class Session' }}
            </h4>
            <hr>
            <form action="{{ route('master.class.session.store') }}" method="POST" id="classSessionForm">
                @csrf
                @if(!empty($classSessionMaster)) 
                    <input type="hidden" name="id" value="{{ encrypt($classSessionMaster->pk) }}">
                @endif
                <div class="row">
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <x-input 
                                name="shift_name" 
                                label="Shift Name :" 
                                placeholder="Shift Name" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('shift_name', $classSessionMaster->shift_name ?? '') }}"
                                />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <x-input 
                                type="time"
                                name="start_time" 
                                label="Start Time :" 
                                placeholder="Start Time" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('start_time', $classSessionMaster->start_time ?? '') }}"
                                />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <x-input 
                                type="time"
                                name="end_time" 
                                label="End Time :" 
                                placeholder="End Time" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('end_time', $classSessionMaster->end_time ?? '') }}"
                                />
                        </div>
                    </div>
                </div>
                <hr>
                <div class="mb-3 text-end gap-3">
                    <button class="btn btn-primary" type="submit" id="saveClassSessionForm">
                        {{ !empty($classSessionMaster) ? 'Update' : 'Save' }}
                    </button>
                    <a href="{{ route('master.class.session.index') }}" class="btn btn-secondary">
                        Back
                    </a>
                </div>
            </form>
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


@endsection