@extends('admin.layouts.master')

@section('title', 'Hostel Floor')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Hostel Floor" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($hostelFloorMaster) ? 'Edit Hostel Floor' : 'Create Hostel Floor' }}
            </h4>
            <hr>
            <form action="{{ route('master.hostel.floor.store') }}" method="POST" id="hostelFloorForm">
                @csrf
                @if(!empty($hostelFloorMaster)) 
                    <input type="hidden" name="pk" value="{{ encrypt($hostelFloorMaster->pk) }}">
                @endif
                <div class="row">
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <x-input
                                name="floor_name"
                                label="Name :" 
                                placeholder="Name" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('floor_name', $hostelFloorMaster->floor_name ?? '') }}"
                                />
                        </div>
                    </div>
                    
                </div>
                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit" id="saveClassSessionForm">
                        <i class="material-icons menu-icon">save</i>
                        {{ !empty($hostelFloorMaster) ? 'Update' : 'Save' }}
                    </button>
                    <a href="{{ route('master.hostel.floor.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
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