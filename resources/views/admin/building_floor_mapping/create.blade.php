@extends('admin.layouts.master')

@section('title', 'Hostel Building Floor Mapping')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Hostel Building Floor Mapping" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($hostelFloorMapping) ? 'Edit Hostel Building Floor Mapping' : 'Create Hostel Building Floor Mapping' }}
            </h4>
            <hr>
            <form action="{{ route('hostel.building.map.store') }}" method="POST" id="hostelFloorForm">
                @csrf
                @if(!empty($hostelFloorMapping)) 
                    <input type="hidden" name="pk" value="{{ encrypt($hostelFloorMapping->pk) }}">
                @endif
                <div class="row">
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            


                            <x-select 
                                        name="hostelbuilding" 
                                        label="Hostel Building Name:" 
                                        formLabelClass="form-label"
                                        :options="$hostelBuilding"
                                        required="true"
                                        value="{{ !empty($hostelFloorMapping) ? $hostelFloorMapping->hostel_building_master_pk : '' }}"
                                        />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            
                                <x-select 
                                        name="hostel_floor_name" 
                                        label="Hostel Floor Name:" 
                                        formLabelClass="form-label"
                                        :options="$hostelFloor"
                                        required="true"
                                        value="{{ !empty($hostelFloorMapping) ? $hostelFloorMapping->hostel_floor_master_pk : '' }}"
                                        />
                                        
                        </div>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit" id="saveClassSessionForm">
                        <i class="material-icons menu-icon">save</i>
                        {{ !empty($hostelFloorMapping) ? 'Update' : 'Save' }}
                    </button>
                    <a href="{{ route('hostel.building.map.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
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