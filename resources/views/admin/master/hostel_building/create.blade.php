@extends('admin.layouts.master')

@section('title', 'Building Master')

@section('content')

<div class="container-fluid">
    <x-breadcrum title="Building Master" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($hostelBuildingMaster) ? 'Edit Building Master' : 'Create Building Master' }}
            </h4>
            <hr>
            <form action="{{ route('master.hostel.building.store') }}" method="POST" id="hostelBuildingForm">
                @csrf
                @if(!empty($hostelBuildingMaster)) 
                    <input type="hidden" name="pk" value="{{ encrypt($hostelBuildingMaster->pk) }}">
                @endif
                <div class="row">

                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-input
                                name="building_name"
                                label="Name :" 
                                placeholder="Name" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('building_name', $hostelBuildingMaster->building_name ?? '') }}"
                                />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-input
                                name="no_of_floors"
                                label="No. of Floors :" 
                                placeholder="No. of Floors" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('no_of_floors', $hostelBuildingMaster->no_of_floors ?? '') }}"
                                />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-input
                                name="no_of_rooms"
                                label="No. of Rooms :" 
                                placeholder="No. of Rooms" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('no_of_rooms', $hostelBuildingMaster->no_of_rooms ?? '') }}"
                                />
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-select
                                name="building_type"
                                label="Building Type :"
                                :options="$buildingType"
                                placeholder="Select Building Type"
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('building_type', $hostelBuildingMaster->building_type ?? '') }}"
                            />
                        </div>
                    </div>
                    
                </div>
                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit" id="saveClassSessionForm">
                        <i class="material-icons menu-icon">save</i>
                        {{ !empty($hostelBuildingMaster) ? 'Update' : 'Save' }}
                    </button>
                    <a href="{{ route('master.hostel.building.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
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