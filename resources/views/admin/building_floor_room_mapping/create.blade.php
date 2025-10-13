@extends('admin.layouts.master')

@section('title', 'Hostel Building Floor Room Mapping')

@section('content')

    <div class="container-fluid">
        <x-breadcrum title="Hostel Building Floor Room Mapping" />
        <x-session_message />
        <!-- start Vertical Steps Example -->
        <div class="card" style="border-left: 4px solid #004a93;">
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

                        <div class="col-md-6">
                            <div class="mb-3">

                                <x-select name="building_master_pk" label="Building :" formLabelClass="form-label"
                                    :options="$building" required="true"
                                    value="{{ !empty($hostelFloorMappingRoom) ? $hostelFloorMappingRoom->building_master_pk : '' }}" />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">

                                <x-select name="floor_master_pk" label="Floor :" formLabelClass="form-label"
                                    :options="$floor" required="true"
                                    value="{{ !empty($hostelFloorMappingRoom) ? $hostelFloorMappingRoom->floor_master_pk : '' }}" />

                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                
                                <label for="basic-url" class="form-label">Your vanity URL</label>
                                <div class="input-group">
                                    <span class="input-group-text floor_room_name" id="basic-addon3">-</span>
                                    <input type="text" class="form-control" id="basic-url"
                                        aria-describedby="basic-addon3 basic-addon4">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">

                                <x-input name="capacity" label="Capacity :" formLabelClass="form-label"
                                    value="{{ !empty($hostelFloorMappingRoom) ? $hostelFloorMappingRoom->capacity : '' }}"
                                    placeholder="Enter Room Capacity" required="true" />

                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="mb-3">
                        <button class="btn btn-primary hstack gap-6 float-end" type="submit" id="saveClassSessionForm">
                            {{ !empty($hostelFloorMappingRoom) ? 'Update' : 'Save' }}
                        </button>
                        <a href="{{ route('hostel.building.floor.room.map.index') }}"
                            class="btn btn-secondary hstack gap-6 float-end me-2">
                            Back
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <!-- end Vertical Steps Example -->
    </div>


@endsection