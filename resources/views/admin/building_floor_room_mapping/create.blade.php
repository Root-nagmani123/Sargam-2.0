@extends('admin.layouts.master')

@section('title', !empty($hostelFloorMappingRoom) ? 'Edit Hostel Floor Room' : 'Add Hostel Floor Room')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $prefix = '-';
    $suffix = '';
    $middleString = '';
    if (!empty($hostelFloorMappingRoom) && !empty($hostelFloorMappingRoom->room_name)) {
        $prefix = substr($hostelFloorMappingRoom->room_name, 0, 6); // first 6 letters
        $suffix = substr($hostelFloorMappingRoom->room_name, 6);    // rest of the string

        $middleStringArr = explode('-', $suffix);
        $middleStringArr ? $middleString = $middleStringArr[0] : $middleString = '';
    }

    // old() is an array when the field came back as name[]: keep the default, not a 500.
    $hrOld = fn (string $key, $default) => is_scalar($value = old($key, $default)) ? (string) $value : (string) $default;
    $hrSelBuilding = $hrOld('building_master_pk', $hostelFloorMappingRoom->building_master_pk ?? '');
    $hrSelFloor = $hrOld('floor_master_pk', $hostelFloorMappingRoom->floor_master_pk ?? '');
    $hrSelType = $hrOld('room_type', $hostelFloorMappingRoom->room_type ?? '');
@endphp
<div class="container-fluid mst-page hostel-room-page">

    <x-breadcrum title="{{ !empty($hostelFloorMappingRoom) ? 'Edit Hostel Floor Room' : 'Add Hostel Floor Room' }}" />
    <x-session_message />

    {{-- Field ids (building_master_pk, floor_master_pk, room_type), the
         .floor_room_name prefix and .floor_room_name_span preview are bound by
         public/admin_assets/js/custom.js ("Building Floor Room Mapping"), which
         also compares the placeholder option text to "Select" — keep both. --}}
    <form action="{{ route('hostel.building.floor.room.map.store') }}" method="POST" id="hostelFloorForm">
        @csrf
        @if(!empty($hostelFloorMappingRoom))
            <input type="hidden" name="pk" value="{{ encrypt($hostelFloorMappingRoom->pk) }}">
        @endif

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Room Details</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="building_master_pk" class="mst-form-label d-block">
                            Building <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="building_master_pk" id="building_master_pk"
                                class="form-select mst-control mst-searchable @error('building_master_pk') is-invalid @enderror"
                                data-placeholder="Select Building" required aria-required="true"
                                @error('building_master_pk') aria-describedby="building_master_pkError" @enderror>
                            <option value="">Select</option>
                            @foreach(($building ?? []) as $value => $label)
                                <option value="{{ $value }}" @selected($hrSelBuilding === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('building_master_pk')
                            <span class="mst-field-error" id="building_master_pkError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="floor_master_pk" class="mst-form-label d-block">
                            Floor <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="floor_master_pk" id="floor_master_pk"
                                class="form-select mst-control mst-searchable @error('floor_master_pk') is-invalid @enderror"
                                data-placeholder="Select Floor" required aria-required="true"
                                @error('floor_master_pk') aria-describedby="floor_master_pkError" @enderror>
                            <option value="">Select</option>
                            @foreach(($floor ?? []) as $value => $label)
                                <option value="{{ $value }}" @selected($hrSelFloor === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('floor_master_pk')
                            <span class="mst-field-error" id="floor_master_pkError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="basic-url" class="mst-form-label d-block">Room Name</label>
                        <div class="input-group">
                            <span class="input-group-text floor_room_name" id="basic-addon3">{{ $prefix }}</span>
                            <input type="text" class="form-control mst-control @error('room_name') is-invalid @enderror" id="basic-url"
                                   aria-describedby="basic-addon3" name="room_name" value="{{ old('room_name', $middleString) }}">
                        </div>
                        @error('room_name')
                            <span class="mst-field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="room_type" class="mst-form-label d-block">
                            Room Type <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="room_type" id="room_type"
                                class="form-select mst-control mst-searchable @error('room_type') is-invalid @enderror"
                                data-placeholder="Select Room Type" required aria-required="true"
                                @error('room_type') aria-describedby="room_typeError" @enderror>
                            <option value="">Select</option>
                            @foreach(($roomTypes ?? []) as $value => $label)
                                <option value="{{ $value }}" @selected($hrSelType === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('room_type')
                            <span class="mst-field-error" id="room_typeError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="capacity" class="mst-form-label d-block">
                            Capacity <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="number" name="capacity" id="capacity" min="1"
                               class="form-control mst-control @error('capacity') is-invalid @enderror"
                               value="{{ old('capacity', $hostelFloorMappingRoom->capacity ?? '') }}"
                               placeholder="Enter Room Capacity" required aria-required="true"
                               @error('capacity') aria-describedby="capacityError" @enderror>
                        @error('capacity')
                            <span class="mst-field-error" id="capacityError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6 d-flex align-items-end">
                        <span class="floor_room_name_span" aria-live="polite"></span>
                    </div>
                </div>

                <div class="mst-form-footer">
                    <a href="{{ route('hostel.building.floor.room.map.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                    <button type="submit" class="btn mst-btn-submit px-4" id="saveClassSessionForm">
                        {{ !empty($hostelFloorMappingRoom) ? 'Update' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/master-admin.js') }}?v={{ @filemtime(public_path('js/master-admin.js')) ?: time() }}"></script>
@endpush
