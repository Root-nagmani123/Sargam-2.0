@extends('admin.layouts.master')

@section('title', 'Hostel Room')

@section('setup_content')

<div class="container-fluid">
    <x-breadcrum title="Hostel Room" />
    <x-session_message />
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">
                {{ !empty($hostelRoomMaster) ? 'Edit Hostel Room' : 'Create Hostel Room' }}
            </h4>
            <hr>
            <form action="{{ route('master.hostel.room.store') }}" method="POST" id="hostelRoomForm">
                @csrf
                @if(!empty($hostelRoomMaster)) 
                    <input type="hidden" name="pk" value="{{ encrypt($hostelRoomMaster->pk) }}">
                @endif
                <div class="row">
                    
                    <div class="col-md-4">
                        <div class="mb-3">
                            <x-input
                                name="hostel_room_name"
                                label="Name :" 
                                placeholder="Name" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('hostel_room_name', $hostelRoomMaster->hostel_room_name ?? '') }}"
                                />
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <x-input
                                name="capacity"
                                label="Capacity :" 
                                placeholder="Capacity" 
                                formLabelClass="form-label"
                                required="true"
                                value="{{ old('capacity', $hostelRoomMaster->capacity ?? '') }}"
                                />
                        </div>
                    </div>
                    
                </div>
                <hr>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit" id="saveClassSessionForm">
                        <i class="material-icons menu-icon">save</i>
                        {{ !empty($hostelRoomMaster) ? 'Update' : 'Save' }}
                    </button>
                    <a href="{{ route('master.hostel.room.index') }}" class="btn btn-secondary hstack gap-6 float-end me-2">
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