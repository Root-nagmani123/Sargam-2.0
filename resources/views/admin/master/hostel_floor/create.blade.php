@extends('admin.layouts.master')

@section('title', !empty($hostelFloorMaster) ? 'Edit Hostel Floor' : 'Add Hostel Floor')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/master-admin.css') }}?v={{ @filemtime(public_path('css/master-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid mst-page hostel-floor-page">

    <x-breadcrum title="{{ !empty($hostelFloorMaster) ? 'Edit Hostel Floor' : 'Add Hostel Floor' }}" />
    <x-session_message />

    <form action="{{ route('master.hostel.floor.store') }}" method="POST" id="hostelFloorForm">
        @csrf
        @if(!empty($hostelFloorMaster))
            <input type="hidden" name="pk" value="{{ encrypt($hostelFloorMaster->pk) }}">
        @endif

        <div class="card mst-form-card">
            <div class="card-body">
                <h2 class="mst-form-section-title h6">Floor Details</h2>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="floor_name" class="mst-form-label d-block">
                            Floor Name <span class="mst-req" aria-hidden="true">*</span>
                        </label>
                        <input type="text" name="floor_name" id="floor_name"
                               class="form-control mst-control @error('floor_name') is-invalid @enderror"
                               value="{{ old('floor_name', $hostelFloorMaster->floor_name ?? '') }}"
                               placeholder="Enter floor name" maxlength="255" required aria-required="true"
                               @error('floor_name') aria-describedby="floor_nameError" @enderror>
                        @error('floor_name')
                            <span class="mst-field-error" id="floor_nameError">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="mst-form-footer">
                    <a href="{{ route('master.hostel.floor.index') }}" class="btn mst-btn-cancel px-4">Cancel</a>
                    <button type="submit" class="btn mst-btn-submit px-4" id="saveClassSessionForm">
                        {{ !empty($hostelFloorMaster) ? 'Update' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
