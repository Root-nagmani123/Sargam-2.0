@extends('admin.layouts.master')

@section('title', 'Hostel Building Floor Room Mapping')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Hostel Building Floor Room Mapping" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($hostelFloorMappingRoom) ? 'Edit Hostel Building Floor Room Mapping' : 'Create Hostel Building Floor Room Mapping' }}
            </h4>
            <hr>
            <form action="{{ route('hostel.building.floor.room.map.store') }}" method="POST" id="hostelFloorForm">
                @csrf
                @if(!empty($hostelFloorMappingRoom)) 
                    <input type="hidden" name="pk" value="{{ encrypt($hostelFloorMappingRoom->pk) }}">
                @endif
                <div class="row">
                    
                    <div class="col-md-4">
                        <div class="mb-3">

                            <x-select 
                                        name="hostel_building_floor" 
                                        label="Hostel Building Floor:" 
                                        formLabelClass="form-label"
                                        :options="$hostelBuilding"
                                        required="true"
                                        value="{{ !empty($hostelFloorMappingRoom) ? $hostelFloorMappingRoom->hostel_building_floor_mapping_pk : '' }}"
                                        />
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            
                                <x-select 
                                        name="hostel_room" 
                                        label="Hostel Room Name:" 
                                        formLabelClass="form-label"
                                        :options="$hostelRoom"
                                        required="true"
                                        value="{{ !empty($hostelFloorMappingRoom) ? $hostelFloorMappingRoom->hostel_room_master_pk : '' }}"
                                        />
                                        
                        </div>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit" id="saveClassSessionForm">
                        <i class="material-icons menu-icon">save</i>
                        {{ !empty($hostelFloorMappingRoom) ? 'Update' : 'Save' }}
                    </button>
                    <a href="{{ route('hostel.building.floor.room.map.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
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