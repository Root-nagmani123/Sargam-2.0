@extends('admin.layouts.master')

@section('title', !empty($hostelBuildingMaster) ? 'Edit Building' : 'Add Building')

@push('styles')
@include('admin.layouts.partials.select2-assets')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $hbSelectedType = (string) old('building_type', $hostelBuildingMaster->building_type ?? '');
@endphp
<div class="container-fluid mst-page hostel-building-page">

    <x-breadcrum title="{{ !empty($hostelBuildingMaster) ? 'Edit Building' : 'Add Building' }}" />
    <x-session_message />

    <form action="{{ route('master.hostel.building.store') }}" method="POST" id="hostelBuildingForm">
        @csrf
        @if(!empty($hostelBuildingMaster))
            <input type="hidden" name="pk" value="{{ encrypt($hostelBuildingMaster->pk) }}">
        @endif

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Building Details</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="building_name" class="mst-form-label d-block">
                            Building Name <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="text" name="building_name" id="building_name"
                               class="form-control mst-control @error('building_name') is-invalid @enderror"
                               value="{{ old('building_name', $hostelBuildingMaster->building_name ?? '') }}"
                               placeholder="Enter building name" maxlength="255" required aria-required="true"
                               @error('building_name') aria-describedby="building_nameError" @enderror>
                        @error('building_name')
                            <span class="mst-field-error" id="building_nameError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="building_type" class="mst-form-label d-block">
                            Building Type <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <select name="building_type" id="building_type"
                                class="form-select mst-control mst-searchable @error('building_type') is-invalid @enderror"
                                data-placeholder="Select Building Type" required aria-required="true"
                                @error('building_type') aria-describedby="building_typeError" @enderror>
                            <option value="">Select Building Type</option>
                            @foreach(($buildingType ?? []) as $value => $label)
                                <option value="{{ $value }}" @selected($hbSelectedType === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('building_type')
                            <span class="mst-field-error" id="building_typeError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="no_of_floors" class="mst-form-label d-block">
                            No. of Floors <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="number" name="no_of_floors" id="no_of_floors" min="0"
                               class="form-control mst-control @error('no_of_floors') is-invalid @enderror"
                               value="{{ old('no_of_floors', $hostelBuildingMaster->no_of_floors ?? '') }}"
                               placeholder="Enter number of floors" required aria-required="true"
                               @error('no_of_floors') aria-describedby="no_of_floorsError" @enderror>
                        @error('no_of_floors')
                            <span class="mst-field-error" id="no_of_floorsError">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="no_of_rooms" class="mst-form-label d-block">
                            No. of Rooms <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="number" name="no_of_rooms" id="no_of_rooms" min="0"
                               class="form-control mst-control @error('no_of_rooms') is-invalid @enderror"
                               value="{{ old('no_of_rooms', $hostelBuildingMaster->no_of_rooms ?? '') }}"
                               placeholder="Enter number of rooms" required aria-required="true"
                               @error('no_of_rooms') aria-describedby="no_of_roomsError" @enderror>
                        @error('no_of_rooms')
                            <span class="mst-field-error" id="no_of_roomsError">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mst-form-footer">
                    <a href="{{ route('master.hostel.building.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                    <button type="submit" class="btn mst-btn-submit px-4" id="saveClassSessionForm">
                        {{ !empty($hostelBuildingMaster) ? 'Update' : 'Save' }}
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
